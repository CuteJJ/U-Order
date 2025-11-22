<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cart.php");
    exit;
}

$cartItemId = $_POST['cart_item_id'];
$action = $_POST['action']; // 'increase' or 'decrease'

try {
    // 1. Get current quantity
    $sql = "SELECT Quantity FROM cartitems WHERE CartItemId = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $cartItemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $newQty = $item['Quantity'];

        if ($action === 'increase') {
            $newQty++;
            // Ideally check stock here, but keeping it simple for now
        } elseif ($action === 'decrease') {
            $newQty--;
        }

        if ($newQty > 0) {
            $sql = "UPDATE cartitems SET Quantity = :qty WHERE CartItemId = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':qty' => $newQty, ':id' => $cartItemId]);
        } else {
            // If quantity becomes 0, remove it? Or just stop at 1. 
            // Let's stop at 1. If they want to remove, they use the remove button.
            flash('warning', 'Minimum quantity is 1. Use remove button to delete.');
        }
    }
} catch (Exception $e) {
    // Silent fail or flash error
}

header("Location: cart.php");
exit;
?>