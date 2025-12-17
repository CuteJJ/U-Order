<?php
require __DIR__ . '/../configs/db.php';
header('Content-Type: application/json');

$productId = $_GET['id'] ?? 0;

try {
    $sql = "SELECT 
                p.IsAvailable AS ProductOpen,
                p.Stock,
                p.IsUnlimitedStock,
                s.IsAvailable AS StallOpen
            FROM products p
            JOIN stalls s ON p.StallId = s.StallId
            WHERE p.ProductId = :pid";

    $stmt = $db->prepare($sql);
    $stmt->execute([':pid' => $productId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Product not found.'
        ]);
        exit;
    }

    $stock = isset($row['Stock']) ? (int)$row['Stock'] : 0;
    $isUnlimitedStock = isset($row['IsUnlimitedStock']) ? (int)$row['IsUnlimitedStock'] : 0;
    
    // If stock is negative, treat as 0
    if ($stock < 0) $stock = 0;

    echo json_encode([
        'status'             => 'ok',
        'stall_open'         => (int)$row['StallOpen'],
        'product_open'       => (int)$row['ProductOpen'],
        'stock'              => $stock,
        'is_unlimited_stock' => $isUnlimitedStock
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}