<?php
include 'configs/db.php';
include 'includes/functions.php';
include 'includes/db_cart.php';

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
                    <a href="<?php echo $stallClosed ? 'javascript:void(0)' : 'pages/menu.php?stallid=' . $stall['StallId']; ?>"
                        style="<?php echo $stallClosed ? 'pointer-events:none;opacity:0.5;' : ''; ?>">
                        Visit Stall
                    </a>
                </div>
            </div>

            <div class="horizontal-scroll">
                <?php foreach ($stallProducts as $product): ?>
                    <?php
                    $cleanedImgUrl = fixAssetUrl($product['ImageURL']);
                    $isUnavailable = ($product['IsAvailable'] == 0 || $stallClosed);
                    ?>

                    <a href="<?php echo $isUnavailable ? 'javascript:void(0)' : 'pages/product_detail.php?id=' . $product['ProductId']; ?>"
                        style="text-decoration:none;color:inherit;">
                        <div class="product-card <?php echo $isUnavailable ? 'unavailable-card' : ''; ?>"
                            data-product-id="<?php echo $product['ProductId']; ?>"
                            data-category-id="<?php echo htmlspecialchars($product['CategoryId']); ?>">

                            <div class="product-image">
                                <img src="<?php echo $cleanedImgUrl; ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
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
$catSql = "SELECT DISTINCT c.* FROM categories c
           JOIN products p ON c.CategoryId = p.CategoryId
           ORDER BY c.CategoryId ASC";
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
            <a href="order/notification.php" id="navNotificationLink" class="nav-notification-link">
                Notification
                <span id="notifDot" class="notif-dot" style="
        display:none; 
        width:10px; 
        height:10px; 
        background:#e74c3c; 
        border-radius:50%;
        margin-left:6px;
    "></span>
            </a>


            <?php
            // DYNAMIC LINK BASED ON ROLE
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                echo '<a href="pages/admin_dashboard.php">Dashboard</a>';
            } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'vendor') {
                echo '<a href="pages/vendor_dashboard.php">Dashboard</a>';
            } else {
                echo '<a href="pages/order_history.php">Activity</a>';
            }
            ?>

            <a href="pages/profile.php">Profile</a>
        </nav>

        <form class="search-bar-container" id="searchForm">
            <div class="search-input-group">
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
                $imgUrl = $product['ImageURL'];
                
                if (strpos($imgUrl, '../') === 0) {
                    $cleanedImgUrl = str_replace('../', '', $imgUrl);
                } else {
                    // If it doesn't start with '../', keep the original path
                    $cleanedImgUrl = $imgUrl;
                }
                $isUnavailable = ($product['IsAvailable'] == 0 || $product['StallOpen'] == 0);
                ?>

                <a href="<?php echo $isUnavailable ? 'javascript:void(0)' : 'pages/product_detail.php?id=' . $product['ProductId']; ?>"
                    style="text-decoration:none;color:inherit;">
                    <div class="product-card <?php echo $isUnavailable ? 'unavailable-card' : ''; ?>"
                        data-product-id="<?php echo $product['ProductId']; ?>"
                        data-category-id="<?php echo htmlspecialchars($product['CategoryId']); ?>">

                        <div class="product-image" style="position:relative;">
                            <img src="<?php echo $cleanedImgUrl; ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
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
                        <a href="<?php echo $stallClosed ? 'javascript:void(0)' : 'pages/menu.php?stallid=' . $stall['StallId']; ?>"
                            style="<?php echo $stallClosed ? 'pointer-events:none;opacity:0.5;' : ''; ?>">
                            Visit Stall
                        </a>
                    </div>
                </div>

                <div class="horizontal-scroll">
                    <?php foreach ($stallProducts as $product): ?>
                        <?php
                $imgUrl = $product['ImageURL'];
                
                if (strpos($imgUrl, '../') === 0) {
                    $cleanedImgUrl = str_replace('../', '', $imgUrl);
                } else {
                    // If it doesn't start with '../', keep the original path
                    $cleanedImgUrl = $imgUrl;
                }
                        $isUnavailable = ($product['IsAvailable'] == 0 || $stallClosed);
                        ?>

                        <a href="<?php echo $isUnavailable ? 'javascript:void(0)' : 'pages/product_detail.php?id=' . $product['ProductId']; ?>"
                            style="text-decoration:none;color:inherit;">
                            <div class="product-card <?php echo $isUnavailable ? 'unavailable-card' : ''; ?>"
                                data-product-id="<?php echo $product['ProductId']; ?>"
                                data-category-id="<?php echo htmlspecialchars($product['CategoryId']); ?>"
                                style="min-width:260px;width:260px;">

                                <div class="product-image" style="height:140px;position:relative;">
                                    <img src="<?php echo $cleanedImgUrl; ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
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
<script>
    setInterval(() => {
        $.get('/U-Order/order/get_notifications.php', res => {
            if (res.success && res.count > 0) {
                $("#notifDot").show();
            } else {
                $("#notifDot").hide();
            }
        });
    }, 4000);
</script>

</body>

</html>