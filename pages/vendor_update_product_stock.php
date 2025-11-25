<?php
require_once "base.php";
header("Content-Type: application/json");

// 必须 vendor 登录
if (!isset($_SESSION['UserId'])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$stock     = (int)($_POST['stock'] ?? -1);

if ($productId <= 0 || $stock < 0) {
    echo json_encode(["success" => false, "message" => "Invalid stock"]);
    exit;
}

// 更新数据库
$stmt = $db->prepare("UPDATE products SET Stock = ? WHERE ProductId = ?");
$ok = $stmt->execute([$stock, $productId]);

echo json_encode([
    "success" => $ok,
    "message" => $ok ? "Stock updated." : "Database error."
]);
