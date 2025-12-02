<?php
include 'configs/db.php';
include 'includes/functions.php';
include 'includes/db_cart.php';

/* ============================================================
   FIX IMAGE PATH
============================================================ */
function fixAssetUrl($path) {
    if (!$path) return "https://via.placeholder.com/300?text=No+Image";
    if (strpos($path, 'http') === 0) return $path;
    $clean = ltrim($path, '/');
    return "assets/" . $clean;
}

/* ============================================================
   AJAX REQUEST HANDLER
============================================================ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'search') {
    header('Content-Type: application/json');
    
    $search = $_GET['search'] ?? '';
    $categoryId = $_GET['category'] ?? 'all';
    
    $response = [
        'success' => true,
        'search' => $search,
        'category' => $categoryId,
        'html' => '',
        'hasResults' => false
    ];
    
    // Get stalls
    $stallSql = "SELECT * FROM stalls ORDER BY StallName ASC";
    $stallStmt = $db->prepare($stallSql);
    $stallStmt->execute();
    $stalls = $stallStmt->fetchAll(PDO::FETCH_ASSOC);
    
    ob_start();
    
    $totalResultCount = 0;
    
    foreach ($stalls as $stall) {
        // Get products for this stall
        $sql = "SELECT p.*, p.CategoryId,
                (SELECT ImageURL FROM productimages pi 
                 WHERE pi.ProductId = p.ProductId LIMIT 1) AS ImageURL
                FROM products p
                WHERE p.StallId = :sid";
        
        $params = [':sid' => $stall['StallId']];
        
        if (!empty($search)) {
            $sql .= " AND (p.ProductName LIKE :search OR p.Description LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if ($categoryId !== 'all') {
            $sql .= " AND p.CategoryId = :catid";
            $params[':catid'] = $categoryId;
        }
        
        $sql .= " ORDER BY p.ProductName ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $stallProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($stallProducts)) continue;
        
        $totalResultCount += count($stallProducts);
        $stallClosed = ($stall['IsAvailable'] == 0);
        ?>
        
        <section class="section-stalls <?php echo $stallClosed ? 'stall-closed' : ''; ?>"
                 data-stall-id="<?php echo $stall['StallId']; ?>">
            <div class="section-header">
                <h3>
                    <?php echo htmlspecialchars($stall['StallName']); ?>
                    <?php if ($stallClosed): ?>
                        <span class="closed-tag">(Closed)</span>
                    <?php endif; ?>
                </h3>
                <div class="see-all">
                    <a href="<?php echo $stallClosed ? 'javascript:void(0)' : 'pages/menu.php?stallid='.$stall['StallId']; ?>"
                       style="<?php echo $stallClosed ? 'pointer-events:none;opacity:0.5;' : ''; ?>">
                       Visit Stall
                    </a>
                </div>
            </div>
            
            <div class="horizontal-scroll">
                <?php foreach ($stallProducts as $product): ?>
                <?php
                    $imgUrl = fixAssetUrl($product['ImageURL']);
                    $isUnavailable = ($product['IsAvailable'] == 0 || $stallClosed);
                ?>
                
                <a href="<?php echo $isUnavailable ? 'javascript:void(0)' : 'pages/product_detail.php?id='.$product['ProductId']; ?>"
                   style="text-decoration:none;color:inherit;">
                    <div class="product-card <?php echo $isUnavailable ? 'unavailable-card' : ''; ?>"
                         data-product-id="<?php echo $product['ProductId']; ?>"
                         data-category-id="<?php echo htmlspecialchars($product['CategoryId']); ?>">
                        
                        <div class="product-image">
                            <img src="<?php echo $imgUrl; ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                            <div class="unavailable-layer <?php echo $isUnavailable ? '' : 'hidden-unavailable'; ?>">
                                <img src="assets/images/unavailable.png" alt="Unavailable">
                            </div>
                        </div>
                        
                        <div class="product-content">
                            <h4 class="product-title">
                                <?php echo htmlspecialchars($product['ProductName']); ?>
                            </h4>
                            <div class="product-footer">
                                <span class="product-price">
                                    RM <?php echo number_format($product['UnitPrice'], 2); ?>
                                </span>
                                <div class="add-btn">
                                     <i class="fas fa-plus"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        
        <?php
    }
    
    $response['html'] = ob_get_clean();
    $response['hasResults'] = $totalResultCount > 0;
    
    echo json_encode($response);
    exit;
}

/* ============================================================
   NORMAL PAGE LOAD
============================================================ */
$search = $_GET['search'] ?? '';

