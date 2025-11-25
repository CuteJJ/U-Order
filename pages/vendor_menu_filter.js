console.log("vendor_menu_filters.js 已加载");

const searchInput       = document.getElementById("searchInput");
const categoryFilter    = document.getElementById("categoryFilter");
const filterUnavailable = document.getElementById("filterUnavailable");
const filterLowStock    = document.getElementById("filterLowStock");

let filterState = {
    search: "",
    category: 0,
    unavailable: 0,
    low_stock: 0
};

/* =========================================================
   ⭐ 初次加载分类 (非常重要)
   ========================================================= */
function loadCategories() {
    fetch("vendor_menu_data.php?page=1")
        .then(res => res.json())
        .then(data => {
            const categories = data.categories || [];

            categoryFilter.innerHTML = `
                <option value="0">All Categories</option>
            `;

            categories.forEach(cat => {
                categoryFilter.innerHTML += `
                    <option value="${cat.CategoryId}">${cat.CategoryName}</option>
                `;
            });

        })
        .catch(err => console.error("加载分类失败:", err));
}

/* 首次执行加载分类 */
loadCategories();

/* =========================================================
   ⭐ 应用筛选并刷新菜单
   ========================================================= */
function reloadMenuWithFilters(page = 1) {

    const params = new URLSearchParams();

    params.append("page", page);

    if (filterState.search !== "") params.append("search", filterState.search);
    if (filterState.category > 0)  params.append("category", filterState.category);
    if (filterState.unavailable)   params.append("unavailable", 1);
    if (filterState.low_stock)     params.append("low_stock", 1);

    const finalURL = "vendor_menu_data.php?" + params.toString();

    console.log("Filter 请求 URL:", finalURL);

    fetch(finalURL)
        .then(res => res.json())
        .then(data => {
            renderProducts(data.products || []);
            renderPagination(data.page || 1, data.totalPages || 1);
        })
        .catch(err => console.error("Filter AJAX 出错:", err));
}

/* =========================================================
   监听输入框 - 实时搜索
   ========================================================= */
searchInput.addEventListener("input", () => {
    filterState.search = searchInput.value.trim();
    reloadMenuWithFilters(1);
});

/* =========================================================
   监听分类选择
   ========================================================= */
categoryFilter.addEventListener("change", () => {
    filterState.category = parseInt(categoryFilter.value, 10) || 0;
    reloadMenuWithFilters(1);
});

/* =========================================================
   监听 Unavailable
   ========================================================= */
filterUnavailable.addEventListener("change", () => {
    filterState.unavailable = filterUnavailable.checked ? 1 : 0;
    reloadMenuWithFilters(1);
});

/* =========================================================
   监听 Low Stock
   ========================================================= */
filterLowStock.addEventListener("change", () => {
    filterState.low_stock = filterLowStock.checked ? 1 : 0;
    reloadMenuWithFilters(1);
});
