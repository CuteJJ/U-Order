<?php
/* 假设 $itemData 已经存在 */
?>

<div class="item-card"
     data-id="<?= $itemData['OrderListId'] ?>"
     data-selected="0">

    <div class="item-header">
        <div class="item-title-group">
            <span class="order-id-tag">#<?= $itemData["OrderId"] ?></span>
            <div class="item-title"><?= htmlspecialchars($itemData["ProductName"]) ?></div>
        </div>
        <div class="item-qty">x<?= $itemData["Quantity"] ?></div>
    </div>

    <div class="item-meta">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        <span class="meta-value"><?= htmlspecialchars($itemData["CustomerName"]) ?></span>
    </div>

    <div class="item-meta">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        <span class="meta-label">Ordered:</span>
        <span class="meta-value"><?= date("H:i", strtotime($itemData["CreatedAt"])) ?></span>
    </div>

    <?php if (!empty($itemData["Note"])): ?>
        <div class="item-note">
            <span class="note-label">Note:</span>
            <span class="note-text"><?= htmlspecialchars($itemData["Note"]) ?></span>
        </div>
    <?php endif; ?>

    <div class="item-footer">
        
        <div class="footer-info">
            <span class="meta-label">Pickup:</span>
            <span class="pickup-time">
                <?= $itemData["PickupTime"] ? date("H:i", strtotime($itemData["PickupTime"])) : "ASAP" ?>
            </span>
        </div>

        <div class="footer-actions">
            <?php if ($itemData["Status"] === "pending"): ?>
                <button class="card-btn btn-start"
                        onclick="updateItemStatusSingle(<?= $itemData['OrderListId'] ?>, 'preparing', event)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                    Start
                </button>
            <?php elseif ($itemData["Status"] === "preparing"): ?>
                <button class="card-btn btn-ready"
                        onclick="updateItemStatusSingle(<?= $itemData['OrderListId'] ?>, 'ready', event)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    Ready
                </button>
            <?php endif; ?>
        </div>
        
    </div>
</div>