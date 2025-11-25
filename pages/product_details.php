<?php
include '../configs/db.php';
include '../includes/functions.php';

$productId   = $_GET['product_id'] ?? null;
$cartItemId  = $_GET['cart_item_id'] ?? null;

if (!$productId) die("Missing product ID");

// ----------- Fetch product ------------
$sql = "SELECT p.*, s.StallName 
        FROM products p
        JOIN stalls s ON p.StallId = s.StallId
        WHERE p.ProductId = :id";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) die("Product not found");

// ----------- Fetch images ------------
$imgs = $db->prepare("SELECT ImageURL FROM productimages WHERE ProductId = :id");
$imgs->execute([':id' => $productId]);
$images = $imgs->fetchAll(PDO::FETCH_COLUMN);

// ----------- If editing existing cart item ------------
$existing = null;
if ($cartItemId) {
    $sql = "SELECT Quantity, Note, PickupTime
            FROM cartitems
            WHERE CartItemId = :cid";
    $st = $db->prepare($sql);
    $st->execute([':cid' => $cartItemId]);
    $existing = $st->fetch(PDO::FETCH_ASSOC);
}

// ----------- Generate pickup times ------------
date_default_timezone_set("Asia/Kuala_Lumpur");
$interval = 15;
$open  = "08:00";
$close = "20:00";

$now = new DateTime();
$openTime = new DateTime($open);
$closeTime = new DateTime($close);

$times = [];
for ($t = clone $openTime; $t <= $closeTime; $t->modify("+{$interval} minutes")) {
    if ($t > $now) {
        $times[] = [
            "label" => $t->format("h:i A"),
            "value" => $t->format("Y-m-d H:i:s")
        ];
    }
}

$isEditMode = $cartItemId ? 'true' : 'false';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($product["ProductName"]) ?></title>
<link rel="stylesheet" href="../assets/css/root.css">

<style>
body {
    margin: 0;
    background: #f5f7fa;
    font-family: var(--font-main);
}

.wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    height: calc(100vh - 40px);
    padding: 20px;
    gap: 20px;
}

/* LEFT */
.left-box {
    background: white;
    border-radius: 20px;
    box-shadow: var(--shadow-soft);
    padding: 20px;
}

.main-img {
    width: 100%;
    height: 330px;
    object-fit: cover;
    border-radius: 16px;
    background: #e5e5e5;
}

.thumb-row {
    display: flex;
    gap: 10px;
    margin-top: 14px;
}

.thumb-row img {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 10px;
    cursor: pointer;
    border: 2px solid transparent;
}
.thumb-row img.active {
    border-color: var(--frost-3);
}

/* RIGHT */
.right-box {
    background: white;
    border-radius: 20px;
    padding: 24px;
    box-shadow: var(--shadow-soft);
    display: flex;
    flex-direction: column;
}

.back-btn {
    color: #555;
    margin-bottom: 10px;
    cursor: pointer;
}

.p-name {
    font-size: 2rem;
    font-weight: 800;
}

.p-price {
    font-size: 1.4rem;
    margin-top: 5px;
    color: var(--aurora-green);
    font-weight: 700;
}

.p-desc {
    margin-top: 10px;
    line-height: 1.5rem;
    color: #666;
}

.section-title {
    font-weight: bold;
    margin-top: 25px;
}

.input-box {
    width: 100%;
    padding: 14px;
    margin-top: 8px;
    border-radius: 12px;
    border: 1px solid #d3d3d3;
    font-size: 1rem;
}

/* Footer Bar */
.footer-bar {
    margin-top: auto;
    display: flex;
    gap: 15px;
    align-items: center;
}

.qty-btn {
    width: 38px;
    height: 38px;
    font-size: 20px;
    border-radius: 10px;
    border: 1px solid #ccc;
    background: #fafafa;
    cursor: pointer;
}

.qty-num {
    min-width: 20px;
    text-align: center;
    font-weight: bold;
    font-size: 1.1rem;
}

.add-btn {
    flex: 1;
    background: var(--aurora-green);
    color: white;
    padding: 15px;
    border-radius: 12px;
    text-align: center;
    cursor: pointer;
    font-weight: 700;
    letter-spacing: .5px;
    font-size: 1.1rem;
}

