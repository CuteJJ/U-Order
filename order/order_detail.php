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
// CONFIRM PICKUP — UPDATE ORDER AND ALL ITEMS TO COMPLETE
// ================================================================
if (isset($_GET['action']) && $_GET['action'] === 'confirm_pickup') {
    try {
        $db->beginTransaction();

        // 1. Update Main Order Status
        $sqlOrder = "UPDATE orders SET Status = 'complete' WHERE OrderId = :orderId";
        $stmtOrder = $db->prepare($sqlOrder);
        $stmtOrder->execute([':orderId' => $orderId]);

        // 2. Update All Items Status
        $sqlItems = "UPDATE orderitems SET Status = 'complete' WHERE OrderId = :orderId";
        $stmtItems = $db->prepare($sqlItems);
        $stmtItems->execute([':orderId' => $orderId]);

        $db->commit();

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        $db->rollBack();
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
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
    <link rel="stylesheet" href="/U-Order/assets/css/order_detail.css">
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
                                    
                                    <!-- SHOW PICKUP TIME -->
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

                <!-- DYNAMIC ACTION SECTION -->
                <div class="section" id="dynamic-action-section"></div>

                <!-- CONFIRM PICKUP BUTTON - Only show if status is "ready" on page load -->
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
        // Pass PHP data to JavaScript
        const ORDER_ID = <?= $orderId ?>;
        const INITIAL_STATUS = "<?= strtolower($order['Status']) ?>";
        const INITIAL_PAYMENT_STATUS = "<?= strtolower($order['PaymentStatus'] ?? 'pending') ?>";
    </script>
    <script src="/U-Order/assets/js/order_detail.js"></script>
</body>

</html>