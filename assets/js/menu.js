// assets/js/menu.js
(function () {
    if (typeof window.MENU_CONFIG === "undefined") return;

    const cfg = window.MENU_CONFIG;

    const grid           = document.getElementById("menu-grid");
    const form           = document.getElementById("menu-filter-form");
    const searchInput    = document.getElementById("search-input");
    const sortSelect     = document.getElementById("sort-select");
    const categoryHidden = document.getElementById("category-hidden");
    const catScroll      = document.getElementById("category-scroll");
    const searchBtn      = document.getElementById("search-btn");

    if (!grid || !form || !searchInput || !sortSelect || !categoryHidden || !catScroll) {
        return;
    }

    let currentSearch   = cfg.search || "";
    let currentCategory = cfg.category || "";
    let currentSort     = cfg.sort || "default";
    let isLoading       = false;

    function escapeHtml(str) {
        if (str === null || str === undefined) return "";
        return String(str)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    /* ===============================================================
       渲染空状态
    =============================================================== */
    function renderEmptyState() {
        grid.innerHTML = `
            <div class="menu-empty-state">
                <div class="menu-empty-emoji">(っ °Д °;)っ</div>
                <div class="menu-empty-title">No results found</div>
                <div class="menu-empty-sub">
                    Try searching something else or choosing another category.
                </div>
            </div>
        `;
    }

    /* ===============================================================
       渲染 Products
    =============================================================== */
    function renderProducts(items) {
        if (!items || !items.length) return renderEmptyState();

        const html = items.map(p => {
            const id       = p.ProductId;
            const name     = escapeHtml(p.ProductName);
            const desc     = escapeHtml(p.Description || "");
            const price    = Number(p.UnitPrice || 0).toFixed(2);
            const img      = p.ImageURL || "../assets/images/products/placeholder_food.png";
            const isAvail  = (parseInt(p.IsAvailable, 10) === 1);

            return `
            <div class="card ${isAvail ? "" : "unavailable"}" data-id="${id}"
                 ${isAvail ? `onclick="window.location.href='product_detail.php?id=${id}'"` : ""}>
                
                <div class="card-img" style="background-image:url('${img}')">
                    <div class="unavailable-layer ${isAvail ? "hidden-unavailable" : ""}">
                        <img src="../assets/images/unavailable.png">
                    </div>
                </div>

                <div class="card-body">
                    <div class="card-title">${name}</div>
                    <div class="card-desc">${desc}</div>
                    <div class="price-tag">RM ${price}</div>
                </div>
            </div>`;
        }).join("");

        grid.innerHTML = html;
    }

    function setLoading(v) {
        isLoading = v;
        grid.style.opacity = v ? "0.5" : "1";
    }

    /* ===============================================================
       AJAX 请求 menu.php?ajax=1
    =============================================================== */
    async function ajaxFetch() {
        if (isLoading) return;
        setLoading(true);

        const params = new URLSearchParams({
            ajax: 1,
            stallid: cfg.stallId,
            search: currentSearch,
            category: currentCategory,
            sort: currentSort
        });

        try {
            const res = await fetch(`menu.php?${params.toString()}`, {
                method: "GET",
                cache: "no-store",
                headers: { "Accept": "application/json" }
            });

            const data = await res.json();

            if (data.success) {
                renderProducts(data.items);
            } else {
                renderEmptyState();
            }

        } catch (err) {
            console.warn("AJAX Error:", err);
            renderEmptyState();
        } finally {
            setLoading(false);
             if (grid.style.opacity === "0") {
        requestAnimationFrame(() => {
            grid.style.opacity = "1";
        });
    }
        }
    }

    /* ===============================================================
       Category 点击 → AJAX
    =============================================================== */
    catScroll.addEventListener("click", e => {
        const card = e.target.closest(".cat-card");
        if (!card) return;
        
        e.preventDefault();
        currentCategory = card.dataset.category || "";
        categoryHidden.value = currentCategory;

        updateCategoryActive();
       ajaxFetch();

    });

    function updateCategoryActive() {
        catScroll.querySelectorAll(".cat-card").forEach(card => {
            card.classList.toggle("active", card.dataset.category === currentCategory);
        });
    }

    /* ===============================================================
       Sort → AJAX
    =============================================================== */
    sortSelect.addEventListener("change", () => {
        currentSort = sortSelect.value || "default";
        ajaxFetch();
    });

    /* ===============================================================
       Search Button 点击 → AJAX
    =============================================================== */
    searchBtn.addEventListener("click", () => {
        currentSearch = searchInput.value.trim();
        ajaxFetch();
    });

    /* ===============================================================
       Search Enter → AJAX
    =============================================================== */
    form.addEventListener("submit", e => {
        e.preventDefault();
        currentSearch = searchInput.value.trim();
        ajaxFetch();
    });

    /* ===============================================================
       🔥 POLLING（每 3 秒更新 avail）
    =============================================================== */
    async function pollAvailability() {
        const params = new URLSearchParams({
            stallid: cfg.stallId,
            search: currentSearch,
            category: currentCategory
        });

        try {
            const res = await fetch(`menu_polling.php?${params.toString()}`, {
                cache: "no-store",
                headers: { "Accept": "application/json" }
            });

            const data = await res.json();
            if (!data.success) return;

            data.items.forEach(row => {
                const card = document.querySelector(`.card[data-id='${row.ProductId}']`);
                if (!card) return;

                const isAvail = (parseInt(row.IsAvailable, 10) === 1);
                const overlay = card.querySelector(".unavailable-layer");

                if (isAvail) {
                    card.classList.remove("unavailable");
                    overlay?.classList.add("hidden-unavailable");
                    card.setAttribute("onclick", `location.href='product_detail.php?id=${row.ProductId}'`);
                } else {
                    card.classList.add("unavailable");
                    overlay?.classList.remove("hidden-unavailable");
                    card.removeAttribute("onclick");
                }
            });

        } catch (err) {
            console.warn("Polling error:", err);
        }
    }

    setInterval(pollAvailability, 3000);

    // 初始化
    updateCategoryActive();
    ajaxFetch();

})();
