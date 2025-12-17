<?php
// ======================================================
// IMPORTANT: No BOM, No whitespace, No echo before JSON
// ======================================================
require_once '../configs/db.php';

header('Content-Type: application/json; charset=utf-8');

// ------------------------------------------------------
// 1. Fetch all stalls (StallId, IsAvailable)
// ------------------------------------------------------
try {
    $sqlStalls = "SELECT StallId, IsAvailable 
                FROM stalls";
    $stmtStalls = $db->prepare($sqlStalls);
    $stmtStalls->execute();
    $stalls = $stmtStalls->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch stalls: ' . $e->getMessage()
    ]);
    exit;
}

// ------------------------------------------------------
// 2. Fetch all products (ProductId, StallId, IsAvailable, Stock, IsUnlimitedStock)
// ------------------------------------------------------
try {
    $sqlProducts = "SELECT ProductId, StallId, IsAvailable, Stock, IsUnlimitedStock
                    FROM products";
    $stmtProducts = $db->prepare($sqlProducts);
    $stmtProducts->execute();
    $products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert Stock and IsUnlimitedStock to integer for consistency
    foreach ($products as &$product) {
        $product['Stock'] = isset($product['Stock']) ? (int)$product['Stock'] : 0;
        $product['IsUnlimitedStock'] = isset($product['IsUnlimitedStock']) ? (int)$product['IsUnlimitedStock'] : 0;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch products: ' . $e->getMessage()
    ]);
    exit;
}

// ------------------------------------------------------
// 3. JSON response (no extra characters)
// ------------------------------------------------------
echo json_encode([
    'success'  => true,
    'stalls'   => $stalls,
    'products' => $products
], JSON_PRETTY_PRINT); // Optional: makes debugging easier
exit;