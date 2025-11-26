<?php
$cartItems = [];
$cartTotal = 0;
$cartCount = 0;

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    try {
        // 1. Get Cart Items
        $sql = "SELECT ci.CartItemId, ci.Quantity, p.ProductName, p.UnitPrice, s.StallName, 
                       (p.UnitPrice * ci.Quantity) as Subtotal
                FROM carts c
                JOIN cartitems ci ON c.CartId = ci.CartId
                JOIN products p ON ci.ProductId = p.ProductId
                JOIN stalls s ON p.StallId = s.StallId
                WHERE c.UserId = :uid";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Calculate Totals
        foreach ($cartItems as $item) {
            $cartTotal += $item['Subtotal'];
            $cartCount += $item['Quantity']; // Or count($cartItems) for unique items
        }

    } catch (PDOException $e) {
        // Log error or handle silently
        error_log("Cart Error: " . $e->getMessage());
    }
}
?>