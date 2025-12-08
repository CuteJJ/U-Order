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
// GET ORDER DETAIL WITH PAYMENT STATUS
// ================================================================
function getOrderDetail(PDO $db, int $orderId): ?array
{
    $sql = "
        SELECT 
            o.OrderId, o.UserId, o.StallId, o.PaymentId, o.Status, 
            o.Notes, o.CreatedAt,
            p.PaymentMethod, p.Status AS PaymentStatus,
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
            p.PaymentMethod, p.Status, s.StallName, u.Name
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
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $current['Status'] ?? 'unknown',
        'paymentStatus' => $current['PaymentStatus'] ?? 'unknown'
    ]);
    exit;
}

// ================================================================
// CONFIRM PICKUP — UPDATE STATUS TO COMPLETE
// ================================================================
if (isset($_GET['action']) && $_GET['action'] === 'confirm_pickup') {
    $sql = "UPDATE orders SET Status = 'complete' WHERE OrderId = :orderId";
    $stmt = $db->prepare($sql);
    $stmt->execute([':orderId' => $orderId]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}
// ================================================================
// GET ORDER ITEMS
// ================================================================
function getOrderItems(PDO $db, int $orderId): array
{
    $sql = "
        SELECT 
            oi.Quantity, p.ProductName, p.UnitPrice, oi.Note, 
            oi.Subtotal, oi.Status AS ItemStatus,
            oi.PickupTime
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $orderId ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6B8DB8;
            --bg-main: #f5f7fa;
            --bg-white: #ffffff;
            --text-dark: #2c3e50;
            --text-muted: #6b7280;
            --text-light: #9ca3af;
            --border: #e5e7eb;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --radius-md: 12px;
            --radius-lg: 16px;

            /* Status Colors */
            --pending: #f59e0b;
            --preparing: #3b82f6;
            --ready: #ef4444;
            --complete: #10b981;
            --paid: #10b981;
            --unpaid: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--bg-main);
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            color: var(--text-dark);
            padding: 0;
            min-height: 100vh;
        }

        .container {
            max-width: 650px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
        }

        /* ========== Back Button ========== */
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: var(--radius-md);
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateX(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        /* ========== Main Card ========== */
        .order-card {
            background: var(--bg-white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        /* ========== Header Section ========== */
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 32px 24px;
            position: relative;
        }

        .header-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 32px;
        }

        .header-title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .header-subtitle {
            font-size: 14px;
            opacity: 0.95;
        }

        /* ========== Card Body ========== */
        .card-body {
            padding: 24px;
        }

        /* ========== Section ========== */
        .section {
            margin-bottom: 24px;
        }

        .section:last-child {
            margin-bottom: 0;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        /* ========== Order Info Badge ========== */
        .info-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: var(--radius-md);
            color: var(--primary);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 8px;
        }

        .info-row i {
            color: var(--primary);
            width: 16px;
        }

        /* ========== Items List ========== */
        .items-container {
            background: var(--bg-main);
            border-radius: var(--radius-md);
            padding: 16px;
        }

        .item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }

        .item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .item:first-child {
            padding-top: 0;
        }

        .item-details {
            flex: 1;
        }

        .item-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 4px;
        }

        .item-qty {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 26px;
            height: 26px;
            padding: 0 8px;
            background: var(--primary);
            color: white;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
        }

        .item-name {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 15px;
        }

        .item-note {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            font-size: 12px;
            color: var(--text-muted);
            margin-left: 36px;
            font-style: italic;
        }

        .item-price {
            font-weight: 700;
            color: var(--primary);
            font-size: 16px;
            white-space: nowrap;
        }

        /* ========== Status Progress ========== */
        .status-container {
            background: var(--bg-main);
            border-radius: var(--radius-md);
            padding: 20px;
            overflow: hidden;
        }

        .status-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .status-label {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }

        .status-badge.pending {
            background: rgba(245, 158, 11, 0.15);
            color: #92400e;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-badge.preparing {
            background: rgba(59, 130, 246, 0.15);
            color: #1e40af;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .status-badge.ready {
            background: rgba(239, 68, 68, 0.15);
            color: #991b1b;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .status-badge.complete {
            background: rgba(16, 185, 129, 0.15);
            color: #065f46;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        /* Progress Bar */
        .progress-wrapper {
            position: relative;
            padding: 0 10px;
            margin-top: 16px;
            overflow: visible;
        }

        .progress-track {
            position: absolute;
            top: 18px;
            left: 6%;
            right: 6%;
            height: 3px;
            background: #d1d5db;
            border-radius: 999px;
            z-index: 0;
        }

        .progress-fill {
            position: absolute;
            top: 18px;
            left: 6%;
            height: 3px;
            border-radius: 999px;
        }


        .progress-fill.pending {
            background: var(--pending);
        }

        .progress-fill.preparing {
            background: var(--preparing);
        }

        .progress-fill.ready {
            background: var(--ready);
        }

        .progress-fill.complete {
            background: var(--complete);
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 2;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .step-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: white;
            border: 3px solid #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-weight: 700;
            font-size: 13px;
            color: var(--text-light);
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }

        .step-label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: capitalize;
        }

        .step.active .step-circle {
            color: white;
        }

        .step.active.pending .step-circle {
            border-color: var(--pending);
            background: var(--pending);
        }

        .step.active.preparing .step-circle {
            border-color: var(--preparing);
            background: var(--preparing);
        }

        .step.active.ready .step-circle {
            border-color: var(--ready);
            background: var(--ready);
        }

        .step.active.complete .step-circle {
            border-color: var(--complete);
            background: var(--complete);
        }

        .step.current .step-circle {
            transform: scale(1.15);
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.1);
        }

        .step.current .step-label {
            font-weight: 700;
        }

        /* ========== Payment Summary ========== */
        .summary-container {
            background: var(--bg-main);
            border-radius: var(--radius-md);
            padding: 20px;
            overflow: hidden;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            margin-bottom: 12px;
            color: var(--text-dark);
            flex-wrap: wrap;
            gap: 8px;
        }

        .summary-row:last-child {
            margin-bottom: 0;
        }

        .summary-row.divider {
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .summary-row.total {
            margin-top: 12px;
            padding-top: 16px;
            border-top: 2px solid var(--primary);
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
        }

        .summary-label {
            font-weight: 600;
        }

        .payment-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .payment-status.paid {
            background: rgba(16, 185, 129, 0.15);
            color: #065f46;
        }

        .payment-status.pending {
            background: rgba(239, 68, 68, 0.15);
            color: #991b1b;
        }

        /* ========== Responsive ========== */
        @media (max-width: 640px) {
            .container {
                padding: 12px;
            }

            .card-body {
                padding: 20px 16px;
            }

            .card-header {
                padding: 24px 20px;
            }

            .back-btn {
                top: 12px;
                left: 12px;
                padding: 8px 14px;
                font-size: 13px;
            }

            .status-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .step-circle {
                width: 34px;
                height: 34px;
                font-size: 12px;
            }

            .step-label {
                font-size: 10px;
            }

            .summary-row.total {
                font-size: 18px;
            }

            .summary-row {
                font-size: 13px;
            }

            .payment-status {
                font-size: 11px;
                padding: 3px 8px;
            }
        }

        /* ========== Animations ========== */
        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.6;
            }
        }

        .updating {
            animation: pulse 1.5s ease-in-out infinite;
        }
        .confirm-btn {
    width: 100%;
    padding: 14px;
    background: #10B981;
    border: none;
    border-radius: 12px;
    color: white;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
    transition: all 0.25s ease;
}

