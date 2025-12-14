<?php
// ================================================================
// CONFIG
// ================================================================
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
$hideNav = false;

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

include '../includes/header.php';
?>

<link rel="stylesheet" href="/U-Order/assets/css/order_detail.css">

<div class="detail-wrapper">
    <div class="order-card">
        
        <div class="card-header">
            <a href="javascript:history.back()" class="back-btn-hero">
                <i class="fas fa-arrow-left"></i> Back
            </a>

            <div class="header-icon">
                <i class="fas fa-utensils"></i>
            </div>
            <div class="header-title">Order #<?= $orderId ?></div>
            <div class="header-subtitle">
                <?= htmlspecialchars($order['StallName']) ?> • <?= date("d M, h:i A", strtotime($order['CreatedAt'])) ?>
            </div>
        </div>

        <div class="card-body">

            <div class="section">
                <div class="section-title">Order Status</div>
                <div class="status-container">
                    <div class="status-header">
                        <span class="status-label">Current Status</span>
                        <span class="status-badge <?= strtolower($order['Status']) ?>" id="status-badge">
                            <?= ucfirst($order['Status']) ?>
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

            <div class="section">
                <div class="section-title">Your Items</div>
                <div class="items-container">
                    <?php
                    $totalAmount = 0;
                    foreach ($items as $item):
                        $totalAmount += $item['Subtotal'];
                    ?>
                        <div class="item">
                            <div style="flex:1;">
                                <div style="display:flex; align-items:center;">
                                    <span class="item-qty"><?= $item['Quantity'] ?>x</span>
                                    <span class="item-name"><?= htmlspecialchars($item['ProductName']) ?></span>
                                </div>
                                <?php if (!empty($item['Note'])): ?>
                                    <span class="item-note">"<?= htmlspecialchars($item['Note']) ?>"</span>
                                <?php endif; ?>
                                <?php if (!empty($item['PickupTime'])): ?>
                                    <span class="item-note">
                                        <i class="far fa-clock"></i> Pickup: <?= date("h:i A", strtotime($item['PickupTime'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="item-price">
                                RM <?= number_format($item['Subtotal'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Payment Details</div>
                <div class="summary-container">
                    <div class="summary-row divider">
                        <span>Subtotal</span>
                        <span>RM <?= number_format($totalAmount, 2) ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Method</span>
                        <span style="font-weight:700;"><?= htmlspecialchars($order['PaymentMethod'] ?? 'Cash') ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Status</span>
                        <span class="payment-status <?= strtolower($order['PaymentStatus'] ?? 'pending') ?>" id="payment-status-badge">
                            <?= ucfirst($order['PaymentStatus'] ?? 'Pending') ?>
                        </span>
                    </div>

                    <div class="summary-row total">
                        <span>Total Paid</span>
                        <span>RM <?= number_format($totalAmount, 2) ?></span>
                    </div>
                </div>
            </div>

            <div class="section" id="dynamic-action-section"></div>

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
    const ORDER_ID = <?= $orderId ?>;
    const INITIAL_STATUS = "<?= strtolower($order['Status']) ?>";
    const INITIAL_PAYMENT_STATUS = "<?= strtolower($order['PaymentStatus'] ?? 'pending') ?>";
</script>
<script src="/U-Order/assets/js/order_detail.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>