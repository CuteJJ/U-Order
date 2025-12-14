<?php
include '../configs/db.php';
include '../includes/functions.php';

$productId = $_GET['id'] ?? null;
$cartItemId = $_GET['cart_item_id'] ?? null;

if (!$productId) {
    header("Location: ../index.php");
    exit;
}

// =====================================================
// 1. Fetch Product + Stall
// =====================================================
$sql = "SELECT p.*, s.StallName, s.StallId, s.LogoURL 
        FROM products p 
        JOIN stalls s ON p.StallId = s.StallId 
        WHERE p.ProductId = :pid";
$stmt = $db->prepare($sql);
$stmt->execute([':pid' => $productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: ../index.php");
    exit;
}

// =====================================================
// 2. Fetch Images
// =====================================================
$imgSql = "SELECT ImageURL FROM productimages WHERE ProductId = :pid";
$imgStmt = $db->prepare($imgSql);
$imgStmt->execute([':pid' => $productId]);
$images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($images)) {
    $images[] = "https://via.placeholder.com/600x400?text=No+Image";
}

// =====================================================
// 3. Edit Cart Mode
// =====================================================
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

// =====================================================
// 4. Time Slots
// =====================================================
date_default_timezone_set('Asia/Kuala_Lumpur');
$now = time();
$nextSlot = ceil($now / 1800) * 1800;
if ($nextSlot < $now + 900) $nextSlot += 1800;

$timeSlots = [];
$timeSlots[] = [
    'label' => 'Now (ASAP)',
    'value' => 'ASAP',
    'selected' => ($initialTime === 'ASAP' ? 'selected' : '')
];

for ($i = 0; $i < 4; $i++) {
    $ts = $nextSlot + ($i * 1800);
    $label = date('h:i A', $ts);
    $value = date('H:i', $ts);

    $selected = '';
    if ($existing && date('H:i', strtotime($existing['PickupTime'])) == $value) {
        $selected = 'selected';
        $timeSlots[0]['selected'] = '';
    }

    $timeSlots[] = [
        'label' => $label,
        'value' => $value,
        'selected' => $selected
    ];
}

$stallLogoUrl = fixAssetUrl($product['LogoURL']);

include '../includes/header.php';
?>

<link rel="stylesheet" href="/U-Order/assets/css/product.css">
<style>
    .stall-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: 0.2s;
        padding: 8px 16px;
        border-radius: 20px;
        background: #f0f0f0;
        border: 1px solid #ddd;
        text-decoration: none;
        color: inherit;
    }

    .stall-badge:hover {
        background: #e0e0e0;
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .stall-badge-logo {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        object-fit: cover;
    }

    #error-modal {
        opacity: 0;
        transition: opacity .25s ease;
    }

    #error-modal.active {
        opacity: 1;
    }

    #modal-card {
        transform: scale(.95);
        transition: transform .25s cubic-bezier(.16, .8, .3, 1);
    }

    #modal-card.pop {
        transform: scale(1);
    }

    .action-btn.disabled {
        background: #cccccc !important;
        pointer-events: none;
        cursor: not-allowed;
        opacity: 0.7;
    }
</style>
<?php flash(); ?>
<div class="product-page-wrapper">
    <div class="product-carousel">
        <div class="carousel-track">
            <?php foreach ($images as $img): ?>
                <div class="carousel-slide">
                    <img src="<?php echo htmlspecialchars(fixImageUrl($img)); ?>" alt="Product">
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($images) > 1): ?>
            <div class="custom-dots-container">
    <?php foreach ($images as $i => $img): ?>
        <div class="custom-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></div>
    <?php endforeach; ?>
</div>

            <button class="carousel-arrow arrow-prev" aria-label="Previous Slide">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="carousel-arrow arrow-next" aria-label="Next Slide">
                <i class="fas fa-chevron-right"></i>
            </button>
        <?php endif; ?> 

        <a href="../index.php" class="back-overlay-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    <!-- ============================
         PRODUCT INFO
    ============================= -->
    <div class="product-info">

        <!-- Stall Badge -->
        <a href="menu.php?stallid=<?php echo $product['StallId']; ?>" class="stall-badge">
            <img src="<?php echo htmlspecialchars($stallLogoUrl); ?>" class="stall-badge-logo">
            <?php echo htmlspecialchars($product['StallName']); ?>
        </a>

        <h1 class="product-title-large"><?php echo htmlspecialchars($product['ProductName']); ?></h1>

        <div class="price-display">
            <span class="currency-symbol">RM</span>
            <span class="price-value"><?php echo number_format($product['UnitPrice'], 2); ?></span>
        </div>

        <p class="product-description-full"><?php echo nl2br(htmlspecialchars($product['Description'])); ?></p>
        <!-- Desktop Options -->
        <div class="desktop-options">
            <div class="option-group">
                <label class="option-label">Special Instructions</label>
                <textarea id="desktop-note" class="note-input" placeholder="Any remarks?"><?php echo htmlspecialchars($initialNote); ?></textarea>
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
                        <?php
                        // Disable if NOT 'ASAP' AND (Time >= 18:00 OR Time < 09:00)
                        $isOutOfBounds = ($slot['value'] !== 'ASAP' && ($slot['value'] >= '18:00' || $slot['value'] < '09:00'));
                        
                        // Set styles based on boolean
                        $disabledClass = $isOutOfBounds ? 'disabled' : '';
                        $disabledStyle = $isOutOfBounds ? 'pointer-events: none; opacity: 0.5; cursor: not-allowed; background-color: #eee; color: #aaa;' : '';
                        ?>

                        <div class="time-pill <?php echo $slot['selected'] . ' ' . $disabledClass; ?>"
                            data-time="<?php echo $slot['value']; ?>"
                            style="<?php echo $disabledStyle; ?>">
                            <?php echo $slot['label']; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button class="action-btn submit-order-btn" style="width:100%; margin-top:20px;">
                <?php echo $cartItemId ? 'Update Order' : 'Add to Order'; ?>
                - RM <span class="display-total"><?php echo number_format($product['UnitPrice'] * $initialQty, 2); ?></span>
            </button>
        </div>
    </div>
