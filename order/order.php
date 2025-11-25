<?php
session_start();
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: /pages/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// ===== 取当前（最新）订单 =====
$sqlCurrent = "
    SELECT *
    FROM orders
    WHERE UserId = :uid
    ORDER BY OrderId DESC
    LIMIT 1
";
$stmt = $db->prepare($sqlCurrent);
$stmt->execute(['uid' => $userId]);
$currentOrder = $stmt->fetch(PDO::FETCH_ASSOC);

// ===== 取历史订单 =====
$sqlHistory = "
    SELECT *
    FROM orders
    WHERE UserId = :uid
    ORDER BY CreatedAt DESC
";
$stmt2 = $db->prepare($sqlHistory);
$stmt2->execute(['uid' => $userId]);
$historyRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// ===== 工具：status 显示用 =====
function statusBadge($s)
{
    $map = [
        'pending'    => ['#fef3c7', '#b45309'],
        'preparing'  => ['#e0e7ff', '#4338ca'],
        'ready'      => ['#d1fae5', '#065f46'],
        'cancelled'  => ['#fee2e2', '#b91c1c'],
    ];
    if (!isset($map[$s])) return '';

    return "<span style='background: {$map[$s][0]}; color: {$map[$s][1]}; padding:3px 8px; border-radius:6px; font-size:0.75rem;'>
        " . ucfirst($s) . "
    </span>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Orders</title>
    <link rel="stylesheet" href="/assets/css/order_status.css">

    <style>
        body {
            background: #f5f6fa;
            font-family: Inter, sans-serif;
            margin: 0;
        }
        .page-shell {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        h1 {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: #111827;
        }

        /* ===== 当前订单大卡片 ===== */
        .current-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        }
        .current-title {
            font-size: 1.2rem;
            margin-bottom: 12px;
            color: #4f46e5;
        }
        .timeline {
            margin: 16px 0;
            padding-left: 6px;
        }
        .timeline-item {
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }
        .dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
        }
        .dot.done { background: #4ade80; }
        .dot.active { background: #4f46e5; }
        .dot.next { background: #d1d5db; }

        /* ===== 历史订单卡片 ===== */
        .history-card {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 18px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.04);
        }
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .history-title {
            font-weight: 600;
        }
        .history-amount {
            font-size: 1.05rem;
            color: #4f46e5;
        }

        .history-actions {
            margin-top: 12px;
            display: flex;
            gap: 10px;
        }
        .btn-s {
            padding: 6px 14px;
            font-size: 0.8rem;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            cursor: pointer;
            background: #fff;
        }
        .btn-primary-s {
            background: #4f46e5;
            color: #fff;
            border-color: #4f46e5;
        }
    </style>
</head>
<body>

<div class="page-shell">

    <h1>Your Orders</h1>

    <!-- ==================== 当前订单卡片 ==================== -->
    <section class="current-card">

        <div class="current-title">Current Order</div>

        <?php if (!$currentOrder): ?>
            <p style="color:#6b7280;">No active orders.</p>

        <?php else: ?>

            <div class="history-header">
                <div>
                    <div class="history-title">
                        Order #<?= $currentOrder['OrderId'] ?>
                    </div>
                    <div>
                        <?= statusBadge($currentOrder['Status']) ?>
                    </div>
                </div>
                <div class="history-amount">
                    RM <?= number_format($currentOrder['TotalAmount'] ?? 0, 2) ?>
                </div>
            </div>

            <!-- ===== 时间线（前端 JS 会替换状态亮点） ===== -->
            <div class="timeline" id="timeline">
                <div class="timeline-item">
                    <div class="dot done"></div> Order placed
                </div>
                <div class="timeline-item">
                    <div class="dot next"></div> Preparing
                </div>
                <div class="timeline-item">
                    <div class="dot next"></div> Ready for pickup
                </div>
                <div class="timeline-item">
                    <div class="dot next"></div> Completed
                </div>
            </div>

        <?php endif; ?>
    </section>



    <!-- ==================== 历史订单 ==================== -->
    <h2 style="font-size:1.3rem; margin-bottom:12px;">Order History</h2>

    <?php foreach ($historyRows as $o): ?>
        <div class="history-card">

            <div class="history-header">
                <div>
                    <div class="history-title">Order #<?= $o['OrderId'] ?></div>
                    <?= statusBadge($o['Status']) ?>
                </div>

                <div class="history-amount">
                    RM <?= number_format($o['TotalAmount'] ?? 0, 2) ?>
                </div>
            </div>

            <div class="history-actions">
                <!-- 查看状态 -->
                <button class="btn-s btn-primary-s"
                        onclick="window.location.href='/order/order_status.php?id=<?= $o['OrderId'] ?>'">
                    View Status
                </button>

                <!-- 再下一单 -->
                <button class="btn-s"
                        onclick="window.location.href='/order/reorder.php?id=<?= $o['OrderId'] ?>'">
                    Order Again
                </button>
            </div>

        </div>
    <?php endforeach; ?>

</div>

<script src="/assets/js/order_status.js"></script>
</body>
</html>
