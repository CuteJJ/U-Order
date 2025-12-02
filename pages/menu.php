    <?php
    include '../configs/db.php';
    include '../includes/functions.php';

    function fixAssetUrl($path) {
        if (!$path) return "../assets/images/products/placeholder_food.png";
        if (strpos($path, 'http') === 0) return $path;

        $cleanPath = ltrim($path, '/');

        // 如果本来就是 images/... 就补上 ../assets/
        if (strpos($cleanPath, 'images/') === 0) {
            return "../assets/" . $cleanPath;
        }
        // 否则直接拼到 ../assets 下
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
        display: flex;
        align-items: center;
        justify-content: space-between;
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

    /* 新增：Search 按钮 */
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

    /* ================= CATEGORIES 一排小方块 ================= */
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

    /* Active 状态：浅蓝背景 + 轻微抬起 */
    .cat-card.active .cat-box {
        background: #e0e7ff;
        color: #1d4ed8;
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(129, 140, 248, 0.5);
    }

    /* ===================== 产品 Card Grid ===================== */
    .grid-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 20px;
        padding: 0 24px 40px;
    }

    /* Card */
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

    /* Unavailable card */
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

    /* image */
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

    /* Hide layer if available */
    .hidden-unavailable {
        display: none;
    }

    /* Content */
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

    /* No results UI */
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

    /* 动画 */
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
    #menu-grid {
    opacity: 0;
    transition: opacity .25s ease;
}

    </style>
    </head>
    <body>

    <div class="menu-header">
        <div class="menu-header-top">
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

        <!-- Search / Sort 表单：后面由 JS 拦截改成 AJAX -->
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

            <!-- 新增 Search 按钮（手机用户可以直接按） -->
            <button type="submit" class="filter-btn" id="search-btn">
                <i class="fas fa-search"></i>
                <span>Search</span>
            </button>
        </form>
    </div>

    <!-- =================== Categories 一排小方块 =================== -->
    <section class="section-categories">
        <div class="section-header">
            <h3>Categories</h3>
        </div>

        <div class="cat-scroll" id="category-scroll">
            <!-- All -->
            <?php
            // 构造保留 search & sort 的基础 URL（只是 fallback，用 JS 会 preventDefault）
            $baseQuery = "stallid=" . urlencode($stallId) .
                        "&search=" . urlencode($search) .
                        "&sort="   . urlencode($sort);
            ?>
            <a href="menu.php?<?php echo $baseQuery; ?>"
            class="cat-card <?php echo ($categoryFilter === '' ? 'active' : ''); ?>"
            data-category="">
                <div class="cat-box">All</div>
                <div class="cat-label">All</div>
            </a>

            <?php foreach ($cats as $c): 
                $isActive = ($categoryFilter !== '' && (int)$categoryFilter === (int)$c['CategoryId']);
                $catUrl = $baseQuery . "&category=" . urlencode($c['CategoryId']);
            ?>
                <a href="menu.php?<?php echo $catUrl; ?>"
                class="cat-card <?php echo $isActive ? 'active' : ''; ?>"
                data-category="<?php echo (int)$c['CategoryId']; ?>">
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

    <!-- =================== 产品 Grid =================== -->
    <div class="grid-container" id="menu-grid">
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
    </div>

    <script>
    // 兼容你之前的 MENU_STATE，同时新增 MENU_CONFIG 给 JS 用
    const MENU_STATE = {
        stallId: <?php echo (int)$stallId; ?>,
        search: <?php echo json_encode($search); ?>,
        category: <?php echo json_encode($categoryFilter); ?>,
        sort: <?php echo json_encode($sort); ?>
    };

    window.MENU_CONFIG = MENU_STATE;
    </script>

    <script src="https://kit.fontawesome.com/a2e0e6ad65.js" crossorigin="anonymous"></script>
    <script src="../assets/js/menu.js"></script>

    </body>
    </html>
