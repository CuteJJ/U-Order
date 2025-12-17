/* =====================================================
   CONFIG
===================================================== */
const POLL_URL = "pages/check_availability.php";
const POLL_INTERVAL = 5000;

/* Store stall and product status */
const stallStatus = {};
const productStatus = {}; // NEW: Store product-specific status

/* Current selected category */
let currentCategoryId = "all";

/* Current search term */
let currentSearchTerm = "";

/* AJAX debounce */
let searchTimeout = null;

/* =====================================================
   AJAX SEARCH FUNCTIONALITY
===================================================== */
function setupAjaxSearch() {
  const searchForm = document.getElementById("searchForm");
  const searchInput = document.getElementById("searchInput");
  const searchBtn = document.querySelector(".search-btn");
  const loadingSpinner = document.getElementById("loadingSpinner");
  const stallsContainer = document.getElementById("stallsContainer");
  const topSection = document.querySelector(".section-products");
  const categoriesSection = document.querySelector(".section-categories");

  if (!searchForm || !searchInput) return;

  // Form submission
  searchForm.addEventListener("submit", function (e) {
    e.preventDefault();
    performSearch();
  });

  if (searchBtn) {
    searchBtn.addEventListener("click", function (e) {
      e.preventDefault();
      performSearch();
    });
  }

  async function performSearch() {
    const searchTerm = searchInput.value.trim();
    currentSearchTerm = searchTerm;

    // Show loading
    if (loadingSpinner) loadingSpinner.classList.add("active");
    if (stallsContainer) stallsContainer.style.opacity = "0.5";

    try {
      const url = new URL(window.location.href);
      url.searchParams.set("ajax", "search");
      url.searchParams.set("search", searchTerm);
      url.searchParams.set("category", currentCategoryId);

      const response = await fetch(url.toString());
      const data = await response.json();

      if (data.success) {
        // Update URL without page refresh
        const newUrl = searchTerm
          ? `${window.location.pathname}?search=${encodeURIComponent(searchTerm)}`
          : window.location.pathname;
        window.history.pushState({}, "", newUrl);

        // Hide/Show Top Sellers
        if (searchTerm) {
          if (topSection) topSection.style.display = "none";
        } else {
          if (topSection) topSection.style.display = "";
        }

        // Category always visible
        if (categoriesSection) categoriesSection.style.display = "";

        // Update content
        if (stallsContainer) {
          if (data.hasResults) {
            stallsContainer.innerHTML = data.html;

            // Add fade-in animation
            setTimeout(() => {
              document
                .querySelectorAll(".section-stalls")
                .forEach((section, index) => {
                  section.style.animation = "fadeInUp 0.4s ease forwards";
                  section.style.animationDelay = `${index * 0.1}s`;
                });
            }, 50);

            // Re-apply current availability status after search
            applyCurrentAvailabilityStatus();
          } else {
            // No results
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
      console.error("Search error:", error);
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
      // Hide loading
      if (loadingSpinner) loadingSpinner.classList.remove("active");
      if (stallsContainer) stallsContainer.style.opacity = "1";
    }
  }
}

/* =====================================================
   CLEAR SEARCH - RELOAD FULL PAGE
===================================================== */
function clearSearch() {
  // Simply reload the page to show everything including Top Sellers
  window.location.href = window.location.pathname;
}

/* =====================================================
   CATEGORY FILTER (WITH AJAX)
===================================================== */
function initCategoryFilter() {
  const categoryCards = document.querySelectorAll(".cat-card");

  if (!categoryCards.length) return;

  categoryCards.forEach((card) => {
    card.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation(); // Prevent event bubbling

      const categoryId = this.getAttribute("data-category-id");
      
      // Don't do anything if already active
      if (this.classList.contains("active") && currentCategoryId === categoryId) {
        return;
      }
      
      currentCategoryId = categoryId;

      categoryCards.forEach((c) => c.classList.remove("active"));
      this.classList.add("active");

      const searchInput = document.getElementById("searchInput");
      const searchTerm = searchInput ? searchInput.value.trim() : "";

      if (searchTerm === "") {
        // Local filter (no AJAX, no reload)
        filterProducts(categoryId);
      } else {
        // Use AJAX filter when searching
        performCategorySearch(categoryId, searchTerm);
      }
    });
  });

  // Default "All" highlighted
  const allCard = document.querySelector('.cat-card[data-category-id="all"]');
  if (allCard && !document.querySelector(".cat-card.active")) {
    allCard.classList.add("active");
  }
}

/* =====================================================
   CATEGORY SEARCH AJAX
===================================================== */
async function performCategorySearch(categoryId, searchTerm) {
  const loadingSpinner = document.getElementById("loadingSpinner");
  const stallsContainer = document.getElementById("stallsContainer");
  const topSection = document.querySelector(".section-products");

  if (loadingSpinner) loadingSpinner.classList.add("active");
  if (stallsContainer) stallsContainer.style.opacity = "0.5";

  try {
    const url = new URL(window.location.href);
    url.searchParams.set("ajax", "search");
    url.searchParams.set("search", searchTerm);
    url.searchParams.set("category", categoryId);

    const response = await fetch(url.toString());
    const data = await response.json();

    if (data.success && stallsContainer) {
      // Control Top Sellers visibility based on search term
      if (searchTerm) {
        if (topSection) topSection.style.display = "none";
      } else {
        if (topSection) topSection.style.display = "";
      }

      if (data.hasResults) {
        stallsContainer.innerHTML = data.html;

        // Animation
        setTimeout(() => {
          document
            .querySelectorAll(".section-stalls")
            .forEach((section, index) => {
              section.style.animation = "fadeInUp 0.4s ease forwards";
              section.style.animationDelay = `${index * 0.1}s`;
            });
        }, 50);

        // Re-apply current availability status
        applyCurrentAvailabilityStatus();
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
    console.error("Category search error:", error);
  } finally {
    if (loadingSpinner) loadingSpinner.classList.remove("active");
    if (stallsContainer) stallsContainer.style.opacity = "1";
  }
}

/* =====================================================
   LOCAL PRODUCT FILTER (NO SEARCH)
===================================================== */
function filterProducts(categoryId) {
  const stallSections = document.querySelectorAll(".section-stalls");
  const topSection = document.querySelector(".section-products");

  // Control Top Sellers
  if (categoryId === "all") {
    if (topSection) topSection.style.display = "";
  } else {
    if (topSection) topSection.style.display = "none";
  }

  // Filter Stalls
  stallSections.forEach((section) => {
    let visibleCount = 0;

    section.querySelectorAll(".product-card").forEach((card) => {
      const cid = card.getAttribute("data-category-id");
      const link = card.closest("a");
      const show = categoryId === "all" || cid === categoryId;

      card.style.display = show ? "" : "none";
      if (link) link.style.display = show ? "" : "none";

      if (show) visibleCount++;
    });

    section.style.display = visibleCount > 0 ? "" : "none";
  });

  // Animation
  requestAnimationFrame(() => {
    document.querySelectorAll(".product-card").forEach((card, index) => {
      const parent = card.closest("a") || card;
      if (parent.style.display !== "none" && card.style.display !== "none") {
        card.style.animation = "fadeInUp 0.35s ease forwards";
        card.style.animationDelay = `${index * 0.02}s`;
        setTimeout(() => {
          card.style.animation = "";
        }, 450);
      }
    });
  });
}

/* =====================================================
   UPDATE STALL STATUS
===================================================== */
function updateStallAvailability(stalls) {
  stalls.forEach((stall) => {
    const stallId = stall.StallId;
    const isOpen = stall.IsAvailable == 1;

    // Store status
    stallStatus[stallId] = isOpen;

    const sections = document.querySelectorAll(
      `.section-stalls[data-stall-id="${stallId}"]`
    );

    sections.forEach((section) => {
      if (!section) return;

      const header = section.querySelector(".section-header h3");
      const visitLink = section.querySelector(".see-all a");

      if (!header) return;

      if (!isOpen) {
        // Stall is CLOSED
        section.classList.add("stall-closed");
        
        // Add closed tag
        if (!header.querySelector(".closed-tag")) {
          const tag = document.createElement("span");
          tag.className = "closed-tag";
          tag.textContent = " (Closed)";
          header.appendChild(tag);
        }

        // Disable visit stall link
        if (visitLink) {
          visitLink.href = "javascript:void(0)";
          visitLink.style.pointerEvents = "none";
          visitLink.style.opacity = "0.5";
          visitLink.style.cursor = "not-allowed";
        }
      } else {
        // Stall is OPEN
        section.classList.remove("stall-closed");
        
        // Remove closed tag
        const tag = header.querySelector(".closed-tag");
        if (tag) tag.remove();

        // Enable visit stall link
        if (visitLink) {
          visitLink.href = `pages/menu.php?stallid=${stallId}`;
          visitLink.style.pointerEvents = "";
          visitLink.style.opacity = "";
          visitLink.style.cursor = "";
        }
      }
    });
  });
}

/* =====================================================
   UPDATE PRODUCT AVAILABILITY (INCLUDING STOCK)
===================================================== */
function updateProductAvailability(products) {
  products.forEach((prod) => {
    const productId = prod.ProductId;
    const productOpen = prod.IsAvailable == 1;
    const stock = parseInt(prod.Stock) || 0;
    const isUnlimitedStock = prod.IsUnlimitedStock == 1;

    // Store product status
    productStatus[productId] = {
      isAvailable: productOpen,
      stock: stock,
      isUnlimitedStock: isUnlimitedStock,
      stallId: prod.StallId
    };

    // Check stall status
    let stallOpen = true;
    if (prod.StallId && stallStatus.hasOwnProperty(prod.StallId)) {
      stallOpen = stallStatus[prod.StallId];
    }

    // Check if out of stock
    // Out of stock ONLY if: IsUnlimitedStock = 0 AND Stock <= 0
    const outOfStock = !isUnlimitedStock && stock <= 0;

    // Product is unavailable if:
    // 1. Product itself is unavailable, OR
    // 2. Stall is closed, OR
    // 3. Out of stock (IsUnlimitedStock = 0 AND Stock = 0)
    const unavailable = !productOpen || !stallOpen || outOfStock;

    const cards = document.querySelectorAll(
      `.product-card[data-product-id="${productId}"]`
    );

    cards.forEach((card) => {
      const overlay = card.querySelector(".unavailable-layer");
      const link = card.closest("a");

      if (unavailable) {
        // Make product unavailable
        card.classList.add("unavailable-card");
        if (overlay) {
          overlay.classList.remove("hidden-unavailable");
        }
        if (link) {
          link.href = "javascript:void(0)";
          link.style.pointerEvents = "none";
        }
      } else {
        // Make product available
        card.classList.remove("unavailable-card");
        if (overlay) {
          overlay.classList.add("hidden-unavailable");
        }
        if (link) {
          link.href = `pages/product_detail.php?id=${productId}`;
          link.style.pointerEvents = "";
        }
      }
    });
  });
}

/* =====================================================
   APPLY CURRENT AVAILABILITY STATUS
   (After AJAX content reload)
===================================================== */
function applyCurrentAvailabilityStatus() {
  // Re-apply stall status
  Object.keys(stallStatus).forEach((stallId) => {
    const isOpen = stallStatus[stallId];
    const sections = document.querySelectorAll(
      `.section-stalls[data-stall-id="${stallId}"]`
    );

    sections.forEach((section) => {
      const header = section.querySelector(".section-header h3");
      const visitLink = section.querySelector(".see-all a");
      
      if (!header) return;

      if (!isOpen) {
        // Stall is CLOSED
        section.classList.add("stall-closed");
        
        // Add closed tag
        if (!header.querySelector(".closed-tag")) {
          const tag = document.createElement("span");
          tag.className = "closed-tag";
          tag.textContent = " (Closed)";
          header.appendChild(tag);
        }

        // Disable visit stall link
        if (visitLink) {
          visitLink.href = "javascript:void(0)";
          visitLink.style.pointerEvents = "none";
          visitLink.style.opacity = "0.5";
          visitLink.style.cursor = "not-allowed";
        }
      } else {
        // Stall is OPEN
        section.classList.remove("stall-closed");
        
        // Remove closed tag
        const tag = header.querySelector(".closed-tag");
        if (tag) tag.remove();

        // Enable visit stall link
        if (visitLink) {
          visitLink.href = `pages/menu.php?stallid=${stallId}`;
          visitLink.style.pointerEvents = "";
          visitLink.style.opacity = "";
          visitLink.style.cursor = "";
        }
      }
    });
  });

  // Re-apply product status
  Object.keys(productStatus).forEach((productId) => {
    const prod = productStatus[productId];
    const stallOpen = prod.stallId ? (stallStatus[prod.stallId] || true) : true;
    
    // Check if out of stock
    const outOfStock = !prod.isUnlimitedStock && prod.stock <= 0;
    
    const unavailable = !prod.isAvailable || !stallOpen || outOfStock;

    const cards = document.querySelectorAll(
      `.product-card[data-product-id="${productId}"]`
    );

    cards.forEach((card) => {
      const overlay = card.querySelector(".unavailable-layer");
      const link = card.closest("a");

      if (unavailable) {
        card.classList.add("unavailable-card");
        if (overlay) overlay.classList.remove("hidden-unavailable");
        if (link) {
          link.href = "javascript:void(0)";
          link.style.pointerEvents = "none";
        }
      } else {
        card.classList.remove("unavailable-card");
        if (overlay) overlay.classList.add("hidden-unavailable");
        if (link) {
          link.href = `pages/product_detail.php?id=${productId}`;
          link.style.pointerEvents = "";
        }
      }
    });
  });
}

/* =====================================================
   POLLING
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
   CSS ANIMATIONS
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
   INITIALIZATION
===================================================== */
function initializeAll() {
  setupAjaxSearch();
  initCategoryFilter();
  pollAvailability();
  setInterval(pollAvailability, POLL_INTERVAL);
}

/* =====================================================
   DOM READY
===================================================== */
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializeAll);
} else {
  initializeAll();
}

/* =====================================================
   GLOBAL FUNCTIONS (FOR HTML)
===================================================== */
window.clearSearch = clearSearch;