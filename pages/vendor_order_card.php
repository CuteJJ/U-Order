<?php
// pages/vendor_order_card.php

if (!isset($item)) return;

// 1. 邏輯計算
$isCash = strtolower($item['PaymentMethod']) === 'cash';
$isPendingPay = $item['PaymentStatus'] === 'pending';
$showUnpaidWarning = $isCash && $isPendingPay;

// 時間顯示
$pickupDisplay = 'ASAP';
$pickupClass = 'badge-asap';
if (!empty($item['PickupTime'])) {
    $pickupTime = strtotime($item['PickupTime']);
    $pickupDisplay = date('H:i', $pickupTime); 
    $pickupClass = 'badge-time';
}

// 按鈕
$actionBtn = '';
if ($item['Status'] === 'pending') {
    $actionBtn = '<button class="btn-action btn-cook" onclick="updateItemStatusSingle('.$item['OrderListId'].', \'preparing\', event)">Cook</button>';
} elseif ($item['Status'] === 'preparing') {
    $actionBtn = '<button class="btn-action btn-ready" onclick="updateItemStatusSingle('.$item['OrderListId'].', \'ready\', event)">Ready</button>';
}
?>

<div class="item-card <?= $showUnpaidWarning ? 'card-unpaid' : '' ?>" 
     data-id="<?= $item['OrderListId'] ?>" 
     onclick="toggleSelection(this)">

    <input type="checkbox" class="batch-checkbox" style="display:none;" value="<?= $item['OrderListId'] ?>">

    <div class="card-header">
        <span class="order-id">#<?= $item['OrderId'] ?></span>
        <span class="pickup-badge <?= $pickupClass ?>">
            <?= $pickupDisplay ?>
        </span>
    </div>

    <div class="card-body">
        <h4>
            <?= htmlspecialchars($item['ProductName']) ?>
            <span class="qty">x<?= $item['Quantity'] ?></span>
        </h4>
        
        <?php if (!empty($item['Note'])): ?>
            <div class="order-note">
                <?= htmlspecialchars($item['Note']) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card-footer">
        <div>
            <span class="customer-name"><?= htmlspecialchars($item['CustomerName']) ?></span>
            
            <?php if ($showUnpaidWarning): ?>
                <span class="badge-payment badge-warn">UNPAID</span>
            <?php elseif ($isCash): ?>
                <span class="badge-payment badge-ok">PAID</span>
            <?php else: ?>
                <span class="badge-payment badge-ok"><?= strtoupper($item['PaymentMethod']) ?></span>
            <?php endif; ?>
        </div>

        <div>
            <?= $actionBtn ?>
        </div>
    </div>
</div>