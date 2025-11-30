<?php
require_once '../configs/db.php';

header("Content-Type: application/json");

// 登录检查
if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

if (!isset($_POST["item_ids"]) || !isset($_POST["status"])) {
    echo json_encode(["success" => false, "message" => "Invalid parameters"]);
    exit;
}

$itemIds = $_POST["item_ids"];
$status = $_POST["status"];

// 批量更新
$sql = "UPDATE orderitems SET Status = ? WHERE OrderListId = ?";
$stmt = $db->prepare($sql);

foreach ($itemIds as $id) {
    $stmt->execute([$status, $id]);
}

echo json_encode(["success" => true]);
exit;
