<?php
include '../configs/db.php';
include '../includes/functions.php';

$productId = $_GET['id'] ?? null;
$cartItemId = $_GET['cart_item_id'] ?? null;

if (!$productId) {
    header("Location: ../index.php");
    exit;
}

// 1. Fetch Product
$sql = "SELECT p.*, s.StallName FROM products p JOIN stalls s ON p.StallId = s.StallId WHERE p.ProductId = :pid";
$stmt = $db->prepare($sql);
$stmt->execute([':pid' => $productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: ../index.php");
    exit;
}

// 2. Fetch Images
$imgSql = "SELECT ImageURL FROM productimages WHERE ProductId = :pid";
$imgStmt = $db->prepare($imgSql);
$imgStmt->execute([':pid' => $productId]);
$images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($images)) $images[] = 'https://via.placeholder.com/600x400?text=No+Image';

// 3. Check Existing Cart Item (Edit Mode)
$existing = null;
if ($cartItemId) {
    $sql = "SELECT Quantity, Note, PickupTime FROM cartitems WHERE CartItemId = :cid";
    $st = $db->prepare($sql);
    $st->execute([':cid' => $cartItemId]);
    $existing = $st->fetch(PDO::FETCH_ASSOC);
}

$initialQty = $existing ? (int)$existing['Quantity'] : 1;
$initialNote = $existing ? $existing['Note'] : '';
$initialTime = $existing ? $existing['PickupTime'] : 'ASAP'; 

// 4. Generate Time Slots
date_default_timezone_set('Asia/Kuala_Lumpur');
$now = time();
$nextSlot = ceil($now / 1800) * 1800; 
if ($nextSlot < $now + 900) $nextSlot += 1800;

$timeSlots = [];
// Force "ASAP" as first option
$timeSlots[] = ['label' => 'Now (ASAP)', 'value' => 'ASAP', 'selected' => ($initialTime === 'ASAP' ? 'selected' : '')];

for ($i = 0; $i < 4; $i++) {
    $ts = $nextSlot + ($i * 1800);
    $timeLabel = date('h:i A', $ts);
    $timeValue = date('H:i', $ts);
    
    $selected = '';
    if ($existing && date('H:i', strtotime($existing['PickupTime'])) == $timeValue) {
        $selected = 'selected';
        // Clear ASAP if we found a specific time match
        $timeSlots[0]['selected'] = ''; 
    }
    
    $timeSlots[] = ['label' => $timeLabel, 'value' => $timeValue, 'selected' => $selected];
}

include '../includes/header.php';
?>

<link rel="stylesheet" href="/U-Order/assets/css/product.css">

<div class="product-page-wrapper">
    <div class="product-carousel">
        <div class="carousel-track">
            <?php foreach ($images as $img): ?>
                <div class="carousel-slide">
                    <?php 
                        $displayImg = strpos($img, 'http') === 0 ? $img : "../assets" . $img;
                        $displayImg = str_replace('assets//', 'assets/', $displayImg);
                    ?>
                    <img src="<?php echo htmlspecialchars($displayImg); ?>" alt="Product">
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($images) > 1): ?>
        <div class="carousel-indicators">
            <?php foreach ($images as $index => $img): ?>
                <div class="indicator <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <a href="../index.php" class="back-overlay-btn"><i class="fas fa-arrow-left"></i></a>
    </div>

    <div class="product-info">
        <span class="stall-badge"><i class="fas fa-store"></i> <?php echo htmlspecialchars($product['StallName']); ?></span>
        <h1 class="product-title-large"><?php echo htmlspecialchars($product['ProductName']); ?></h1>
        <div class="price-display">
            <span class="currency-symbol">RM</span>
            <span class="price-value"><?php echo number_format($product['UnitPrice'], 2); ?></span>
        </div>
        <p class="product-description-full"><?php echo nl2br(htmlspecialchars($product['Description'])); ?></p>

        <!-- DESKTOP OPTIONS -->
        <div class="desktop-options">
            <div class="option-group">
                <label class="option-label">Special Instructions</label>
                <textarea id="desktop-note" class="note-input" placeholder="E.g. No onions"><?php echo htmlspecialchars($initialNote); ?></textarea>
            </div>

            <div class="option-group">
                <label class="option-label">Quantity</label>
                <div class="quantity-wrapper">
                    <button class="qty-btn qty-minus"><i class="fas fa-minus"></i></button>
                    <span class="qty-display"><?php echo $initialQty; ?></span>
                    <button class="qty-btn qty-plus"><i class="fas fa-plus"></i></button>
                </div>
            </div>

            <div class="option-group">
                <label class="option-label">Pickup Time</label>
                <div class="time-selector">
                    <?php foreach ($timeSlots as $slot): ?>
                        <div class="time-pill <?php echo $slot['selected']; ?>" data-time="<?php echo $slot['value']; ?>">
                            <?php echo $slot['label']; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button class="action-btn submit-order-btn" style="width: 100%; margin-top: 20px;">
                <?php echo $cartItemId ? 'Update Order' : 'Add to Order'; ?> - RM <span class="display-total"><?php echo number_format($product['UnitPrice'] * $initialQty, 2); ?></span>
            </button>
        </div>
    </div>
