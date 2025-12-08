/* path: assets/js/vendor_edit_product.js */

document.addEventListener('DOMContentLoaded', function() {
    // ==========================================
    // 1. 定義 DOM 元素
    // ==========================================
    const categorySelect = document.getElementById('categorySelect');
    const newCategoryGroup = document.getElementById('new-category-group');
    const newCategoryInput = document.getElementById('newCategory');
    
    const isUnlimited = document.getElementById('isUnlimited');
    const stockFieldRow = document.getElementById('stockFieldRow');
    const stockInput = document.getElementById('stock');

    const productNameInput = document.getElementById('productName');
    const unitPriceInput = document.getElementById('unitPrice');
    
    // 圖片相關
    const productImageInput = document.getElementById('productImageInput');
    const imagePreviewGrid = document.getElementById('imagePreviewGrid');
    const uploadDropZone = document.querySelector('.upload-drop-zone');
    const deletedImageIdsInput = document.getElementById('deletedImageIds');

    // 錯誤訊息容器
    const errorName = document.getElementById('errorName');
    const errorCategory = document.getElementById('errorCategory');
    const errorNewCategory = document.getElementById('errorNewCategory');
    const errorPrice = document.getElementById('errorPrice');
    const errorStock = document.getElementById('errorStock');
    const errorImage = document.getElementById('errorImage');

    const editProductForm = document.getElementById('editProductForm');

    // 常數
    const MAX_IMAGES = 5;
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
    
    // 用來存放「新上傳」的檔案
    let dt = new DataTransfer();
    
    // 用來存放「被刪除的舊圖 ID」
    let deletedIds = [];

    // ==========================================
    // 2. 輔助函式 (顯示/清除錯誤)
    // ==========================================
    function showError(inputElement, errorElement, message) {
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
        if (inputElement) {
            inputElement.classList.add('input-error');
            inputElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function clearError(inputElement, errorElement) {
        if (errorElement) {
            errorElement.style.display = 'none';
            errorElement.textContent = '';
        }
        if (inputElement) {
            inputElement.classList.remove('input-error');
        }
    }

    // ==========================================
    // 3. 基礎互動 (分類 & 庫存)
    // ==========================================
    if (categorySelect) {
        categorySelect.addEventListener('change', function () {
            clearError(categorySelect, errorCategory);
            if (this.value === 'other') {
                newCategoryGroup.style.display = 'block';
            } else {
                newCategoryGroup.style.display = 'none';
                clearError(newCategoryInput, errorNewCategory);
            }
        });
        if (categorySelect.value === 'other') newCategoryGroup.style.display = 'block';
    }

    if (isUnlimited) {
        const toggleStockField = function () {
            if (isUnlimited.checked) {
                stockFieldRow.style.opacity = 0.5;
                stockInput.value = '';
                stockInput.disabled = true;
                clearError(stockInput, errorStock);
            } else {
                stockFieldRow.style.opacity = 1;
                stockInput.disabled = false;
            }
        };
        isUnlimited.addEventListener('change', toggleStockField);
        toggleStockField(); // 初始化
    }

    const inputsToWatch = [
        { input: productNameInput, error: errorName },
        { input: unitPriceInput, error: errorPrice },
        { input: stockInput, error: errorStock },
        { input: newCategoryInput, error: errorNewCategory }
    ];
    inputsToWatch.forEach(item => {
        if (item.input) {
            item.input.addEventListener('input', () => clearError(item.input, item.error));
        }
    });

    // ==========================================
    // 4. 圖片處理邏輯 (Edit 核心 - 修復版)
    // ==========================================

    // 4.1 處理「舊圖片」的刪除
    const existingRemoveBtns = document.querySelectorAll('.existing-item .remove-btn');
    existingRemoveBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const item = this.closest('.existing-item');
            const id = item.getAttribute('data-existing-id');
            
            if (id) {
                deletedIds.push(id);
                deletedImageIdsInput.value = deletedIds.join(',');
            }
            
            item.remove();
            checkGridVisibility(); // 更新顯示狀態
        });
    });

    // 4.2 處理「新圖片」的上傳
    if (productImageInput && imagePreviewGrid) {
        productImageInput.addEventListener('change', function(e) {
            const files = this.files;
            let hasError = false;
            let errorMsg = '';
            
            clearError(uploadDropZone, errorImage);

            const existingCount = document.querySelectorAll('.existing-item').length;
            const currentNewCount = dt.files.length;
            const addingCount = files.length;

            if (existingCount + currentNewCount + addingCount > MAX_IMAGES) {
                hasError = true;
                errorMsg = `Maximum ${MAX_IMAGES} images allowed (Existing + New).`;
            } else {
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    if (!ALLOWED_TYPES.includes(file.type)) {
                        hasError = true;
                        errorMsg = `Invalid file type: "${file.name}"`;
                        continue;
                    }
                    dt.items.add(file);
                }
            }

            this.files = dt.files;
            renderNewPreviews();

            if (hasError) {
                showError(uploadDropZone, errorImage, errorMsg);
            }
        });
    }

    function renderNewPreviews() {
        // 先移除所有舊的 "新預覽圖"
        document.querySelectorAll('.new-item').forEach(el => el.remove());

        const files = dt.files;
        
        // 關鍵修復：在圖片讀取前就先檢查並顯示 Grid
        // 這樣可以避免「讀取延遲」導致 Grid 沒被打開
        checkGridVisibility();

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const reader = new FileReader();

            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'preview-item new-item';

                const img = document.createElement('img');
                img.src = e.target.result;

                const btn = document.createElement('button');
                btn.className = 'remove-btn';
                btn.innerHTML = '×';
                btn.type = 'button';
                btn.onclick = function() { removeNewFile(i); };

                div.appendChild(img);
                div.appendChild(btn);
                imagePreviewGrid.appendChild(div);
            };
            reader.readAsDataURL(file);
        }
    }

    window.removeNewFile = function(index) {
        const newDt = new DataTransfer();
        const files = dt.files;
        for (let i = 0; i < files.length; i++) {
            if (i !== index) newDt.items.add(files[i]);
        }
        dt = newDt;
        productImageInput.files = dt.files;
        renderNewPreviews();
    };

    // 關鍵修復：這裡不只看 DOM 元素，還要看 dt.files
    function checkGridVisibility() {
        const existingCount = document.querySelectorAll('.existing-item').length;
        const newCount = dt.files.length; // 直接看內存裡的檔案數量

        if (existingCount + newCount > 0) {
            imagePreviewGrid.style.display = 'grid';
            clearError(uploadDropZone, errorImage);
        } else {
            imagePreviewGrid.style.display = 'none';
        }
    }

    // ==========================================
    // 5. 表單提交驗證
    // ==========================================
    if (editProductForm) {
        editProductForm.addEventListener('submit', function(e) {
            let isValid = true;
            let firstErrorInput = null;

            // 圖片驗證：舊圖數量 + 新圖數量
            const existingCount = document.querySelectorAll('.existing-item').length;
            const newCount = dt.files.length;
            
            if (existingCount + newCount === 0) {
                showError(uploadDropZone, errorImage, 'Please ensure at least one image remains.');
                isValid = false;
                if (!firstErrorInput) firstErrorInput = uploadDropZone;
            }

            if (!productNameInput.value.trim()) {
                showError(productNameInput, errorName, 'Product name is required.');
                isValid = false;
                if (!firstErrorInput) firstErrorInput = productNameInput;
            }

            if (!categorySelect.value) {
                showError(categorySelect, errorCategory, 'Please select a category.');
                isValid = false;
                if (!firstErrorInput) firstErrorInput = categorySelect;
            } else if (categorySelect.value === 'other') {
                if (!newCategoryInput.value.trim()) {
                    showError(newCategoryInput, errorNewCategory, 'Please enter a name for the new category.');
                    isValid = false;
                    if (!firstErrorInput) firstErrorInput = newCategoryInput;
                }
            }

            if (!unitPriceInput.value || parseFloat(unitPriceInput.value) < 0) {
                showError(unitPriceInput, errorPrice, 'Please enter a valid price.');
                isValid = false;
                if (!firstErrorInput) firstErrorInput = unitPriceInput;
            }

            if (!isUnlimited.checked) {
                if (stockInput.value === '' || parseInt(stockInput.value) < 0) {
                    showError(stockInput, errorStock, 'Please enter a valid stock quantity.');
                    isValid = false;
                    if (!firstErrorInput) firstErrorInput = stockInput;
                }
            }

            if (!isValid) {
                e.preventDefault();
                if (firstErrorInput) {
                    firstErrorInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (firstErrorInput.tagName === 'INPUT' || firstErrorInput.tagName === 'TEXTAREA') {
                        firstErrorInput.focus({ preventScroll: true });
                    }
                }
            }
        });
    }
});