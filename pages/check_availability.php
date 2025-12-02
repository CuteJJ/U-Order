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
        'message' => 'Failed to fetch stalls'
    ]);
    exit;
}

// ------------------------------------------------------
// 2. Fetch all products (ProductId, StallId, IsAvailable)
// ------------------------------------------------------
try {
    $sqlProducts = "SELECT ProductId, StallId, IsAvailable
                    FROM products";
    $stmtProducts = $db->prepare($sqlProducts);
    $stmtProducts->execute();
    $products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch products'
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
]);
exit;
