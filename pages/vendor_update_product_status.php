<?php
require_once "base.php";
header("Content-Type: application/json");

// 必须 vendor 登录
if (!isset($_SESSION['UserId'])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$status    = (int)($_POST['status'] ?? -1);

if ($productId <= 0 || ($status !== 0 && $status !== 1)) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

// 更新数据库
$stmt = $db->prepare("UPDATE products SET IsAvailable = ? WHERE ProductId = ?");
$ok = $stmt->execute([$status, $productId]);

echo json_encode([
    "success" => $ok,
    "message" => $ok ? "Status updated." : "Database error."
]);
