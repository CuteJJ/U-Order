<?php
require_once '../configs/db.php';

// 必须 vendor 登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$vendorId = (int)$_SESSION['user_id'];

/* 拿 StallId */
$stmt = $db->prepare("SELECT StallId FROM stalls WHERE StaffId = ? LIMIT 1");
$stmt->execute([$vendorId]);
$stall = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$stall) {
    die("No stall assigned.");
}
$stallId = (int)$stall["StallId"];

/*
 * 拉出当前档口所有行级 orderitems
 */
$sql = "
    SELECT 
        oi.OrderListId,
        oi.OrderId,
        oi.ProductId,
        oi.Quantity,
        oi.Note,
        oi.PickupTime,
        oi.Status,
        p.ProductName,
        p.CategoryId,
        u.Name AS CustomerName,
        o.CreatedAt
    FROM orderitems oi
    JOIN products p ON p.ProductId = oi.ProductId
    JOIN orders o   ON o.OrderId = oi.OrderId
    JOIN users u    ON u.UserId = o.UserId
    WHERE o.StallId = ?
    ORDER BY 
        (oi.PickupTime IS NULL),
        oi.PickupTime,
        oi.OrderListId
";
$stmt = $db->prepare($sql);
$stmt->execute([$stallId]);
$allItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 按 status into 3 columns */
$pendingItems   = [];
$preparingItems = [];
$readyItems     = [];

foreach ($allItems as $item) {
    switch ($item['Status']) {
        case 'pending':
            $pendingItems[] = $item;
            break;
        case 'preparing':
            $preparingItems[] = $item;
            break;
        case 'ready':
            $readyItems[] = $item;
            break;
    }
}

?>

<?php include "vendor_sidebar.php"; ?>

<div class="main-area">

    <?php include "vendor_topbar.php"; ?>

    <div class="page-content">

        <!-- CSS 文件 -->
        <link rel="stylesheet" href="../assets/css/vendor_orders.css">
        <link rel="stylesheet" href="../assets/css/item_card.css">

        <!-- FILTER BAR（重做 UI） -->
        <div class="filter-bar">

            <div class="filter-group">
                <label>Category:</label>
                <select id="filter-category">
                    <option value="all">All</option>
                    <option value="1">Rice</option>
                    <option value="2">Drinks</option>
                    <option value="3">Dessert</option>
                </select>
            </div>

            <div class="filter-group">
    <label>Pickup:</label>
    <select id="filter-pickup">
        <option value="all">All</option>
        <option value="now">Now (ASAP + ≤30 min)</option>
        <option value="1h">Within 1 hour</option>
        <option value="2h">Within 2 hours</option>
    </select>
</div>


            <!-- 批量操作按钮 -->
            <button id="batchPrepare" class="batch-btn" disabled>
                Start Preparing
            </button>

            <button id="batchReady" class="batch-btn" disabled>
                Mark Ready
            </button>

        </div>

        <!-- THREE COLUMNS -->
        <div class="orders-board">

            <!-- Pending -->
            <div class="orders-column" id="col-pending">
                <div class="column-header">
                    <span class="column-title">To Prepare</span>
                    <span class="column-count" id="count-pending"><?= count($pendingItems) ?></span>
                </div>

                <div class="column-body">
                    <?php foreach ($pendingItems as $item): ?>
                        <div class="order-item-wrapper"
                             data-item-id="<?= $item['OrderListId'] ?>"
                             data-status="<?= htmlspecialchars($item['Status']) ?>"
                             data-category="<?= (int)$item['CategoryId'] ?>"
                             data-pickup="<?= $item['PickupTime'] ? htmlspecialchars($item['PickupTime']) : '' ?>">

                            <?php $itemData = $item; include "item_card.php"; ?>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Preparing -->
            <div class="orders-column" id="col-preparing">
                <div class="column-header">
                    <span class="column-title">Cooking Now</span>
                    <span class="column-count" id="count-preparing"><?= count($preparingItems) ?></span>
                </div>

                <div class="column-body">
                    <?php foreach ($preparingItems as $item): ?>
                        <div class="order-item-wrapper"
                             data-item-id="<?= $item['OrderListId'] ?>"
                             data-status="<?= htmlspecialchars($item['Status']) ?>"
                             data-category="<?= (int)$item['CategoryId'] ?>"
                             data-pickup="<?= $item['PickupTime'] ? htmlspecialchars($item['PickupTime']) : '' ?>">

                            <?php $itemData = $item; include "item_card.php"; ?>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Ready -->
            <div class="orders-column" id="col-ready">
                <div class="column-header">
                    <span class="column-title">Ready for Pickup</span>
                    <span class="column-count" id="count-ready"><?= count($readyItems) ?></span>
                </div>

                <div class="column-body">
                    <?php foreach ($readyItems as $item): ?>
                        <div class="order-item-wrapper"
                             data-item-id="<?= $item['OrderListId'] ?>"
                             data-status="<?= htmlspecialchars($item['Status']) ?>"
                             data-category="<?= (int)$item['CategoryId'] ?>"
                             data-pickup="<?= $item['PickupTime'] ? htmlspecialchars($item['PickupTime']) : '' ?>">

                            <?php $itemData = $item; include "item_card.php"; ?>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div><!-- /.orders-board -->

    </div><!-- /.page-content -->

</div><!-- /.main-area -->

<!-- JS -->
<script src="../assets/js/item_card.js"></script>
<script src="../assets/js/vendor_orders.js"></script>
