<?php
include '../configs/db.php';
include '../includes/functions.php';

$productId = $_GET['id'] ?? 0;

// 1. Fetch Product Info
$sql = "SELECT p.*, s.StallName 
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

// 2. Fetch Images
$imgSql = "SELECT ImageURL FROM productimages WHERE ProductId = :pid";
$imgStmt = $db->prepare($imgSql);
$imgStmt->execute([':pid' => $productId]);
$images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($images)) {
    $images[] = 'https://via.placeholder.com/600x400?text=No+Image';
}

// 3. Generate Time Slots (KL Time)
date_default_timezone_set('Asia/Kuala_Lumpur');
$now = time();
// Round to next 30 mins
$nextSlot = ceil($now / 1800) * 1800; 
if ($nextSlot < $now + 900) { // Ensure at least 15 mins prep time
    $nextSlot += 1800;
}

$timeSlots = [];
// Generate next 4 slots
for ($i = 0; $i < 4; $i++) {
    $timeSlots[] = date('h:i A', $nextSlot + ($i * 1800));
}

include '../includes/header.php';
?>

<link rel="stylesheet" href="/U-Order/assets/css/product.css">

<div class="product-page-wrapper">
    
    <!-- Left: Carousel -->
    <div class="product-carousel">
        <div class="carousel-track">
            <?php foreach ($images as $img): ?>
                <div class="carousel-slide">
                    <img src="<?php echo htmlspecialchars($img); ?>" alt="Product Image">
                </div>
            <?php endforeach; ?>
        </div>
        <a href="../index.php" class="back-overlay-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    <!-- Right: Details & Desktop Options -->
    <div class="product-info">
        <span class="stall-badge"><i class="fas fa-store"></i> <?php echo htmlspecialchars($product['StallName']); ?></span>
        <h1 class="product-title-large"><?php echo htmlspecialchars($product['ProductName']); ?></h1>
        
        <div class="price-display">
            <span class="currency-symbol">RM</span>
            <span class="price-value"><?php echo number_format($product['UnitPrice'], 2); ?></span>
        </div>

        <p class="product-description-full">
            <?php echo nl2br(htmlspecialchars($product['Description'])); ?>
        </p>

        <!-- DESKTOP OPTIONS -->
        <div class="desktop-options">
            <div class="option-group">
                <label class="option-label">Quantity</label>
                <div class="quantity-wrapper">
                    <button class="qty-btn qty-minus"><i class="fas fa-minus"></i></button>
                    <span class="qty-display">1</span>
                    <button class="qty-btn qty-plus"><i class="fas fa-plus"></i></button>
                </div>
            </div>

            <div class="option-group">
                <label class="option-label">Pickup Time</label>
                <div class="time-selector">
                    <div class="time-pill selected" data-time="ASAP">Now (ASAP)</div>
                    <?php foreach ($timeSlots as $slot): ?>
                        <div class="time-pill" data-time="<?php echo $slot; ?>"><?php echo $slot; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button class="action-btn submit-order-btn" style="width: 100%; margin-top: 20px;">
                Add to Order - RM <span class="display-total"><?php echo number_format($product['UnitPrice'], 2); ?></span>
            </button>
        </div>
    </div>
</div>

<!-- MOBILE BOTTOM BAR -->
<div class="bottom-action-bar mobile-only">
    <div class="bar-price">
        <span>Total</span>
        <strong>RM <strong class="display-total"><?php echo number_format($product['UnitPrice'], 2); ?></strong></strong>
    </div>
    <button class="action-btn" id="trigger-sheet-btn">Add to Order</button>
</div>

<!-- MOBILE BOTTOM SHEET -->
<div class="sheet-overlay"></div>
<div class="bottom-sheet">
    <div class="sheet-header">
        <span class="sheet-title">Customize Order</span>
        <span class="close-sheet">&times;</span>
    </div>
    
    <div class="sheet-body">
        <div class="option-group">
            <label class="option-label">Quantity</label>
            <div class="quantity-wrapper" style="width: 100%; justify-content: space-between;">
                <button class="qty-btn qty-minus"><i class="fas fa-minus"></i></button>
                <span class="qty-display sheet-qty-val">1</span>
                <button class="qty-btn qty-plus"><i class="fas fa-plus"></i></button>
            </div>
        </div>
        
        <div class="option-group">
            <label class="option-label">Pickup Time</label>
            <div class="time-selector">
                <div class="time-pill selected" data-time="ASAP">Now (ASAP)</div>
                <?php foreach ($timeSlots as $slot): ?>
                    <div class="time-pill" data-time="<?php echo $slot; ?>"><?php echo $slot; ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="sheet-footer" style="margin-top: 20px;">
        <button class="action-btn submit-order-btn" style="width: 100%;">
            Confirm - RM <span class="display-total"><?php echo number_format($product['UnitPrice'], 2); ?></span>
        </button>
    </div>
</div>

<!-- Hidden Form -->
<form id="order-form" action="cart_action.php" method="POST">
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="product_id" value="<?php echo $product['ProductId']; ?>">
    <input type="hidden" name="quantity" id="final-qty" value="1">
    <input type="hidden" name="pickup_time" id="final-time" value="ASAP">
</form>

<input type="hidden" id="unit-price" value="<?php echo $product['UnitPrice']; ?>">

<script src="/U-Order/assets/js/product.js"></script>
</body>
</html>