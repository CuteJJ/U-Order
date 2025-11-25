<?php
require_once "db.php";

header("Content-Type: application/json");

// 必须已登录 vendor
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$vendorId = (int)$_SESSION['user_id'];

// 找到 vendor 的 stall
$sql = "SELECT StallId, IsAvailable FROM stalls WHERE StaffId = ? LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute([$vendorId]);
$stall = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stall) {
    echo json_encode(["error" => "No stall found"]);
    exit;
}

$stallId = (int)$stall['StallId'];
$isOpen  = (int)$stall['IsAvailable'];


/* ==========================================================
   1. Pending
   ========================================================== */
$stmtPending = $db->prepare("
    SELECT COUNT(*) 
    FROM orders
    WHERE StallId = ?
      AND Status = 'pending'
");
$stmtPending->execute([$stallId]);
$pending = (int)($stmtPending->fetchColumn() ?: 0);


/* ==========================================================
   2. Preparing
   ========================================================== */
$stmtPrep = $db->prepare("
    SELECT COUNT(*) 
    FROM orders
    WHERE StallId = ?
      AND Status = 'preparing'
");
$stmtPrep->execute([$stallId]);
$preparing = (int)($stmtPrep->fetchColumn() ?: 0);


/* ==========================================================
   3. Done (原 ready)
   ========================================================== */
$stmtDone = $db->prepare("
    SELECT COUNT(*) 
    FROM orders
    WHERE StallId = ?
      AND Status = 'ready'
");
$stmtDone->execute([$stallId]);
$done = (int)($stmtDone->fetchColumn() ?: 0);


/* ==========================================================
   输出 JSON 给前端
   ========================================================== */
echo json_encode([
    "isOpen"     => $isOpen,
    "pending"    => $pending,
    "preparing"  => $preparing,
    "done"       => $done
]);

exit;
