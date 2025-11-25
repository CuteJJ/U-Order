</div> <!-- End Main Wrapper -->

    <?php if (!isset($hideNav) || !$hideNav): ?>
        <!-- ONLY SHOW CART & NAV IF NOT AUTH PAGE -->

        <!-- Floating Cart Button -->
        <div class="floating-cart-btn" id="cartToggle">
            <i class="fas fa-shopping-basket"></i>
            <span class="cart-badge">2</span>
        </div>

        <!-- Slide-out Cart -->
        <div class="cart-sidebar" id="cartSidebar">
            <div class="cart-header">
                <h3>Your Cart</h3>
                <span class="close-cart" id="closeCart">&times;</span>
            </div>
            <div class="cart-body">
                <!-- Cart Items Demo -->
                <div class="cart-item">
                    <div>
                        <strong>Chicken Rice</strong><br>
                        <small>RM 10.90</small>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span>x1</span>
                        <i class="fas fa-trash" ></i>
                    </div>
                </div>
            </div>
            <div class="cart-footer">
                <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-weight:bold;">
                    <span>Total</span>
                    <span>RM 10.90</span>
                </div>
                <button class="checkout-btn" style="width:100%;">Checkout</button>
            </div>
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

    <script src="/U-Order/assets/js/app.js"></script>
</body>
</html>