<?php
include '../configs/db.php';
include '../includes/functions.php';

// Get search parameters
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

// Build query dynamically based on filters
$params = [];
$sql = "SELECT p.*, s.StallName, c.CategoryName, 
        (SELECT ImageURL FROM productimages pi WHERE pi.ProductId = p.ProductId LIMIT 1) as ImageURL
        FROM products p
        JOIN stalls s ON p.StallId = s.StallId
        LEFT JOIN categories c ON p.CategoryId = c.CategoryId
        WHERE p.IsAvailable = 1 AND s.IsAvailable = 1";

if ($search) {
    $sql .= " AND (p.ProductName LIKE :search OR s.StallName LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($categoryFilter) {
    $sql .= " AND c.CategoryId = :cat";
    $params[':cat'] = $categoryFilter;
}

$sql .= " ORDER BY s.StallName ASC, p.ProductName ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Categories for filter dropdown
$cats = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canteen Menu</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <style>
        /* Simple Grid Layout for Menu */
        .menu-header {
            background: #fff;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-bar {
            display: flex;
            gap: 10px;
            flex: 1;
            max-width: 600px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            padding: 0 20px 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .card-img {
            height: 180px;
            background-color: #eee;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .stall-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
        }
        
        .card-body {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .card-title {
            font-size: 1.1em;
            font-weight: bold;
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .card-desc {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 15px;
            flex: 1; /* Pushes price/btn down */
        }
        
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }
        
        .price {
            font-size: 1.2em;
            color: #28a745;
            font-weight: bold;
        }
        
        .add-btn {
            background: #4a90e2;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .add-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .cart-icon {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #28a745;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            text-decoration: none;
            z-index: 100;
        }
    </style>
</head>
<body>
    
    <!-- Navigation / Filter Header -->
    <div class="menu-header">
        <h2>Canteen Menu</h2>
        
        <form class="search-bar" action="" method="GET">
            <input type="text" name="search" class="search-input" placeholder="Search food or stall..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="category" class="search-input" style="max-width: 150px;">
                <option value="">All Categories</option>
                <?php foreach($cats as $c): ?>
                    <option value="<?php echo $c['CategoryId']; ?>" <?php if($categoryFilter == $c['CategoryId']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($c['CategoryName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="add-btn">Filter</button>
        </form>
        
        <div>
            <?php if(isLoggedIn()): ?>
                <a href="profile.php">My Profile</a> | 
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </div>

    <?php flash(); ?>

    <!-- Products Grid -->
    <div class="grid-container">
        <?php if(empty($products)): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 50px;">
                <h3>No products found matching your criteria.</h3>
            </div>
        <?php else: ?>
            <?php foreach($products as $p): 
                // Check Stock
                $hasStock = $p['IsUnlimitedStock'] || $p['Stock'] > 0;
                // Image Fallback
                $bgImage = $p['ImageURL'] ? $p['ImageURL'] : '../assets/images/placeholder_food.png';
            ?>
            <div class="card">
                <div class="card-img" style="background-image: url('<?php echo htmlspecialchars($bgImage); ?>');">
                    <span class="stall-badge"><?php echo htmlspecialchars($p['StallName']); ?></span>
                    <?php if(!$hasStock): ?>
                        <div style="position:absolute; inset:0; background:rgba(255,255,255,0.7); display:flex; align-items:center; justify-content:center; font-weight:bold; color:red; transform: rotate(-15deg);">OUT OF STOCK</div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h3 class="card-title"><?php echo htmlspecialchars($p['ProductName']); ?></h3>
                    <p class="card-desc"><?php echo htmlspecialchars($p['Description']); ?></p>
                    
                    <form action="add_to_cart.php" method="POST" class="card-footer">
                        <div class="price">RM <?php echo number_format($p['UnitPrice'], 2); ?></div>
                        
                        <input type="hidden" name="product_id" value="<?php echo $p['ProductId']; ?>">
                        
                        <!-- Quantity Input (Small) -->
                        <input type="number" name="quantity" value="1" min="1" max="10" style="width: 50px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; margin-right: 5px;" <?php echo $hasStock ? '' : 'disabled'; ?>>
                        
                        <button type="submit" class="add-btn" <?php echo $hasStock ? '' : 'disabled'; ?>>
                            <?php echo $hasStock ? 'Add +' : 'Sold Out'; ?>
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Floating Cart Button -->
    <a href="cart.php" class="cart-icon" title="View Cart">
        🛒
    </a>

</body>
</html>