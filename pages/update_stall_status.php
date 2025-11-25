<?php
// update_stall_status.php
require_once "db.php"; // 包含 $db 和 session_start()

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$vendorId = (int)$_SESSION['user_id'];
$status   = isset($_POST['status']) ? (int)$_POST['status'] : null;

if ($status !== 0 && $status !== 1) {
    die("Invalid status");
}

// --- 1. 找到 vendor 的 StallId ---
$sql = "SELECT StallId FROM stalls WHERE StaffId = ? LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute([$vendorId]);
$stall = $stmt->fetch(PDO::FETCH_OBJ);

if (!$stall) {
    die("No stall found.");
}

$stallId = (int)$stall->StallId;

// --- 2. 更新档口状态（IsAvailable）---
$sql2 = "UPDATE stalls SET IsAvailable = ? WHERE StallId = ?";
$stmt2 = $db->prepare($sql2);
$stmt2->execute([$status, $stallId]);

echo "OK";
?>
