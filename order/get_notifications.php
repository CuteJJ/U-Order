<?php
session_start();
require_once '../config/connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['UserId']) && !isset($_GET['userId'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_GET['userId'] ?? $_SESSION['UserId'];

try {
    // Get all orders for the user with stall information
    $query = "SELECT 
                o.OrderId,
                o.Status,
                o.CreatedAt,
                o.Notes,
                s.StallName,
                p.TotalAmount
              FROM orders o
              INNER JOIN payments p ON o.PaymentId = p.PaymentId
              INNER JOIN stalls s ON o.StallId = s.StallId
              WHERE o.UserId = ?
              ORDER BY o.CreatedAt DESC
              LIMIT 50";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'count' => count($notifications)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>