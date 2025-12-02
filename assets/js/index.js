/* =====================================================
   CONFIG
===================================================== */
const POLL_URL = "pages/check_availability.php";
const POLL_INTERVAL = 6000;

/* 保存每个 stall 当前是否 open（给产品用） */
const stallStatus = {};

/* 当前选中的分类 */
let currentCategoryId = "all";

/* 当前搜索词 */
let currentSearchTerm = "";

/* AJAX 防抖 */
let searchTimeout = null;

/* =====================================================
   AJAX 搜索功能
===================================================== */
function setupAjaxSearch() {
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.querySelector('.search-btn'); // ⭐新增 click 搜索   
    const loadingSpinner = document.getElementById('loadingSpinner');
    const stallsContainer = document.getElementById('stallsContainer');
    const topSection = document.querySelector('.section-products');
    const categoriesSection = document.querySelector('.section-categories');

    if (!searchForm || !searchInput) return;

    // 表单提交
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        performSearch();
    });

if (searchBtn) {
    searchBtn.addEventListener('click', function(e) {
        e.preventDefault();
        performSearch();
    });
}

    

    async function performSearch() {
        const searchTerm = searchInput.value.trim();
        currentSearchTerm = searchTerm;

        // 显示加载
        if (loadingSpinner) loadingSpinner.classList.add('active');
        if (stallsContainer) stallsContainer.style.opacity = '0.5';

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('ajax', 'search');
            url.searchParams.set('search', searchTerm);
            url.searchParams.set('category', currentCategoryId);

            const response = await fetch(url.toString());
            const data = await response.json();

            if (data.success) {
                // 更新 URL 但不刷新页面
                const newUrl = searchTerm 
                    ? `${window.location.pathname}?search=${encodeURIComponent(searchTerm)}`
                    : window.location.pathname;
                window.history.pushState({}, '', newUrl);

                // 隐藏/显示 Top Sellers 和 Categories
                if (searchTerm) {
                    if (topSection) topSection.style.display = 'none';
                    if (categoriesSection) categoriesSection.style.display = 'none';
                } else {
                    if (topSection) topSection.style.display = '';
                    if (categoriesSection) categoriesSection.style.display = '';
                }

                // 更新内容
                if (stallsContainer) {
                    if (data.hasResults) {
                        stallsContainer.innerHTML = data.html;
                        
                        // 添加淡入动画
                        setTimeout(() => {
                            document.querySelectorAll('.section-stalls').forEach((section, index) => {
                                section.style.animation = 'fadeInUp 0.4s ease forwards';
                                section.style.animationDelay = `${index * 0.1}s`;
                            });
                        }, 50);
                    } else {
                        // 没有结果
                        stallsContainer.innerHTML = `
                            <div class="no-results-box">
                                <div class="emoji">🥺🔍</div>
                                <h2>No results found</h2>
                                <p>Try another keyword?</p>
                                <a href="#" class="clear-btn" onclick="clearSearch(); return false;">Clear Search</a>
                            </div>
                        `;
                    }
                }
            }

        } catch (error) {
            console.error('Search error:', error);
            if (stallsContainer) {
                stallsContainer.innerHTML = `
                    <div class="no-results-box">
                        <div class="emoji">⚠️</div>
                        <h2>Oops! Something went wrong</h2>
                        <p>Please try again</p>
                    </div>
                `;
            }
        } finally {
            // 隐藏加载
            if (loadingSpinner) loadingSpinner.classList.remove('active');
            if (stallsContainer) stallsContainer.style.opacity = '1';
        }
    }
}

/* =====================================================
   清除搜索
===================================================== */
function clearSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        currentSearchTerm = '';
    }
    
    // 重载页面或执行搜索
    window.location.href = window.location.pathname;
}

/* =====================================================
   分类筛选功能（带 AJAX）
===================================================== */
function initCategoryFilter() {
    const categoryCards = document.querySelectorAll(".cat-card");
    
    if (!categoryCards.length) return;

    categoryCards.forEach(card => {
        card.addEventListener("click", async function () {
            const categoryId = this.getAttribute("data-category-id");
            currentCategoryId = categoryId;

            // 更新激活状态
            categoryCards.forEach(c => c.classList.remove("active"));
            this.classList.add("active");

            // 如果有搜索词，执行 AJAX 搜索
            const searchInput = document.getElementById('searchInput');
            if (searchInput && searchInput.value.trim()) {
                await performCategorySearch(categoryId, searchInput.value.trim());
            } else {
                // 本地筛选
                filterProducts(categoryId);
            }

            // 动画
            this.style.animation = "pulse 0.4s ease";
            setTimeout(() => {
                this.style.animation = "";
            }, 400);
        });
    });

    // 默认 All 高亮
    const allCard = document.querySelector('.cat-card[data-category-id="all"]');
    if (allCard && !document.querySelector('.cat-card.active')) {
        allCard.classList.add("active");
    }
}

