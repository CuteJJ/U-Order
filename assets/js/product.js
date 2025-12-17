// /assets/js/product.js
document.addEventListener("DOMContentLoaded", () => {
    // =========================
    // DOM References
    // =========================
    const hiddenQty  = document.getElementById("final-qty");
    const hiddenTime = document.getElementById("final-time");
    const hiddenNote = document.getElementById("final-note");
    const unitPriceEl = document.getElementById("unit-price");
    const productIdInput = document.querySelector('[name="product_id"]');
    const orderForm = document.getElementById("order-form");
    
    if (!hiddenQty || !hiddenTime || !hiddenNote || !unitPriceEl || !productIdInput || !orderForm) {
        console.warn("product.js: required elements missing, abort init.");
        return;
    }
    
    const unitPrice = parseFloat(unitPriceEl.value) || 0;
    const productId = productIdInput.value;
    
    const inlineErrDesktop = document.getElementById("inline-error");
    const inlineErrMobile  = document.getElementById("inline-error-mobile");
    const qtyDisplays   = document.querySelectorAll(".qty-display");
    const displayTotals = document.querySelectorAll(".display-total");
    const submitButtons = document.querySelectorAll(".submit-order-btn");
    
    // =========================
    // Modal Popup
    // =========================
    const modal      = document.getElementById("error-modal");
    const modalCard  = document.getElementById("modal-card");
    const modalMsg   = document.getElementById("error-modal-message");
    const modalOk    = document.getElementById("error-modal-ok");
    
    function showModal(msg) {
        if (!modal || !modalCard || !modalMsg || !modalOk) return;
        modalMsg.textContent = msg || "Error";
        modal.style.display = "flex";
        requestAnimationFrame(() => {
            modal.classList.add("active");
            modalCard.classList.add("pop");
        });
        modalOk.onclick = () => {
            modalCard.classList.remove("pop");
            modal.classList.remove("active");
            setTimeout(() => {
                modal.style.display = "none";
            }, 200);
        };
    }
    
    // =========================
    // Inline Error Handling
    // =========================
    function setInlineError(msg) {
        if (inlineErrDesktop) {
            inlineErrDesktop.style.display = msg ? "block" : "none";
            inlineErrDesktop.textContent = msg || "";
        }
        if (inlineErrMobile) {
            inlineErrMobile.style.display = msg ? "block" : "none";
            inlineErrMobile.textContent = msg || "";
        }
    }
    
    function clearInlineError() {
        setInlineError("");
    }
    
    function setButtonsDisabled(disabled) {
        submitButtons.forEach(btn => {
            if (!btn) return;
            btn.disabled = disabled;
            if (disabled) {
                btn.classList.add("disabled");
            } else {
                btn.classList.remove("disabled");
            }
        });
    }
    
    // =========================
    // Quantity & Price
    // =========================
    let currentQty = parseInt(hiddenQty.value, 10) || 1;
    let serverStock = null; // Stock from server
    let isUnlimitedStock = false; // Whether product has unlimited stock
    
    function refreshQtyDisplays() {
        qtyDisplays.forEach(el => {
            if (el) el.textContent = currentQty;
        });
        hiddenQty.value = currentQty;
    }
    
    function refreshTotal() {
        const total = (currentQty * unitPrice).toFixed(2);
        displayTotals.forEach(el => {
            if (el) el.textContent = total;
        });
    }
    
    function changeQty(delta) {
        let nextQty = currentQty + delta;
        if (nextQty < 1) nextQty = 1;
        
        // Only check stock limit if NOT unlimited stock
        if (!isUnlimitedStock && serverStock !== null) {
            if (serverStock <= 0) {
                // Stock is 0 or negative, prevent any quantity increase
                setInlineError("Out of stock.");
                return; // Don't change quantity
            } else if (nextQty > serverStock) {
                // Quantity exceeds available stock
                setInlineError(`Only ${serverStock} left in stock.`);
                nextQty = serverStock;
            } else {
                // Quantity is within range
                clearInlineError();
            }
        } else {
            // Unlimited stock or no stock check needed
            clearInlineError();
        }
        
        currentQty = nextQty;
        refreshQtyDisplays();
        refreshTotal();
    }
    
    document.querySelectorAll(".qty-minus").forEach(btn => {
        btn.addEventListener("click", () => {
            changeQty(-1);
        });
    });
    
    document.querySelectorAll(".qty-plus").forEach(btn => {
        btn.addEventListener("click", () => {
            changeQty(1);
        });
    });
    
    // Initialize display
    refreshQtyDisplays();
    refreshTotal();
    
    // =========================
    // Pickup Time Selection
    // =========================
    const timePills = document.querySelectorAll(".time-pill");
    timePills.forEach(pill => {
        pill.addEventListener("click", () => {
            const val = pill.dataset.time || "";
            timePills.forEach(x => x.classList.remove("selected"));
            pill.classList.add("selected");
            hiddenTime.value = val;
        });
    });
    
    // =========================
    // Note Sync (Desktop + Mobile)
    // =========================
    const noteInputs = document.querySelectorAll(".note-input");
    noteInputs.forEach(input => {
        input.addEventListener("input", () => {
            const val = input.value;
            noteInputs.forEach(x => {
                if (x !== input) x.value = val;
            });
            hiddenNote.value = val;
        });
    });
    
    // =========================
    // Mobile Bottom Sheet
    // =========================
    const sheet       = document.querySelector(".bottom-sheet");
    const sheetOverlay = document.querySelector(".sheet-overlay");
    const triggerSheetBtn = document.getElementById("trigger-sheet-btn");
    const closeSheetBtn   = document.querySelector(".close-sheet");
    
    function openSheet() {
        if (!sheet || !sheetOverlay) return;
        sheetOverlay.style.display = "block";
        sheet.style.display = "block";
        requestAnimationFrame(() => {
            sheetOverlay.classList.add("active");
            sheet.classList.add("active");
        });
    }
    
    function closeSheet() {
        if (!sheet || !sheetOverlay) return;
        sheetOverlay.classList.remove("active");
        sheet.classList.remove("active");
        setTimeout(() => {
            sheetOverlay.style.display = "none";
            sheet.style.display = "none";
        }, 200);
    }
    
    triggerSheetBtn?.addEventListener("click", openSheet);
    sheetOverlay?.addEventListener("click", closeSheet);
    closeSheetBtn?.addEventListener("click", closeSheet);
    
    // =========================
    // AJAX Verify Product
    // =========================
    async function verifyProduct(options = {}) {
        const {
            showPopup       = false,  // Show modal popup
            affectButtons   = false,  // Enable/disable buttons based on result
            softInlineError = false   // Only show warning, don't disable
        } = options;
        
        try {
            const res = await fetch(`check_product.php?id=${encodeURIComponent(productId)}&t=${Date.now()}`, {
                cache: "no-store"
            });
            
            if (!res.ok) {
                throw new Error("HTTP " + res.status);
            }
            
            const data = await res.json();
            const asNumber = v => (typeof v === "string" ? parseInt(v, 10) : Number(v));
            
            if (!data || data.status !== "ok") {
                const msg = "Unable to verify product.";
                if (softInlineError) {
                    setInlineError(msg);
                    if (affectButtons) setButtonsDisabled(false);
                } else {
                    setInlineError(msg);
                    if (affectButtons) setButtonsDisabled(true);
                    if (showPopup) showModal(msg);
                }
                return false;
            }
            
            const stallOpen   = asNumber(data.stall_open);
            const productOpen = asNumber(data.product_open);
            const stock       = data.stock != null ? asNumber(data.stock) : null;
            const unlimited   = asNumber(data.is_unlimited_stock);
            
            // Update global state
            serverStock = stock;
            isUnlimitedStock = unlimited === 1;
            
            // Check 1: Stall closed
            if (stallOpen === 0) {
                const msg = "This stall is currently closed. You cannot place orders at this time.";
                setInlineError(msg);
                if (affectButtons) setButtonsDisabled(true);
                if (showPopup) showModal(msg);
                return false;
            }
            
            // Check 2: Product unavailable
            if (productOpen === 0) {
                const msg = "This item is unavailable.";
                setInlineError(msg);
                if (affectButtons) setButtonsDisabled(true);
                if (showPopup) showModal(msg);
                return false;
            }
            
            // Check 3: Out of stock (ONLY if IsUnlimitedStock = 0)
            if (!isUnlimitedStock && stock !== null && stock <= 0) {
                const msg = "Out of stock.";
                setInlineError(msg);
                if (affectButtons) setButtonsDisabled(true);
                if (showPopup) showModal(msg);
                return false;
            }
            
            // Check 4: Quantity exceeds stock (ONLY if IsUnlimitedStock = 0)
            if (!isUnlimitedStock && stock !== null && stock > 0 && currentQty > stock) {
                const msg = `Only ${stock} left in stock.`;
                setInlineError(msg);
                
                // Auto-adjust quantity to max available
                currentQty = stock;
                refreshQtyDisplays();
                refreshTotal();
                
                if (!softInlineError && affectButtons && showPopup) {
                    showModal(msg);
                }
                
                // Not a critical error, just limit the quantity
                if (affectButtons) setButtonsDisabled(false);
                return true;
            }
            
            // ✅ All checks passed - ALWAYS clear error and enable buttons
            clearInlineError();
            if (affectButtons) setButtonsDisabled(false);
            return true;
            
        } catch (err) {
            console.error("verifyProduct error:", err);
            const msg = "Network error. Please try again.";
            if (softInlineError) {
                setInlineError(msg);
                if (affectButtons) setButtonsDisabled(false);
            } else {
                setInlineError(msg);
                if (affectButtons) setButtonsDisabled(true);
                if (showPopup) showModal(msg);
            }
            return false;
        }
    }
    
    // =========================
    // Page Load & Polling
    // =========================
    // Initial soft check on page load
    verifyProduct({ showPopup: false, affectButtons: false, softInlineError: true });
    
    // Poll every 3 seconds
    setInterval(() => {
        verifyProduct({ showPopup: false, affectButtons: false, softInlineError: true });
    }, 3000);
    
    // =========================
    // Submit Logic
    // =========================
    submitButtons.forEach(btn => {
        btn.addEventListener("click", async e => {
            e.preventDefault();
            
            if (!hiddenTime.value) {
                showModal("Please select pickup time.");
                return;
            }
            
            // Hard check before submit
            const ok = await verifyProduct({
                showPopup: true,
                affectButtons: true,
                softInlineError: false
            });
            
            if (!ok) return;
            
            // All validations passed, submit form
            orderForm.submit();
        });
    });
    
    // ==========================================
    // CAROUSEL LOGIC
    // ==========================================
    const track = document.querySelector('.carousel-track');
    const slides = document.querySelectorAll('.carousel-slide');
    const indicators = document.querySelectorAll('.custom-dot');
    const carouselContainer = document.querySelector('.product-carousel');
    const prevBtn = document.querySelector('.arrow-prev');
    const nextBtn = document.querySelector('.arrow-next');
    
    if (track && slides.length > 0 && carouselContainer) {
        let slideIndex = 0;
        const totalSlides = slides.length;
        
        function updateCarousel() {
            const slideWidth = carouselContainer.getBoundingClientRect().width;
            track.style.transform = `translateX(-${slideIndex * slideWidth}px)`;
            
            indicators.forEach((dot, index) => {
                dot.classList.toggle('active', index === slideIndex);
            });
        }
        
        indicators.forEach(dot => {
            dot.addEventListener('click', (e) => {
                const idx = parseInt(e.target.dataset.index);
                if (!isNaN(idx)) {
                    slideIndex = idx;
                    updateCarousel();
                }
            });
        });
        
        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', () => {
                slideIndex = (slideIndex > 0) ? slideIndex - 1 : totalSlides - 1;
                updateCarousel();
            });
            
            nextBtn.addEventListener('click', () => {
                slideIndex = (slideIndex < totalSlides - 1) ? slideIndex + 1 : 0;
                updateCarousel();
            });
        }
        
        window.addEventListener('resize', updateCarousel);
        
        // Touch/Swipe Support
        let startX = 0;
        let endX = 0;
        
        carouselContainer.addEventListener('touchstart', (e) => {
            startX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        carouselContainer.addEventListener('touchend', (e) => {
            endX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });
        
        function handleSwipe() {
            const threshold = 50;
            if (endX < startX - threshold) {
                nextBtn ? nextBtn.click() : null;
            } else if (endX > startX + threshold) {
                prevBtn ? prevBtn.click() : null;
            }
        }
        
        setTimeout(updateCarousel, 50);
    }
});