<?php
// ================================================================
// CONFIG
// ================================================================
require __DIR__ . '/../configs/db.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Get order ID
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : die('Order ID required.');


// ================================================================
// GET ORDER DETAIL
// ================================================================
function getOrderDetail(PDO $db, int $orderId): ?array {
    $sql = "
        SELECT 
            o.OrderId, o.UserId, o.StallId, o.PaymentId, o.Status, 
            o.Notes, o.CreatedAt,
            p.PaymentMethod, 
            s.StallName, 
            u.Name AS UserName,
            COUNT(oi.OrderListId) AS TotalItems
        FROM orders o
        LEFT JOIN payments p ON o.PaymentId = p.PaymentId
        LEFT JOIN stalls s ON o.StallId = s.StallId
        LEFT JOIN users u ON o.UserId = u.UserId
        LEFT JOIN orderitems oi ON o.OrderId = oi.OrderId
        WHERE o.OrderId = :orderId
        GROUP BY 
            o.OrderId, o.UserId, o.StallId, o.PaymentId,
            o.Status, o.Notes, o.CreatedAt,
            p.PaymentMethod, s.StallName, u.Name
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([':orderId' => $orderId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}


// ================================================================
// POLLING — MUST COME BEFORE ANY HTML OUTPUT
// ================================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_status') {
    $current = getOrderDetail($db, $orderId);
    $status = $current['Status'] ?? 'unknown';
    header('Content-Type: application/json');
    echo json_encode(['status' => $status]);
    exit;
}


// ================================================================
// GET ORDER ITEMS
// ================================================================
function getOrderItems(PDO $db, int $orderId): array {
    $sql = "
        SELECT 
            oi.Quantity, p.ProductName, p.UnitPrice, oi.Note, 
            oi.Subtotal, oi.Status AS ItemStatus
        FROM orderitems oi
        JOIN products p ON oi.ProductId = p.ProductId
        WHERE oi.OrderId = :orderId
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([':orderId' => $orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// INITIAL DATA
$order = getOrderDetail($db, $orderId);
$items = getOrderItems($db, $orderId);

if (!$order) die("Order not found.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Order #<?= $orderId ?></title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
/* ============================
   COFFEE RECEIPT THEME
   ============================ */
:root {
    --bg: #e5d4c0;          /* 背景木色 */
    --paper: #fbf7ef;       /* 纸张色 */
    --header: #a36333;      /* 咖啡色 header */
    --header-dark: #5b3217; /* 深咖啡文字 */
    --accent: #c78944;      /* 金咖啡点缀 */
    --line: #d0c2ad;        /* 虚线颜色 */
    --text: #4d3c2b;        /* 正文字色 */
    --muted: #8a7a67;
    --total: #b33119;       /* Total 红棕色 */
}

/* ========== Layout ========== */
body {
    background: radial-gradient(circle at top, #f5e8d4 0, #d9c4aa 40%, #b79a7c 100%);
    font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
    padding: 24px;
    color: var(--text);
}

.container {
    max-width: 430px;
    margin: 0 auto;
    background: var(--paper);
    border-radius: 14px;
    box-shadow:
        0 10px 25px rgba(0,0,0,0.25),
        0 0 0 1px rgba(255,255,255,0.9);
    overflow: hidden;
    position: relative;
}

/* 纸张上下轻微阴影边缘 */
.container::before,
.container::after {
    content: "";
    position: absolute;
    left: 10px;
    right: 10px;
    height: 10px;
    background: radial-gradient(circle, rgba(0,0,0,0.18) 0, transparent 60%);
    z-index: -1;
}
.container::before { top: -6px; filter: blur(2px); opacity: .25; }
.container::after  { bottom: -8px; filter: blur(3px); opacity: .35; }

/* ========== Header ========== */
.header {
    background: linear-gradient(135deg, var(--header) 0, #c37a3f 100%);
    color: #fff;
    text-align: center;
    padding: 26px 20px 30px;
    position: relative;
}

.header::after {
    content: "";
    position: absolute;
    bottom: -16px;
    left: 0;
    width: 100%;
    height: 32px;
    background: url("data:image/svg+xml,%3Csvg viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0 L0 60 C20 30, 80 30, 100 60 L100 0 Z' fill='%23fbf7ef'/%3E%3C/svg%3E");
    background-size: 100% 32px;
}

/* 小圆盘 icon */
.header-icon {
    width: 42px;
    height: 42px;
    border-radius: 999px;
    border: 2px solid rgba(255,255,255,0.85);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(2px);
    font-size: 18px;
}

.header-title {
    font-size: 24px;
    font-weight: 800;
    letter-spacing: 1px;
}

.header-sub {
    margin-top: 5px;
    font-size: 14px;
    opacity: 0.95;
}

/* ========== Body ========== */
.body {
    padding: 32px 24px 22px;
}

/* section title */
.summary-title {
    text-align: center;
    font-size: 18px;
    font-weight: 800;
    letter-spacing: 1.4px;
    color: var(--header-dark);
    margin-bottom: 14px;
}

/* 订单时间绿色条，这里换成咖啡金色 pill */
.info-bar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 7px 14px;
    background: #4c7a52;
    color: #f8fff8;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
    margin: 0 auto 22px;
}

/* 中间用 flex + center */
.info-bar-wrapper {
    display: flex;
    justify-content: center;
}

/* ========== Items ========== */
.item-card {
    display: flex;
    justify-content: space-between;
    padding: 9px 4px;
    font-size: 14px;
    border-bottom: 1px dashed var(--line);
}

.item-left {
    max-width: 70%;
}

.item-name {
    font-weight: 600;
}

.item-note {
    font-size: 12px;
    color: var(--muted);
    margin-top: 1px;
}

.item-price {
    font-weight: 600;
}

/* ========== Status ========== */
.status-section {
    margin-top: 20px;
    padding-top: 14px;
    border-top: 1px dashed var(--line);
}

.status-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--header);
    margin-bottom: 10px;
}

/* progress bar */
.progress {
    margin-top: 6px;
    position: relative;
    display: flex;
    justify-content: space-between;
    padding: 0 12px 0 16px;
}

.progress::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 9%;
    width: 82%;
    height: 3px;
    background: #d2c4b1;
    transform: translateY(-50%);
    border-radius: 999px;
}

.progress::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 9%;
    height: 3px;
    background: #7b5a39;
    width: var(--progress-width, 0%);
    transform: translateY(-50%);
    transition: width 0.4s ease;
    border-radius: 999px;
}

.step {
    z-index: 1;
    text-align: center;
    flex: 1;
}

.circle {
    width: 30px;
    height: 30px;
    line-height: 29px;
    border-radius: 999px;
    border: 2px solid #d2c4b1;
    background: #fdf8ee;
    font-size: 13px;
    font-weight: 700;
    color: #a59783;
    margin: 0 auto;
    transition: 0.25s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.08);
}

.step-label {
    font-size: 11px;
    margin-top: 5px;
    color: var(--muted);
}

/* 已完成步骤 */
.step.active .circle {
    border-color: #7b5a39;
    background: #7b5a39;
    color: #fef7ec;
}

/* 当前步骤 */
.step.current .circle {
    border-color: #f5e1b7;
    background: #f0c875;
    color: #5b3217;
    box-shadow:
        0 0 0 2px rgba(255,255,255,0.8),
        0 4px 6px rgba(0,0,0,0.22);
}

.step.current .step-label {
    color: #5b3217;
    font-weight: 600;
}

/* ========== Total ========== */
.total-box {
    margin-top: 20px;
    padding-top: 14px;
    border-top: 1px dashed var(--line);
}

.total-row {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    margin-bottom: 5px;
}

.total-row span:first-child {
    letter-spacing: 0.5px;
}

.total-bold {
    font-size: 20px;
    font-weight: 900;
    color: var(--total);
    margin-top: 6px;
}

/* 一点点底部留白 */
.bottom-gap {
    height: 18px;
}

/* ============================
   BACK BUTTON (Frosted Glass)
   ============================ */
.back-btn {
    position: fixed;
    top: 16px;
    left: 16px;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 14px;
    font-size: 15px;
    font-weight: 600;
    border-radius: 14px;
    cursor: pointer;

    /* Frosted glass effect */
    background: rgba(255,255,255,0.25);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.35);
    color: #ffffff;

    box-shadow: 0 4px 14px rgba(0,0,0,0.15);
    transition: 0.25s ease;
    z-index: 999;
}

