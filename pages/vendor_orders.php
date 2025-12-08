<?php
// pages/vendor_orders.php
require_once '../configs/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<?php include "vendor_sidebar.php"; ?>

<div class="main-area">
    <?php include "vendor_topbar.php"; ?>

    <div class="page-content">
        <link rel="stylesheet" href="../assets/css/vendor_orders.css">

        <div class="filter-bar">
            <div class="filter-group">
                <label>Category:</label>
                <select id="filter-category">
                    <option value="all">All Categories</option>
                    <option value="1">Rice</option>
                    <option value="2">Drinks</option>
                    <option value="3">Dessert</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Pickup:</label>
                <select id="filter-pickup">
                    <option value="all">Any Time</option>
                    <option value="now">Now (ASAP + 30m)</option>
                    <option value="1h">Within 1 Hour</option>
                    <option value="2h">Within 2 Hours</option>
                </select>
            </div>

            <button id="batchPrepare" class="batch-btn" disabled onclick="batchUpdate('preparing')">
                Start Preparing
            </button>
            <button id="batchReady" class="batch-btn" disabled onclick="batchUpdate('ready')">
                Mark Ready
            </button>
        </div>

        <div class="orders-board">
            
            <div class="orders-column" data-status="pending">
                <div class="column-header">
                    <span class="column-title">To Prepare</span>
                    <span class="column-count" id="count-pending">0</span>
                </div>
                <div class="column-body" id="list-pending"></div>
                
                <button class="load-more-btn" id="btn-more-pending" style="display:none" 
                        onclick="loadMore('pending')">
                    Show More...
                </button>
            </div>

            <div class="orders-column" data-status="preparing">
                <div class="column-header">
                    <span class="column-title">Cooking Now</span>
                    <span class="column-count" id="count-preparing">0</span>
                </div>
                <div class="column-body" id="list-preparing"></div>
                <button class="load-more-btn" id="btn-more-preparing" style="display:none" 
                        onclick="loadMore('preparing')">
                    Show More...
                </button>
            </div>

            <div class="orders-column" data-status="ready">
                <div class="column-header">
                    <span class="column-title">Ready for Pickup</span>
                    <span class="column-count" id="count-ready">0</span>
                </div>
                <div class="column-body" id="list-ready"></div>
                <button class="load-more-btn" id="btn-more-ready" style="display:none" 
                        onclick="loadMore('ready')">
                    Show More...
                </button>
            </div>

        </div>
    </div>
</div>

<script src="../assets/js/vendor_orders.js"></script>