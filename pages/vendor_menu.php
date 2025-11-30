<?php
require_once '../configs/db.php';

// 必须 vendor 登录
if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

?>

<?php include 'vendor_sidebar.php'; ?>

<div class="main-area">

    <?php include 'vendor_topbar.php'; ?>

    <div class="page-content">

        <link rel="stylesheet" href="../assets/css/vendor_menu.css">

        <!-- 工具栏（不会 reload，全 AJAX） -->
        <div class="toolbar">

            <input class="input" type="text" id="searchInput" placeholder="Search dish...">

            <select class="select" id="categoryFilter">
                <option value="0">All Categories</option>
                <!-- 分类 AJAX 填入 -->
            </select>

            <label class="select">
                <input type="checkbox" id="filterUnavailable">
                Show Unavailable Only
            </label>

            <label class="select">
                <input type="checkbox" id="filterLowStock">
                Low Stock (≤5)
            </label>

            <button class="select" id="btnAddProduct"
                    onclick="window.location.href='add_product.php'">
                + Add Product
            </button>

        </div>

        <!-- 产品卡容器（AJAX 填充） -->
        <div id="menu-container" class="menu-grid">
            <!-- JS 会把每个产品 append 到这里 -->
        </div>

        <!-- 分页容器 -->
        <div id="pagination" class="pagination">
            <!-- JS 动态生成页码 -->
        </div>

    </div>

    <div id="toast"></div>
</div>



<script src="../assets/js/vendor_menu.js"></script>
<script src="../assets/js/vendor_menu_filter.js"></script>

