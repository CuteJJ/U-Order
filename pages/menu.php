<?php
include '../configs/db.php';
include '../includes/functions.php';

$stallId = $_GET['stallid'] ?? null;
if (!$stallId) {
    die("Stall ID missing.");
}

// Get search / category / sort
$search         = $_GET['search']   ?? '';
$categoryFilter = $_GET['category'] ?? '';
$sort           = $_GET['sort']     ?? 'default';

/* ============================================================
   取当前档口产品（显示全部，包括 unavailable）
============================================================ */
$params = [':stallid' => $stallId];

$sql = "SELECT 
            p.*,
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
        WHERE s.StallId = :stallid
        AND s.IsAvailable = 1";

if ($search) {
    $sql .= " AND (p.ProductName LIKE :search OR p.Description LIKE :search)";
    $params[':search'] = "%$search%";
}

// 后端 category 过滤
if ($categoryFilter !== '') {
    $sql .= " AND p.CategoryId = :cat";
    $params[':cat'] = $categoryFilter;
}

// 排序：默认按名字，Sort 改为按价格
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
   ⭐  AJAX: menu.php?ajax=1
============================================================ */
if (isset($_GET['ajax'])) {
    // 修复 ImageURL 路径
    foreach ($products as &$p) {
        $img = $p['ImageURL'] ?? '';
        if (!$img) {
            $p['ImageURL'] = "../assets/images/products/placeholder_food.png";
        } else if (strpos($img, "http") === 0) {
            $p['ImageURL'] = $img;
        } else {
            $p['ImageURL'] = "../assets/" . ltrim($img, '/');
        }
    }
    echo json_encode([
        "success" => true,
        "items"   => $products
    ]);
    exit;
}

/* ============================================================
   只取【这个 stall 拥有的】category 列表
   (Includes Logo for display)
============================================================ */
$catSql = "SELECT DISTINCT c.CategoryId, c.CategoryName, c.CategoryLogo
           FROM products p
           JOIN categories c ON p.CategoryId = c.CategoryId
           WHERE p.StallId = :stallid
           ORDER BY c.CategoryName ASC";
$catStmt = $db->prepare($catSql);
$catStmt->execute([':stallid' => $stallId]);
$cats = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// Stall Info for Header
$stallName = !empty($products) ? $products[0]['StallName'] : 'Stall Menu';
$stallLogo = !empty($products) ? fixAssetUrl($products[0]['LogoUrl']) : '';

include '../includes/header.php';
?>

<style>
    /* STALL HERO */
    .stall-hero {
        background: #fff;
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 25px;
        display: flex;
        flex-direction: column;
        gap: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }
    @media (min-width: 1024px) {
        .stall-hero { flex-direction: row; justify-content: space-between; align-items: center; }
    }
    .stall-info { display: flex; align-items: center; gap: 15px; }
    .stall-logo-img { width: 65px; height: 65px; border-radius: 50%; object-fit: cover; border: 2px solid var(--nord4); }
    .stall-text h1 { margin: 0; font-size: 1.6rem; color: var(--nord0); font-weight: 800; }
    .stall-text p { margin: 0; color: var(--nord3); font-size: 0.95rem; }

    /* CONTROLS (Search & Sort) */
    .menu-controls { display: flex; gap: 12px; width: 100%; max-width: 650px; flex-wrap: wrap; }
    .search-input-wrapper { position: relative; flex-grow: 1; min-width: 200px; }
    .search-input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--nord3); pointer-events: none; }
    
    #search-input {
        width: 100%; padding: 12px 12px 12px 45px;
        border: 2px solid var(--nord4); border-radius: 12px;
        font-family: inherit; background: var(--nord6); color: var(--nord0);
        transition: 0.2s;
    }
    #search-input:focus { outline: none; border-color: var(--nord9); background: #fff; }

    /* CUSTOM DROPDOWN CSS */
    .custom-select-wrapper {
        position: relative;
        min-width: 160px;
    }
    
    #sort-select {
        width: 100%;
        appearance: none; /* Hide default arrow */
        -webkit-appearance: none;
        -moz-appearance: none;
        padding: 12px 40px 12px 20px; /* Right padding for custom arrow */
        border: 2px solid var(--nord4);
        border-radius: 12px;
        background: #fff;
        cursor: pointer;
        font-weight: 600;
        color: var(--nord2);
        font-family: inherit;
        /* Custom Chevron Icon (SVG) */
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234C566A' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 16px;
    }
    #sort-select:focus { outline: none; border-color: var(--nord9); }

    /* CATEGORIES SCROLL */
    .section-categories { margin-bottom: 30px; }
    .section-header h3 { margin: 0 0 10px 0; font-size: 1.2rem; color: var(--nord1); font-weight: 700; }

    #category-scroll {
        display: flex; gap: 15px; overflow-x: auto; 
        /* Key Fix: Add padding to container so active transformation isn't clipped */
        padding: 10px 5px 20px 5px;
        scrollbar-width: none;
    }
    #category-scroll::-webkit-scrollbar { display: none; }

    .cat-card {
        display: flex; flex-direction: column; align-items: center; gap: 10px;
        cursor: pointer; min-width: 72px; text-decoration: none; transition: 0.2s;
        opacity: 0.7;
    }
    .cat-card.active { opacity: 1; transform: translateY(-8px);}
    .cat-card.active .cat-box { background: var(--nord10); box-shadow: 0 8px 20px rgba(94, 129, 172, 0.4); }
    .cat-card.active .cat-box i, .cat-card.active .cat-box img {filter: brightness(0) saturate(100%) invert(100%) sepia(100%) saturate(23%) hue-rotate(229deg) brightness(105%) contrast(102%); }

    .cat-box {
        width: 60px; height: 60px; background: #fff; border-radius: 18px;
        display: flex; justify-content: center; align-items: center;
        font-size: 1.4rem; color: var(--nord10);
        box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: 0.2s;
        overflow: hidden;
    }
    .cat-label { font-size: 0.85rem; font-weight: 600; color: var(--nord2); text-align: center; }

    /* PRODUCT GRID */
    #menu-grid {
        margin-left: 20px;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 20px;
        padding-bottom: 50px;
        transition: opacity 0.2s ease;
    }
    @media (min-width: 1024px) {
        #menu-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 30px; }
    }

    /* CARD generated by JS */
    .card {
        background: #fff; border-radius: 20px; overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05); border: 1px solid var(--nord6);
        display: flex; flex-direction: column; cursor: pointer;
        transition: transform 0.2s; height: 100%;
    }
    .card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
    
    .card-img {
        height: 160px; background-size: cover; background-position: center; position: relative;
    }
    @media (min-width: 1024px) { .card-img { height: 200px; } }

    .card-body { padding: 1.2rem; flex-grow: 1; display: flex; flex-direction: column; }
    
    .card-title { font-size: 1.1rem; font-weight: 700; color: var(--nord0); margin-bottom: 5px; }
    .card-desc { 
        font-size: 0.9rem; color: var(--nord3); margin-bottom: 15px; 
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        flex-grow: 1;
    }
    .price-tag { font-size: 1.2rem; font-weight: 800; color: var(--nord0); margin-top: auto; }

    /* Unavailable State */
    .card.unavailable { opacity: 0.7; filter: grayscale(1); }
    .unavailable-layer {
        position: absolute; inset: 0; background: rgba(0,0,0,0.6);
        display: flex; align-items: center; justify-content: center;
    }
    .hidden-unavailable { display: none !important; }
    .unavailable-layer img { width: 60%; opacity: 0.8; }

    /* Empty State */
    .menu-empty-state { grid-column: 1/-1; text-align: center; padding: 50px; color: var(--nord3); }
    .menu-empty-emoji { font-size: 3rem; margin-bottom: 15px; }

