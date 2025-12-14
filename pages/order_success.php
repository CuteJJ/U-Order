<?php
// order_success.php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$paymentId = $_GET['payment_id'] ?? 'Unknown';

flash('success', 'Payment Successful!');

include '../includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/U-Order/assets/css/order_success.css">

<div class="success-wrapper">
    <div class="success-card">
        <div class="icon-container">
            <div class="success-circle-border"></div>
            <div class="success-circle">
                <i class="fa-solid fa-check"></i>
            </div>
        </div>

        <h1 class="title">Payment Successful!</h1>
        <p class="subtitle">Your transaction has been completed.</p>

        <div class="order-details-box">
            <div class="detail-row">
                <span>Transaction ID</span>
                <strong>#<?php echo htmlspecialchars($paymentId); ?></strong>
            </div>
            <div class="detail-row">
                <span>Status</span>
                <span class="status-badge">Paid</span>
            </div>
            <div class="divider"></div>
            <p class="info-text">
                Your order has been sent to the stall.<br>
                Please wait for your number to be called.
            </p>
        </div>

        <div class="action-buttons">
            <a href="../order/notification.php" class="btn btn-primary">
                View My Orders
            </a>
            <a href="../index.php" class="btn btn-secondary">
                Back to Home
            </a>
        </div>

        <div class="redirect-timer">
            Redirecting to orders in <span id="countdown">10</span>s...
        </div>
    </div>
</div>

<script>
    // Auto Redirect Logic
    let seconds = 10;
    const countdownEl = document.getElementById('countdown');
    
    const timer = setInterval(() => {
        seconds--;
        countdownEl.textContent = seconds;
        
        if (seconds <= 0) {
            clearInterval(timer);
            window.location.href = '../order/notification.php';
        }
    }, 1000);
</script>

</body>
</html>