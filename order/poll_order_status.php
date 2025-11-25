<?php
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

header("Content-Type: application/json");
if (!isLoggedIn()) { echo json_encode(['ok'=>false]); exit; }

$userId = $_SESSION['user_id'];

$sql = "
SELECT Status
FROM orders
WHERE UserId = :uid
ORDER BY OrderId DESC
LIMIT 1
";
$stmt = $db->prepare($sql);
$stmt->execute(['uid'=>$userId]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'ok'=>true,
    'status'=>$row ? $row['Status'] : null
]);
