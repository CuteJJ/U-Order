<?php
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

header("Content-Type: application/json");
if (!isLoggedIn()) { echo json_encode(['ok'=>false]); exit; }

$userId = $_SESSION['user_id'];

$sql = "
SELECT *
FROM orders
WHERE UserId = :uid
ORDER BY OrderId DESC
LIMIT 1
";

$stmt = $db->prepare($sql);
$stmt->execute(['uid'=>$userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['ok'=>true, 'hasOrder'=>false]);
    exit;
}

$itemsStmt = $db->prepare("
    SELECT oi.*, p.ProductName, p.UnitPrice
    FROM orderitems oi
    JOIN products p ON oi.ProductId = p.ProductId
    WHERE oi.OrderId = :id
");
$itemsStmt->execute(['id'=>$order['OrderId']]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'ok' => true,
    'hasOrder' => true,
    'order' => $order,
    'items' => $items
]);
