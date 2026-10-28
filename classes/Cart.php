
<?php


class Cart {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

     // Add item to cart (or update quantity if it exists)
    public function addItem($userId, $variantId, $quantity) {
        // Changed the second :quantity to :quantity_add to fix the PDO error
        $sql = "INSERT INTO cart_items (user_id, variant_id, quantity) 
                VALUES (:user_id, :variant_id, :quantity) 
                ON DUPLICATE KEY UPDATE quantity = quantity + :quantity_add";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':variant_id' => $variantId,
            ':quantity' => $quantity,
            ':quantity_add' => $quantity // Passing the value again
        ]);
    }

    // Get all cart items for a user
    public function getCartItems($userId) {
        // We join with product_variants and products to get the names and prices
        $sql = "SELECT c.id as cart_id, c.quantity, c.variant_id, 
               v.price, v.size_ml, p.name as product_name
        FROM cart_items c
        JOIN product_variants v ON c.variant_id = v.id
        JOIN products p ON v.product_id = p.id
        WHERE c.user_id = :user_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    // Remove an item from cart
    public function removeItem($cartId, $userId) {
        $sql = "DELETE FROM cart_items WHERE id = :cart_id AND user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':cart_id' => $cartId, ':user_id' => $userId]);
    }



    
    // Update item quantity in cart
    public function updateQuantity($cartId, $userId, $quantity) {
        
        // Ensure quantity is at least 1
        if ($quantity < 1) {
            return $this->removeItem($cartId, $userId); // If 0, just delete it
        }

        $sql = "UPDATE cart_items SET quantity = :quantity WHERE id = :cart_id AND user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':quantity' => $quantity,
            ':cart_id' => $cartId,
            ':user_id' => $userId
        ]);
    }

}


?>