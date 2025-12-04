// assets/js/menu.js - FIXED VERSION
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
       Render Empty State
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
       Render Products
    =============================================================== */
    function renderProducts(items) {
        if (!items || !items.length) {
            renderEmptyState();
            return;
        }

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
        grid.style.opacity = v ? "0.4" : "1";
        
        // Disable inputs during loading
        if (searchInput) searchInput.disabled = v;
        if (sortSelect) sortSelect.disabled = v;
        if (searchBtn) searchBtn.disabled = v;
    }

    /* ===============================================================
       AJAX Fetch with proper error handling
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
                headers: { 
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                }
            });

            if (!res.ok) {
                throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            }

            const data = await res.json();

            if (data.success) {
                renderProducts(data.items);
            } else {
                renderEmptyState();
            }

        } catch (err) {
            console.error("AJAX Error:", err);
            grid.innerHTML = `
                <div class="menu-empty-state">
                    <div class="menu-empty-emoji">⚠️</div>
                    <div class="menu-empty-title">Something went wrong</div>
                    <div class="menu-empty-sub">
                        ${escapeHtml(err.message)}
                    </div>
                </div>
            `;
        } finally {
            setLoading(false);
        }
    }

    /* ===============================================================
       🔥 FIX: Category Click - Clear search when changing category
    =============================================================== */
    catScroll.addEventListener("click", e => {
        const card = e.target.closest(".cat-card");
        if (!card) return;
        
        e.preventDefault();
        
        // Update category
        currentCategory = card.dataset.category || "";
        categoryHidden.value = currentCategory;

        // 🔥 KEY FIX: Clear search input when changing category
        currentSearch = "";
        searchInput.value = "";

        // Update UI
        updateCategoryActive();
        
        // Fetch with cleared search
        ajaxFetch();
    });

    function updateCategoryActive() {
        catScroll.querySelectorAll(".cat-card").forEach(card => {
            card.classList.toggle("active", card.dataset.category === currentCategory);
        });
    }

    /* ===============================================================
       Sort Change
    =============================================================== */
    sortSelect.addEventListener("change", () => {
        currentSort = sortSelect.value || "default";
        ajaxFetch();
    });

    /* ===============================================================
       Search Input - Real-time search on typing (debounced)
    =============================================================== */
    let searchTimeout = null;
    searchInput.addEventListener("input", () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = searchInput.value.trim();
            ajaxFetch();
        }, 500); // Wait 500ms after user stops typing
    });

    /* ===============================================================
       Search Button Click
    =============================================================== */
    searchBtn.addEventListener("click", (e) => {
        e.preventDefault();
        currentSearch = searchInput.value.trim();
        ajaxFetch();
    });

    /* ===============================================================
       Form Submit (Enter key)
    =============================================================== */
    form.addEventListener("submit", e => {
        e.preventDefault();
        currentSearch = searchInput.value.trim();
        ajaxFetch();
    });

    /* ===============================================================
       🔥 POLLING - Update availability status
    =============================================================== */
    let pollingInterval = null;

    async function pollAvailability() {
        if (isLoading || document.hidden) return;

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

            if (!res.ok) return;

            const data = await res.json();
            if (!data.success || !data.items) return;

            data.items.forEach(row => {
                const card = document.querySelector(`.card[data-id='${row.ProductId}']`);
                if (!card) return;

                const isAvail = (parseInt(row.IsAvailable, 10) === 1);
                const wasAvail = !card.classList.contains("unavailable");
                
                if (isAvail === wasAvail) return;

                const overlay = card.querySelector(".unavailable-layer");

                if (isAvail) {
                    card.classList.remove("unavailable");
                    overlay?.classList.add("hidden-unavailable");
                    card.setAttribute("onclick", `location.href='product_detail.php?id=${row.ProductId}'`);
                    card.style.cursor = "pointer";
                } else {
                    card.classList.add("unavailable");
                    overlay?.classList.remove("hidden-unavailable");
                    card.removeAttribute("onclick");
                    card.style.cursor = "not-allowed";
                }
            });

        } catch (err) {
            console.warn("Polling error:", err);
        }
    }

    function startPolling() {
        if (pollingInterval) return;
        pollingInterval = setInterval(pollAvailability, 5000);
    }

    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }

    document.addEventListener("visibilitychange", () => {
        if (document.hidden) {
            stopPolling();
        } else {
            startPolling();
            pollAvailability();
        }
    });

    /* ===============================================================
       🔥 INIT - Don't call ajaxFetch, show PHP-rendered content
    =============================================================== */
    grid.style.opacity = "1";
    updateCategoryActive();
    startPolling();

    console.log("Menu initialized:", {
        stallId: cfg.stallId,
        search: currentSearch,
        category: currentCategory,
        sort: currentSort
    });

})();