<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// 1. Get Selected Items from POST
$selectedItems = $_POST['selected_items'] ?? null;

if (empty($selectedItems)) {
    flash('error');
    // Correct redirect to the cart folder
    header("Location: ../cart/cart.php");
    exit;
}

// Ensure it's an array and sanitize IDs
if (!is_array($selectedItems)) {
    $selectedItems = explode(',', $selectedItems);
}
$selectedIds = array_map('intval', $selectedItems);
// Filter out 0 or invalid IDs
$selectedIds = array_filter($selectedIds);

if (empty($selectedIds)) {
    flash('error', 'Invalid selection data.');
    header("Location: ../cart/cart.php");
    exit;
}

$selectedIdsStr = implode(',', $selectedIds);

// 2. Recalculate total ONLY for selected items
// Added: AND ci.CartItemId IN ($selectedIdsStr)
$sql = "SELECT SUM(p.UnitPrice * ci.Quantity) as Total 
        FROM carts c
        JOIN cartitems ci ON c.CartId = ci.CartId
        JOIN products p ON ci.ProductId = p.ProductId
        WHERE c.UserId = :uid 
          AND ci.CartItemId IN ($selectedIdsStr)";

$stmt = $db->prepare($sql);
$stmt->execute([':uid' => $userId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalAmount = $result['Total'] ?? 0;

if ($totalAmount == 0) {
    flash('error', 'Selected items have a total of 0 or represent an invalid selection.');
    header("Location: ../cart/cart.php");
    exit;
}

$sqlDetails = "SELECT p.ProductName, p.UnitPrice, ci.Quantity 
               FROM cartitems ci
               JOIN products p ON ci.ProductId = p.ProductId
               WHERE ci.CartItemId IN ($selectedIdsStr)";
$stmtDetails = $db->query($sqlDetails);
$items = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);
include '../includes/header.php';
?>

<link rel="stylesheet" href="/U-Order/assets/css/payment.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/U-Order/assets/css/payment.css">

<div class="checkout-wrapper">
    <div class="checkout-container">

        <div class="summary-section">
            <div class="summary-header">
                <h3><i class="fa-solid fa-receipt"></i> Order Summary</h3>
                <span class="item-count"><?php echo count($items); ?> items</span>
            </div>

            <div class="order-items-scroll">
                <?php foreach ($items as $item): ?>
                    <div class="summary-item">
                        <div class="item-info">
                            <span class="item-qty">x<?php echo $item['Quantity']; ?></span>
                            <span class="item-name"><?php echo htmlspecialchars($item['ProductName']); ?></span>
                        </div>
                        <span class="item-price">RM <?php echo number_format($item['UnitPrice'] * $item['Quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-total">
                <span>Total to pay</span>
                <span class="total-amount">RM <?php echo number_format($totalAmount, 2); ?></span>
            </div>
        </div>

        <div class="payment-section">
            <div class="secure-badge">
                <i class="fa-solid fa-lock"></i> Secure SSL Checkout
            </div>

            <h2 class="payment-title">Payment Method</h2>

            <form id="paymentForm" action="process_payment.php" method="POST">
                <?php foreach ($selectedIds as $id): ?>
                    <input type="hidden" name="selected_items[]" value="<?= htmlspecialchars($id) ?>">
                <?php endforeach; ?>
                <input type="hidden" name="payment_method" id="selectedMethod" value="stripe">

                <div class="method-grid">
                    <div class="method-option active" data-method="stripe">
                        <div class="icon-circle">
                            <i class="fa-regular fa-credit-card"></i>
                        </div>
                        <span class="method-name">Card</span>
                        <div class="check-mark"><i class="fa-solid fa-check"></i></div>
                    </div>

                    <div class="method-option" data-method="ewallet">
                        <div class="icon-circle">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <span class="method-name">E-Wallet</span>
                        <div class="check-mark"><i class="fa-solid fa-check"></i></div>
                    </div>

                    <div class="method-option" data-method="cash">
                        <div class="icon-circle">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                        <span class="method-name">Cash</span>
                        <div class="check-mark"><i class="fa-solid fa-check"></i></div>
                    </div>
                </div>

                <div class="payment-details-window">
                    <div id="view-stripe" class="method-view active">
                        <div class="wallet-placeholder">
                            <div class="wallet-icons">
                                <i class="fa-brands fa-stripe"></i>
                            </div>
                            <p>You will be redirected to Stripe payment gateway.</p>
                        </div>
                    </div>

                    <div id="view-ewallet" class="method-view">
                        <div class="wallet-placeholder">
                            <div class="wallet-icons">
                                <i class="fa-brands fa-google-pay"></i>
                                <span style="font-weight:800; font-size:1.2rem;">TNG</span>
                                <span style="font-weight:800; font-size:1.2rem; color:#00b140;">Grab</span>
                            </div>
                            <p>You will be redirected to the secure gateway login.</p>
                        </div>
                    </div>

                    <div id="view-cash" class="method-view">
                        <div class="cash-placeholder">
                            <div class="cash-icon-lg">
                                <i class="fa-solid fa-store"></i>
                            </div>
                            <p>Pay at the counter when you pick up your order.</p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="pay-btn" id="submitButton">
                    <span class="btn-text">Confirm Payment</span>
                    <span class="btn-amount">RM <?php echo number_format($totalAmount, 2); ?></span>
                    <div class="btn-spinner"><i class="fa-solid fa-circle-notch fa-spin"></i></div>
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script src="/U-Order/assets/js/payment.js"></script>
</body>
</html>