</div>

<!-- MOBILE BOTTOM BAR -->
<div class="bottom-action-bar mobile-only">
    <div class="bar-price">
        <span>Total</span>
        <strong>RM <span class="display-total"><?php echo number_format($product['UnitPrice'] * $initialQty, 2); ?></span></strong>
    </div>
    <button class="action-btn" id="trigger-sheet-btn"><?php echo $cartItemId ? 'Update' : 'Add'; ?></button>
</div>

<!-- MOBILE SHEET -->
<div class="sheet-overlay"></div>
<div class="bottom-sheet">
    <div class="sheet-header">
        <span class="sheet-title"><?php echo $cartItemId ? 'Update Order' : 'Customize'; ?></span>
        <span class="close-sheet">&times;</span>
    </div>
    <div class="sheet-body">
        <div class="option-group">
            <label class="option-label">Special Instructions</label>
            <textarea id="mobile-note" class="note-input" placeholder="E.g. No onions"><?php echo htmlspecialchars($initialNote); ?></textarea>
        </div>
        <div class="option-group">
            <label class="option-label">Quantity</label>
            <div class="quantity-wrapper" style="width: 100%; justify-content: space-between;">
                <button class="qty-btn qty-minus"><i class="fas fa-minus"></i></button>
                <span class="qty-display sheet-qty-val"><?php echo $initialQty; ?></span>
                <button class="qty-btn qty-plus"><i class="fas fa-plus"></i></button>
            </div>
        </div>
        <div class="option-group">
            <label class="option-label">Pickup Time</label>
            <div class="time-selector">
                <?php foreach ($timeSlots as $slot): ?>
                    <div class="time-pill <?php echo $slot['selected']; ?>" data-time="<?php echo $slot['value']; ?>">
                        <?php echo $slot['label']; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="sheet-footer" style="margin-top: 20px;">
        <button class="action-btn submit-order-btn" style="width: 100%;">
            Confirm - RM <span class="display-total"><?php echo number_format($product['UnitPrice'] * $initialQty, 2); ?></span>
        </button>
    </div>
</div>

<form id="order-form" action="cart_action.php" method="POST">
    <input type="hidden" name="action" value="<?php echo $cartItemId ? 'update_item' : 'add'; ?>">
    <input type="hidden" name="product_id" value="<?php echo $product['ProductId']; ?>">
    <input type="hidden" name="cart_item_id" value="<?php echo $cartItemId; ?>">
    <input type="hidden" name="quantity" id="final-qty" value="<?php echo $initialQty; ?>">
    <input type="hidden" name="pickup_time" id="final-time" value="<?php echo $initialTime; ?>">
    <input type="hidden" name="note" id="final-note" value="<?php echo htmlspecialchars($initialNote); ?>">
</form>

<input type="hidden" id="unit-price" value="<?php echo $product['UnitPrice']; ?>">
<script src="/U-Order/assets/js/product.js"></script>
</body>
</html>