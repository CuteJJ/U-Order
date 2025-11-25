<?php
include '../configs/db.php';
include '../includes/functions.php';

$stallId = $_GET['stallid'] ?? null;
if (!$stallId) {
    die("Stall ID missing.");
}

// Get search parameters
$search         = $_GET['search']   ?? '';
$categoryFilter = $_GET['category'] ?? '';

// Build query（显示全部产品，不过滤 IsAvailable）
$params = [':stallid' => $stallId];
$sql = "SELECT 
            p.*,
            s.StallName,
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

if ($categoryFilter) {
    $sql .= " AND p.CategoryId = :cat";
    $params[':cat'] = $categoryFilter;
}

$sql .= " ORDER BY p.ProductName ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories
$cats = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
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

.menu-header {
    background: var(--snow-1);
    padding: 18px 24px;
    box-shadow: var(--shadow-small);
    margin-bottom: 22px;
}

.menu-header h2 {
    margin: 0 0 10px 0;
    color: var(--polar-1);
}

.filter-bar {
    display: flex;
    gap: 10px;
}

.filter-input,
.filter-select {
    padding: 10px;
    border-radius: var(--radius-md);
    border: 1px solid var(--polar-4);
    flex: 1;
    min-width: 200px;
}

.filter-btn {
    background: var(--frost-3);
    color: white;
    padding: 10px 20px;
    border-radius: var(--radius-md);
    border: none;
}

.grid-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 24px;
    padding: 0 24px 60px;
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
    font-size: 1.1rem;
    font-weight: bold;
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
</style>
</head>
<body>

<div class="menu-header">
    <h2>Menu – <?php echo htmlspecialchars($products[0]['StallName'] ?? ''); ?></h2>

    <form class="filter-bar" method="GET">
        <input type="hidden" name="stallid" value="<?php echo $stallId; ?>">

        <input type="text" name="search" placeholder="Search food..."
               value="<?php echo htmlspecialchars($search); ?>" class="filter-input">

        <select name="category" class="filter-select">
            <option value="">All Categories</option>
            <?php foreach ($cats as $c): ?>
                <option value="<?php echo $c['CategoryId']; ?>"
                    <?php if ($c['CategoryId'] == $categoryFilter) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($c['CategoryName']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="filter-btn">Filter</button>
    </form>
</div>

<div class="grid-container" id="menu-grid">
<?php foreach ($products as $p): ?>
    <?php
        $img = $p['ImageURL']
            ? "../assets" . $p['ImageURL']
            : "../assets/images/products/placeholder_food.png";

        $isUnavailable = ($p['IsAvailable'] == 0);
    ?>

    <div class="card <?php echo $isUnavailable ? 'unavailable' : ''; ?>"
         data-id="<?php echo $p['ProductId']; ?>"
         <?php if (!$isUnavailable): ?>
             onclick="location.href='product_details.php?product_id=<?php echo $p['ProductId']; ?>'"
         <?php endif; ?>
    >

        <div class="card-img" style="background-image:url('<?php echo $img; ?>');">
            <div class="unavailable-layer <?php echo $isUnavailable ? '' : 'hidden-unavailable'; ?>">
                <img src="http://localhost/U-Order/assets/images/unavailable.png">
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
const MENU_STATE = {
    stallId: <?php echo (int)$stallId; ?>,
    search: <?php echo json_encode($search); ?>,
    category: <?php echo json_encode($categoryFilter); ?>
};
</script>

<script src="../assets/js/menu.js"></script>

</body>
</html>
