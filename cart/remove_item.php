<?php
session_start();
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
$cartItemId = isset($_POST['cartItemId']) ? (int)$_POST['cartItemId'] : 0;
if ($cartItemId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
    exit;
}

// 确认这个 cartItem 属于当前用户
$sql = "
    SELECT ci.CartItemId
    FROM cartitems ci
    JOIN carts c ON ci.CartId = c.CartId
    WHERE ci.CartItemId = :id AND c.UserId = :uid
";
$stmt = $db->prepare($sql);
$stmt->execute(['id' => $cartItemId, 'uid' => $userId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit;
}

// 删除
$del = $db->prepare("DELETE FROM cartitems WHERE CartItemId = :id");
$del->execute(['id' => $cartItemId]);

echo json_encode(['success' => true]);
