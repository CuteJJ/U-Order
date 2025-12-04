<?php
include '../configs/db.php';
include '../includes/functions.php';

function fixAssetUrl($path) {
    if (!$path) return "../assets/images/products/placeholder_food.png";
    if (strpos($path, 'http') === 0) return $path;

    $cleanPath = ltrim($path, '/');

    if (strpos($cleanPath, 'images/') === 0) {
        return "../assets/" . $cleanPath;
    }
    return "../assets/" . $cleanPath;
}

$stallId = $_GET['stallid'] ?? null;
if (!$stallId) {
    die("Stall ID missing.");
}

// Get search / category / sort
$search         = $_GET['search']   ?? '';
$categoryFilter = $_GET['category'] ?? '';
$sort           = $_GET['sort']     ?? 'default';

/* ============================================================
   Query products for this stall
============================================================ */
$params = [':stallid' => $stallId];

$sql = "SELECT 
            p.ProductId,
            p.ProductName,
            p.Description,
            p.UnitPrice,
            p.IsAvailable,
            p.CategoryId,
            p.StallId,
            s.StallName,
            s.LogoUrl,
            c.CategoryName,
            (SELECT ImageURL 
             FROM productimages pi 
             WHERE pi.ProductId = p.ProductId 
             LIMIT 1) AS ImageURL
        FROM products p
        JOIN stalls s ON p.StallId = s.StallId
        LEFT JOIN categories c ON p.CategoryId = c.CategoryId
        WHERE s.StallId = :stallid";
        
if ($search) {
    $sql .= " AND (p.ProductName LIKE :search OR p.Description LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($categoryFilter !== '') {
    $sql .= " AND p.CategoryId = :cat";
    $params[':cat'] = $categoryFilter;
}

// Sorting
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.UnitPrice ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.UnitPrice DESC";
        break;
    default:
        $sql .= " ORDER BY p.ProductName ASC";
        break;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   ⭐  AJAX ENDPOINT: menu.php?ajax=1
============================================================ */
if (isset($_GET['ajax'])) {
    // Set proper headers
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Fix ImageURL paths
    foreach ($products as &$p) {
        $p['ImageURL'] = fixAssetUrl($p['ImageURL'] ?? '');
    }

    echo json_encode([
        "success" => true,
        "items"   => $products,
        "count"   => count($products)
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

/* ============================================================
   Get categories for this stall
============================================================ */
$catSql = "SELECT DISTINCT c.CategoryId, c.CategoryName
        FROM products p
        JOIN categories c ON p.CategoryId = c.CategoryId
        WHERE p.StallId = :stallid
        ORDER BY c.CategoryName ASC";
$catStmt = $db->prepare($catSql);
$catStmt->execute([':stallid' => $stallId]);
$cats = $catStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Canteen Menu</title>
<link rel="stylesheet" href="../assets/css/root.css">

<style>
body {
    background: var(--snow-3);
    font-family: var(--font-main);
    margin: 0;
}

/* ================= HEADER（Menu + Search + Sort） ================ */
.menu-header {
    background: var(--snow-1);
    padding: 18px 24px;
    box-shadow: var(--shadow-small);
    margin-bottom: 12px;
}

.menu-header-top {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    gap: 12px;
    margin-bottom: 12px;
}

.menu-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.menu-title-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.04);
}

.menu-title-pill-icon {
    width: 20px;
    height: 20px;
    border-radius: 999px;
    background: #111827;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #f9fafb;
}

.menu-header h2 {
    margin: 0;
    color: var(--polar-1);
    font-size: 1.4rem;
}

/* Search + Sort 条 */
.filter-bar {
    display: flex;
    gap: 10px;
    align-items: center;
}

.filter-input {
    padding: 10px 12px;
    border-radius: var(--radius-md);
    border: 1px solid var(--polar-4);
    flex: 1;
    min-width: 200px;
    font-size: 0.95rem;
}

.filter-select {
    padding: 10px 12px;
    border-radius: var(--radius-md);
    border: 1px solid var(--polar-4);
    min-width: 170px;
    font-size: 0.9rem;
    background: #fff;
}

/* Search button */
.filter-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 999px;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 600;
    background: var(--frost-3);
    color: #f9fafb;
    box-shadow: 0 6px 18px rgba(37, 99, 235, 0.35);
    transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}

