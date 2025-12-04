<?php
// Include Cart Logic to fetch data for the sidebar
// Use absolute path or relative based on your structure. 
// Assuming includes/ is in the same directory as footer.php
include_once __DIR__ . '/db_cart.php'; 
?>

    </div> <!-- End Main Wrapper -->

    <?php if (!isset($hideNav) || !$hideNav): ?>
        <!-- ONLY SHOW CART & NAV IF NOT AUTH PAGE -->

        <!-- Floating Cart Button -->
        <div class="floating-cart-btn" id="cartToggle">
            <i class="fas fa-shopping-basket"></i>
            <?php if ($cartCount > 0): ?>
                <span class="cart-badge"><?php echo $cartCount; ?></span>
            <?php endif; ?>
        </div>

        <!-- Slide-out Cart -->
        <div class="cart-sidebar" id="cartSidebar">
            <div class="cart-header">
                <h3>Your Cart</h3>
                <span class="close-cart" id="closeCart">&times;</span>
            </div>
            
            <?php flash(); ?> 
            <div class="cart-body">
                <?php if (empty($cartItems)): ?>
                    <div class="cart-empty-state">
                        <div style="text-align: center; padding: 20px;">
                            <i class="fas fa-shopping-cart" style="font-size: 3rem; color: var(--nord4); margin-bottom: 10px;"></i>
                            <p style="color: var(--nord3);">Your cart is empty.</p>
                            <a href="/U-Order/index.php" class="btn btn-primary" style="margin-top: 10px; display: inline-block;">Browse Menu</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item">
                            <div style="flex-grow: 1;">
                                <strong style="color: var(--nord0);"><?php echo htmlspecialchars($item['ProductName']); ?></strong><br>
                                <small style="color: var(--nord3);">
                                    <?php echo $item['Quantity']; ?> x RM <?php echo number_format($item['UnitPrice'], 2); ?>
                                </small>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span style="font-weight: bold; color: var(--nord10);">RM <?php echo number_format($item['Subtotal'], 2); ?></span>
                                <!-- Delete Action (Form for simplicity) -->
                                <form action="/U-Order/pages/cart_action.php" method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="item_id" value="<?php echo $item['CartItemId']; ?>">
                                    <button type="submit" style="background:none; border:none; cursor:pointer; color:var(--nord11); padding: 5px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($cartItems)): ?>
                <div class="cart-footer">
                    <div style="display:flex; justify-content:space-between; margin-bottom:15px; font-weight:bold; font-size: 1.1rem; color: var(--nord0);">
                        <span>Total</span>
                        <span>RM <?php echo number_format($cartTotal, 2); ?></span>
                    </div>
                    <!-- Checkout Button -->
                    <a href="/U-Order/cart/cart.php" style="text-decoration: none;">
                        <button class="checkout-btn" style="width: 100%;">
                            Checkout
                        </button>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Mobile Bottom Nav -->
        <nav class="bottom-nav">
            <a href="/U-Order/index.php" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="#" class="nav-item"><i class="fas fa-bell"></i><span>Notify</span></a>
            <a href="#" class="nav-item"><i class="fas fa-clock"></i><span>Activity</span></a>
            <a href="/U-Order/pages/profile.php" class="nav-item"><i class="fas fa-user"></i><span>Profile</span></a>
        </nav>

        <!-- Overlay -->
        <div class="cart-overlay" id="cartOverlay"></div>
    <?php endif; ?>
<div id="order-slip-wrapper">
    <?php include __DIR__ . '/../order/order_slip.php'; ?>
</div>
<script src="/U-Order/assets/js/orderslip.js"></script>

<script src="/U-Order/assets/js/app.js"></script>
</body>
</html>