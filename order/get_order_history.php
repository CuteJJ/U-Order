<?php
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

header("Content-Type: application/json");
if (!isLoggedIn()) { echo json_encode(['ok'=>false]); exit; }

$userId = $_SESSION['user_id'];

$sql = "
SELECT o.*, s.StallName
FROM orders o
JOIN stalls s ON o.StallId = s.StallId
WHERE o.UserId = :uid
ORDER BY o.OrderId DESC
";
$stmt = $db->prepare($sql);
$stmt->execute(['uid'=>$userId]);

echo json_encode([
    'ok'=>true,
    'history'=>$stmt->fetchAll(PDO::FETCH_ASSOC)
]);
