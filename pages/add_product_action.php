<?php
// add_product_action.php

session_start();
require_once '../configs/db.php';
require_once '../includes/functions.php';

// 1. 權限檢查
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_product.php');
    exit;
}

// 2. 接收表單資料
$stallId = $_POST['stall_id'] ?? null;
$productName = trim($_POST['product_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$unitPrice = $_POST['unit_price'] ?? ''; 
$categorySelect = $_POST['category_select'] ?? '';
$newCategoryName = trim($_POST['new_category'] ?? '');

// 處理 Checkbox
$isUnlimitedStock = isset($_POST['is_unlimited']) ? 1 : 0;
$stock = $_POST['stock'] ?? 0;

$errors = [];

// 3. 資料驗證 (基本欄位)
if (empty($stallId)) {
    $errors[] = "Stall ID is missing.";
}
if (empty($productName)) {
    $errors[] = "Product name is required.";
}
if (!is_numeric($unitPrice) || $unitPrice < 0) {
    $errors[] = "Price must be a valid positive number.";
}
if (empty($categorySelect)) {
    $errors[] = "Category is required.";
}

// 4. 分類邏輯 (選擇現有 或 新增)
$finalCategoryId = $categorySelect;

if ($categorySelect === 'other') {
    if (empty($newCategoryName)) {
        $errors[] = "Please enter a name for the new category.";
    } else {
        try {
            // 檢查是否已存在
            $stmt = $db->prepare("SELECT CategoryId FROM categories WHERE CategoryName = ? LIMIT 1");
            $stmt->execute([$newCategoryName]);
            $existingCat = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingCat) {
                $finalCategoryId = $existingCat['CategoryId'];
            } else {
                // 不存在則新增
                $stmt = $db->prepare("INSERT INTO categories (CategoryName) VALUES (?)");
                $stmt->execute([$newCategoryName]);
                $finalCategoryId = $db->lastInsertId();
            }
        } catch (Exception $e) {
            $errors[] = "Failed to create category: " . $e->getMessage();
        }
    }
}

// 5. 處理圖片上傳 (含數量限制與類型驗證)
$uploadDir = '../assets/images/products/'; 
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$uploadedImages = []; 

if (isset($_FILES['product_images'])) {
    // 過濾掉空的上傳 (有時候沒選檔案也會傳空的 array)
    // 計算實際有檔名的數量
    $fileCount = 0;
    foreach ($_FILES['product_images']['name'] as $name) {
        if (!empty($name)) $fileCount++;
    }

    // 【安全性驗證 1】 檢查數量限制 (最多 5 張)
    if ($fileCount > 5) {
        $errors[] = "Maximum 5 images allowed.";
    } else {
        // 開始處理上傳
        $totalInput = count($_FILES['product_images']['name']);
        
        for ($i = 0; $i < $totalInput; $i++) {
            // 確保沒有上傳錯誤 (UPLOAD_ERR_NO_FILE 代表該欄位沒選檔案，忽略即可)
            if ($_FILES['product_images']['error'][$i] === UPLOAD_ERR_OK) {
                
                $tmpName = $_FILES['product_images']['tmp_name'][$i];
                $fileName = $_FILES['product_images']['name'][$i];
                
                // 【安全性驗證 2】 檢查檔案類型
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (in_array($ext, $allowed)) {
                    // 命名規則: prod_時間_亂數_索引.jpg
                    $newFileName = 'prod_' . time() . '_' . rand(1000, 9999) . "_{$i}." . $ext;
                    $destPath = $uploadDir . $newFileName;

                    if (move_uploaded_file($tmpName, $destPath)) {
                        $dbPath = 'assets/images/products/' . $newFileName;
                        $uploadedImages[] = $dbPath;
                    } else {
                        $errors[] = "Failed to move uploaded file: " . $fileName;
                    }
                } else {
                    // 發現非法檔案類型，報錯
                    $errors[] = "Invalid file type: '{$fileName}'. Only JPG, PNG, GIF, WEBP are allowed.";
                }
            } elseif ($_FILES['product_images']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                // 其他上傳錯誤 (如檔案太大)
                $errors[] = "Error uploading file '{$_FILES['product_images']['name'][$i]}'. Error Code: " . $_FILES['product_images']['error'][$i];
            }
        }
    }
}

// 6. 資料庫寫入 (使用 Transaction)
if (count($errors) === 0) {
    try {
        $db->beginTransaction();

        // A. 寫入 products 主表
        $sql = "INSERT INTO products 
                (StallId, CategoryId, ProductName, Description, UnitPrice, IsAvailable, IsUnlimitedStock, Stock) 
                VALUES (?, ?, ?, ?, ?, 1, ?, ?)";
        
        $stmt = $db->prepare($sql);
        
        // 處理庫存邏輯
        $finalStock = $isUnlimitedStock ? 0 : $stock;

        $stmt->execute([
            $stallId,
            $finalCategoryId,
            $productName,
            $description,
            $unitPrice,
            $isUnlimitedStock,
            $finalStock
        ]);

        $newProductId = $db->lastInsertId();

        // B. 寫入 productimages 表
        if (!empty($uploadedImages)) {
            $sqlImg = "INSERT INTO productimages (ProductId, ImageURL) VALUES (?, ?)";
            $stmtImg = $db->prepare($sqlImg);

            foreach ($uploadedImages as $imgUrl) {
                $stmtImg->execute([$newProductId, $imgUrl]);
            }
        }

        $db->commit();
        
        header('Location: add_product.php?status=success');
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        $errors[] = "Database Error: " . $e->getMessage();
    }
}

// 7. 錯誤處理
if (count($errors) > 0) {
    $_SESSION['error_msg'] = implode('<br>', $errors);
    $_SESSION['form_data'] = $_POST;
    header('Location: add_product.php');
    exit;
}
?>