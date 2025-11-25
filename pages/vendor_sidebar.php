<?php
// vendor_sidebar.php
require_once '../configs/db.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$userId = (int)$_SESSION['user_id'];

// 找到 vendor 的 stall
$sql = "SELECT StallName, LogoUrl FROM stalls WHERE StaffId = ? LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$stall = $stmt->fetch(PDO::FETCH_OBJ);

$logo = $stall->LogoUrl ?? "/images/default-stall.png";
$stallName = $stall->StallName ?? "My Stall";
?>

<link rel="stylesheet" href="vendor_layout.css">

<div class="sidebar">

    <div class="sidebar-header">
        <img src="<?php echo $logo; ?>" class="sidebar-logo">
        <h3><?php echo htmlspecialchars($stallName); ?></h3>
    </div>

    <ul class="sidebar-menu">
        <li><a href="vendor_orders.php">Orders</a></li>
        <li><a href="vendor_sales_summary.php">Sales Summary</a></li>
        <li><a href="vendor_menu.php">Menu</a></li>
        <li><a href="vendor_add_product.php">Add Product</a></li>
        <li><a href="vendor_profile.php">Profile</a></li>
        <li><a href="vendor_logout.php">Logout</a></li>
    </ul>

</div>
