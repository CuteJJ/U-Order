<?php
include '../configs/db.php';
include '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Recalculate total for security display
$sql = "SELECT SUM(p.UnitPrice * ci.Quantity) as Total 
        FROM carts c
        JOIN cartitems ci ON c.CartId = ci.CartId
        JOIN products p ON ci.ProductId = p.ProductId
        WHERE c.UserId = :uid";
$stmt = $db->prepare($sql);
$stmt->execute([':uid' => $userId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalAmount = $result['Total'] ?? 0;

if ($totalAmount == 0) {
    flash('error', 'Your cart is empty.');
    header("Location: cart.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Payment</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <!-- Stripe JS Library -->
    <script src="https://js.stripe.com/v3/"></script>
    
    <style>
        body { background-color: #f4f7f6; }
        .payment-container { max-width: 550px; margin: 50px auto; background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .payment-header { background: #6772e5; color: white; padding: 20px; text-align: center; } /* Stripe Purple */
        .payment-header h3 { margin: 0; font-weight: 500; }
        
        .payment-body { padding: 30px; }
        .amount-display { text-align: center; font-size: 2.2em; font-weight: bold; color: #333; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        
        /* Payment Method Tabs */
        .payment-methods { display: flex; gap: 10px; margin-bottom: 25px; }
        .method-card { flex: 1; border: 2px solid #eee; border-radius: 8px; padding: 15px 5px; text-align: center; cursor: pointer; transition: all 0.2s; color: #555; }
        .method-card:hover { border-color: #6772e5; background: #f6f9fc; }
        .method-card.active { border-color: #6772e5; background: #eef2ff; color: #6772e5; font-weight: bold; }
        .method-icon { font-size: 24px; display: block; margin-bottom: 5px; }
        
        /* Stripe Element Container */
        .StripeElement {
            box-sizing: border-box;
            height: 40px;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: white;
            box-shadow: 0 1px 3px 0 #e6ebf1;
            -webkit-transition: box-shadow 150ms ease;
            transition: box-shadow 150ms ease;
            margin-bottom: 15px;
        }
        .StripeElement--focus { box-shadow: 0 1px 3px 0 #cfd7df; }
        .StripeElement--invalid { border-color: #fa755a; }
        .StripeElement--webkit-autofill { background-color: #fefde5 !important; }

        /* Info Boxes */
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 0.9em; color: #666; margin-bottom: 20px; display: none; border-left: 4px solid #ccc; }
        
        .btn-pay { width: 100%; background: #6772e5; color: white; padding: 12px; border: none; border-radius: 5px; font-size: 1.1em; cursor: pointer; font-weight: bold; transition: background 0.3s; }
        .btn-pay:hover { background: #5469d4; }

        /* Loading Overlay */
        .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.9); z-index: 1000; justify-content: center; align-items: center; flex-direction: column; }
        .spinner { width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid #6772e5; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 15px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="payment-container">
    <div class="payment-header">
        <h3>Secure Checkout</h3>
    </div>
    <div class="payment-body">
        <div class="amount-display">
            <small style="font-size: 0.4em; vertical-align: middle;">RM</small> <?php echo number_format($totalAmount, 2); ?>
        </div>

        <form id="paymentForm" action="process_payment.php" method="POST">
            <label style="font-weight:bold; display:block; margin-bottom:10px;">Select Payment Method</label>
            
            <div class="payment-methods">
                <!-- Stripe Option -->
                <div class="method-card active" onclick="selectMethod('stripe')">
                    <span class="method-icon">💳</span>
                    Card
                </div>
                <!-- E-Wallet Option -->
                <div class="method-card" onclick="selectMethod('ewallet')">
                    <span class="method-icon">📱</span>
                    E-Wallet
                </div>
                <!-- Cash Option -->
                <div class="method-card" onclick="selectMethod('cash')">
                    <span class="method-icon">💵</span>
                    Cash
                </div>
            </div>
            
            <input type="hidden" name="payment_method" id="selectedMethod" value="stripe">
            
            <!-- STRIPE Section -->
            <div id="section-stripe">
                <label style="margin-bottom:5px; display:block; color:#666;">Credit or Debit Card</label>
                <div id="card-element">
                    <!-- A Stripe Element will be inserted here. -->
                </div>
                <div id="card-errors" role="alert" style="color: #fa755a; margin-top: 5px; font-size: 0.9em;"></div>
                <p style="font-size:0.8em; color:#999; margin-top:10px;">
                    TEST MODE: Use <strong style="color:#333">4242 4242 4242 4242</strong> for the card number.
                </p>
            </div>

            <!-- E-WALLET Section -->
            <div id="section-ewallet" class="info-box">
                <strong>📲 E-Wallet Payment</strong><br>
                You will be redirected to the Touch 'n Go / GrabPay simulator to complete your payment.
            </div>

            <!-- CASH Section -->
            <div id="section-cash" class="info-box" style="border-left-color: #28a745;">
                <strong>💵 Pay on Pickup</strong><br>
                Please pay cash at the counter when you collect your food. <br>
                <small>Your order status will be 'Pending' until payment is received.</small>
            </div>

            <button type="submit" class="btn-pay" id="submitButton">Pay RM <?php echo number_format($totalAmount, 2); ?></button>
        </form>
    </div>
</div>

<!-- Processing Overlay -->
<div class="overlay" id="loadingOverlay">
    <div class="spinner"></div>
    <h3 id="loadingText">Processing Payment...</h3>
    <p>Please do not close this window.</p>
</div>

<script>
    // 1. Initialize Stripe
    // Replace this with your own Publishable Key if you have one. 
    // This is a generic test key provided by Stripe docs for testing.
    var stripe = Stripe('pk_test_TYooMQauvdEDq54NiTphI7jx'); 
    var elements = stripe.elements();

    // Style the Stripe Element
    var style = {
        base: {
            color: '#32325d',
            fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': { color: '#aab7c4' }
        },
        invalid: { color: '#fa755a', iconColor: '#fa755a' }
    };

    // Create and mount the card element
    var card = elements.create('card', {style: style});
    card.mount('#card-element');

    // Handle real-time validation errors
    card.on('change', function(event) {
        var displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    // 2. Payment Method Selection Logic
    function selectMethod(method) {
        // Visual updates
        document.querySelectorAll('.method-card').forEach(el => el.classList.remove('active'));
        event.currentTarget.classList.add('active');
        document.getElementById('selectedMethod').value = method;
        
        // Toggle Sections
        document.getElementById('section-stripe').style.display = (method === 'stripe') ? 'block' : 'none';
        document.getElementById('section-ewallet').style.display = (method === 'ewallet') ? 'block' : 'none';
        document.getElementById('section-cash').style.display = (method === 'cash') ? 'block' : 'none';

        // Update Button Text
        const btn = document.getElementById('submitButton');
        if (method === 'cash') {
            btn.innerText = "Place Order (Pay Cash)";
            btn.style.backgroundColor = "#28a745"; // Green for Cash
        } else {
            btn.innerText = "Pay RM <?php echo number_format($totalAmount, 2); ?>";
            btn.style.backgroundColor = "#6772e5"; // Stripe Purple
        }
    }

    // 3. Form Submission Logic
    var form = document.getElementById('paymentForm');
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        var method = document.getElementById('selectedMethod').value;

        if (method === 'stripe') {
            // -- STRIPE FLOW --
            document.getElementById('loadingOverlay').style.display = 'flex';
            document.getElementById('loadingText').innerText = "Contacting Stripe...";

            stripe.createToken(card).then(function(result) {
                if (result.error) {
                    // Inform the user if there was an error.
                    var errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                    document.getElementById('loadingOverlay').style.display = 'none';
                } else {
                    // Send the token to your server.
                    stripeTokenHandler(result.token);
                }
            });
        } else {
            // -- CASH / EWALLET FLOW --
            document.getElementById('loadingOverlay').style.display = 'flex';
            if(method === 'ewallet') document.getElementById('loadingText').innerText = "Connecting to E-Wallet...";
            if(method === 'cash') document.getElementById('loadingText').innerText = "Placing Order...";
            
            // Simulate delay for realism
            setTimeout(() => {
                form.submit();
            }, 1500);
        }
    });

    // Submit the token to the server
    function stripeTokenHandler(token) {
        var form = document.getElementById('paymentForm');
        var hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'stripeToken');
        hiddenInput.setAttribute('value', token.id);
        form.appendChild(hiddenInput);
        
        // Submit the form
        form.submit();
    }
</script>

</body>
</html>