.filter-btn i {
    font-size: 0.9rem;
}

.filter-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 26px rgba(37, 99, 235, 0.45);
    opacity: 0.96;
}

.filter-btn:active {
    transform: translateY(0);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
}

.filter-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* ================= CATEGORIES ================= */
.section-categories {
    padding: 6px 24px 18px 24px;
}

.section-categories .section-header {
    margin-bottom: 10px;
}

.section-categories .section-header h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--polar-1);
}

.cat-scroll {
    display: flex;
    gap: 18px;
    padding: 6px 4px 10px 4px;
    overflow-x: auto;
}

.cat-scroll::-webkit-scrollbar {
    height: 6px;
}
.cat-scroll::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.6);
    border-radius: 999px;
}

.cat-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    min-width: 72px;
    text-decoration: none;
    color: var(--polar-2);
    cursor: pointer;
}

.cat-box {
    width: 68px;
    height: 68px;
    border-radius: 22px;
    background: #ffffff;
    box-shadow: 0 8px 30px rgba(15,23,42,0.08);
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 0.95rem;
    font-weight: 600;
    color: #111827;
    transition: 0.23s ease;
}

.cat-label {
    font-size: 0.9rem;
    font-weight: 500;
}

.cat-card.active .cat-box {
    background: #e0e7ff;
    color: #1d4ed8;
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(129, 140, 248, 0.5);
}

/* ===================== Product Cards ===================== */
.grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 20px;
    padding: 0 24px 40px;
    transition: opacity 0.25s ease;
}

.card {
    background: white;
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-soft);
    cursor: pointer;
    height: 360px;
    transition: 0.25s;
    display: flex;
    flex-direction: column;
    animation: fadeInUp 0.35s ease forwards;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.12);
}

.card.unavailable {
    background: #dcdcdc;
    filter: grayscale(0.6);
    cursor: not-allowed;
}

.card.unavailable .card-title,
.card.unavailable .card-desc,
.card.unavailable .price-tag {
    color: var(--polar-4) !important;
}

.card-img {
    height: 170px;
    background-size: cover;
    background-position: center;
    position: relative;
}

.unavailable-layer {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.40);
    display: flex;
    align-items: center;
    justify-content: center;
}

.unavailable-layer img {
    width: 65%;
    opacity: 0.9;
}

.hidden-unavailable {
    display: none;
}

.card-body {
    padding: 15px;
    display: flex;
    flex-direction: column;
}

.card-title {
    font-size: 1.05rem;
    font-weight: 700;
    margin-bottom: 4px;
    color: var(--polar-1);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.card-desc {
    font-size: 0.9rem;
    color: var(--polar-4);
    height: 40px;
    overflow: hidden;
}

.price-tag {
    font-weight: bold;
    margin-top: auto;
    color: var(--aurora-green);
}

/* Empty state */
.menu-empty-state {
    width: 100%;
    padding: 40px 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: var(--polar-3);
    animation: fadeInUp 0.3s ease forwards;
    grid-column: 1 / -1;
}

.menu-empty-emoji {
    font-size: 2.4rem;
    margin-bottom: 10px;
}

.menu-empty-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 4px;
    color: var(--polar-1);
}

.menu-empty-sub {
    font-size: 0.95rem;
    color: var(--polar-4);
}
/* Back button INSIDE the header */
.back-overlay-btn {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
}
.back-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #ffffff; /* 纯白最清楚 */
    display: flex;
    align-items: center;
    justify-content: center;
    color: #111; /* 深色箭头，超明显 */
    font-size: 1.2rem;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: 0.2s ease;
}

.back-btn:hover {
    transform: translateY(-2px);
}



