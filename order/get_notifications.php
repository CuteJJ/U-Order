<?php
session_start();
require_once __DIR__ . '/../configs/db.php';

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$userId = $_SESSION['user_id'];

/*
    RETURN ALL NON-COMPLETED ORDERS
*/
$sql = "
    SELECT 
        o.OrderId,
        o.Status,
        o.CreatedAt,
        s.StallName,
        p.TotalAmount
    FROM orders o
    JOIN stalls s ON o.StallId = s.StallId
    JOIN payments p ON o.PaymentId = p.PaymentId
    WHERE o.UserId = :uid
      AND o.Status != 'complete'
    ORDER BY 
        FIELD(o.Status, 'ready', 'preparing', 'pending', 'cancelled'),
        o.CreatedAt DESC
";

$stmt = $db->prepare($sql);
$stmt->execute(['uid' => $userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'notifications' => $orders
]);
?>
