/* path: assets/js/vendor_add_product.js */

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
    
    const productImageInput = document.getElementById('productImageInput');
    const imagePreviewGrid = document.getElementById('imagePreviewGrid');
    const uploadDropZone = document.querySelector('.upload-drop-zone'); // 用於顯示紅框

    // 錯誤訊息容器
    const errorName = document.getElementById('errorName');
    const errorCategory = document.getElementById('errorCategory');
    const errorNewCategory = document.getElementById('errorNewCategory');
    const errorPrice = document.getElementById('errorPrice');
    const errorStock = document.getElementById('errorStock');
    const errorImage = document.getElementById('errorImage');

    const addProductForm = document.getElementById('addProductForm');

    // 常數設定
    const MAX_IMAGES = 5;
    const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
    let dt = new DataTransfer();

    // ==========================================
    // 2. 輔助函式：顯示與清除錯誤
    // ==========================================
    function showError(inputElement, errorElement, message) {
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
        if (inputElement) {
            inputElement.classList.add('input-error');
            // 如果是輸入框，讓它晃動一下提醒用戶 (Optional UX)
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
    // 3. 互動邏輯 (分類 & 庫存)
    // ==========================================
    if (categorySelect) {
        categorySelect.addEventListener('change', function () {
            clearError(categorySelect, errorCategory); // 一選就清錯誤
            if (this.value === 'other') {
                newCategoryGroup.style.display = 'block';
            } else {
                newCategoryGroup.style.display = 'none';
                clearError(newCategoryInput, errorNewCategory); // 隱藏時順便清錯誤
            }
        });
    }

    if (isUnlimited) {
        const toggleStockField = function () {
            if (isUnlimited.checked) {
                stockFieldRow.style.opacity = 0.5;
                stockInput.value = '';
                stockInput.disabled = true;
                clearError(stockInput, errorStock); // 禁用時清錯誤
            } else {
                stockFieldRow.style.opacity = 1;
                stockInput.disabled = false;
            }
        };
        isUnlimited.addEventListener('change', toggleStockField);
        toggleStockField();
    }

    // ==========================================
    // 4. 即時清除錯誤 (使用者一打字就移除紅框)
    // ==========================================
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
    // 5. 圖片處理邏輯
    // ==========================================
    if (productImageInput && imagePreviewGrid) {
        productImageInput.addEventListener('change', function(e) {
            const files = this.files;
            let hasError = false;
            let errorMsg = '';

            // 清除之前的錯誤樣式
            clearError(uploadDropZone, errorImage);

            // 檢查總數
            if (dt.files.length + files.length > MAX_IMAGES) {
                hasError = true;
                errorMsg = `Maximum ${MAX_IMAGES} images allowed.`;
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
            renderPreviews();

            if (hasError) {
                showError(uploadDropZone, errorImage, errorMsg);
            }
        });
    }

    function renderPreviews() {
        imagePreviewGrid.innerHTML = '';
        const files = dt.files;

        if (files.length > 0) {
            imagePreviewGrid.style.display = 'grid';
            // 有圖片時，清除錯誤訊息
            clearError(uploadDropZone, errorImage);
        } else {
            imagePreviewGrid.style.display = 'none';
        }

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const reader = new FileReader();

            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'preview-item';
                
                const img = document.createElement('img');
                img.src = e.target.result;

                const btn = document.createElement('button');
                btn.className = 'remove-btn';
                btn.innerHTML = '×';
                btn.type = 'button';
                btn.onclick = function() { removeFile(i); };

                div.appendChild(img);
                div.appendChild(btn);
                imagePreviewGrid.appendChild(div);
            };
            reader.readAsDataURL(file);
        }
    }

    window.removeFile = function(index) {
        const newDt = new DataTransfer();
        const files = dt.files;
        for (let i = 0; i < files.length; i++) {
            if (i !== index) newDt.items.add(files[i]);
        }
        dt = newDt;
        productImageInput.files = dt.files;
        renderPreviews();
    };

    // ==========================================
    // 6. 表單提交全面驗證 (Final Check)
    // ==========================================
    if (addProductForm) {
        addProductForm.addEventListener('submit', function(e) {
            let isValid = true;
            let firstErrorInput = null; // 用來紀錄第一個錯誤，方便捲動視窗

            // --- 驗證 1: 圖片 ---
            if (dt.files.length === 0) {
                showError(uploadDropZone, errorImage, 'Please upload at least one image.');
                isValid = false;
                if (!firstErrorInput) firstErrorInput = uploadDropZone;
            }

            // --- 驗證 2: 產品名稱 ---
            if (!productNameInput.value.trim()) {
                showError(productNameInput, errorName, 'Product name is required.');
                isValid = false;
                if (!firstErrorInput) firstErrorInput = productNameInput;
            }

            // --- 驗證 3: 分類 ---
            if (!categorySelect.value) {
                showError(categorySelect, errorCategory, 'Please select a category.');
                isValid = false;
                if (!firstErrorInput) firstErrorInput = categorySelect;
            } else if (categorySelect.value === 'other') {
                // 如果選了 Other，檢查新分類名稱
                if (!newCategoryInput.value.trim()) {
                    showError(newCategoryInput, errorNewCategory, 'Please enter a name for the new category.');
                    isValid = false;
                    if (!firstErrorInput) firstErrorInput = newCategoryInput;
                }
            }

            // --- 驗證 4: 價格 ---
            // 檢查是否為空 或 負數
            if (!unitPriceInput.value || parseFloat(unitPriceInput.value) < 0) {
                showError(unitPriceInput, errorPrice, 'Please enter a valid price (e.g. 5.50).');
                isValid = false;
                if (!firstErrorInput) firstErrorInput = unitPriceInput;
            }

            // --- 驗證 5: 庫存 ---
            // 只有在 "Unlimited" 沒勾選時才檢查
            if (!isUnlimited.checked) {
                // 檢查是否為空，或是否為負數，或是否為非整數 (視需求，這裡假設整數)
                if (stockInput.value === '' || parseInt(stockInput.value) < 0) {
                    showError(stockInput, errorStock, 'Please enter a valid stock quantity.');
                    isValid = false;
                    if (!firstErrorInput) firstErrorInput = stockInput;
                }
            }

            // 如果有任何錯誤，阻止表單送出
            if (!isValid) {
                e.preventDefault();
                // 捲動到第一個錯誤欄位，體驗更好
                if (firstErrorInput) {
                    firstErrorInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // 如果是輸入框，自動 focus
                    if (firstErrorInput.tagName === 'INPUT' || firstErrorInput.tagName === 'TEXTAREA') {
                        firstErrorInput.focus({ preventScroll: true });
                    }
                }
            }
            // 如果 isValid 為 true，表單就會自然送出 (submit)
        });
    }
});