console.log("vendor_menu.js 已加载");

// 当前页
let currentPage = 1;

document.addEventListener("DOMContentLoaded", () => {
    loadMenu(1);
});

/* =========================================================
   🔥 Toast 右上角提示
   ========================================================= */
function showToast(message, type = "success") {
    const toast = document.getElementById("toast");
    if (!toast) return;

    toast.textContent = message;
    toast.className = "toast show " + type;

    setTimeout(() => {
        toast.className = "toast"; // 移除 show
    }, 2200);
}

/* =========================================================
   1. AJAX 加载产品 + 分页
   ========================================================= */
function loadMenu(page = 1) {
    currentPage = page;

    // ⭐ 新增：安全获取过滤器 DOM 元素
    const searchInput   = document.getElementById("searchInput");
    const categorySel   = document.getElementById("categoryFilter");
    const unavailableCb = document.getElementById("filterUnavailable");
    const lowStockCb    = document.getElementById("filterLowStock");

    // ⭐ 新增：从输入框 / 下拉 / checkbox 读出当前过滤条件
    const search      = searchInput   ? searchInput.value.trim() : "";
    const category    = categorySel   ? categorySel.value : 0;
    const unavailable = unavailableCb && unavailableCb.checked ? 1 : 0;
    const lowStock    = lowStockCb    && lowStockCb.checked    ? 1 : 0;

    // ⭐ 新增：用 URLSearchParams 组合查询字符串，避免手动拼接出错
    const params = new URLSearchParams();
    params.set("page", page);
    params.set("search", search);
    params.set("category", category);
    params.set("unavailable", unavailable);
    params.set("lowStock", lowStock);

    // 之前：fetch("vendor_menu_data.php?page=" + page)
    // 现在：把所有过滤参数带上
    fetch("vendor_menu_data.php?" + params.toString())
        .then(res => res.json())
        .then(data => {
            renderProducts(data.products || []);
            renderPagination(data.page || 1, data.totalPages || 1);
        })
        .catch(err => console.error("加载失败:", err));
}

/* =========================================================
   2. 渲染产品卡
   ========================================================= */
function renderProducts(products) {
    const container = document.getElementById("menu-container");
    container.innerHTML = "";

    if (!products.length) {
        container.innerHTML = "<p style='color:#666;'>No products found.</p>";
        return;
    }

    products.forEach(p => {
        const img         = p.ImageURL || "https://via.placeholder.com/120x100?text=No+Image";
        const isAvailable = parseInt(p.IsAvailable, 10) === 1;
        const isUnlimited = parseInt(p.IsUnlimitedStock, 10) === 1;
        const stock       = parseInt(p.Stock, 10);
        const price       = parseFloat(p.UnitPrice || 0).toFixed(2);
        const category    = p.CategoryName || "-";

        const card = document.createElement("article");
        card.className = "item";

        card.innerHTML = `
            <img class="item__img" src="${img}" alt="">
            <div>
                <h4>${p.ProductName}</h4>
                <div class="price">RM ${price}</div>

                <div class="status-row">
                    <span class="tag ${isAvailable ? "tag--available" : "tag--unavailable"}">
                        ${isAvailable ? "Available" : "Unavailable"}
                    </span>

                    <span class="tag ${isUnlimited ? "tag--unlimited" : "tag--limited"}">
                       ${isUnlimited ? "Unlimited" : `Stock ${stock}`}
                    </span>

                    <span class="tag">${category}</span>
                </div>

                <div class="controls">

                    <!-- 上下架 -->
                    <button
                        class="ctrl btn-toggle-status"
                        data-product-id="${p.ProductId}"
                        data-new-status="${isAvailable ? 0 : 1}">
                        ${isAvailable ? "Set Unavailable" : "Set Available"}
                    </button>

                    <!-- 修改库存 -->
                    <label class="stock">
                        <span class="small">Stock</span>
                        <input
                            type="number"
                            class="stock-input"
                            min="0"
                            value="${stock}"
                            data-product-id="${p.ProductId}"
                            data-original-stock="${stock}"
                            ${isUnlimited ? "disabled" : ""} />
                    </label>

                    <button
                        class="ctrl btn-update-stock"
                        data-product-id="${p.ProductId}"
                        ${isUnlimited ? "disabled" : ""}>
                        Update
                    </button>

                    <a class="ctrl" href="edit_product.php?id=${p.ProductId}">Edit</a>
                </div>
            </div>
        `;

        container.appendChild(card);
    });

    bindProductActions();
}

/* =========================================================
   3. 绑定事件
   ========================================================= */
function bindProductActions() {

    // 上/下架
    document.querySelectorAll(".btn-toggle-status").forEach(btn => {
        btn.addEventListener("click", () => {
            toggleProductStatus(btn.dataset.productId, parseInt(btn.dataset.newStatus));
        });
    });

    // 更新库存
    document.querySelectorAll(".btn-update-stock").forEach(btn => {
        btn.addEventListener("click", () => {

            const id = btn.dataset.productId;
            const input = document.querySelector(`.stock-input[data-product-id="${id}"]`);
            if (!input) return;

            const rawValue = input.value.trim();   // 保留用户真实输入

           
            if (!/^[0-9]+$/.test(rawValue)) {
                showToast("Invalid stock value. Numbers only.", "error");
                return;
            }

            const newStock = parseInt(rawValue, 10);

            updateProductStock(id, newStock, input);
        });
    });
}

/* =========================================================
   4. 上架 / 下架
   ========================================================= */
function toggleProductStatus(productId, newStatus) {
    const formData = new FormData();
    formData.append("product_id", productId);
    formData.append("status", newStatus);

    fetch("vendor_update_product_status.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast("Product status updated", "success");
                loadMenu(currentPage);
            } else {
                showToast("Failed: " + (data.message || ""), "error");
            }
        })
        .catch(() => showToast("Server error updating status", "error"));
}

/* =========================================================
   5. 更新库存
   ========================================================= */
function updateProductStock(productId, newStock, inputElement) {

    const formData = new FormData();
    formData.append("product_id", productId);
    formData.append("stock", newStock);

    fetch("vendor_update_product_stock.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast("Stock updated successfully", "success");

                if (inputElement) {
                    inputElement.dataset.originalStock = newStock;
                }

                loadMenu(currentPage);
            } else {
                showToast("Failed: " + (data.message || ""), "error");
            }
        })
        .catch(() => showToast("Server error updating stock", "error"));
}

/* =========================================================
   6. 分页
   ========================================================= */
function renderPagination(page, totalPages) {
    const container = document.getElementById("pagination");
    container.innerHTML = "";

    if (totalPages <= 1) return;

    if (page > 1) {
        const prev = document.createElement("a");
        prev.href = "#";
        prev.textContent = "Prev";
        prev.onclick = e => { e.preventDefault(); loadMenu(page - 1); };
        container.appendChild(prev);
    }

    for (let i = 1; i <= totalPages; i++) {
        const link = document.createElement("a");
        link.href = "#";
        link.textContent = i;

        if (i === page) link.style.fontWeight = "700";

        link.onclick = e => { e.preventDefault(); loadMenu(i); };

        container.appendChild(link);
    }

    if (page < totalPages) {
        const next = document.createElement("a");
        next.href = "#";
        next.textContent = "Next";
        next.onclick = e => { e.preventDefault(); loadMenu(page + 1); };
        container.appendChild(next);
    }
}
