<?php
session_start();
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: ../pages/login.php");
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header("Location: ../pages/login.php");
    exit;
}

function asset_url($path)
{
    if (!$path) return "https://via.placeholder.com/100?text=No+Image";
    if (strpos($path, "http") === 0) return $path;
    $cleanPath = ltrim($path, '/');
    if (strpos($cleanPath, "images/") === 0) return "/U-Order/assets/" . $cleanPath;
    if (strpos($cleanPath, "assets/") === 0) return "/U-Order/" . $cleanPath;
    return "/U-Order/assets/" . $cleanPath;
}

// Handle AJAX validation request
if (isset($_GET['action']) && $_GET['action'] === 'validate_checkout') {
    header('Content-Type: application/json');
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['cartItemIds']) || !is_array($data['cartItemIds'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }
    
    $cartItemIds = array_map('intval', $data['cartItemIds']);
    
    if (empty($cartItemIds)) {
        echo json_encode(['success' => false, 'message' => 'No items selected']);
        exit;
    }
    
    // Fetch all selected items with detailed info
    $placeholders = implode(',', array_fill(0, count($cartItemIds), '?'));
    $sql = "
    SELECT 
        ci.CartItemId,
        ci.Quantity,
        ci.ProductId,
        
        p.ProductName,
        p.UnitPrice,
        p.IsUnlimitedStock,
        p.Stock,
        p.IsAvailable AS ProductIsAvailable,
        p.StallId,
        
        s.StallName,
        s.IsAvailable AS StallIsAvailable,
        
        c.UserId
    FROM cartitems ci
    JOIN carts c ON ci.CartId = c.CartId
    JOIN products p ON ci.ProductId = p.ProductId
    JOIN stalls s ON p.StallId = s.StallId
    WHERE ci.CartItemId IN ($placeholders)
      AND c.UserId = ?
    ";
    
    $params = array_merge($cartItemIds, [$userId]);
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if all items belong to user
    if (count($items) !== count($cartItemIds)) {
        echo json_encode([
            'success' => false,
            'message' => 'Some items not found in your cart'
        ]);
        exit;
    }
    
    $errors = [];
    
    foreach ($items as $item) {
        $cartItemId = $item['CartItemId'];
        $productName = $item['ProductName'];
        $stallName = $item['StallName'];
        $quantity = (int)$item['Quantity'];
        $isUnlimited = (int)$item['IsUnlimitedStock'] === 1;
        $stock = (int)$item['Stock'];
        $stallAvailable = (int)$item['StallIsAvailable'] === 1;
        $productAvailable = (int)$item['ProductIsAvailable'] === 1;
        
        // Check 1: Stall is closed
        if (!$stallAvailable) {
            $errors[] = [
                'cartItemId' => $cartItemId,
                'type' => 'stall_closed',
                'message' => "'{$productName}' is unavailable - {$stallName} is currently closed"
            ];
            continue;
        }
        
        // Check 2: Product is unavailable
        if (!$productAvailable) {
            $errors[] = [
                'cartItemId' => $cartItemId,
                'type' => 'product_unavailable',
                'message' => "'{$productName}' is currently unavailable"
            ];
            continue;
        }
        
       // Check 3: Stock validation
if (!$isUnlimited) {
    if ($stock <= 0) {
        $errors[] = [
            'cartItemId' => $cartItemId,
            'type' => 'out_of_stock',
            'message' => "'{$productName}' is out of stock"
        ];
        continue;
    }
    
    if ($quantity > $stock) {
        $errors[] = [
            'cartItemId' => $cartItemId,
            'type' => 'insufficient_stock',
            'message' => "'{$productName}' - Only {$stock} available, but you have {$quantity} in cart"
        ];
        continue;
    }
} else {
    // ✅ ADD THIS: Even for unlimited items, ensure Stock is not 0
    if ($stock <= 0) {
        $errors[] = [
            'cartItemId' => $cartItemId,
            'type' => 'out_of_stock',
            'message' => "'{$productName}' is currently out of stock"
        ];
        continue;
    }
}
    }
    
    // If any errors found
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Some items cannot be checked out',
            'errors' => $errors
        ]);
        exit;
    }
    
    // All validations passed
    echo json_encode([
        'success' => true,
        'message' => 'All items validated successfully'
    ]);
    exit;
}

// Fetch Cart Data
$sql = "
SELECT 
    ci.CartItemId,
    ci.Quantity,
    ci.Note,
    ci.PickupTime,

    p.ProductId,
    p.ProductName,
    p.Description,
    p.UnitPrice,
    p.StallId,
    p.IsUnlimitedStock,
    p.Stock,
    p.IsAvailable AS ProductIsAvailable,

    s.StallName,
    s.LogoURL,
    s.IsAvailable AS StallIsAvailable,

    (SELECT ImageURL FROM productimages 
        WHERE ProductId = p.ProductId 
        ORDER BY ImageId ASC LIMIT 1
    ) AS ProductImage
