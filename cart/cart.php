<?php
session_start();
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: /pages/login.php");
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header("Location: /pages/login.php");
    exit;
}

function asset_url($path)
{
    if (!$path) return "/assets/images/placeholder.png";
    if (strpos($path, "/assets") === 0) return $path;
    if (strpos($path, "/images") === 0) return "/assets" . $path;
    return "/assets/images/products/" . $path;
}

// 取购物车 + 商品 + 图片 + 档口 + note + pickuptime + availability
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
ORDER BY s.StallName, p.ProductName
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

    // 可用性 & 状态文字
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

    // 这里 total 是所有 items 的（右边 summary 我用 JS 只算勾选的）
    $totalQty   += $r->Quantity;
    $totalPrice += $subtotal;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Order</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/cart.css">

    <style>

        .order-item.is-disabled {
            opacity: 0.45;
            /* 不再禁用 pointer-events，方便勾选/删除 */
            position: relative;
        }

        .item-status-pill {
            position: absolute;
            top: 10px;
            right: 18px;
            padding: 4px 10px;
            font-size: 0.75rem;
            border-radius: 999px;
            background: #e5e7eb;
            color: #4b5563;
        }

        .item-extra {
            margin-top: 4px;
            font-size: 0.85rem;
            color: #6b7280;
        }
        .item-extra .extra-label {
            font-weight: 600;
        }
        .item-extra .extra-sep {
            margin: 0 6px;
            opacity: 0.7;
        }

        .item-actions {
            display: flex;
            gap: 6px;
            margin-top: 4px;
            font-size: 0.8rem;
        }
        .btn-ghost {
            border: none;
            background: transparent;
            color: #4f46e5;
            cursor: pointer;
            padding: 3px 6px;
            border-radius: 999px;
        }
        .btn-ghost:hover {
            background: rgba(79,70,229,0.06);
        }

        .btn-icon {
            border: none;
            background: transparent;
            cursor: pointer;
            padding: 3px 4px;
        }

        .btn-icon svg {
            width: 16px;
            height: 16px;
            stroke: #9ca3af;
        }

        .btn-icon:hover svg {
            stroke: #ef4444;
        }

        .btn-primary[disabled],
        .btn-primary.btn-disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* 顶部提示条（有 unavailable 时显示） */
        .cart-banner {
            display: none;
            margin: 0 24px 6px;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 0.85rem;
            background: #f9fafb;
            color: #6b7280;
        }
        .cart-banner.show {
            display: block;
        }

        /* 顶部右侧 Delete selected 的那一行（你红框的位置） */
        .cart-actions-row {
            margin: 0 24px 12px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .btn-danger {
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 0.85rem;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #b91c1c;
            cursor: pointer;
        }
        .btn-danger:hover {
            background: #fee2e2;
        }

        /* 勾选框样式 */
        .item-main {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .item-check-wrap {
            padding-top: 10px;
        }
        .item-check {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

    </style>
</head>
<body>

<div class="order-shell">

    <!-- 顶部 -->
    <div class="order-top">
        <div class="order-top-left">
            <h1>Review your order</h1>
            <p>Check items from different stalls, adjust quantities, then proceed to payment.</p>
        </div>
        <div class="order-step-pill">
            STEP 2 · ORDER
        </div>
    </div>

    <!-- 顶部细小提示（有 unavailable item 时显示） -->
    <div id="cartBanner" class="cart-banner">
        Some items are unavailable. You can edit/remove them, but they cannot be checked out.
    </div>

    <!-- Delete selected 按钮行 -->
    <div class="cart-actions-row">
        <button id="btnDeleteSelected" class="btn-danger" type="button">
            Delete selected
        </button>
    </div>

    <!-- 主体两列 -->
    <div class="order-layout">

        <!-- 左侧：商品列表 -->
        <section class="order-main">
            <?php if (empty($stalls)) : ?>
                <div class="order-empty">Your cart is empty.</div>
            <?php else: ?>
                <?php foreach ($stalls as $stall): ?>
                    <article class="stall-panel">
                        <header class="stall-panel-header">
                            <div class="stall-panel-info">
                                <img src="<?= htmlspecialchars($stall['logoUrl']) ?>" class="stall-panel-logo">
                                <div>
                                    <h2><?= htmlspecialchars($stall['stallName']) ?></h2>
                                    <span>
                                        <?= count($stall['items']) ?> item(s) from this stall
                                    </span>
                                </div>
                            </div>
                        </header>

                        <!-- 每个档口内部 -->
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
                                        <div class="item-status-pill">
                                            <?= htmlspecialchars($item['statusLabel'] ?: 'Unavailable') ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- 左：勾选 + 图片 + 文本 -->
                                    <div class="item-main">
                                        <label class="item-check-wrap">
                                            <input type="checkbox"
                                                   class="item-check"
                                                   <?= $item['isUnavailable'] ? '' : 'checked' ?>>
                                        </label>

                                        <img src="<?= htmlspecialchars($item['imageUrl']) ?>" class="item-img">
                                        <div class="item-text">
                                            <h3><?= htmlspecialchars($item['name']) ?></h3>
                                            <p><?= htmlspecialchars($item['description']) ?></p>

                                            <?php if ($item['note'] || $pickupLabel): ?>
                                                <p class="item-extra">
                                                    <?php if ($item['note']): ?>
                                                        <span class="extra-label">Note:</span>
                                                        <?= htmlspecialchars($item['note']) ?>
                                                    <?php endif; ?>

                                                    <?php if ($item['note'] && $pickupLabel): ?>
                                                        <span class="extra-sep">•</span>
                                                    <?php endif; ?>

                                                    <?php if ($pickupLabel): ?>
                                                        <span class="extra-label">Pickup:</span>
                                                        <?= $pickupLabel ?>
                                                    <?php endif; ?>
                                                </p>
                                            <?php endif; ?>

                                            <div class="item-actions">
                                                <button type="button"
                                                        class="btn-ghost btn-edit"
                                                        data-product-id="<?= (int)$item['productId'] ?>"
                                                        data-cart-item-id="<?= (int)$item['cartItemId'] ?>">
                                                    Edit
                                                </button>

                                                <!-- 单个删除：灰色垃圾桶 -->
                                                <button type="button"
                                                        class="btn-icon btn-remove"
                                                        aria-label="Remove item">
                                                    <svg viewBox="0 0 24 24" fill="none">
                                                        <path d="M6 7h12M10 7v10M14 7v10M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2M5 7h14l-1 13H6L5 7z"
                                                              stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 中：数量 -->
                                    <div class="item-qty">
                                        <button class="qty-btn minus" type="button">−</button>
                                        <span class="qty-value"><?= (int)$item['quantity'] ?></span>
                                        <button class="qty-btn plus" type="button">+</button>
                                    </div>

                                    <!-- 右：价格 -->
                                    <div class="item-price">
                                        <div class="item-unit">
                                            RM <span class="unit-val"><?= number_format($item['unitPrice'], 2) ?></span>
                                        </div>
                                        <div class="item-sub">
                                            Subtotal:
                                            <span class="sub-val">
                                                RM <?= number_format($item['subtotal'], 2) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- 右侧：Summary -->
        <aside class="order-side">
            <div class="summary-card">
                <h2>Your order</h2>

                <div class="summary-row">
                    <span>Selected items</span>
                    <span id="sumQty"><?= $totalQty ?> items</span>
                </div>

                <div class="summary-row summary-row-total">
                    <span>Total</span>
                    <strong>RM <span id="sumTotal"><?= number_format($totalPrice, 2) ?></span></strong>
                </div>

                <p class="summary-note">
                    * You can order from multiple stalls in one go. Service fee &amp; payment will be calculated in the next step.
                </p>

                <div class="summary-actions">
                    <a href="/pages/menu.php" class="btn-secondary">Add more items</a>
                    <button id="btnProceed" class="btn-primary" type="button">Proceed to payment</button>
                </div>
            </div>
        </aside>
    </div>
</div>
<script src="../assets/js/cart.js"></script>
</body>
</html>
