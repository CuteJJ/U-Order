<?php
session_start();
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: /pages/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

/* ============================================================
    FIX IMAGE URL (NO CHANGE)
============================================================ */
function fixImageUrl($path)
{
    if (!$path) return "/U-Order/assets/images/placeholder_food.png";

    $clean = ltrim($path, '/');

    // 如果数据库存的是 images/products/xxx → 加 /assets/
    if (strpos($clean, 'images/') === 0) {
        return "/U-Order/assets/" . $clean;
    }

    // 最后兜底
    return "/U-Order/assets/images/products/" . $clean;
}

/* ============================================================
    取所有订单 (NO CHANGE)
============================================================ */
$sqlOrders = "
    SELECT o.*, s.StallName
    FROM orders o
    JOIN stalls s ON o.StallId = s.StallId
    WHERE o.UserId = :uid
    ORDER BY o.CreatedAt DESC
";
$stmt = $db->prepare($sqlOrders);
$stmt->execute(['uid' => $userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
    取每个订单商品 (NO CHANGE)
============================================================ */
function getOrderItems($db, $orderId)
{
    $sql = "
        SELECT 
            oi.*,
            p.ProductName,
            p.UnitPrice,
            (
                SELECT ImageURL 
                FROM productimages 
                WHERE ProductId = p.ProductId 
                LIMIT 1
            ) AS ImageURL
        FROM orderitems oi
        JOIN products p ON oi.ProductId = p.ProductId
        WHERE oi.OrderId = :oid
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute(['oid' => $orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ============================================================
    Payment Method (NO CHANGE)
============================================================ */
function getPaymentMethod($db, $paymentId)
{
    if (!$paymentId) return "Unknown";
    $stmt = $db->prepare("SELECT PaymentMethod FROM payments WHERE PaymentId = :pid");
    $stmt->execute(['pid' => $paymentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? ucfirst($row['PaymentMethod']) : "Unknown";
}

/* ============================================================
    Status Badge (Nord Style) (NO CHANGE)
============================================================ */
function statusBadge($status)
{
    // Nord Theme Color Palette mapping for status
    $nordColors = [
        'pending'    => ['#ebcb8b', '#4c566a', '#fff4e6'], // Aurora Yellow (Warning)
        'preparing'  => ['#88c0d0', '#2e3440', '#e9f6fb'], // Frost Light Blue (In Progress)
        'ready'      => ['#a3be8c', '#2e3440', '#f0f6e9'], // Aurora Green (Success)
        'cancelled'  => ['#bf616a', '#4c566a', '#fdf3f4'], // Aurora Red (Danger)
        'default'    => ['#4c566a', '#2e3440', '#f0f2f5'], // Polar Dark Gray
    ];

    $c = $nordColors[strtolower($status)] ?? $nordColors['default'];

    $mainColor = $c[0];
    $textColor = $c[1];
    $lightColor = $c[2];

    return "
    <span style='
        display:inline-block;
        padding:7px 16px;
        border-radius:999px;
        font-size:0.85rem;
        font-weight:700;
        letter-spacing:0.5px;
        
        color:{$textColor};
        
        background: {$lightColor}; 
        border: 1px solid {$mainColor};
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);

        position:relative;
        overflow:hidden;
    '>
        ".strtoupper($status)."
    </span>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>My Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    /* === NORD THEME COLOR PALETTE === */
    :root {
        --nord-polar-night-1: #2e3440; /* Darkest background/header */
        --nord-polar-night-2: #3b4252; /* Darker text */
        --nord-polar-night-3: #434c5e; /* Medium text/label */
        --nord-polar-night-4: #4c566a; /* Lightest dark color/detail text */

        --nord-snow-storm-1: #d8dee9; /* Light background */
        --nord-snow-storm-2: #eceff4; /* Lighter background/card inner */

        --nord-frost-1: #8fbcbb; /* Mint */
        --nord-frost-2: #88c0d0; /* Light Blue */
        --nord-frost-3: #81a1c1; /* Medium Blue (Primary) */

        --nord-aurora-green: #a3be8c; /* Price color */
    }

    body {
        /* Lighter, cool-toned background */
        background: var(--nord-snow-storm-1); 
        font-family: 'Inter', sans-serif;
        margin:0;
        min-height: 100vh;
        color: var(--nord-polar-night-2);
    }
    .page-shell {
        max-width:850px; 
        margin:0 auto;
        padding: 40px 20px 80px;
    }
    
    /* === HEADER === */
    .page-header {
        display: flex;
        align-items: center;
        margin-bottom: 40px;
        padding: 0 10px;
    }

    /* Back Button Style - FIX: text-decoration: none; */
    .back-btn {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: var(--nord-frost-3);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white; 
        font-size: 1.2rem;
        box-shadow: 0 4px 15px rgba(129, 161, 193, 0.4);
        margin-right: 20px;
        text-decoration: none; /* Prevents the underline on the link/button */
    }

    .back-btn:hover {
        background: #88c0d0;
        transform: scale(1.05);
    }

    h1 { 
        font-size:2.4rem; 
        margin:0;
        font-weight: 800;
        color: var(--nord-polar-night-1);
    }

    /* ----------- NORD-INSPIRED CARD ----------- */
    .order-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 28px;
        margin-bottom: 25px;
        border: 1px solid var(--nord-snow-storm-1);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        transition: all 0.25s ease;
        animation: fadeInUp 0.5s ease forwards;
    }

    .order-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.15); 
    }

    .order-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
    }

    .order-id {
        font-size:1.6rem; 
        font-weight:800;
        color: var(--nord-polar-night-1);
    }

    .order-stall {
        font-size: 1rem;
        color: var(--nord-polar-night-4);
        margin-top: 2px;
        font-weight: 500;
    }

    /* FIX: Removed dashed border */
    .order-date {
        color: var(--nord-polar-night-4);
        margin-top:8px;
        font-size: 0.9rem;
        font-weight: 500;
        padding-bottom: 15px; /* Keep padding for spacing */
    }
    /* END FIX */

    .order-items {
        background:var(--nord-snow-storm-2);
        border-radius:12px;
        padding:18px 20px;
        margin-top:20px;
    }

    .item-row {
        /* Changed to column to stack item details */
        display:flex;
        flex-direction: column; 
        align-items: stretch;
        border-bottom:1px solid var(--nord-snow-storm-1);
        padding:12px 0;
        font-size: 0.95rem;
    }
    .item-row:last-of-type { border-bottom:none; }

    /* New style for the product name/price line */
    .item-main-details {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px; /* Space between main details and notes */
    }
    
    .item-img {
        width:60px;
        height:60px;
        border-radius:10px;
        object-fit:cover;
        background:#d1d5db;
        margin-right:15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .item-left {
        display:flex;
        align-items:center;
        flex:1;
        font-weight: 600;
        color: var(--nord-polar-night-2);
    }
    .item-main-details > div:last-child { 
        font-weight: 700; 
        color: var(--nord-polar-night-1); 
    }

    /* Item Notes and Pickup Time style */
    .item-addon-details {
        font-size: 0.9rem;
        color: var(--nord-polar-night-4);
        padding-left: 75px; /* Aligns with text next to image */
        line-height: 1.4;
    }
    .item-addon-details .label {
        font-weight: 700;
        color: var(--nord-polar-night-3);
        margin-right: 5px;
    }

    /* Total Row Style (Kept simple row structure) */
    .total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size:1.1rem !important;
        font-weight:800; 
        padding-top: 15px !important;
    }
    .total-row > div:last-child {
        color: var(--nord-aurora-green); /* Price in Nord Green */
    }


    /* Order-level details at the bottom (Payment Method) */
    .order-level-details {
        margin-top:20px;
        font-size:0.95rem;
        line-height:1.8;
        padding-left: 5px;
        color: var(--nord-polar-night-4);
    }
    .order-level-details .label { 
        font-weight:700; 
        color: var(--nord-polar-night-3);
        margin-right: 5px;
    }

    /* Animation for dynamic load */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(15px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
</head>

<body>

<div class="page-shell">

    <div class="page-header">
        <a href="../index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1>My Order History</h1>
    </div>

<?php foreach ($orders as $i => $order): ?>
<?php 
    $items = getOrderItems($db, $order['OrderId']);
    $method = getPaymentMethod($db, $order['PaymentId']);

    /* 自动计算 Total */
    $total = $order['TotalAmount'] ?? array_sum(
        array_map(fn($it)=>$it['Quantity']*$it['UnitPrice'], $items)
    );
?>

<div class="order-card" style="animation-delay: <?= $i * 0.1 ?>s;">

    <div class="order-header">
        <div>
            <div class="order-id">Order #<?= $order['OrderId'] ?></div>
            <div class="order-stall">Stall: <?= htmlspecialchars($order['StallName']) ?></div>
        </div>
        <?= statusBadge($order['Status']) ?>
    </div>

    <div class="order-date">
        <?= date("d M Y, h:i A", strtotime($order['CreatedAt'])) ?>
    </div>

    <div class="order-items">
        <?php foreach ($items as $it): ?>
        <?php 
            $img = fixImageUrl($it['ImageURL']);
            $subtotal = $it['Quantity'] * $it['UnitPrice'];
            $note = htmlspecialchars($it['Note'] ?? ""); // Use 'Note' from orderitems table
            $pickupTime = $it['PickupTime'] ?: "ASAP"; // Use 'PickupTime' from orderitems table
        ?>
        <div class="item-row">
            <div class="item-main-details">
                <div class="item-left">
                    <img src="<?= $img ?>" class="item-img" alt="<?= htmlspecialchars($it['ProductName']) ?>">
                    <div><?= $it['Quantity'] ?>x <?= htmlspecialchars($it['ProductName']) ?></div>
                </div>
                <div>RM <?= number_format($subtotal, 2) ?></div>
            </div>
            
            <div class="item-addon-details">
                <?php if ($note): ?>
                    <div><span class="label">Note:</span> <?= $note ?></div>
                <?php endif; ?>
                <div><span class="label">Pickup:</span> <?= $pickupTime ?></div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="total-row">
            <div>Total</div>
            <div>RM <?= number_format($total, 2) ?></div>
        </div>
    </div>

    <div class="order-level-details">
        <div><span class="label">Payment Method:</span> <?= $method ?></div>
    </div>

</div>

<?php endforeach; ?>

</div>

</body>
</html>