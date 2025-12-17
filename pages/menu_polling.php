<?php
// No BOM, no whitespace before <?php
include '../configs/db.php';

header('Content-Type: application/json; charset=utf-8');

$stallId = $_GET['stallid'] ?? null;
$search  = $_GET['search']  ?? '';
$cat     = $_GET['category'] ?? '';

if (!$stallId) {
    echo json_encode(['success' => false, 'message' => 'Missing stallid']);
    exit;
}

$params = [':stallid' => $stallId];

// ✅ Includes StallIsAvailable to check if stall is closed
$sql = "SELECT 
            p.ProductId,
            p.IsAvailable,
            p.Stock,
            p.IsUnlimitedStock,
            s.IsAvailable AS StallIsAvailable
        FROM products p
        JOIN stalls s ON p.StallId = s.StallId
        WHERE s.StallId = :stallid";

if ($search) {
    $sql .= " AND (p.ProductName LIKE :search OR p.Description LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($cat) {
    $sql .= " AND p.CategoryId = :cat";
    $params[':cat'] = $cat;
}

$sql .= " ORDER BY p.ProductName ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'items'   => $rows
]);