FROM carts c
JOIN cartitems ci ON c.CartId = ci.CartId
JOIN products p  ON ci.ProductId = p.ProductId
JOIN stalls   s  ON p.StallId = s.StallId
WHERE c.UserId = :uid
ORDER BY ci.CartItemId DESC
";

$stmt = $db->prepare($sql);
$stmt->execute(["uid" => $userId]);
$rows = $stmt->fetchAll(PDO::FETCH_OBJ);

$stalls = [];
$totalQty   = 0;
$totalPrice = 0;

foreach ($rows as $r) {
    $stallId = $r->StallId;

    if (!isset($stalls[$stallId])) {
        $stalls[$stallId] = [
            "stallId"   => $stallId,
            "stallName" => $r->StallName,
            "logoUrl"   => asset_url($r->LogoURL),
            "items"     => []
        ];
    }

    $subtotal = $r->UnitPrice * $r->Quantity;

    // Availability Logic
    $isUnavailable = false;
    $statusLabel   = '';

    if ((int)$r->StallIsAvailable !== 1) {
        $isUnavailable = true;
        $statusLabel   = 'Stall closed';
    } elseif ((int)$r->ProductIsAvailable !== 1) {
        $isUnavailable = true;
        $statusLabel   = 'Unavailable';
    } elseif ((int)$r->Stock <= 0) {
        $isUnavailable = true;
        $statusLabel   = 'Out of stock';
    }

    $stalls[$stallId]["items"][] = [
        "cartItemId"    => $r->CartItemId,
        "productId"     => $r->ProductId,
        "name"          => $r->ProductName,
        "description"   => $r->Description,
        "quantity"      => $r->Quantity,
        "unitPrice"     => $r->UnitPrice,
        "subtotal"      => $subtotal,
        "imageUrl"      => asset_url($r->ProductImage),
        "isUnlimited"   => $r->IsUnlimitedStock,
        "stock"         => $r->Stock,
        "note"          => $r->Note,
        "pickupTime"    => $r->PickupTime,
        "isUnavailable" => $isUnavailable,
        "statusLabel"   => $statusLabel,
    ];

    $totalQty   += $r->Quantity;
    $totalPrice += $subtotal;
}

