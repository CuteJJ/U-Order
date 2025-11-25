<?php
// 绝对不要输出多余空格或 BOM
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

$sql = "SELECT 
            p.ProductId,
            p.IsAvailable
        FROM products p
        JOIN stalls s ON p.StallId = s.StallId
        WHERE s.StallId = :stallid
          AND s.IsAvailable = 1";

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

// 返回结构: { success: true, items: [ { ProductId: 1, IsAvailable: 1 }, ... ] }
echo json_encode([
    'success' => true,
    'items'   => $rows
]);
