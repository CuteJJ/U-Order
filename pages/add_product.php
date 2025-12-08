<?php
// add_product.php

require_once '../configs/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'vendor') {
    header('Location: login.php');
    exit;
}

// 取得 vendor 的 StallId
$userId = $_SESSION['user_id'] ?? $_SESSION['UserId'] ?? null;
$stallId = null;

if ($userId) {
    $stmt = $db->prepare("SELECT StallId FROM stalls WHERE StaffId = ? LIMIT 1");
    $stmt->execute([$userId]);
    $stall = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($stall) {
        $stallId = (int)$stall['StallId'];
    }
}

// 讀取分類
$categories = [];
try {
    $stmt = $db->query("SELECT CategoryId, CategoryName FROM categories ORDER BY CategoryName");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // 可視情況印錯誤或忽略
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product | Vendor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../assets/css/vendor_add_product.css">
    
    </head>
<body>
<div class="page-wrapper">
    <?php include 'vendor_sidebar.php'; ?>

    <div class="content-area">
        <div class="content-header">
            <h1 class="content-title">Add New Product</h1>
            <p class="content-subtitle">
                Create a new menu item for your stall.
            </p>
            
            <?php if (isset($_SESSION['error_msg'])): ?>
                <div style="background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 8px; margin-top: 10px; border: 1px solid #fecaca;">
                    <?= $_SESSION['error_msg']; ?>
                    <?php unset($_SESSION['error_msg']); ?>
                </div>
            <?php endif; ?>
        </div>

        <form id="addProductForm" enctype="multipart/form-data" method="post" action="add_product_action.php">
            <input type="hidden" name="stall_id" value="<?= htmlspecialchars($stallId ?? '') ?>">

            <div class="product-card">
                <div class="card-section card-media">
                    <div class="section-title">Product Images</div>
                    
                    <div class="image-upload-container">
                        <label class="image-frame upload-drop-zone" id="imageFrame" for="productImageInput" style="cursor:pointer">
                            <div class="image-placeholder" id="imagePlaceholder">
                                <div class="image-placeholder-icon">📸</div>
                                <div style="font-weight:500; margin-bottom:4px;">Click to Add Photos</div>
                                <div class="hint-text">Select multiple files<br>(JPG / PNG)</div>
                            </div>
                        </label>

                        <div class="preview-grid" id="imagePreviewGrid" style="display:none;"></div>
                        
                        <div class="error-text" id="errorImage"></div>

                        <input type="file" name="product_images[]" id="productImageInput" accept="image/*" multiple style="display:none;">
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
                            <input type="text" id="productName" name="product_name" placeholder="E.g. Signature Chicken Rice">
                            <small>Customers will see this name in the menu and order summary.</small>
                            <div class="error-text" id="errorName"></div>
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"
                                      placeholder="Short description, e.g. Poached chicken with fragrant rice."></textarea>
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
                                    <option value="<?= htmlspecialchars($cat['CategoryId']) ?>">
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
                                <input type="checkbox" id="isUnlimited" name="is_unlimited">
                            </div>
                        </div>
                    </div>

                    <div class="field-row" id="stockFieldRow">
                        <div class="field">
                            <label for="stock">
                                Daily stock
                            </label>
                            <input type="number" min="0" id="stock" name="stock" placeholder="E.g. 50">
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
                    Add product
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
            title: 'Product Added!',
            text: 'Your new product is now live on the menu.',
            confirmButtonColor: '#2563eb', 
            confirmButtonText: 'Add Another',
            showCancelButton: true,
            cancelButtonText: 'Go to Dashboard'
        }).then((result) => {
            if (result.isConfirmed) {
                window.history.replaceState(null, null, window.location.pathname);
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                window.location.href = 'vendor_dashboard.php';
            }
        });
    }
</script>

<script src="../assets/js/vendor_add_product.js"></script>

</body>
</html>