/* =====================================================
   分类搜索 AJAX
===================================================== */
async function performCategorySearch(categoryId, searchTerm) {
    const loadingSpinner = document.getElementById('loadingSpinner');
    const stallsContainer = document.getElementById('stallsContainer');

    if (loadingSpinner) loadingSpinner.classList.add('active');
    if (stallsContainer) stallsContainer.style.opacity = '0.5';

    try {
        const url = new URL(window.location.href);
        url.searchParams.set('ajax', 'search');
        url.searchParams.set('search', searchTerm);
        url.searchParams.set('category', categoryId);

        const response = await fetch(url.toString());
        const data = await response.json();

        if (data.success && stallsContainer) {
            if (data.hasResults) {
                stallsContainer.innerHTML = data.html;
                
                // 动画
                setTimeout(() => {
                    document.querySelectorAll('.section-stalls').forEach((section, index) => {
                        section.style.animation = 'fadeInUp 0.4s ease forwards';
                        section.style.animationDelay = `${index * 0.1}s`;
                    });
                }, 50);
            } else {
                stallsContainer.innerHTML = `
                    <div class="no-results-box">
                        <div class="emoji">🥺🔍</div>
                        <h2>No results found</h2>
                        <p>Try another category or keyword</p>
                        <a href="#" class="clear-btn" onclick="clearSearch(); return false;">Clear Search</a>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Category search error:', error);
    } finally {
        if (loadingSpinner) loadingSpinner.classList.remove('active');
        if (stallsContainer) stallsContainer.style.opacity = '1';
    }
}

/* =====================================================
   本地筛选商品（无搜索时）
===================================================== */
function filterProducts(categoryId) {
    const stallSections = document.querySelectorAll('.section-stalls');
    const topSection = document.querySelector('.section-products');

    // 控制 Top Sellers
    if (categoryId === 'all') {
        if (topSection) topSection.style.display = '';
    } else {
        if (topSection) topSection.style.display = 'none';
    }

    // 筛选 Stalls
    stallSections.forEach(section => {
        let visibleCount = 0;

        section.querySelectorAll('.product-card').forEach(card => {
            const cid = card.getAttribute('data-category-id');
            const link = card.closest('a');
            const show = (categoryId === 'all') || (cid === categoryId);

            card.style.display = show ? '' : 'none';
            if (link) link.style.display = show ? '' : 'none';

            if (show) visibleCount++;
        });

        section.style.display = visibleCount > 0 ? '' : 'none';
    });

    // 动画
    requestAnimationFrame(() => {
        document.querySelectorAll('.product-card').forEach((card, index) => {
            const parent = card.closest('a') || card;
            if (parent.style.display !== 'none' && card.style.display !== 'none') {
                card.style.animation = 'fadeInUp 0.35s ease forwards';
                card.style.animationDelay = `${index * 0.02}s`;
                setTimeout(() => { card.style.animation = ''; }, 450);
            }
        });
    });
}

/* =====================================================
   更新档口 (stall) 状态
===================================================== */
function updateStallAvailability(stalls) {
    stalls.forEach(stall => {
        const stallId = stall.StallId;
        const isOpen = stall.IsAvailable == 1;

        stallStatus[stallId] = isOpen;

        const section = document.querySelector(
            `.section-stalls[data-stall-id="${stallId}"]`
        );
        if (!section) return;

        const header = section.querySelector(".section-header h3");
        if (!header) return;

        if (!isOpen) {
            section.classList.add("stall-closed");
            if (!header.querySelector(".closed-tag")) {
                const tag = document.createElement("span");
                tag.className = "closed-tag";
                tag.textContent = " (Closed)";
                header.appendChild(tag);
            }
        } else {
            section.classList.remove("stall-closed");
            const tag = header.querySelector(".closed-tag");
            if (tag) tag.remove();
        }
    });
}

/* =====================================================
   更新商品状态
===================================================== */
function updateProductAvailability(products) {
    products.forEach(prod => {
        const productId = prod.ProductId;
        const productOpen = prod.IsAvailable == 1;

        let stallOpen = true;
        if (prod.StallId && stallStatus.hasOwnProperty(prod.StallId)) {
            stallOpen = stallStatus[prod.StallId];
        }

        const unavailable = !(productOpen && stallOpen);

        const cards = document.querySelectorAll(
            `.product-card[data-product-id="${productId}"]`
        );

        cards.forEach(card => {
            const overlay = card.querySelector(".unavailable-layer");
            const link = card.closest("a");

            if (unavailable) {
                card.classList.add("unavailable-card");
                if (overlay) overlay.classList.remove("hidden-unavailable");
                if (link) link.href = "javascript:void(0)";
            } else {
                card.classList.remove("unavailable-card");
                if (overlay) overlay.classList.add("hidden-unavailable");
                if (link) link.href = `pages/product_detail.php?id=${productId}`;
            }
        });
    });
}

/* =====================================================
   Polling
===================================================== */
let isPolling = false;

async function pollAvailability() {
    if (isPolling) return;
    isPolling = true;

    try {
        const res = await fetch(POLL_URL, { cache: "no-store" });
        const data = await res.json();

        if (data.success) {
            if (Array.isArray(data.stalls)) {
                updateStallAvailability(data.stalls);
            }
            if (Array.isArray(data.products)) {
                updateProductAvailability(data.products);
            }
        }
    } catch (err) {
        console.warn("[POLL ERROR]", err);
    }

    isPolling = false;
}

/* =====================================================
   CSS 动画
===================================================== */
const style = document.createElement("style");
style.textContent = `
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }
`;
document.head.appendChild(style);

/* =====================================================
   初始化
===================================================== */
function initializeAll() {
    setupAjaxSearch();
    initCategoryFilter();
    pollAvailability();
    setInterval(pollAvailability, POLL_INTERVAL);
}

/* =====================================================
   DOM Ready
===================================================== */
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeAll);
} else {
    initializeAll();
}

/* =====================================================
   全局函数（供 HTML 调用）
===================================================== */
window.clearSearch = clearSearch;