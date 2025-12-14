<?php
// pages/edit_product.php

require_once '../configs/db.php';
require_once '../includes/functions.php';

// 1. 權限檢查
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: login.php');
    exit;
}

// 取得 StallId
$userId = $_SESSION['user_id'];
$stallId = null;
$stmt = $db->prepare("SELECT StallId FROM stalls WHERE StaffId = ? LIMIT 1");
$stmt->execute([$userId]);
$stall = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stall) {
    header('Location: vendor_dashboard.php');
    exit;
}
$stallId = (int)$stall['StallId'];

// 2. 獲取產品 ID 與資料驗證
$productId = $_GET['id'] ?? null;

if (!$productId) {
    header('Location: vendor_dashboard.php');
    exit;
}

// 3. 讀取產品資料
$stmt = $db->prepare("SELECT * FROM products WHERE ProductId = ? AND StallId = ? LIMIT 1");
$stmt->execute([$productId, $stallId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found or access denied.");
}

// 4. 讀取該產品的圖片
$stmtImg = $db->prepare("SELECT * FROM productimages WHERE ProductId = ?");
$stmtImg->execute([$productId]);
$existingImages = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

// 5. 讀取分類
$categories = [];
try {
    $stmt = $db->query("SELECT CategoryId, CategoryName FROM categories ORDER BY CategoryName");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product | Vendor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../assets/css/vendor_add_product.css">
</head>
<body>
<div class="page-wrapper">
    <?php include 'vendor_sidebar.php'; ?>

    <div class="content-area">
        <div class="content-header">
            <h1 class="content-title">Edit Product</h1>
            <p class="content-subtitle">
                Update your product details, price or manage images.
            </p>
            
            <?php if (isset($_SESSION['error_msg'])): ?>
                <div style="background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 8px; margin-top: 10px; border: 1px solid #fecaca;">
                    <?= $_SESSION['error_msg']; ?>
                    <?php unset($_SESSION['error_msg']); ?>
                </div>
            <?php endif; ?>
        </div>

        <form id="editProductForm" enctype="multipart/form-data" method="post" action="edit_product_action.php">
            
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['ProductId']) ?>">
            
            <input type="hidden" name="deleted_image_ids" id="deletedImageIds" value="">

            <div class="product-card">
                <div class="card-section card-media">
                    <div class="section-title">Product Images</div>
                    
                    <div class="image-upload-container">
                        <label class="image-frame upload-drop-zone" id="imageFrame" for="productImageInput" style="cursor:pointer">
                            <div class="image-placeholder" id="imagePlaceholder">
                                <div class="image-placeholder-icon">📸</div>
                                <div style="font-weight:500; margin-bottom:4px;">Add New Photos</div>
                                <div class="hint-text">Select multiple files<br>(JPG / PNG)</div>
                            </div>
                        </label>

                        <div class="preview-grid" id="imagePreviewGrid" style="<?= count($existingImages) > 0 ? 'display:grid' : 'display:none' ?>">
                             <?php foreach ($existingImages as $img): ?>
                                <div class="preview-item existing-item" data-existing-id="<?= $img['ImageId'] ?>">
                                    <img src="<?= htmlspecialchars($img['ImageURL']) ?>" alt="Product Image">
                                    <button type="button" class="remove-btn">×</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="error-text" id="errorImage"></div>

                        <input type="file" name="new_product_images[]" id="productImageInput" accept="image/*" multiple style="display:none;">
                    </div>

                    <div class="image-actions">
                        <span class="hint-text">
                            Tip: The first image will be the cover photo.
                        </span>
                    </div>
                </div>

                <div class="card-section card-main">
                    <div class="section-title">Basic Details</div>

                    <div class="field-row">
                        <div class="field">
                            <label for="productName">
                                Product name
                                <span class="label-badge">Required</span>
                            </label>
                            <input type="text" id="productName" name="product_name" 
                                   value="<?= htmlspecialchars($product['ProductName']) ?>"
                                   placeholder="E.g. Signature Chicken Rice">
                            <small>Customers will see this name in the menu and order summary.</small>
                            <div class="error-text" id="errorName"></div>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"
                                      placeholder="Short description..."><?= htmlspecialchars($product['Description']) ?></textarea>
                            <small>1–2 sentences to help students understand what they are ordering.</small>
                            <div class="error-text" id="errorDescription"></div>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label for="categorySelect">
                                Category
                                <span class="label-badge">Required</span>
                            </label>
                            <select id="categorySelect" name="category_select">
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['CategoryId']) ?>"
                                        <?= $cat['CategoryId'] == $product['CategoryId'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['CategoryName']) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="other">Other…</option>
                            </select>
                            <small>Group products logically so students can browse easily.</small>
                            <div class="error-text" id="errorCategory"></div>
                        </div>
                    </div>

                    <div class="field-row" id="new-category-group">
                        <div class="field">
                            <label for="newCategory">
                                New category name
                                <span class="label-badge">Will be created</span>
                            </label>
                            <input type="text" id="newCategory" name="new_category" placeholder="E.g. Snacks / Sides">
                            <small>If the name already exists, the system will reuse that category instead.</small>
                            <div class="error-text" id="errorNewCategory"></div>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label for="unitPrice">
                                Unit price (RM)
                                <span class="label-badge">Required</span>
                            </label>
                            <input type="number" step="0.10" min="0" id="unitPrice" name="unit_price"
                                   value="<?= $product['UnitPrice'] ?>"
                                   placeholder="E.g. 6.00">
                            <small>Final selling price per portion.</small>
                            <div class="error-text" id="errorPrice"></div>
                        </div>
                    </div>
                </div>

                <div class="card-section card-stock">
                    <div class="section-title">Availability</div>

                    <div class="field-row">
                        <div class="field">
                            <div class="pill-toggle">
                                <div class="pill-toggle-label">
                                    <strong>Unlimited stock</strong>
                                </div>
                                <input type="checkbox" id="isUnlimited" name="is_unlimited"
                                       <?= $product['IsUnlimitedStock'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <div class="field-row" id="stockFieldRow">
                        <div class="field">
                            <label for="stock">
                                Daily stock
                            </label>
                            <input type="number" min="0" id="stock" name="stock" 
                                   value="<?= $product['Stock'] ?>"
                                   placeholder="E.g. 50">
                            <small>How many portions you can serve for this product today.</small>
                            <div class="error-text" id="errorStock"></div>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <div class="stock-pill" id="stockHintPill">
                                <span class="icon">📊</span>
                                <span>Students will see “Only few left” when stock is low.</span>
                            </div>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <span class="hint-text">
                                You can adjust stock or temporarily hide the item later from your menu management page.
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="actions-bar">
                <a href="vendor_dashboard.php" class="btn btn-ghost" type="button">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Update Product
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'success') {
        Swal.fire({
            icon: 'success',
            title: 'Updated!',
            text: 'Product details have been updated successfully.',
            confirmButtonColor: '#2563eb', 
            confirmButtonText: 'OK'
        }).then((result) => {
            // 清除 status 參數
            const newUrl = window.location.pathname + "?id=<?= $productId ?>";
            window.history.replaceState(null, null, newUrl);
        });
    }
</script>

<script src="../assets/js/vendor_edit_product.js"></script>

</body>
</html>