/* ---------- Toast (绿色成功弹窗 / 红色错误) ---------- */
.toast-box {
    position: fixed;
    left: 50%;
    top: 20px;
    transform: translateX(-50%) translateY(-10px);
    background: #16a34a; /* 绿色成功 */
    color: #fff;
    padding: 10px 20px;
    border-radius: 999px;
    font-size: 0.95rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.18);
    opacity: 0;
    pointer-events: none;
    transition: opacity .25s ease, transform .25s ease;
    z-index: 9999;
}
.toast-box.error {
    background: #dc2626; /* 红色错误 */
}
.toast-box.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
</style>
</head>
<body>

<div class="wrapper">

    <!-- LEFT -->
    <div class="left-box">
        <img id="mainImage" class="main-img"
             src="../assets<?= $images[0] ?? '/images/products/placeholder_food.png' ?>">

        <div class="thumb-row">
            <?php foreach ($images as $i => $img): ?>
                <img class="thumb <?= $i == 0 ? 'active':'' ?>"
                     src="../assets<?= $img ?>}"
                     onclick="changeImage(this)">
            <?php endforeach; ?>
        </div>
    </div>

    <!-- RIGHT -->
    <div class="right-box">

        <div class="back-btn" onclick="history.back()">← Back</div>

        <div class="p-name"><?= htmlspecialchars($product["ProductName"]) ?></div>
        <div class="p-price">RM <?= number_format($product["UnitPrice"],2) ?></div>
        <div class="p-desc"><?= htmlspecialchars($product["Description"]) ?></div>

        <!-- Instructions -->
        <div class="section-title">Special Instructions</div>
        <textarea id="remarksBox" class="input-box" placeholder="E.g. No onions please"><?= 
            $existing ? htmlspecialchars($existing['Note']) : '' 
        ?></textarea>

        <!-- Pickup Time -->
        <div class="section-title">Pickup Time</div>
        <select id="pickupTime" class="input-box">
            <option value="">Choose time…</option>
            <?php foreach ($times as $t): ?>
                <option value="<?= $t['value'] ?>"
                    <?= ($existing && $existing['PickupTime'] == $t['value']) ? 'selected' : '' ?>>
                    <?= $t['label'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Bottom Bar -->
        <div class="footer-bar">
            <button class="qty-btn" onclick="updateQty(-1)">−</button>

            <div id="qtyNum" class="qty-num">
                <?= $existing ? (int)$existing['Quantity'] : 1 ?>
            </div>

            <button class="qty-btn" onclick="updateQty(1)">+</button>

            <div class="add-btn" onclick="addToCart()">
                <?= $cartItemId ? "Update Item" : "Add to Basket – RM " . number_format($product["UnitPrice"],2) ?>
            </div>
        </div>

    </div>
</div>

<!-- Toast 容器 -->
<div id="toast" class="toast-box"></div>

<script>
const IS_EDIT_MODE = <?= $isEditMode ?>;

function changeImage(elem){
    document.getElementById("mainImage").src = elem.src;
    document.querySelectorAll(".thumb").forEach(t => t.classList.remove("active"));
    elem.classList.add("active");
}

function updateQty(n){
    let num = document.getElementById("qtyNum");
    let v = parseInt(num.innerText) + n;
    if (v < 1) v = 1;
    num.innerText = v;
}

// 绿色成功弹窗 / 红色错误弹窗
function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    toast.textContent = message || '';
    toast.classList.remove('error');
    if (isError) {
        toast.classList.add('error');
    }
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
    }, 2000);
}

function addToCart(){
    const qty  = document.getElementById("qtyNum").innerText;
    const note = document.getElementById("remarksBox").value;
    const pickup = document.getElementById("pickupTime").value;

    fetch("add_to_cart.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({
            product_id: <?= (int)$productId ?>,
            qty: qty,
            remarks: note,
            pickup_time: pickup,
            cart_item_id: <?= $cartItemId ? (int)$cartItemId : "null" ?>
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showToast(IS_EDIT_MODE ? "Item updated" : "Item added to cart", false);

            setTimeout(() => {
             
                if (IS_EDIT_MODE) {
                    window.location.href = "/cart/cart.php";
                } else {
                    // 普通 Add：回到上一页（例如菜单）
                    history.back();
                }
            }, 900);

        } else {
            showToast(d.message || "Failed to add to cart", true);
        }
    })
    .catch(() => {
        showToast("Network error", true);
    });
}
</script>

</body>
</html>