/* Back to Menu Button */
.back-pill {
    margin-left: 20px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    background: var(--nord6); 
    color: var(--nord0);      
    border: 1px solid var(--nord4);
}

.back-pill:hover {
    background: rgba(255, 255, 255, 0.4);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
</style>

<div class="app-wrapper">

    <div class="stall-hero">
        <div class="stall-info">
            <img src="<?php echo htmlspecialchars($stallLogo); ?>" class="stall-logo-img">
            <div class="stall-text">
                <h1><?php echo htmlspecialchars($stallName); ?></h1>
                <p>Browsing Menu</p>
            </div>
        </div>
        <form id="menu-filter-form" class="menu-controls">
            <input type="hidden" id="category-hidden" value="<?php echo htmlspecialchars($categoryFilter); ?>">
            
            <div class="search-input-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="search-input" placeholder="Search food..." 
                       value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
            </div>

            <div class="custom-select-wrapper">
                <select id="sort-select">
                    <option value="default" <?php if ($sort === 'default') echo 'selected'; ?>>Sort: Default</option>
                    <option value="price_asc" <?php if ($sort === 'price_asc') echo 'selected'; ?>>Price: Low</option>
                    <option value="price_desc" <?php if ($sort === 'price_desc') echo 'selected'; ?>>Price: High</option>
                </select>
            </div>
        </form>
    </div>

        <a href="/U-Order/index.php" class="back-pill">
            <i class="fas fa-arrow-left"></i> Back to Menu
        </a>

    <section class="section-categories">
        <div class="section-header">
            <h3>Categories</h3>
        </div>

        <div id="category-scroll">
            <div class="cat-card <?php echo ($categoryFilter === '') ? 'active' : ''; ?>" data-category="">
                <div class="cat-box">
                    <i class="fas fa-border-all"></i>
                </div>
                <div class="cat-label">All</div>
            </div>

            <?php foreach ($cats as $c): ?>
                <?php $isActive = ($categoryFilter == $c['CategoryId']); ?>
                <div class="cat-card <?php echo $isActive ? 'active' : ''; ?>" 
                     data-category="<?php echo $c['CategoryId']; ?>">
                    <div class="cat-box">
                        <?php if (!empty($c['CategoryLogo'])): ?>
                            <img src="<?php echo htmlspecialchars($c['CategoryLogo']); ?>" 
                                 style="width:30px; height:30px; object-fit:contain;">
                        <?php else: ?>
                            <i class="fas fa-utensils"></i>
                        <?php endif; ?>
                    </div>
                    <div class="cat-label"><?php echo htmlspecialchars($c['CategoryName']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div id="menu-grid">
        <?php foreach ($products as $p): ?>
            <?php
            $img = fixAssetUrl($p['ImageURL'] ?? '');
            $isUnavailable = ($p['IsAvailable'] == 0);
            ?>
            <div class="card <?php echo $isUnavailable ? 'unavailable' : ''; ?>" 
                 data-id="<?php echo $p['ProductId']; ?>"
                 <?php if (!$isUnavailable): ?>
                 onclick="window.location.href='product_detail.php?id=<?php echo $p['ProductId']; ?>'"
                 <?php endif; ?>>
                
                <div class="card-img" style="background-image:url('<?php echo $img; ?>')">
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
        
        <?php if (empty($products)): ?>
            <div class="menu-empty-state">
                <div class="menu-empty-emoji">(っ °Д °;)っ</div>
                <div class="menu-empty-title">No results found</div>
                <div class="menu-empty-sub">Try searching something else.</div>
            </div>
        <?php endif; ?>
    </div>

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

<script src="../assets/js/menu.js"></script>

<?php include '../includes/footer.php'; ?>
