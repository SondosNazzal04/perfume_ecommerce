<?php

class Order {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

        // Create a new order and move items from cart to order_items
    public function createOrder($userId, $addressData, $financialData, $cartItems, $paymentMethod = 'cash') {
        try {
            $this->pdo->beginTransaction();

            // 1. Insert into orders table (Header/Invoice)
            $sql = "INSERT INTO orders (user_id, city, area, street, building, subtotal, discount_amount, delivery_fee, total_amount, status) 
                    VALUES (:user_id, :city, :area, :street, :building, :subtotal, :discount_amount, :delivery_fee, :total_amount, 'pending')";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':city' => $addressData['city'],
                ':area' => $addressData['area'],
                ':street' => $addressData['street'],
                ':building' => $addressData['building'],
                ':subtotal' => $financialData['subtotal'],
                ':discount_amount' => $financialData['discount_amount'],
                ':delivery_fee' => $financialData['delivery_fee'],
                ':total_amount' => $financialData['total_amount']
            ]);

            $orderId = $this->pdo->lastInsertId();

             //Insert into payments table
            // If Visa, it's completed immediately. If Cash, it's pending until delivery.
            $initialPaymentStatus = ($paymentMethod === 'visa') ? 'completed' : 'pending';

            $sqlPayment = "INSERT INTO payments (order_id, amount, payment_method, status) 
                           VALUES (:order_id, :amount, :payment_method, :status)";
            $stmtPayment = $this->pdo->prepare($sqlPayment);
            $stmtPayment->execute([
                ':order_id' => $orderId,
                ':amount' => $financialData['total_amount'],
                ':payment_method' => $paymentMethod,
                ':status' => $initialPaymentStatus
            ]);



            //  Move items to order_items & Deduct Stock
            foreach ($cartItems as $item) {
                $sqlUpdateStock = "UPDATE product_variants 
                                   SET stock = stock - :quantity 
                                   WHERE id = :variant_id AND stock >= :quantity_check";
                                   
                $stmtUpdateStock = $this->pdo->prepare($sqlUpdateStock);
                $stmtUpdateStock->execute([
                    ':quantity' => $item['quantity'],
                    ':variant_id' => $item['variant_id'],
                    ':quantity_check' => $item['quantity'] 
                ]);

                if ($stmtUpdateStock->rowCount() === 0) {
                    throw new Exception("Not enough stock for this product. Please update your cart.");
                }

                $sqlItem = "INSERT INTO order_items (order_id, variant_id, quantity, unit_price) 
                            VALUES (:order_id, :variant_id, :quantity, :unit_price)";
                $stmtItem = $this->pdo->prepare($sqlItem);
                $stmtItem->execute([
                    ':order_id' => $orderId,
                    ':variant_id' => $item['variant_id'],
                    ':quantity' => $item['quantity'],
                    ':unit_price' => $item['price'] 
                ]);
            }        

            // 4. Clear the user's cart
            $stmtClear = $this->pdo->prepare("DELETE FROM cart_items WHERE user_id = :user_id");
            $stmtClear->execute([':user_id' => $userId]);

            $this->pdo->commit();
            return $orderId; 

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

   // Get all orders (For Admin Dashboard)
    public function getAllOrders() {
        $this->autoConfirmOrders(); 

        // Added LEFT JOIN with payments to fetch the payment_method
        $sql = "SELECT o.*, u.name as customer_name, p.payment_method 
                FROM orders o
                JOIN users u ON o.user_id = u.id
                LEFT JOIN payments p ON o.id = p.order_id
                ORDER BY o.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Get details of a single order (Header + Items)
    public function getOrderDetails($orderId) {
                $this->autoConfirmOrders(); 

        $sql = "SELECT o.*, u.name as customer_name, u.email 
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.id = :order_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':order_id' => $orderId]);
        $order = $stmt->fetch();

