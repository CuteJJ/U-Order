<?php
include '../configs/db.php';
include '../includes/functions.php';

// 1. Check Login
if (!isLoggedIn()) {
    flash('error', 'Please login to order food.');
    header("Location: login.php");
    exit;
}

// 2. Validate Input
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'], $_POST['quantity'])) {
    $userId = $_SESSION['user_id'];
    $productId = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity <= 0) {
        flash('error', 'Invalid quantity.');
        // Redirect back to wherever they came from (e.g., menu)
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    try {
        // 3. Get or Create Cart for User
        $sql = "SELECT CartId FROM carts WHERE UserId = :uid";
        $stmt = $db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart) {
            $cartId = $cart['CartId'];
        } else {
            // Create new cart
            $sql = "INSERT INTO carts (UserId) VALUES (:uid)";
            $stmt = $db->prepare($sql);
            $stmt->execute([':uid' => $userId]);
            $cartId = $db->lastInsertId();
        }

        // 4. Check if item already exists in cart
        $sql = "SELECT CartItemId, Quantity FROM cartitems WHERE CartId = :cid AND ProductId = :pid";
        $stmt = $db->prepare($sql);
        $stmt->execute([':cid' => $cartId, ':pid' => $productId]);
        $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingItem) {
            // Update quantity
            $newQty = $existingItem['Quantity'] + $quantity;
            $sql = "UPDATE cartitems SET Quantity = :qty WHERE CartItemId = :ciid";
            $stmt = $db->prepare($sql);
            $stmt->execute([':qty' => $newQty, ':ciid' => $existingItem['CartItemId']]);
        } else {
            // Insert new item
            $sql = "INSERT INTO cartitems (CartId, ProductId, Quantity) VALUES (:cid, :pid, :qty)";
            $stmt = $db->prepare($sql);
            $stmt->execute([':cid' => $cartId, ':pid' => $productId, ':qty' => $quantity]);
        }

        flash('success', 'Item added to cart!');

    } catch (PDOException $e) {
        flash('error', 'Error adding to cart: ' . $e->getMessage());
    }
}

// Redirect back
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'menu.php'));
exit;
?>