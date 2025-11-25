<?php
session_start();
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

$userId     = $_SESSION['user_id'] ?? 0;
$cartItemId = isset($_POST['cartItemId']) ? (int)$_POST['cartItemId'] : 0;
$newQty     = isset($_POST['newQty'])     ? (int)$_POST['newQty']     : 0;

if ($cartItemId <= 0 || $newQty <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// 确认 item 属于当前用户，并取库存信息
$sql = "
    SELECT ci.CartItemId, c.UserId, p.IsUnlimitedStock, p.Stock
    FROM cartitems ci
    JOIN carts c  ON ci.CartId = c.CartId
    JOIN products p ON ci.ProductId = p.ProductId
    WHERE ci.CartItemId = :id
";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $cartItemId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || (int)$row['UserId'] !== (int)$userId) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit;
}

$isUnlimited = (int)$row['IsUnlimitedStock'] === 1;
$stock       = (int)$row['Stock'];

if (!$isUnlimited && $newQty > $stock) {
    echo json_encode([
        'success' => false,
        'message' => 'Stock exceeded on server side (' . $stock . ')'
    ]);
    exit;
}

// 更新数量
$upd = $db->prepare("UPDATE cartitems SET Quantity = :qty WHERE CartItemId = :id");
$upd->execute([
    'qty' => $newQty,
    'id'  => $cartItemId
]);

echo json_encode(['success' => true]);
