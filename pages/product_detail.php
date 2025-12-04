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

$initialQty  = $existing ? (int)$existing['Quantity'] : 1;
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
    $ts     = $nextSlot + ($i * 1800);
    $label  = date('h:i A', $ts);
    $value  = date('H:i', $ts);
    $sel    = '';

    if ($existing && date('H:i', strtotime($existing['PickupTime'])) == $value) {
        $sel = 'selected';
        $timeSlots[0]['selected'] = '';
    }

    $timeSlots[] = [
        'label'    => $label,
        'value'    => $value,
        'selected' => $sel
    ];
}

// =====================================================
// 5. IMAGE FIXERS
// =====================================================
function fixAssetUrl($path) {
    if (!$path) return "https://via.placeholder.com/40?text=S";
    if (strpos($path, 'http') === 0) return $path;
    return "/U-Order/assets/" . ltrim($path, '/');
}

function fixImageUrl($path) {
    if (!$path) return "https://via.placeholder.com/600x400?text=No+Image";
    if (strpos($path, 'http') === 0) return $path;
    return "../assets/" . ltrim($path, '/');
}

$stallLogoUrl = fixAssetUrl($product['LogoURL']);

include '../includes/header.php';
?>

<link rel="stylesheet" href="/U-Order/assets/css/product.css">

