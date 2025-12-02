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
    } elseif (!(int)$r->IsUnlimitedStock && (int)$r->Stock <= 0) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Cart</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* --- NORD PALETTE & VARIABLES --- */
        :root {
            /* Polar Night */
            --nord0: #2e3440;
            --nord1: #3b4252;
            --nord2: #434c5e;
            --nord3: #4c566a;
            /* Snow Storm */
            --nord4: #d8dee9;
            --nord5: #e5e9f0;
            --nord6: #eceff4;
            /* Frost */
            --nord7: #8fbcbb;
            --nord8: #88c0d0;
            --nord9: #81a1c1;
            --nord10: #5e81ac;
            /* Aurora */
            --nord11: #bf616a; /* Red */
            --nord12: #d08770; /* Orange */
            --nord13: #ebcb8b; /* Yellow */
            --nord14: #a3be8c; /* Green */
            --nord15: #b48ead; /* Purple */

            --bg-color: var(--nord6);
            --card-bg: #ffffff;
            --text-main: var(--nord0);
            --text-muted: var(--nord3);
            --border-color: var(--nord4);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
        }

        a { text-decoration: none; color: inherit; }

        /* --- LAYOUT --- */
        .order-shell {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
        }

        /* --- HEADER --- */
        .order-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .header-left h1 {
            margin: 0 0 4px 0;
            font-size: 1.8rem;
            color: var(--nord1);
        }
        .header-left p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        .btn-back-menu {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--nord10);
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.2s;
            margin-bottom: 10px;
        }
        .btn-back-menu:hover { color: var(--nord9); }

        .order-step-pill {
            background: var(--nord10);
            color: #fff;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        /* --- ALERTS --- */
        .cart-banner {
            display: none; /* JS toggles this */
            background-color: var(--nord13); /* Yellow/Orange warning */
            color: var(--nord1);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border-left: 4px solid var(--nord12);
        }
        .cart-banner.show { display: block; }

        .cart-actions-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 16px;
        }
        .btn-danger-outline {
            background: transparent;
            border: 1px solid var(--nord11);
            color: var(--nord11);
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .btn-danger-outline:hover {
            background: var(--nord11);
            color: #fff;
        }

        /* --- MAIN GRID --- */
        .order-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
        }
        @media (max-width: 850px) {
            .order-layout { grid-template-columns: 1fr; }
        }

        /* --- STALL CARDS --- */
        .stall-panel {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.03);
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        .stall-panel-header {
            padding: 16px;
            background: #fcfcfc;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
        }
        .stall-panel-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .stall-panel-logo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--nord4);
        }
        .stall-panel-info h2 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--nord1);
        }
        .stall-panel-info span {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* --- ITEMS --- */
        .stall-items { padding: 0; }
        .order-item {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 16px;
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            position: relative;
            align-items: start;
        }
        .order-item:last-child { border-bottom: none; }
        
        .order-item.is-disabled {
            background-color: #fafafa;
            opacity: 0.8;
        }
        .order-item.is-disabled .item-img, 
        .order-item.is-disabled .item-text { opacity: 0.5; }

        /* Checkbox */
        .item-check-wrap { padding-top: 5px; }
        .item-check {
            width: 18px; height: 18px;
            accent-color: var(--nord10);
            cursor: pointer;
        }

        /* Image */
        .item-img {
            width: 80px; height: 80px;
            object-fit: cover;
            border-radius: 8px;
            background: var(--nord6);
        }

        /* Text Info */
        .item-text h3 {
            margin: 0 0 4px 0;
            font-size: 1rem;
            color: var(--nord1);
        }
        .item-text p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.4;
        }
        
        /* Badges/Pills for Pickup & Note */
        .item-meta {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .meta-pill {
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 4px;
            background: var(--nord6);
            color: var(--nord2);
            border: 1px solid var(--nord4);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .meta-pill.pickup {
            background: rgba(143, 188, 187, 0.15); /* Light Nord7 */
            color: var(--nord0);
            border-color: var(--nord7);
        }

        /* Actions: Edit & Remove */
        .item-actions-row {
            margin-top: 10px;
            display: flex;
            gap: 12px;
            font-size: 0.85rem;
        }
        .action-link {
            cursor: pointer;
            border: none;
            background: none;
            padding: 0;
            font-weight: 500;
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .action-edit { color: var(--nord10); }
        .action-edit:hover { color: var(--nord9); }
        .action-remove { color: var(--nord11); }
        .action-remove:hover { color: var(--nord12); }

        /* Qty Control */
        .item-qty {
            display: flex;
            align-items: center;
            border: 1px solid var(--nord4);
            border-radius: 6px;
            overflow: hidden;
            height: 32px;
            background: #fff;
        }
        .qty-btn {
            border: none;
            background: #fff;
            width: 28px;
            height: 100%;
            cursor: pointer;
            color: var(--nord3);
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qty-btn:hover { background: var(--nord6); }
        .qty-value {
            min-width: 30px;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--nord1);
        }

        /* Price */
        .item-price {
            text-align: right;
            min-width: 80px;
        }
        .unit-price { font-size: 0.8rem; color: var(--text-muted); }
        .sub-total {
            font-weight: 700;
            color: var(--nord1);
            font-size: 1rem;
            margin-top: 2px;
        }

        /* Warning Pill for Unavailable */
        .status-badge {
            position: absolute;
            top: 10px; right: 10px;
            background: var(--nord11);
            color: #fff;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 99px;
            font-weight: 600;
            z-index: 2;
        }

        /* --- SUMMARY SIDEBAR --- */
        .summary-card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            position: sticky;
            top: 20px;
        }
        .summary-card h2 {
            margin: 0 0 20px 0;
            font-size: 1.2rem;
            color: var(--nord1);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.95rem;
            color: var(--nord2);
        }
        .summary-row-total {
            border-top: 1px dashed var(--nord4);
            padding-top: 16px;
            margin-top: 16px;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--nord0);
        }
        .summary-note {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin: 16px 0 24px;
            line-height: 1.4;
        }

        .btn-primary {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: var(--nord10);
            color: #fff;
            text-align: center;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary:hover { background-color: var(--nord9); }
        .btn-primary:disabled { background-color: var(--nord4); cursor: not-allowed; }

        .btn-secondary {
            display: block;
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            background-color: transparent;
            color: var(--nord10);
            text-align: center;
            border: 1px solid var(--nord10);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.2s;
        }
        .btn-secondary:hover { background-color: rgba(94, 129, 172, 0.05); }

    </style>
</head>
<body>

<div class="order-shell">

    <!-- Header & Back Button -->
    <a href="../index.php" class="btn-back-menu">
        <i class="fas fa-arrow-left"></i> Back to Menu
    </a>

    <div class="order-top">
        <div class="header-left">
            <h1>My Cart</h1>
            <p>Review items, adjust quantities, or add notes.</p>
        </div>
        <div class="order-step-pill">Review</div>
    </div>

    <!-- Unavailable Item Banner -->
    <div id="cartBanner" class="cart-banner">
        <i class="fas fa-exclamation-triangle"></i> 
        Some items are unavailable. Please remove them to proceed.
    </div>

    <!-- Global Actions -->
    <div class="cart-actions-row">
        <button id="btnDeleteSelected" class="btn-danger-outline" type="button">
            <i class="fas fa-trash-alt"></i> Delete Selected
        </button>
    </div>

    <div class="order-layout">
        <!-- LEFT COLUMN: Items -->
        <section class="order-main">
            <?php if (empty($stalls)) : ?>
                <div style="text-align: center; padding: 60px; color: var(--nord3);">
                    <i class="fas fa-shopping-basket" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.5;"></i>
                    <p>Your cart is empty.</p>
                    <a href="../index.php" class="btn-secondary" style="display:inline-block; width:auto; margin-top:10px;">Go to Menu</a>
                </div>
            <?php else: ?>
                <?php foreach ($stalls as $stall): ?>
                    <article class="stall-panel">
                      <header class="stall-panel-header">
    <a href="../pages/menu.php?stallid=<?= $stall['stallId'] ?>" 
       style="text-decoration: none; color: inherit; display: flex; align-items: center; width: 100%; transition: opacity 0.2s;">
        <div class="stall-panel-info">
            <img src="<?= htmlspecialchars($stall['logoUrl']) ?>" class="stall-panel-logo" alt="Stall Logo">
            <div>
                <h2><?= htmlspecialchars($stall['stallName']) ?></h2>
            </div>
        </div>
    </a>
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
                                     data-status-label="<?= htmlspecialchars($item['statusLabel']) ?>"
                                >
                                    <?php if ($item['isUnavailable']): ?>
                                        <div class="status-badge">
                                            <?= htmlspecialchars($item['statusLabel'] ?: 'Unavailable') ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- 1. Checkbox -->
                                    <div class="item-check-wrap">
                                        <input type="checkbox" class="item-check" <?= $item['isUnavailable'] ? '' : 'checked' ?>>
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
                                                <!-- Link to product_detail.php correctly -->
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

    <!-- Checkout button should NOT be inside <a> -->
    <button id="btnProceed" class="btn-primary" type="button">
        Checkout
    </button>
</div>

            </div>
        </aside>
    </div>
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