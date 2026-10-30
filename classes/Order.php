<?php

class Order {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // Create a new order and move items from cart to order_items
    public function createOrder($userId, $addressData, $financialData, $cartItems) {
        try {
            // 1. Start the transaction (Crucial for data integrity)
            $this->pdo->beginTransaction();

            // 2. Insert into orders table (Header/Invoice)
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

            // Get the newly created Order ID
            $orderId = $this->pdo->lastInsertId();

            // 3. Move items to order_items & Deduct Stock
            foreach ($cartItems as $item) {
                
                // A. Deduct stock (Only if stock is enough)
                $sqlUpdateStock = "UPDATE product_variants 
                                   SET stock = stock - :quantity 
                                   WHERE id = :variant_id AND stock >= :quantity_check";
                                   
                $stmtUpdateStock = $this->pdo->prepare($sqlUpdateStock);
                $stmtUpdateStock->execute([
                    ':quantity' => $item['quantity'],
                    ':variant_id' => $item['variant_id'],
                    ':quantity_check' => $item['quantity'] 
                ]);

                // If rowCount is 0, it means the condition (stock >= quantity) failed. Not enough stock!
                if ($stmtUpdateStock->rowCount() === 0) {
                    throw new Exception("Not enough stock for this product. Please update your cart.");
                }

                // B. Insert into order_items (Invoice Details)
                $sqlItem = "INSERT INTO order_items (order_id, variant_id, quantity, unit_price) 
                            VALUES (:order_id, :variant_id, :quantity, :unit_price)";
                $stmtItem = $this->pdo->prepare($sqlItem);
                $stmtItem->execute([
                    ':order_id' => $orderId,
                    ':variant_id' => $item['variant_id'],
                    ':quantity' => $item['quantity'],
                    ':unit_price' => $item['price'] // From Cart model
                ]);
            }        

            // 4. Clear the user's cart
            $stmtClear = $this->pdo->prepare("DELETE FROM cart_items WHERE user_id = :user_id");
            $stmtClear->execute([':user_id' => $userId]);

            // 5. Commit the transaction (Save everything)
            $this->pdo->commit();

            return $orderId; // Return the order ID for the response

        } catch (Exception $e) {
            // If anything fails, rollback everything to prevent partial data
            $this->pdo->rollBack();
            throw $e; // Throw the error back to the API to handle it
        }
    }



        // 1. Get all orders (For Admin Dashboard)
    public function getAllOrders() {
        $sql = "SELECT o.*, u.name as customer_name 
                FROM orders o
                JOIN users u ON o.user_id = u.id
                ORDER BY o.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // 2. Get details of a single order (Header + Items)
    public function getOrderDetails($orderId) {
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

        public function updateOrderStatus($orderId, $status) {
        $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $allowedStatuses)) {
            return false; 
        }

        // If cancelling, return the stock first (using a transaction)
        if ($status === 'cancelled') {
            try {
                $this->pdo->beginTransaction();

                // 1. Get current order status to ensure it wasn't already cancelled
                $stmtCheck = $this->pdo->prepare("SELECT status FROM orders WHERE id = :id");
                $stmtCheck->execute([':id' => $orderId]);
                $currentOrder = $stmtCheck->fetch();

                if ($currentOrder && $currentOrder['status'] !== 'cancelled') {
                    // 2. Get order items
                    $stmtItems = $this->pdo->prepare("SELECT variant_id, quantity FROM order_items WHERE order_id = :id");
                    $stmtItems->execute([':id' => $orderId]);
                    $items = $stmtItems->fetchAll();

                    // 3. Return stock
                    foreach ($items as $item) {
                        $stmtStock = $this->pdo->prepare("UPDATE product_variants SET stock = stock + :qty WHERE id = :v_id");
                        $stmtStock->execute([':qty' => $item['quantity'], ':v_id' => $item['variant_id']]);
                    }
                }

                // 4. Update the status
                $stmtUpdate = $this->pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
                $stmtUpdate->execute([':status' => $status, ':id' => $orderId]);

                $this->pdo->commit();
                return true;
            } catch (Exception $e) {
                $this->pdo->rollBack();
                return false;
            }
        }

        // Normal status update (processing, shipped, delivered)
        $stmt = $this->pdo->prepare("UPDATE orders SET status = :status WHERE id = :order_id");
        return $stmt->execute([':status' => $status, ':order_id' => $orderId]);
    }
}
?>