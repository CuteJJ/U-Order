<?php
require_once "base.php";

header("Content-Type: application/json");

// 必须 vendor 登录
if (!isset($_SESSION['UserId'])) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$userId = (int)$_SESSION['UserId'];

/* ===========================
   找到 vendor 的 stallId
=========================== */
$sql = "SELECT StallId FROM stalls WHERE StaffId = ? LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute([$userId]);
$stall = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stall) {
    echo json_encode(["error" => "No stall assigned"]);
    exit;
}

$stallId = (int)$stall['StallId'];

/* ===========================
   AJAX 参数（全部与 JS 名称对上）
=========================== */
$page        = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage     = 8;
$search      = trim($_GET['search'] ?? '');
$category    = (int)($_GET['category'] ?? 0);
$unavailable = (int)($_GET['unavailable'] ?? 0); //  0/1
$lowStock    = (int)($_GET['low_stock'] ?? 0);   

/* ===========================
   WHERE 条件
=========================== */
$where = ["p.StallId = :stallId"];
$params = ["stallId" => $stallId];

if ($search !== '') {
    $where[] = "p.ProductName LIKE :search";
    $params["search"] = "%$search%";
}

if ($category > 0) {
    $where[] = "p.CategoryId = :category";
    $params["category"] = $category;
}

if ($unavailable === 1) {
    $where[] = "p.IsAvailable = 0";
}

if ($lowStock === 1) {
    $where[] = "(p.IsUnlimitedStock = 0 AND p.Stock <= 5)";
}

$whereSql = implode(" AND ", $where);

/* ===========================
   分类列表
=========================== */
$catStmt = $db->query("
    SELECT CategoryId, CategoryName
    FROM categories
    ORDER BY CategoryName
");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   获取总数量
=========================== */
$countSql = "SELECT COUNT(*) FROM products p WHERE $whereSql";
$stmtCount = $db->prepare($countSql);

foreach ($params as $k => $v) {
    $stmtCount->bindValue(":".$k, $v);
}
$stmtCount->execute();
$totalProducts = (int)$stmtCount->fetchColumn();

$totalPages = max(1, ceil($totalProducts / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

/* ===========================
   获取产品列表
=========================== */
$productSql = "
    SELECT 
        p.ProductId,
        p.ProductName,
        p.UnitPrice,
        p.Stock,
        p.IsAvailable,
        p.IsUnlimitedStock,
        c.CategoryName,
        (
            SELECT ImageURL FROM productimages i
            WHERE i.ProductId = p.ProductId
            LIMIT 1
        ) AS ImageURL
    FROM products p
    LEFT JOIN categories c ON p.CategoryId = c.CategoryId
    WHERE $whereSql
    ORDER BY p.ProductId ASC
    LIMIT :limit OFFSET :offset
";

$stmtProd = $db->prepare($productSql);

// 绑定参数
foreach ($params as $k => $v) {
    $stmtProd->bindValue(":".$k, $v);
}
$stmtProd->bindValue(":limit", $perPage, PDO::PARAM_INT);
$stmtProd->bindValue(":offset", $offset, PDO::PARAM_INT);

$stmtProd->execute();
$products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

/* ===========================
   返回 JSON
=========================== */
echo json_encode([
    "page"        => $page,
    "totalPages"  => $totalPages,
    "totalItems"  => $totalProducts,
    "categories"  => $categories,
    "products"    => $products
]);

exit;
