<?php
require __DIR__ . '/../configs/db.php';
require __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: /auth/login.php");
    exit;
}
$userId = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Orders</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script defer src="/assets/js/order_status.js"></script>
</head>
<body>

<div class="page-wrapper">

    <h2>Your Orders</h2>

    <!-- 最新订单 -->
    <section id="latest-order-section" class="card-section">
        <h3>Current Order</h3>
        <div id="latestOrderBox" class="order-box empty">
            <p>No active orders.</p>
        </div>
    </section>

    <!-- 历史记录 -->
    <section id="history-section" class="card-section">
        <h3>Order History</h3>
        <div id="historyList"></div>
    </section>

</div>

</body>
</html>
