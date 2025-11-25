<?php
include 'configs/db.php';
include 'includes/functions.php';

// --- DATA FETCHING ---

// 1. Fetch Categories
$catSql = "SELECT * FROM categories";
$catStmt = $db->prepare($catSql);
$catStmt->execute();
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch Top Sellers (Smart Fill Logic)
$topSql = "SELECT p.*, s.StallName, 
           (SELECT ImageURL FROM productimages pi WHERE pi.ProductId = p.ProductId LIMIT 1) as ImageURL,
           COUNT(oi.OrderListId) as SalesCount
           FROM products p
           JOIN orderitems oi ON p.ProductId = oi.ProductId
           JOIN orders o ON oi.OrderId = o.OrderId
           JOIN payments pay ON o.PaymentId = pay.PaymentId
           JOIN stalls s ON p.StallId = s.StallId
           WHERE pay.Status = 'paid' AND p.IsAvailable = 1
           GROUP BY p.ProductId
           ORDER BY SalesCount DESC
           LIMIT 3";

$topStmt = $db->prepare($topSql);
$topStmt->execute();
$topSellers = $topStmt->fetchAll(PDO::FETCH_ASSOC);

// Fill gaps if less than 3
$needed = 3 - count($topSellers);
if ($needed > 0) {
    $existingIds = array_column($topSellers, 'ProductId');
    if (empty($existingIds)) $existingIds = [0];
    $placeholders = implode(',', array_fill(0, count($existingIds), '?'));

    $fallbackSql = "SELECT p.*, s.StallName, 
                    (SELECT ImageURL FROM productimages pi WHERE pi.ProductId = p.ProductId LIMIT 1) as ImageURL
                    FROM products p
                    JOIN stalls s ON p.StallId = s.StallId
                    WHERE p.IsAvailable = 1 AND p.ProductId NOT IN ($placeholders)
                    ORDER BY RAND() LIMIT $needed";

    $fallbackStmt = $db->prepare($fallbackSql);
    $fallbackStmt->execute($existingIds);
    $fillerProducts = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
    $topSellers = array_merge($topSellers, $fillerProducts);
}

// 3. Fetch Active Stalls
$stallSql = "SELECT * FROM stalls WHERE IsAvailable = 1";
$stallStmt = $db->prepare($stallSql);
$stallStmt->execute();
$stalls = $stallStmt->fetchAll(PDO::FETCH_ASSOC);

function getStallProducts($db, $stallId)
{
    $sql = "SELECT p.*, 
            (SELECT ImageURL FROM productimages pi WHERE pi.ProductId = p.ProductId LIMIT 1) as ImageURL
            FROM products p
            WHERE p.StallId = :sid AND p.IsAvailable = 1
            LIMIT 4";
    $stmt = $db->prepare($sql);
    $stmt->execute([':sid' => $stallId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$userName = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Student';
$userInitials = isset($_SESSION['name']) ? urlencode($_SESSION['name']) : 'Student';

include 'includes/header.php';
?>

<header class="home-header">
    <div class="header-content">
        <div class="greeting">
            <div class="profile-pic-small">
                <img src="https://ui-avatars.com/api/?name=<?php echo $userInitials; ?>&background=81A1C1&color=fff&size=128" alt="Profile">
            </div>
            <h1>Good Morning, <br><span><?php echo $userName; ?>!</span></h1>
        </div>
        <nav class="desktop-nav">
            <a href="index.php" class="active">Home</a>
            <a href="#">Notification</a>
            <a href="#">Activity</a>
            <a href="pages/profile.php">Profile</a>
        </nav>
        <div class="search-bar-container">
            <div class="search-input-group">
                <i class="fas fa-search search-icon"></i>
                <input type="text" placeholder="Search for food...">
            </div>
            <button class="filter-btn"><i class="fas fa-sliders-h"></i></button>
        </div>
    </div>
</header>

<!-- CATEGORIES -->
<section class="section-categories">
    <div class="section-header">
        <h3>Categories</h3>
        <div class="see-all">See All</div>
    </div>
    <div class="horizontal-scroll">
        <?php foreach ($categories as $cat): ?>
            <div class="cat-card">
                <div class="cat-icon">
                    <?php if (!empty($cat['CategoryLogo'])): ?>
                        <img src="<?php echo htmlspecialchars($cat['CategoryLogo']); ?>" style="width: 30px; height: 30px;">
                    <?php else: ?>
                        <i class="fas fa-utensils"></i>
                    <?php endif; ?>
                </div>
                <span><?php echo htmlspecialchars($cat['CategoryName']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- TOP SELLERS -->
<section class="section-products">
    <div class="section-header">
        <h3>Top Sellers 🔥</h3>
    </div>
    <div class="product-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
        <?php foreach ($topSellers as $product): ?>
            <!-- Simple Link to Detail Page -->
            <a href="pages/product_detail.php?id=<?php echo $product['ProductId']; ?>" style="text-decoration:none; color:inherit;">
                <div class="product-card">
                    <div class="product-image">
                        <?php $imgUrl = $product['ImageURL'] ? htmlspecialchars($product['ImageURL']) : 'https://via.placeholder.com/300?text=No+Image'; ?>
                        <img src="<?php echo $imgUrl; ?>" alt="Product">
                    </div>
                    <div class="product-content">
                        <h4 class="product-title"><?php echo htmlspecialchars($product['ProductName']); ?></h4>
                        <p class="product-desc"><?php echo htmlspecialchars($product['Description']); ?></p>
                        <div class="product-footer">
                            <span class="product-price">RM <?php echo number_format($product['UnitPrice'], 2); ?></span>
                            <div class="add-btn"><i class="fas fa-plus"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- STALLS -->
<?php foreach ($stalls as $stall): ?>
    <?php $stallProducts = getStallProducts($db, $stall['StallId']);
    if (empty($stallProducts)) continue; ?>
    <section class="section-stalls">
        <div class="section-header">
            <h3><?php echo htmlspecialchars($stall['StallName']); ?></h3>
            <div class="see-all">Visit Stall</div>
        </div>
        <div class="horizontal-scroll">
            <?php foreach ($stallProducts as $product): ?>
                <a href="pages/product_detail.php?id=<?php echo $product['ProductId']; ?>" style="text-decoration:none; color:inherit;">
                    <div class="stalls-product-card-wrapper">
                        <div class="product-card" style="min-width: 260px; width: 260px;">
                            <div class="product-image" style="height: 140px;">
                                <?php $imgUrl = $product['ImageURL'] ? htmlspecialchars($product['ImageURL']) : 'https://via.placeholder.com/300?text=No+Image'; ?>
                                <img src="<?php echo $imgUrl; ?>" alt="Product">
                            </div>
                            <div class="product-content">
                                <h4 class="product-title" style="font-size: 1rem;"><?php echo htmlspecialchars($product['ProductName']); ?></h4>
                                <div class="product-footer" style="margin-top: 10px;">
                                    <span class="product-price" style="font-size: 1rem;">RM <?php echo number_format($product['UnitPrice'], 2); ?></span>
                                    <div class="add-btn" style="width: 30px; height: 30px; font-size: 0.8rem;"><i class="fas fa-plus"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>

<div style="height: 80px;"></div>
<?php include 'includes/footer.php'; ?>