</div>

<!-- MOBILE BOTTOM BAR -->
<div class="bottom-action-bar mobile-only">
    <div class="bar-price">
        <span>Total</span>
        <strong>RM <?php echo number_format($product['UnitPrice'] * $initialQty, 2); ?></strong>
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
    <div id="inline-error-mobile" style="
    display:none;
    background:#fee2e2;
    color:#b91c1c;
    padding:10px 14px;
    border-radius:8px;
    margin-bottom:10px;
    border:1px solid #fca5a5;
    font-size:0.9rem;
"></div>

    <div class="sheet-body">
        <div class="option-group">
            <label class="option-label">Special Instructions</label>
            <textarea id="mobile-note" class="note-input" placeholder="Any remarks?"><?php echo htmlspecialchars($initialNote); ?></textarea>
        </div>

        <div class="option-group">
            <label class="option-label">Quantity</label>
            <div class="quantity-wrapper" style="justify-content:space-between;">
                <button class="qty-btn qty-minus"><i class="fas fa-minus"></i></button>
                <span class="qty-display sheet-qty-val"><?php echo $initialQty; ?></span>
                <button class="qty-btn qty-plus"><i class="fas fa-plus"></i></button>
            </div>
        </div>

        <div class="option-group">
            <label class="option-label">Pickup Time</label>
            <div class="time-selector">
                <?php foreach ($timeSlots as $slot): ?>
                    <?php
                    // Disable if NOT 'ASAP' AND (Time >= 18:00 OR Time < 09:00)
                    $isOutOfBounds = ($slot['value'] !== 'ASAP' && ($slot['value'] >= '18:00' || $slot['value'] < '09:00'));

                    // Set styles based on boolean
                    $disabledClass = $isOutOfBounds ? 'disabled' : '';
                    $disabledStyle = $isOutOfBounds ? 'pointer-events: none; opacity: 0.5; cursor: not-allowed; background-color: #eee; color: #aaa;' : '';
                    ?>

                    <div class="time-pill <?php echo $slot['selected'] . ' ' . $disabledClass; ?>"
                        data-time="<?php echo $slot['value']; ?>"
                        style="<?php echo $disabledStyle; ?>">
                        <?php echo $slot['label']; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="sheet-footer">
        <button class="action-btn submit-order-btn" style="width:100%;">
            Confirm - RM <span class="display-total"><?php echo number_format($product['UnitPrice'] * $initialQty, 2); ?></span>
        </button>
    </div>
</div>

<!-- Hidden Form -->
<form id="order-form" action="cart_action.php" method="POST">
    <input type="hidden" name="action" value="<?php echo $cartItemId ? 'update_item' : 'add'; ?>">
    <input type="hidden" name="product_id" value="<?php echo $product['ProductId']; ?>">
    <input type="hidden" name="cart_item_id" value="<?php echo $cartItemId; ?>">
    <input type="hidden" name="quantity" id="final-qty" value="<?php echo $initialQty; ?>">
    <input type="hidden" name="pickup_time" id="final-time" value="<?php echo $initialTime; ?>">
    <input type="hidden" name="note" id="final-note" value="<?php echo htmlspecialchars($initialNote); ?>">
</form>

<input type="hidden" id="unit-price" value="<?php echo $product['UnitPrice']; ?>">

<!-- ERROR POPUP MODAL -->
<div id="error-modal" class="modal" style="
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,0.45);
    z-index: 99999;
">
    <div id="modal-card" style="
        background:white;
        width: 85%;
        max-width: 360px;
        padding: 20px;
        border-radius: 12px;
        text-align:center;
    ">
        <h3 style="margin-bottom:12px; font-size:1.2rem;">Notice</h3>
        <p id="error-modal-message" style="margin-bottom:20px; color:#444;"></p>
        <button id="error-modal-ok" style="
            padding:10px 20px;
            border:none;
            background:#2563eb;
            color:white;
            border-radius:8px;
            cursor:pointer;
        ">OK</button>
    </div>
</div>
<script src="/U-Order/assets/js/product.js"></script>
</body>
</html>