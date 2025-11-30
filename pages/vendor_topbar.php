<?php
require_once '../configs/db.php';


// --- 必须已登录 vendor ---
if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$vendorId = (int)$_SESSION['user_id'];

// --- 取出 vendor 负责的 stall ---
$sql = "
    SELECT StallId, StallName, IsAvailable, LogoUrl
    FROM stalls
    WHERE StaffId = ?
    LIMIT 1
";
$stmt = $db->prepare($sql);
$stmt->execute([$vendorId]);
$stall = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stall) {
    die("No stall assigned to this vendor.");
}

$stallId = (int)$stall['StallId'];

// --- Logo ---
$logo = (!empty($stall['LogoUrl']))
    ? $stall['LogoUrl']
    : "/images/default-stall.png";


// ========== KPI（即时状态，不看日期） ==========

// Pending 数量
$stmtPending = $db->prepare("
    SELECT COUNT(*) FROM orders
    WHERE StallId = ? AND Status = 'pending'
");
$stmtPending->execute([$stallId]);
$pending = (int)($stmtPending->fetchColumn() ?: 0);

// Preparing 数量
$stmtPreparing = $db->prepare("
    SELECT COUNT(*) FROM orders
    WHERE StallId = ? AND Status = 'preparing'
");
$stmtPreparing->execute([$stallId]);
$preparing = (int)($stmtPreparing->fetchColumn() ?: 0);

// Done 数量（原 ready）
$stmtDone = $db->prepare("
    SELECT COUNT(*) FROM orders
    WHERE StallId = ? AND Status = 'ready'
");
$stmtDone->execute([$stallId]);
$done = (int)($stmtDone->fetchColumn() ?: 0);

?>

<link rel="stylesheet" href="../assets/css/vendor_topbar.css">

<div class="topbar">

    <!-- 左侧：Logo ＋ Stall 信息 -->
    <div class="topbar__left">

        <img class="logo" src="<?= htmlspecialchars($logo) ?>" alt="Stall Logo">

        <div class="stall-meta">
            <h1><?= htmlspecialchars($stall['StallName']) ?></h1>

            <div class="badges">
                <span class="badge <?= ((int)$stall['IsAvailable'] === 1) ? 'badge--open' : 'badge--closed' ?>">
                    <?= ((int)$stall['IsAvailable'] === 1) ? 'Open' : 'Closed' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- 右侧：Toggle ＋ KPI -->
    <div class="topbar__right">

        <!-- 开门/关门 Toggle -->
        <label class="switch">
            <input type="checkbox"
                   id="stallToggle"
                   <?= ((int)$stall['IsAvailable'] === 1) ? 'checked' : '' ?>
            >
            <span class="switch__track"><span class="switch__thumb"></span></span>
        </label>

        <!-- KPI -->
        <div class="kpis">

            <div class="kpi">
                <b id="kpi-pending"><?= $pending ?></b>
                <span>Pending</span>
            </div>

            <div class="kpi">
                <b id="kpi-preparing"><?= $preparing ?></b>
                <span>Preparing</span>
            </div>

            <div class="kpi">
                <b id="kpi-done"><?= $done ?></b>
                <span>Done</span>
            </div>

        </div>
    </div>

</div>

<script src="../assets/js/vendor_topbar.js"></script>