        if ($order) {
            $sqlItems = "SELECT oi.*, p.name as product_name, v.size_ml 
                         FROM order_items oi
                         JOIN product_variants v ON oi.variant_id = v.id
                         JOIN products p ON v.product_id = p.id
                         WHERE oi.order_id = :order_id";
            $stmtItems = $this->pdo->prepare($sqlItems);
            $stmtItems->execute([':order_id' => $orderId]);
            $order['items'] = $stmtItems->fetchAll();
        }
        return $order;
    }

        // Cancel Order (Returns stock, handles payment, and soft deletes)
    public function cancelOrder($orderId, $isAdmin = false) {
        try {
            $this->pdo->beginTransaction();

                        // Check current status, payment method, and get customer email
            $stmt = $this->pdo->prepare("SELECT o.status, o.user_id, p.payment_method, u.email 
                                         FROM orders o 
                                         LEFT JOIN payments p ON o.id = p.order_id 
                                         JOIN users u ON o.user_id = u.id
                                         WHERE o.id = :id");
            $stmt->execute([':id' => $orderId]);
            $order = $stmt->fetch();

            if (!$order || $order['status'] === 'cancelled' || $order['status'] === 'delivered') {
                throw new Exception("Order cannot be cancelled at this stage.");
            }

            // Customers can ONLY cancel if the order is still 'pending'
            if (!$isAdmin && $order['status'] !== 'pending') {
                throw new Exception("You can only cancel pending orders within the 6-hour window.");
            }

            // Refund Stock
            $stmtItems = $this->pdo->prepare("SELECT variant_id, quantity FROM order_items WHERE order_id = :order_id");
            $stmtItems->execute([':order_id' => $orderId]);
            $items = $stmtItems->fetchAll();

            foreach ($items as $item) {
                $stmtStock = $this->pdo->prepare("UPDATE product_variants SET stock = stock + :qty WHERE id = :v_id");
                $stmtStock->execute([':qty' => $item['quantity'], ':v_id' => $item['variant_id']]);
            }

            // Update Payment Status based on method
            if ($order['payment_method'] === 'visa') {
                $stmtPayment = $this->pdo->prepare("UPDATE payments SET status = 'refunded' WHERE order_id = :order_id");
            } else {
                $stmtPayment = $this->pdo->prepare("UPDATE payments SET status = 'cancelled' WHERE order_id = :order_id");
            }
            $stmtPayment->execute([':order_id' => $orderId]);

            // Soft Delete (Change Status)
            $stmtUpdate = $this->pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = :id");
            $stmtUpdate->execute([':id' => $orderId]);

             // Send email notification to the customer if the admin cancelled the order
            if ($isAdmin) {
                try {
                    // Load Composer's autoloader and mail config
                    require_once __DIR__ . '/../vendor/autoload.php';
                    $mailConfig = require __DIR__ . '/../config/mail.php';

                    // Initialize PHPMailer
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = $mailConfig['host'];
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $mailConfig['username'];
                    $mail->Password   = $mailConfig['password'];
                    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = $mailConfig['port'];

                    // Recipients
                    $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
                    $mail->addAddress($order['email']); // Email fetched from the database

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Order Cancelled - Perfume Store';
                    $mail->Body    = "Dear Customer,<br><br>Your order #<b>{$orderId}</b> has been cancelled by the administration.<br><br>If you have any questions, please contact support.<br><br>Best regards,<br>Perfume Store Team";

                    $mail->send();
                } catch (\Exception $e) {
                    // Log error if email fails, but don't stop the order cancellation process
                    error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

        // Update Order Status (Admin ONLY)
    public function updateOrderStatus($orderId, $status) {
        // Admin can only change to delivered, or cancelled
        $allowedStatuses = ['delivered', 'cancelled'];

        if (!in_array($status, $allowedStatuses)) {
            return false;
        }

        // If admin cancels, use the cancelOrder logic to refund stock and payments
        if ($status === 'cancelled') {
            return $this->cancelOrder($orderId, true);
        }

        // Check if the order is already cancelled (to prevent delivering a cancelled order)
        $stmtCheck = $this->pdo->prepare("SELECT status FROM orders WHERE id = :id");
        $stmtCheck->execute([':id' => $orderId]);
        $currentOrder = $stmtCheck->fetch();

        if ($currentOrder && $currentOrder['status'] === 'cancelled') {
            throw new Exception("Cannot deliver a cancelled order.");
        }

        // Update order status to delivered
        $sql = "UPDATE orders SET status = :status WHERE id = :order_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':status' => $status, ':order_id' => $orderId]);

        // If delivered, and it was Cash, mark payment as completed
        if ($status === 'delivered') {
            $stmtPayment = $this->pdo->prepare("UPDATE payments SET status = 'completed' WHERE order_id = :order_id AND payment_method = 'cash'");
            $stmtPayment->execute([':order_id' => $orderId]);
        }

        return true;
    }

    
    // Auto Confirm Orders (6 Hours)
    public function autoConfirmOrders() {
        $sql = "UPDATE orders 
                SET status = 'confirmed' 
                WHERE status = 'pending' 
                AND created_at < NOW() - INTERVAL 6 HOUR";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
    }
}
?>