include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="../assets/css/cart.css">
<div class="order-shell">
    <div class="order-top">
        <div class="header-left">
            <h1>My Cart</h1>
            <p>Review items, adjust quantities, or add notes.</p>
        </div>
    </div>

    <!-- Unavailable Item Banner -->
    <div id="cartBanner" class="cart-banner">
        <i class="fas fa-exclamation-triangle"></i>
        Some items are unavailable. Please remove them to proceed.
    </div>

    <!-- DISPLAY FLASH MESSAGES HERE -->
    <?php flash(); ?>

    <!-- Global Actions -->
    <div class="cart-actions-row">
        <!-- Header & Back Button -->
    <a href="/U-Order/index.php" class="back-pill">
        <i class="fas fa-arrow-left"></i> Back to Menu
    </a>
        <button id="btnDeleteSelected" class="btn-danger-outline" type="button">
            <i class="fas fa-trash-alt"></i> Delete Selected
        </button>
    </div>

    <!-- WRAP IN FORM TO SUBMIT SELECTED ITEMS TO PAYMENT.PHP -->
    <form action="../pages/payment.php" method="POST" id="checkoutForm">

        <div class="order-layout">
            <!-- LEFT COLUMN: Items -->
            <section class="order-main">
                <?php if (empty($stalls)) : ?>
                    <div style="text-align: center; padding: 60px; color: var(--text-muted);">
                        <i class="fas fa-shopping-basket" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.5;"></i>
                        <p class="cart-empty-message">Your cart is empty.</p>
                        <a href="../index.php" class="btn-secondary" style="display:inline-block; width:auto; margin-top:10px;">Go to Menu</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($stalls as $stall): ?>
                        <article class="stall-panel">
                            <header class="stall-panel-header">
                                <div class="stall-panel-info">
                                    <img src="<?= htmlspecialchars($stall['logoUrl']) ?>" class="stall-panel-logo" alt="Stall Logo">
                                    <div>
                                        <h2><?= htmlspecialchars($stall['stallName']) ?></h2>
                                    </div>
                                </div>
                            </header>

                            <div class="stall-items">
                                <?php foreach ($stall['items'] as $item): ?>
                                    <?php
                                    $pickupLabel = '';
                                    if (!empty($item['pickupTime'])) {
                                        $pickupLabel = date("h:i A", strtotime($item['pickupTime']));
                                    }
                                    ?>
                                    <div class="order-item <?= $item['isUnavailable'] ? 'is-disabled' : '' ?>"
                                        data-id="<?= (int)$item['cartItemId'] ?>"
                                        data-product-id="<?= (int)$item['productId'] ?>"
                                        data-unit-price="<?= (float)$item['unitPrice'] ?>"
                                        data-stock="<?= (int)$item['stock'] ?>"
                                        data-unlimited="<?= (int)$item['isUnlimited'] ?>"
                                        data-unavailable="<?= $item['isUnavailable'] ? '1' : '0' ?>"
                                        data-status-label="<?= htmlspecialchars($item['statusLabel']) ?>">
                                        <?php if ($item['isUnavailable']): ?>
                                            <div class="status-badge">
                                                <?= htmlspecialchars($item['statusLabel'] ?: 'Unavailable') ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- 1. Checkbox: Add name and value for form submission -->
                                        <div class="item-check-wrap">
                                            <input type="checkbox" class="item-check"
                                                name="selected_items[]"
                                                value="<?= $item['cartItemId'] ?>"
                                                <?= $item['isUnavailable'] ? '' : 'checked' ?>>
                                        </div>

                                        <!-- 2. Image & Details -->
                                        <div style="display: flex; gap: 12px; width: 100%;">
                                            <img src="<?= htmlspecialchars($item['imageUrl']) ?>" class="item-img" alt="Product">

                                            <div class="item-text" style="flex: 1;">
                                                <h3><?= htmlspecialchars($item['name']) ?></h3>

                                                <!-- Meta Pills (Note & Pickup) -->
                                                <?php if ($item['note'] || $pickupLabel): ?>
                                                    <div class="item-meta">
                                                        <?php if ($pickupLabel): ?>
                                                            <span class="meta-pill pickup">
                                                                <i class="far fa-clock"></i> <?= $pickupLabel ?>
                                                            </span>
                                                        <?php endif; ?>

                                                        <?php if ($item['note']): ?>
                                                            <span class="meta-pill">
                                                                <i class="far fa-comment-dots"></i> <?= htmlspecialchars($item['note']) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Edit / Remove Links -->
                                                <div class="item-actions-row">
                                                    <a href="../pages/product_detail.php?id=<?= $item['productId'] ?>&cart_item_id=<?= $item['cartItemId'] ?>"
                                                        class="action-link action-edit">
                                                        <i class="fas fa-pen"></i> Edit
                                                    </a>

                                                    <button type="button" class="action-link action-remove btn-remove">
                                                        <i class="fas fa-trash"></i> Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 3. Quantity -->
                                        <div class="item-qty">
                                            <button class="qty-btn minus" type="button">−</button>
                                            <span class="qty-value"><?= (int)$item['quantity'] ?></span>
                                            <button class="qty-btn plus" type="button">+</button>
                                        </div>

                                        <!-- 4. Price -->
                                        <div class="item-price">
                                            <div class="unit-price">RM <?= number_format($item['unitPrice'], 2) ?></div>
                                            <div class="sub-total">RM <span class="sub-val"><?= number_format($item['subtotal'], 2) ?></span></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- RIGHT COLUMN: Summary -->
            <aside class="order-side">
                <div class="summary-card">
                    <h2>Total</h2>

                    <div class="summary-row">
                        <span>Items selected</span>
                        <span id="sumQty"><?= $totalQty ?></span>
                    </div>

                    <div class="summary-row summary-row-total">
                        <span>Total Amount</span>
                        <span style="color: var(--nord10);">RM <span id="sumTotal"><?= number_format($totalPrice, 2) ?></span></span>
                    </div>

                    <p class="summary-note">
                        Review your order carefully. Pickup times are estimates.
                    </p>

                    <div class="summary-actions">
                        <a href="../index.php" class="btn-secondary">Add more items</a>

                        <!-- Checkout button as Submit -->
                        <button id="btnProceed" class="btn-primary" type="submit">
                            Checkout
                        </button>
                    </div>

                </div>
            </aside>
        </div>

    </form> <!-- END FORM -->

</div>
<div id="confirmModal" class="modal-overlay" style="
    display:none;
    position:fixed; inset:0; 
    background:rgba(0,0,0,.45);
    align-items:center; justify-content:center;
    z-index:9999;">

    <div class="modal-box" style="
        background:#fff; 
        width:85%; max-width:380px;
        padding:20px; border-radius:12px;
        text-align:center;
        transform:scale(.9);
        transition:.25s;">

        <h3 style="margin:0 0 10px; font-size:1.25rem;">Confirm</h3>
        <p id="confirmMsg" style="margin-bottom:20px; color:#444;"></p>

        <div style="display:flex; gap:10px; justify-content:center;">
            <button id="confirmCancel" style="padding:8px 14px; border:1px solid #aaa; border-radius:8px; background:#fff;">Cancel</button>
            <button id="confirmOk" style="padding:8px 14px; border:none; border-radius:8px; background:#2563eb; color:#fff;">OK</button>
        </div>
    </div>
</div>

<!-- Assuming existing cart.js handles functionality -->
<script src="../assets/js/cart.js"></script>
</body>

</html> 