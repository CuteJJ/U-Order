<?php
// pages/edit_product_action.php

session_start();
require_once '../configs/db.php';
require_once '../includes/functions.php';

// 1. 權限檢查
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: vendor_dashboard.php');
    exit;
}

// 取得 StallId (安全性檢查用)
$userId = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT StallId FROM stalls WHERE StaffId = ? LIMIT 1");
$stmt->execute([$userId]);
$stall = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$stall) {
    die("Access denied.");
}
$currentStallId = (int)$stall['StallId'];

// 2. 接收表單資料
$productId = $_POST['product_id'] ?? null;
$productName = trim($_POST['product_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$unitPrice = $_POST['unit_price'] ?? '';
$categorySelect = $_POST['category_select'] ?? '';
$newCategoryName = trim($_POST['new_category'] ?? '');

$isUnlimitedStock = isset($_POST['is_unlimited']) ? 1 : 0;
$stock = $_POST['stock'] ?? 0;

// 接收要刪除的圖片 ID 字串 (例如 "12,15")
$deletedImageIdsRaw = $_POST['deleted_image_ids'] ?? '';

$errors = [];

// 3. 資料驗證
if (empty($productId)) $errors[] = "Product ID is missing.";
if (empty($productName)) $errors[] = "Product name is required.";
if (!is_numeric($unitPrice) || $unitPrice < 0) $errors[] = "Price must be valid.";
if (empty($categorySelect)) $errors[] = "Category is required.";

// 4. 分類邏輯 (與 Add Product 相同)
$finalCategoryId = $categorySelect;
if ($categorySelect === 'other') {
    if (empty($newCategoryName)) {
        $errors[] = "Please enter a name for the new category.";
    } else {
        try {
            $stmt = $db->prepare("SELECT CategoryId FROM categories WHERE CategoryName = ? LIMIT 1");
            $stmt->execute([$newCategoryName]);
            $existingCat = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingCat) {
                $finalCategoryId = $existingCat['CategoryId'];
            } else {
                $stmt = $db->prepare("INSERT INTO categories (CategoryName) VALUES (?)");
                $stmt->execute([$newCategoryName]);
                $finalCategoryId = $db->lastInsertId();
            }
        } catch (Exception $e) {
            $errors[] = "Category Error: " . $e->getMessage();
        }
    }
}

// 5. 錯誤檢查點 (如果前面有錯，直接返回)
if (count($errors) > 0) {
    $_SESSION['error_msg'] = implode('<br>', $errors);
    header("Location: edit_product.php?id=" . $productId);
    exit;
}

// 6. 開始資料庫操作 (Transaction)
try {
    $db->beginTransaction();

    // A. 更新產品基本資料
    // 注意：一定要加上 AND StallId = ? 確保 Vendor 只能改自己的商品
    $finalStock = $isUnlimitedStock ? 0 : $stock;
    
    $sql = "UPDATE products 
            SET CategoryId = ?, 
                ProductName = ?, 
                Description = ?, 
                UnitPrice = ?, 
                IsUnlimitedStock = ?, 
                Stock = ? 
            WHERE ProductId = ? AND StallId = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $finalCategoryId,
        $productName,
        $description,
        $unitPrice,
        $isUnlimitedStock,
        $finalStock,
        $productId,
        $currentStallId
    ]);

    // B. 處理舊圖片刪除
    if (!empty($deletedImageIdsRaw)) {
        // 將 "12,15" 轉為陣列
        $deletedIds = explode(',', $deletedImageIdsRaw);
        
        // 過濾只留數字，避免 SQL Injection
        $deletedIds = array_filter($deletedIds, 'is_numeric');

        if (!empty($deletedIds)) {
            // 1. 先查出檔案路徑，以便刪除實體檔案
            $placeholders = implode(',', array_fill(0, count($deletedIds), '?'));
            $stmtFetch = $db->prepare("SELECT ImageURL FROM productimages WHERE ImageId IN ($placeholders) AND ProductId = ?");
            // 參數合併：IDs + ProductId (確保只能刪這個商品的圖)
            $params = $deletedIds;
            $params[] = $productId;
            $stmtFetch->execute($params);
            $imagesToDelete = $stmtFetch->fetchAll(PDO::FETCH_COLUMN);

            // 刪除實體檔案
            foreach ($imagesToDelete as $path) {
                // 路徑修正：DB 存的是 assets/...，PHP 執行檔在 pages/，所以要 ../assets/...
                $filePath = '../' . $path;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // 2. 刪除資料庫記錄
            $stmtDel = $db->prepare("DELETE FROM productimages WHERE ImageId IN ($placeholders) AND ProductId = ?");
            $stmtDel->execute($params);
        }
    }

    // C. 處理新圖片上傳
    $uploadDir = '../assets/images/products/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (isset($_FILES['new_product_images'])) {
        $totalInput = count($_FILES['new_product_images']['name']);
        
        for ($i = 0; $i < $totalInput; $i++) {
            if ($_FILES['new_product_images']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['new_product_images']['tmp_name'][$i];
                $fileName = $_FILES['new_product_images']['name'][$i];
                
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($ext, $allowed)) {
                    $newFileName = 'prod_' . time() . '_' . rand(1000, 9999) . "_{$i}." . $ext;
                    $destPath = $uploadDir . $newFileName;

                    if (move_uploaded_file($tmpName, $destPath)) {
                        $dbPath = '../assets/images/products/' . $newFileName;
                        
                        // 寫入 DB
                        $stmtImg = $db->prepare("INSERT INTO productimages (ProductId, ImageURL) VALUES (?, ?)");
                        $stmtImg->execute([$productId, $dbPath]);
                    }
                }
            }
        }
    }

    // D. 最後檢查：如果所有圖都被刪了，且沒上傳新圖？
    // 這裡視業務邏輯而定。如果你允許商品無圖，這段可省略。
    // 如果必須有圖，可以 COUNT 一下 productimages。
    // 目前 JS 已經擋了，後端暫時信任操作。

    $db->commit();
    
    // 成功回傳
    header("Location: edit_product.php?id=$productId&status=success");
    exit;

} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error_msg'] = "Database Error: " . $e->getMessage();
    header("Location: edit_product.php?id=" . $productId);
    exit;
}
?>