/* ============================================================
   CATEGORIES
============================================================ */
$catSql = "SELECT * FROM categories ORDER BY CategoryId ASC";
$catStmt = $db->prepare($catSql);
$catStmt->execute();
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   TOP SELLERS (SHOW UNAVAILABLE + CLOSED)
============================================================ */
$topSql = "SELECT p.*, p.CategoryId, s.StallName, s.IsAvailable AS StallOpen,
           (SELECT ImageURL FROM productimages pi WHERE pi.ProductId = p.ProductId LIMIT 1) AS ImageURL,
           (SELECT COUNT(*) FROM cartitems ci WHERE ci.ProductId = p.ProductId) AS SalesCount
           FROM products p
           JOIN stalls s ON p.StallId = s.StallId
           GROUP BY p.ProductId
           ORDER BY SalesCount DESC
           LIMIT 3";

$topStmt = $db->prepare($topSql);

try {
    $topStmt->execute();
    $topSellers = $topStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $topSellers = [];
}

/* ============================================================
   STALLS
============================================================ */
$stallSql = "SELECT * FROM stalls ORDER BY StallName ASC";
$stallStmt = $db->prepare($stallSql);
$stallStmt->execute();
$stalls = $stallStmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   FETCH Products Under Stall
============================================================ */
function getStallProducts($db, $stallId, $searchTerm = '')
{
    $sql = "SELECT p.*, p.CategoryId,
            (SELECT ImageURL FROM productimages pi 
             WHERE pi.ProductId = p.ProductId LIMIT 1) AS ImageURL
            FROM products p
            WHERE p.StallId = :sid";

    $params = [':sid' => $stallId];

    if (!empty($searchTerm)) {
        $sql .= " AND (p.ProductName LIKE :search OR p.Description LIKE :search)";
        $params[':search'] = "%$searchTerm%";
    }

    $sql .= " ORDER BY p.ProductName ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ============================================================
   USER INFO
============================================================ */
$userName = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Student';
$userInitials = isset($_SESSION['name']) ? urlencode($_SESSION['name']) : 'Student';

include 'includes/header.php';
?>

<!-- HEADER SECTION -->
<header class="home-header">
    <div class="header-content">
        <div class="greeting">
            <div class="profile-pic-small">
                <img src="https://ui-avatars.com/api/?name=<?php echo $userInitials; ?>&background=81A1C1&color=fff&size=128" alt="Profile">
            </div>
            <h1>Good Morning,<br><span><?php echo $userName; ?>!</span></h1>
        </div>

        <nav class="desktop-nav">
            <a href="index.php" class="active">Home</a>
            <a href="#">Notification</a>
            <a href="#">Activity</a>
            <a href="pages/profile.php">Profile</a>
        </nav>

        <form class="search-bar-container" id="searchForm">
            <div class="search-input-group">
                <i class="fas fa-search search-icon"></i>
                <input type="text" 
                       id="searchInput" 
                       name="search" 
                       placeholder="Search for food..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
</header>

<style>
/* ============================================================
   SEARCH BUTTON
============================================================ */
.search-btn {
    background: linear-gradient(135deg, #88c0d0, #8fbcbb);
    border: none;
    border-radius: 12px;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(136, 192, 208, 0.3);
}

.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(136, 192, 208, 0.5);
}

.search-btn:active {
    transform: translateY(0);
}

.search-btn i {
    color: #2e3440;
    font-size: 1.1rem;
}

/* ============================================================
   LOADING SPINNER
============================================================ */
.loading-spinner {
    display: none;
    text-align: center;
    padding: 40px;
    color: #88c0d0;
}

.loading-spinner.active {
    display: block;
}

.spinner {
    border: 4px solid rgba(136, 192, 208, 0.2);
    border-top: 4px solid #88c0d0;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* ============================================================
   UNAVAILABLE / CLOSED VISUAL
============================================================ */
.unavailable-layer {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 3;
}

.unavailable-layer img {
    width: 70%;
    opacity: 0.9;
}

.hidden-unavailable { 
    display: none !important; 
}

.unavailable-card {
    opacity: 0.55;
    filter: grayscale(0.6);
    pointer-events: none;
}

.section-stalls.stall-closed {
    opacity: 0.55;
}

.closed-tag {
    color: #bf616a;
    font-weight: bold;
}

/* ============================================================
   CATEGORY STYLE
============================================================ */
.section-categories {
    margin-top: 18px;
}

.section-categories .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.section-categories .section-header h3 {
    font-size: 1.2rem;
    font-weight: 700;
}

.cat-scroll {
    display: flex;
    gap: 22px;
    padding: 10px 6px 20px 6px;
    overflow-x: auto;
}

.cat-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    min-width: 80px;
    cursor: pointer;
    user-select: none;
}

.cat-box {
    width: 68px;
    height: 68px;
    border-radius: 22px;
    background: #ffffff;
    box-shadow: 0 4px 14px rgba(0,0,0,0.05);
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 26px;
    color: #374151;
    transition: 0.25s ease;
}

.cat-card span {
    font-size: 0.9rem;
    font-weight: 500;
    color: #374151;
}

.cat-card.active .cat-box {
    background: #e0e7ff;
    color: #3b82f6;
    transform: translateY(-3px);
    box-shadow: 0 4px 14px rgba(102,102,255,0.25);
}

/* ============================================================
   NO RESULTS UI
============================================================ */
.no-results-box {
    margin: 40px auto;
    padding: 35px 20px;
    background: rgba(46, 52, 64, 0.3);
    text-align: center;
    border-radius: 18px;
    width: 80%;
    backdrop-filter: blur(6px);
    border: 1px solid rgba(129, 161, 193, 0.2);
}

.no-results-box .emoji {
    font-size: 2.8rem;
    margin-bottom: 10px;
}

.no-results-box h2 {
    margin: 10px 0 5px;
    font-size: 1.4rem;
    font-weight: 700;
    color: #d8dee9;
}

.no-results-box p {
    margin-bottom: 15px;
    color: #81a1c1;
}

.no-results-box .clear-btn {
    display: inline-block;
    padding: 10px 24px;
    background: linear-gradient(135deg, #88c0d0, #8fbcbb);
    color: #2e3440;
    border-radius: 999px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(136, 192, 208, 0.3);
}

.no-results-box .clear-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(136, 192, 208, 0.5);
}
/* === FINAL PATCH: Fix AJAX card width stretching === */
.horizontal-scroll .product-card {
    width: 260px !important;
    min-width: 260px !important;
    max-width: 260px !important;
    flex: 0 0 260px !important;
}

</style>

<?php if (empty($search)): ?>
<!-- ==========================================================
     CATEGORIES
========================================================== -->
<section class="section-categories">
    <div class="section-header">
        <h3>Categories</h3>
    </div>

    <div class="cat-scroll">
        <!-- All -->
        <div class="cat-card active" data-category-id="all">
            <div class="cat-box">
                <i class="fas fa-border-all"></i>
            </div>
            <span>All</span>
        </div>

        <?php foreach ($categories as $cat): ?>
            <div class="cat-card" data-category-id="<?php echo htmlspecialchars($cat['CategoryId']); ?>">
                <div class="cat-box">
                    <?php if (!empty($cat['CategoryLogo'])): ?>
                        <img src="<?php echo htmlspecialchars($cat['CategoryLogo']); ?>"
                             style="width:26px;height:26px;object-fit:contain;">
                    <?php else: ?>
                        <i class="fas fa-utensils"></i>
                    <?php endif; ?>
                </div>
                <span><?php echo htmlspecialchars($cat['CategoryName']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ==========================================================
     TOP SELLERS
========================================================== -->
<section class="section-products">
    <div class="section-header">
        <h3>Top Sellers 🔥</h3>
    </div>

    <div class="product-grid">
        <?php foreach ($topSellers as $product): ?>
        <?php
            $imgUrl = fixAssetUrl($product['ImageURL']);
            $isUnavailable = ($product['IsAvailable'] == 0 || $product['StallOpen'] == 0);
        ?>
        <a href="<?php echo $isUnavailable ? 'javascript:void(0)' : 'pages/product_detail.php?id='.$product['ProductId']; ?>"
           style="text-decoration:none;color:inherit;">
            <div class="product-card <?php echo $isUnavailable ? 'unavailable-card' : ''; ?>"
                 data-product-id="<?php echo $product['ProductId']; ?>"
                 data-category-id="<?php echo htmlspecialchars($product['CategoryId']); ?>">

                <div class="product-image" style="position:relative;">
                    <img src="<?php echo $imgUrl; ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                    <div class="unavailable-layer <?php echo $isUnavailable ? '' : 'hidden-unavailable'; ?>">
                        <img src="assets/images/unavailable.png" alt="Unavailable">
                    </div>
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
<?php endif; ?>

<!-- ==========================================================
     LOADING SPINNER
========================================================== -->
<div class="loading-spinner" id="loadingSpinner">
    <div class="spinner"></div>
    <p>Loading...</p>
</div>

<!-- ==========================================================
     STALL CONTAINER
========================================================== -->
<div id="stallsContainer">
<?php
$totalResultCount = 0;
foreach ($stalls as $stall) {
    $list = getStallProducts($db, $stall['StallId'], $search);
    $totalResultCount += count($list);
}

if (!empty($search) && $totalResultCount == 0): ?>
    <div class="no-results-box">
        <div class="emoji">🥺🔍</div>
        <h2>No results found</h2>
        <p>Try another keyword?</p>
        <a href="index.php" class="clear-btn">Clear Search</a>
    </div>
<?php else: ?>
    <?php foreach ($stalls as $stall): ?>
    <?php
        $stallProducts = getStallProducts($db, $stall['StallId'], $search);
        if (empty($stallProducts)) continue;
        $stallClosed = ($stall['IsAvailable'] == 0);
    ?>
    <section class="section-stalls <?php echo $stallClosed ? 'stall-closed' : ''; ?>"
             data-stall-id="<?php echo $stall['StallId']; ?>">

        <div class="section-header">
            <h3>
                <?php echo htmlspecialchars($stall['StallName']); ?>
                <?php if ($stallClosed): ?>
                    <span class="closed-tag">(Closed)</span>
                <?php endif; ?>
            </h3>

            <div class="see-all">
                <a href="<?php echo $stallClosed ? 'javascript:void(0)' : 'pages/menu.php?stallid='.$stall['StallId']; ?>"
                   style="<?php echo $stallClosed ? 'pointer-events:none;opacity:0.5;' : ''; ?>">
                   Visit Stall
                </a>
            </div>
        </div>

        <div class="horizontal-scroll">
            <?php foreach ($stallProducts as $product): ?>
            <?php
                $imgUrl = fixAssetUrl($product['ImageURL']);
                $isUnavailable = ($product['IsAvailable'] == 0 || $stallClosed);
            ?>

            <a href="<?php echo $isUnavailable ? 'javascript:void(0)' : 'pages/product_detail.php?id='.$product['ProductId']; ?>"
               style="text-decoration:none;color:inherit;">
                <div class="product-card <?php echo $isUnavailable ? 'unavailable-card' : ''; ?>"
                     data-product-id="<?php echo $product['ProductId']; ?>"
                     data-category-id="<?php echo htmlspecialchars($product['CategoryId']); ?>"
                     style="min-width:260px;width:260px;">

                    <div class="product-image" style="height:140px;position:relative;">
                        <img src="<?php echo $imgUrl; ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                        <div class="unavailable-layer <?php echo $isUnavailable ? '' : 'hidden-unavailable'; ?>">
                            <img src="assets/images/unavailable.png" alt="Unavailable">
                        </div>
                    </div>

                    <div class="product-content">
                        <h4 class="product-title" style="font-size:1rem;">
                            <?php echo htmlspecialchars($product['ProductName']); ?>
                        </h4>

                        <div class="product-footer" style="margin-top:10px;">
                            <span class="product-price" style="font-size:1rem;">
                                RM <?php echo number_format($product['UnitPrice'], 2); ?>
                            </span>
                            <div class="add-btn" style="width:30px;height:30px;font-size:0.8rem;">
                                 <i class="fas fa-plus"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<!-- SPACING -->
<div style="height:80px;"></div>

<!-- FOOTER -->
<?php include 'includes/footer.php'; ?>

<!-- POLLING SCRIPT -->
<script src="assets/js/index.js"></script>

</body>
</html>