.confirm-btn:hover {
    background: #0e9f6e;
    transform: translateY(-2px);
}

.confirm-btn.loading {
    opacity: 0.7;
    pointer-events: none;
}

    </style>
</head>

<body>

    <div class="container">
        <div class="order-card">
            <!-- HEADER -->
            <div class="card-header">
                <button class="back-btn" onclick="history.back()">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Menu</span>
                </button>

                <div class="header-icon">🍽️</div>
                <div class="header-title">U-ORDER</div>
                <div class="header-subtitle">Thanks for ordering!</div>
                <div class="header-subtitle">Your order details below</div>
            </div>

            <!-- BODY -->
            <div class="card-body">

                <!-- ORDER INFORMATION -->
                <div class="section">
                    <div class="section-title">Order Information</div>
                    <div class="info-badge">
                        <i class="fas fa-receipt"></i>
                        <span>Order #<?= $orderId ?></span>
                    </div>
                    <div class="info-row">
                        <i class="fas fa-calendar"></i>
                        <span><?= date("M d, Y", strtotime($order['CreatedAt'])) ?></span>
                        <span>•</span>
                        <i class="fas fa-clock"></i>
                        <span><?= date("h:i A", strtotime($order['CreatedAt'])) ?></span>
                    </div>
                    <?php if (!empty($order['StallName'])): ?>
                        <div class="info-row">
                            <i class="fas fa-store"></i>
                            <span><?= htmlspecialchars($order['StallName']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ORDER ITEMS -->
                <div class="section">
                    <div class="section-title">Order Items</div>
                    <div class="items-container">
                        <?php
                        $totalAmount = 0;
                        foreach ($items as $item):
                            $totalAmount += $item['Subtotal'];
                        ?>
                            <div class="item">
                                <div class="item-details">
                                    <div class="item-header">
                                        <span class="item-qty"><?= $item['Quantity'] ?>×</span>
                                        <span class="item-name"><?= htmlspecialchars($item['ProductName']) ?></span>
                                    </div>
                                    <?php if (!empty($item['Note'])): ?>
                                        <div class="item-note">
                                            <i class="fas fa-comment"></i>
                                            <span><?= htmlspecialchars($item['Note']) ?></span>
                                        </div>

                                    <?php endif; ?>
                                    
                                    <!-- NEW: SHOW PICKUP TIME -->
                                    <?php if (!empty($item['PickupTime'])): ?>
                                        <div class="item-note">
                                            <i class="fas fa-clock"></i>
                                            <span>Pickup: <?= date("h:i A", strtotime($item['PickupTime'])) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="item-price">
                                    RM <?= number_format($item['Subtotal'], 2) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ORDER PROGRESS -->
                <div class="section">
                    <div class="section-title">Order Progress</div>
                    <div class="status-container">
                        <div class="status-header">
                            <span class="status-label">Current Status</span>
                            <span class="status-badge <?= strtolower($order['Status']) ?>" id="status-badge">
                                <i class="fas fa-circle"></i>
                                <span id="status-text"><?= ucfirst($order['Status']) ?></span>
                            </span>
                        </div>

                        <div class="progress-wrapper">
                            <div class="progress-track"></div>
                            <div class="progress-fill <?= strtolower($order['Status']) ?>" id="progress-fill"></div>
                            <div class="progress-steps" id="progress-steps">
                                <?php
                                $steps = ["pending", "preparing", "ready", "complete"];
                                $current = strtolower($order["Status"]);
                                $currentRank = array_search($current, $steps);
                                if ($currentRank === false) $currentRank = 0;

                                foreach ($steps as $i => $s):
                                    $active = $i <= $currentRank ? "active" : "";
                                    $currentStep = $i == $currentRank ? "current" : "";
                                    $statusClass = $i <= $currentRank ? $s : "";
                                ?>
                                    <div class="step <?= $active ?> <?= $currentStep ?> <?= $statusClass ?>" data-status="<?= $s ?>">
                                        <div class="step-circle">
                                            <?php if ($i < $currentRank): ?>
                                                <i class="fas fa-check"></i>
                                            <?php elseif ($i == $currentRank): ?>
                                                <i class="fas fa-circle"></i>
                                            <?php else: ?>
                                                <?= $i + 1 ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="step-label"><?= ucfirst($s) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PAYMENT SUMMARY -->
                <div class="section">
                    <div class="section-title">Payment Summary</div>
                    <div class="summary-container">
                        <div class="summary-row divider">
                            <span class="summary-label">Subtotal</span>
                            <span>RM <?= number_format($totalAmount, 2) ?></span>
                        </div>
                        <?php if (!empty($order['PaymentMethod'])): ?>
                            <div class="summary-row">
                                <span class="summary-label">Payment Method</span>
                                <span><?= htmlspecialchars($order['PaymentMethod']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="summary-row">
                            <span class="summary-label">Payment Status</span>
                            <span class="payment-status <?= strtolower($order['PaymentStatus'] ?? 'pending') ?>" id="payment-status-badge">
                                <i class="fas fa-circle"></i>
                                <span id="payment-status-text"><?= ucfirst($order['PaymentStatus'] ?? 'Pending') ?></span>
                            </span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span>RM <?= number_format($totalAmount, 2) ?></span>
                        </div>
                    </div>
                </div>
<!-- CONFIRM PICKUP BUTTON -->
<?php if (strtolower($order['Status']) === 'ready'): ?>
<div class="section" id="confirm-pickup-section">
    <button class="confirm-btn" id="confirmPickupBtn">
        <i class="fas fa-check-circle"></i>
        Confirm Pickup
    </button>
</div>
<?php endif; ?>

            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const ORDER_ID = <?= $orderId ?>;
            const STEPS = ["pending", "preparing", "ready", "complete"];
            let lastStatus = "<?= strtolower($order['Status']) ?>";
            let lastPaymentStatus = "<?= strtolower($order['PaymentStatus'] ?? 'pending') ?>";

            function updateOrderStatus(status, paymentStatus) {
                status = status.toLowerCase();
                paymentStatus = paymentStatus.toLowerCase();

                const rank = STEPS.indexOf(status);
                if (rank === -1) {
                    console.warn('Invalid status:', status);
                    return;
                }

                const percentage = (rank / (STEPS.length - 1)) * 80 + 10;


                // Update progress fill
                const progressFill = $("#progress-fill");
                progressFill.removeClass("pending preparing ready complete");
                progressFill.addClass(status);
                progressFill.css('width', percentage + '%');

                // Update steps
                $(".step").each(function() {
                    const stepStatus = $(this).data("status");
                    const stepRank = STEPS.indexOf(stepStatus);

                    $(this).removeClass("active current pending preparing ready complete");

                    if (stepRank < rank) {
                        $(this).addClass("active " + stepStatus);
                        $(this).find(".step-circle").html('<i class="fas fa-check"></i>');
                    } else if (stepRank === rank) {
                        $(this).addClass("active current " + status);
                        $(this).find(".step-circle").html('<i class="fas fa-circle"></i>');
                    } else {
                        $(this).find(".step-circle").text(stepRank + 1);
                    }
                });

                // Update status badge
                const badge = $("#status-badge");
                badge.removeClass("pending preparing ready complete");
                badge.addClass(status);
                $("#status-text").text(status.charAt(0).toUpperCase() + status.slice(1));

                // Update payment status badge
                const paymentBadge = $("#payment-status-badge");
                paymentBadge.removeClass("paid pending");
                paymentBadge.addClass(paymentStatus);
                $("#payment-status-text").text(paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1));

                // Add animation if changed
                if (status !== lastStatus || paymentStatus !== lastPaymentStatus) {
                    badge.addClass("updating");
                    paymentBadge.addClass("updating");
                    setTimeout(() => {
                        badge.removeClass("updating");
                        paymentBadge.removeClass("updating");
                    }, 1500);
                    lastStatus = status;
                    lastPaymentStatus = paymentStatus;
                }
            }

            // Initialize
            updateOrderStatus(lastStatus, lastPaymentStatus);

            // Poll for updates every 5 seconds
            setInterval(() => {
                $.ajax({
                    url: `./order_detail.php?action=get_status&id=${ORDER_ID}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(res) {
                        if (res.status && res.status !== 'unknown') {
                            updateOrderStatus(
                                res.status,
                                res.paymentStatus || 'pending'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Polling error:', error);
                    }
                });
            }, 5000);

            $(document).on("click", "#confirmPickupBtn", function () {
    const btn = $(this);
    btn.addClass("loading").html('<i class="fas fa-spinner fa-spin"></i> Updating...');

    $.ajax({
        url: `./order_detail.php?action=confirm_pickup&id=${ORDER_ID}`,
        method: "GET",
        dataType: "json",
        success: function (res) {
            if (res.success) {
                // Force UI update immediately
                updateOrderStatus("complete", lastPaymentStatus);

                // Remove button
                $("#confirm-pickup-section").fadeOut(300, function () {
                    $(this).remove();
                });
            }
        },
        error: function () {
            btn.removeClass("loading").html("Confirm Pickup");
            alert("Failed to update order. Try again.");
        }
    });
});

        });
    </script>
</body>

</html>