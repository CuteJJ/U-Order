<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Get the ID from the URL (e.g., remove_from_cart.php?id=5)
$cartItemId = $_GET['id'] ?? null;

if ($cartItemId) {
    try {
        // Delete the item
        // We also ensure the cart belongs to the current user for security
        $sql = "DELETE ci FROM cartitems ci
                JOIN carts c ON ci.CartId = c.CartId
                WHERE ci.CartItemId = :cid AND c.UserId = :uid";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':cid' => $cartItemId,
            ':uid' => $_SESSION['user_id']
        ]);

        flash('success', 'Item removed.');
    } catch (PDOException $e) {
        flash('error', 'Could not remove item.');
    }
}

header("Location: cart.php");
exit;
?>