<style>
/* =============================
   NEW: Carousel Arrows
============================= */
.carousel-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 30;
    background: rgba(255,255,255,0.85);
    border: none;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.carousel-arrow i { font-size: 18px; color: #444; }
.left-arrow { left: 10px; }
.right-arrow { right: 10px; }
.carousel-arrow:hover { background: #ffffff; }

/* keep your existing CSS */
</style>

<div class="product-page-wrapper">

    <!-- ============================
         IMAGE CAROUSEL (WITH ARROWS)
    ============================= -->
    <div class="product-carousel">

        <!-- LEFT ARROW -->
        <button class="carousel-arrow left-arrow">
            <i class="fas fa-chevron-left"></i>
        </button>

        <div class="carousel-track">
            <?php foreach ($images as $img): ?>
                <div class="carousel-slide">
                    <img src="<?= htmlspecialchars(fixImageUrl($img)) ?>" alt="Product">
                </div>
            <?php endforeach; ?>
        </div>

        <!-- RIGHT ARROW -->
        <button class="carousel-arrow right-arrow">
            <i class="fas fa-chevron-right"></i>
        </button>

        <?php if (count($images) > 1): ?>
            <div class="carousel-indicators">
                <?php foreach ($images as $i => $img): ?>
                    <div class="indicator <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></div>
                <?php endforeach; ?>
            </div>
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
        <a href="menu.php?stallid=<?= $product['StallId'] ?>" class="stall-badge">
            <img src="<?= htmlspecialchars($stallLogoUrl) ?>" class="stall-badge-logo">
            <i class="fas fa-store"></i>
            <?= htmlspecialchars($product['StallName']) ?>
        </a>

        <h1 class="product-title-large"><?= htmlspecialchars($product['ProductName']) ?></h1>

        <div class="price-display">
            <span class="currency-symbol">RM</span>
            <span class="price-value"><?= number_format($product['UnitPrice'], 2) ?></span>
        </div>

        <p class="product-description-full"><?= nl2br(htmlspecialchars($product['Description'])) ?></p>

        <!-- INLINE ERROR MESSAGE -->
        <div id="inline-error" style="
            display:none;
            background:#fee2e2;
            color:#b91c1c;
            padding:10px 14px;
            border-radius:8px;
            margin-bottom:10px;
            border:1px solid #fca5a5;
            font-size:0.95rem;
        "></div>

        <!-- Desktop Options -->
        <div class="desktop-options">
            <div class="option-group">
                <label class="option-label">Special Instructions</label>
                <textarea id="desktop-note" class="note-input"><?= htmlspecialchars($initialNote) ?></textarea>
            </div>

            <div class="option-group">
                <label class="option-label">Quantity</label>
                <div class="quantity-wrapper">
                    <button class="qty-btn qty-minus"><i class="fas fa-minus"></i></button>
                    <span class="qty-display"><?= $initialQty ?></span>
                    <button class="qty-btn qty-plus"><i class="fas fa-plus"></i></button>
                </div>
            </div>

            <div class="option-group">
                <label class="option-label">Pickup Time</label>
                <div class="time-selector">
                    <?php foreach ($timeSlots as $slot): ?>
                        <div class="time-pill <?= $slot['selected'] ?>" data-time="<?= $slot['value'] ?>">
                            <?= $slot['label'] ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button class="action-btn submit-order-btn" style="width:100%; margin-top:20px;">
                <?= $cartItemId ? 'Update Order' : 'Add to Order' ?> - RM 
                <span class="display-total"><?= number_format($product['UnitPrice'] * $initialQty, 2) ?></span>
            </button>
        </div>
    </div>
</div>

<!-- MOBILE BOTTOM BAR -->
<div class="bottom-action-bar mobile-only">
    <div class="bar-price">
        <span>Total</span>
        <strong>RM 
            <span class="display-total"><?= number_format($product['UnitPrice'] * $initialQty, 2) ?></span>
        </strong>
    </div>
    <button class="action-btn" id="trigger-sheet-btn"><?= $cartItemId ? 'Update' : 'Add' ?></button>
</div>

<!-- MOBILE SHEET -->
<div class="sheet-overlay"></div>
<div class="bottom-sheet">
    <div class="sheet-header">
        <span class="sheet-title"><?= $cartItemId ? 'Update Order' : 'Customize' ?></span>
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
            <textarea id="mobile-note" class="note-input"><?= htmlspecialchars($initialNote) ?></textarea>
        </div>

        <div class="option-group">
            <label class="option-label">Quantity</label>
            <div class="quantity-wrapper" style="justify-content:space-between;">
                <button class="qty-btn qty-minus"><i class="fas fa-minus"></i></button>
                <span class="qty-display sheet-qty-val"><?= $initialQty ?></span>
                <button class="qty-btn qty-plus"><i class="fas fa-plus"></i></button>
            </div>
        </div>

        <div class="option-group">
            <label class="option-label">Pickup Time</label>
            <div class="time-selector">
                <?php foreach ($timeSlots as $slot): ?>
                    <div class="time-pill <?= $slot['selected'] ?>" data-time="<?= $slot['value'] ?>">
                        <?= $slot['label'] ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <div class="sheet-footer">
        <button class="action-btn submit-order-btn" style="width:100%;">
            Confirm - RM 
            <span class="display-total"><?= number_format($product['UnitPrice'] * $initialQty, 2) ?></span>
        </button>
    </div>
</div>

<!-- Hidden Form -->
<form id="order-form" action="cart_action.php" method="POST">
    <input type="hidden" name="action" value="<?= $cartItemId ? 'update_item' : 'add' ?>">
    <input type="hidden" name="product_id" value="<?= $product['ProductId'] ?>">
    <input type="hidden" name="cart_item_id" value="<?= $cartItemId ?>">
    <input type="hidden" name="quantity" id="final-qty" value="<?= $initialQty ?>">
    <input type="hidden" name="pickup_time" id="final-time" value="<?= $initialTime ?>">
    <input type="hidden" name="note" id="final-note" value="<?= htmlspecialchars($initialNote) ?>">
</form>

<input type="hidden" id="unit-price" value="<?= $product['UnitPrice'] ?>">

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

<!-- CAROUSEL JS (NEW) -->
<script>
const track = document.querySelector('.carousel-track');
const slides = Array.from(document.querySelectorAll('.carousel-slide'));
const dots = Array.from(document.querySelectorAll('.indicator'));
const leftBtn = document.querySelector('.left-arrow');
const rightBtn = document.querySelector('.right-arrow');

let currentIndex = 0;

function updateCarousel() {
    const width = slides[0].clientWidth;
    track.style.transform = `translateX(-${currentIndex * width}px)`;

    dots.forEach(d => d.classList.remove('active'));
    if (dots[currentIndex]) dots[currentIndex].classList.add('active');
}

rightBtn?.addEventListener('click', () => {
    currentIndex++;
    if (currentIndex >= slides.length) currentIndex = 0;
    updateCarousel();
});

leftBtn?.addEventListener('click', () => {
    currentIndex--;
    if (currentIndex < 0) currentIndex = slides.length - 1;
    updateCarousel();
});

dots.forEach((dot, idx) => {
    dot.addEventListener('click', () => {
        currentIndex = idx;
        updateCarousel();
    });
});

window.addEventListener('resize', updateCarousel);
</script>

<!-- MAIN PRODUCT JS -->
<script src="/U-Order/assets/js/product.js"></script>

</body>
</html>