.back-btn i {
    font-size: 18px;
}

.back-btn:hover {
    background: rgba(255,255,255,0.35);
    transform: translateX(-3px);
}

</style>
</head>

<body>

<div class="container">

    <!-- HEADER -->
    <div class="header">
        <div class="header-icon">🍽️</div>
        <div class="header-title">U-ORDER</div>
        <div class="header-sub">Thanks for ordering!</div>
        <div class="header-sub">Your pickup receipt is below</div>
    </div>

    <div class="body">
        <div class="summary-title">ORDER SUMMARY</div>
<button class="back-btn" onclick="history.back()">
    <i class="fas fa-chevron-left"></i> Back
</button>

        <div class="info-bar-wrapper">
            <div class="info-bar">
                Order #<?= $orderId ?> — <?= date("Y-m-d h:i A", strtotime($order['CreatedAt'])) ?>
            </div>
        </div>

        <!-- ITEMS -->
        <?php 
        $totalAmount = 0;
        foreach ($items as $item):
            $totalAmount += $item['Subtotal'];
        ?>
            <div class="item-card">
                <div class="item-left">
                    <div class="item-name">
                        <?= $item['Quantity'] ?> × <?= htmlspecialchars($item['ProductName']) ?>
                    </div>
                    <?php if ($item['Note']): ?>
                        <div class="item-note">Note: <?= htmlspecialchars($item['Note']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="item-price">
                    RM <?= number_format($item['Subtotal'], 2) ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- STATUS -->
        <div class="status-section">
            <div class="status-title">
                Order Status — <?= strtoupper($order['Status']) ?>
            </div>

            <div class="progress" id="order-progress-bar-container">
                <?php
                $steps = ["pending", "preparing", "ready", "complete"];
                $current = $order["Status"];
                $currentRank = array_search($current, $steps);

                foreach ($steps as $i => $s):
                    $active = $i <= $currentRank ? "active" : "";
                    $currentStep = $i == $currentRank ? "current" : "";
                ?>
                    <div class="step <?= $active ?> <?= $currentStep ?>" data-status="<?= $s ?>">
                        <div class="circle"><?= $i+1 ?></div>
                        <div class="step-label"><?= ucfirst($s) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TOTAL -->
        <div class="total-box">
            <div class="total-row">
                <span>Subtotal ........</span>
                <span>RM <?= number_format($totalAmount, 2) ?></span>
            </div>

            <div class="total-row total-bold">
                <span>Total</span>
                <span>RM <?= number_format($totalAmount, 2) ?></span>
            </div>
        </div>

        <div class="bottom-gap"></div>
    </div>
</div>

<script>
$(document).ready(function () {
    const ORDER_ID = <?= $orderId ?>;
    const STEPS = ["pending", "preparing", "ready", "complete"];

    function updateProgressBar(status) {
        const rank = STEPS.indexOf(status);
        const percentage = rank >= 0 ? (rank / (STEPS.length - 1)) * 100 : 0;
        document.documentElement.style.setProperty('--progress-width', percentage + "%");

        $(".step").each(function(){
            const stepStatus = $(this).data("status");
            const stepRank = STEPS.indexOf(stepStatus);

            $(this).toggleClass("active", stepRank <= rank);
            $(this).toggleClass("current", stepRank === rank);
        });
    }

    updateProgressBar("<?= $order['Status'] ?>");

    // POLLING
    setInterval(() => {
        $.getJSON(`./order_detail.php?action=get_status&id=${ORDER_ID}`, (res) => {
            if (res.status) {
                updateProgressBar(res.status);
            }
        });
    }, 5000);
});
</script>
</body>
</html>