@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(18px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
</head>
<body>

<div class="menu-header">
    <div class="menu-header-top">
      <a href="../index.php" class="back-btn">
        <i class="fas fa-arrow-left"></i>
    </a>
        <div class="menu-title-group">
            <div class="menu-title-pill">
                <div class="menu-title-pill-icon" style="overflow:hidden;">
                    <img src="<?php echo fixAssetUrl($products[0]['LogoUrl'] ?? ''); ?>"
                        style="width:20px;height:20px;border-radius:50%;object-fit:cover;">
                </div>
                <span><?php echo htmlspecialchars($products[0]['StallName'] ?? ''); ?></span>
            </div>
            <h2>Menu</h2>
        </div>
    </div>

    <form class="filter-bar" method="GET" id="menu-filter-form">
        <input type="hidden" name="stallid" value="<?php echo (int)$stallId; ?>">
        <input type="hidden" name="category" id="category-hidden" value="<?php echo htmlspecialchars($categoryFilter); ?>">

        <input type="text" name="search" placeholder="Search food..."
            value="<?php echo htmlspecialchars($search); ?>" class="filter-input" id="search-input">

        <select name="sort" class="filter-select" id="sort-select">
            <option value="default"   <?php if ($sort === 'default')   echo 'selected'; ?>>Sort: Default</option>
            <option value="price_asc" <?php if ($sort === 'price_asc') echo 'selected'; ?>>Price: Low → High</option>
            <option value="price_desc"<?php if ($sort === 'price_desc')echo 'selected'; ?>>Price: High → Low</option>
        </select>

        <button type="submit" class="filter-btn" id="search-btn">
            <i class="fas fa-search"></i>
            <span>Search</span>
        </button>
    </form>
</div>

<!-- Categories -->
<section class="section-categories">
    <div class="section-header">
        <h3>Categories</h3>
    </div>

    <div class="cat-scroll" id="category-scroll">
        <a href="#" class="cat-card <?php echo ($categoryFilter === '' ? 'active' : ''); ?>" data-category="">
            <div class="cat-box">All</div>
            <div class="cat-label">All</div>
        </a>

        <?php foreach ($cats as $c): 
            $isActive = ($categoryFilter !== '' && (int)$categoryFilter === (int)$c['CategoryId']);
        ?>
            <a href="#" class="cat-card <?php echo $isActive ? 'active' : ''; ?>" data-category="<?php echo (int)$c['CategoryId']; ?>">
                <div class="cat-box">
                    <?php echo htmlspecialchars($c['CategoryName']); ?>
                </div>
                <div class="cat-label">
                    <?php echo htmlspecialchars($c['CategoryName']); ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Products Grid -->
<div class="grid-container" id="menu-grid">
<?php if (empty($products)): ?>
    <div class="menu-empty-state">
        <div class="menu-empty-emoji">(っ °Д °;)っ</div>
        <div class="menu-empty-title">No results found</div>
        <div class="menu-empty-sub">
            Try searching something else or choosing another category.
        </div>
    </div>
<?php else: ?>
    <?php foreach ($products as $p): ?>
        <?php
            $img = fixAssetUrl($p['ImageURL'] ?? '');
            $isUnavailable = ($p['IsAvailable'] == 0);
        ?>
        <div class="card <?php echo $isUnavailable ? 'unavailable' : ''; ?>"
            data-id="<?php echo $p['ProductId']; ?>"
            <?php if (!$isUnavailable): ?>
                onclick="location.href='product_detail.php?id=<?php echo $p['ProductId']; ?>'"
            <?php endif; ?>
        >
            <div class="card-img" style="background-image:url('<?php echo $img; ?>');">
                <div class="unavailable-layer <?php echo $isUnavailable ? '' : 'hidden-unavailable'; ?>">
                    <img src="../assets/images/unavailable.png" alt="Unavailable">
                </div>
            </div>

            <div class="card-body">
                <div class="card-title"><?php echo htmlspecialchars($p['ProductName']); ?></div>
                <div class="card-desc"><?php echo htmlspecialchars($p['Description']); ?></div>
                <div class="price-tag">RM <?php echo number_format($p['UnitPrice'], 2); ?></div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<script>
const MENU_STATE = {
    stallId: <?php echo (int)$stallId; ?>,
    search: <?php echo json_encode($search); ?>,
    category: <?php echo json_encode($categoryFilter); ?>,
    sort: <?php echo json_encode($sort); ?>
};

window.MENU_CONFIG = MENU_STATE;
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<script src="../assets/js/menu.js"></script>

</body>
</html>