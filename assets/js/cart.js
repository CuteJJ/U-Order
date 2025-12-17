// ==================== Toast ====================
function showToast(msg, isError) {
    const t = document.createElement("div");
    t.className = "toast-message";
    t.textContent = msg;

    Object.assign(t.style, {
        position: "fixed",
        top: "16px",
        left: "50%",
        transform: "translateX(-50%) translateY(-8px)",
        padding: "8px 16px",
        borderRadius: "999px",
        fontSize: "0.85rem",
        color: "#fff",
        background: isError ? "#ef4444" : "#16a34a",
        opacity: "0",
        transition: "all .23s ease-out",
        zIndex: "9999"
    });

    document.body.appendChild(t);

    requestAnimationFrame(() => {
        t.style.opacity = "1";
        t.style.transform = "translateX(-50%) translateY(0)";
    });

    setTimeout(() => {
        t.style.opacity = "0";
        t.style.transform = "translateX(-50%) translateY(-6px)";
    }, 2000);

    setTimeout(() => t.remove(), 2400);
}

// ==================== Summary ====================
function recalcSummary() {
    let totalQty = 0, totalPrice = 0;

    document.querySelectorAll(".order-item").forEach(item => {
        const check = item.querySelector(".item-check");
        if (check && !check.checked) return;

        const qty = parseInt(item.querySelector(".qty-value").textContent, 10);
        const price = parseFloat(item.dataset.unitPrice);

        totalQty += qty;
        totalPrice += qty * price;
    });

    const sumQtyEl = document.querySelector("#sumQty");
    const sumTotalEl = document.querySelector("#sumTotal");
    
    if (sumQtyEl) sumQtyEl.textContent = totalQty;
    if (sumTotalEl) sumTotalEl.textContent = totalPrice.toFixed(2);
}

// ==================== Banner ====================
function refreshProceedButtonState() {
    let hasSelectedUnavailable = false;
    
    document.querySelectorAll(".order-item").forEach(item => {
        const checkbox = item.querySelector(".item-check");
        const isChecked = checkbox && checkbox.checked;
        const isUnavailable = item.dataset.unavailable === "1";
        
        if (isChecked && isUnavailable) {
            hasSelectedUnavailable = true;
        }
    });
    
    const banner = document.getElementById("cartBanner");
    const proceedBtn = document.getElementById("btnProceed");

    if (hasSelectedUnavailable) {
        banner?.classList.add("show");
        if (proceedBtn) proceedBtn.disabled = true;
    } else {
        banner?.classList.remove("show");
        if (proceedBtn) proceedBtn.disabled = false;
    }
}

// ==================== Sync Qty to server ====================
async function syncToServer(cartItemId, newQty) {
    try {
        const res = await fetch("../cart/cartquant.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `cartItemId=${encodeURIComponent(cartItemId)}&newQty=${encodeURIComponent(newQty)}`
        });

        const data = await res.json();
        
        if (!data.success) {
            showToast(data.message || "Failed to update quantity", true);
            
            // Reload to sync with server state
            setTimeout(() => {
                window.location.reload();
            }, 1500);
            
            return false;
        }
        
        return true;
    } catch (err) {
        console.error(err);
        showToast("Error updating quantity", true);
        return false;
    }
}

// ==================== Remove Item ====================
async function removeItem(cartItemId, domItem, options = {}) {
    const silent = options.silent || false;

    try {
        const res = await fetch("../cart/remove_item.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `cartItemId=${encodeURIComponent(cartItemId)}`
        });

        let data = null;
        try {
            data = await res.json();
        } catch (e) {
            console.warn("Invalid JSON received from server");
            if (!silent) showToast("Server error", true);
            return;
        }

        if (!data || !data.success) {
            if (!silent) showToast("Failed to remove item", true);
            return;
        }

        const panel = domItem.closest(".stall-panel");
        domItem.remove();

        if (panel && !panel.querySelector(".order-item")) {
            panel.remove();
        }

        const hasItems = document.querySelector(".order-item");
        if (!hasItems) {
            const main = document.querySelector(".order-main");
            if (main) {
                main.innerHTML = `
                    <div style="text-align:center; padding:60px; color:var(--nord3);">
                        <i class="fas fa-shopping-basket" style="font-size:3rem; margin-bottom:16px; opacity:.5;"></i>
                        <p>Your cart is empty.</p>
                        <a href="../index.php" class="btn-secondary" style="display:inline-block; width:auto; margin-top:10px;">Go to Menu</a>
                    </div>
                `;
            }
        }

        recalcSummary();
        refreshProceedButtonState();

        if (!silent) showToast("Item removed", false);

    } catch (err) {
        console.error(err);
        if (!silent) showToast("Error removing item", true);
    }
}

// ==================== Confirm Modal ====================
function showConfirm(message) {
    return new Promise(resolve => {
        const modal = document.getElementById("confirmModal");
        const box   = modal.querySelector(".modal-box");
        const msg   = document.getElementById("confirmMsg");
        const ok    = document.getElementById("confirmOk");
        const can   = document.getElementById("confirmCancel");

        msg.textContent = message;
        modal.style.display = "flex";
        box.style.transform = "scale(.9)";

        setTimeout(() => { box.style.transform = "scale(1)"; }, 10);

        const cleanup = (value) => {
            box.style.transform = "scale(.9)";
            setTimeout(() => { modal.style.display = "none"; }, 180);
            ok.onclick = null;
            can.onclick = null;
            resolve(value);
        };

        ok.onclick  = () => cleanup(true);
        can.onclick = () => cleanup(false);
    });
}

// ==================== Delete Selected ====================
async function deleteSelectedItems() {
    const selectedItems = Array.from(document.querySelectorAll(".order-item"))
        .filter(item => {
            const check = item.querySelector(".item-check");
            return check && check.checked;
        });

    if (selectedItems.length === 0) {
        showToast("No items selected", true);
        return;
    }

    const ok = await showConfirm(`Delete ${selectedItems.length} item(s)?`);
    if (!ok) return;

    for (const item of selectedItems) {
        const cartItemId = item.dataset.id;
        await removeItem(cartItemId, item, { silent: true });
    }

    showToast(`${selectedItems.length} item(s) removed`, false);
}

// ==================== Validate Checkout ====================
async function validateCheckout(selectedIds) {
    try {
        // FIXED: Use correct relative path to cart.php
        const res = await fetch("cart.php?action=validate_checkout", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ cartItemIds: selectedIds })
        });
        
        const data = await res.json();
        return data;
    } catch (err) {
        console.error("Validation error:", err);
        return { success: false, message: "Validation failed. Please try again." };
    }
}

// ==================== Smart Polling ====================
async function pollCartStatus() {
    const idList = Array.from(document.querySelectorAll(".order-item"))
        .map(i => i.dataset.id);

    if (idList.length === 0) return;

    try {
        const res = await fetch("../cart/cart_polling.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ items: idList })
        });

        const data = await res.json();
        if (!data.items) return;

        let needsRecalc = false;

        data.items.forEach(status => {
            const item = document.querySelector(`.order-item[data-id="${status.cartItemId}"]`);
            if (!item) return;

            const oldPrice       = parseFloat(item.dataset.unitPrice);
            const oldStock       = parseInt(item.dataset.stock);
            const wasUnavailable = item.dataset.unavailable === "1";
            const nowUnavailable = status.isUnavailable;

            const priceChanged        = oldPrice !== status.unitPrice;
            const stockChanged        = oldStock !== status.stock;
            const availabilityChanged = wasUnavailable !== nowUnavailable;

            if (!priceChanged && !stockChanged && !availabilityChanged) {
                return;
            }

            needsRecalc = true;

            if (priceChanged) {
                item.dataset.unitPrice = status.unitPrice;
                const unitPriceEl = item.querySelector(".unit-price");
                if (unitPriceEl) {
                    unitPriceEl.textContent = `RM ${status.unitPrice.toFixed(2)}`;
                }

                const qty = parseInt(item.querySelector(".qty-value").textContent);
                const subValEl = item.querySelector(".sub-val");
                if (subValEl) {
                    subValEl.textContent = (qty * status.unitPrice).toFixed(2);
                }
            }

            if (stockChanged) {
                item.dataset.stock = status.stock;
                
                // Check if current quantity exceeds new stock
                const currentQty = parseInt(item.querySelector(".qty-value").textContent);
                const isUnlimited = parseInt(item.dataset.unlimited) === 1;
                
                if (!isUnlimited && currentQty > status.stock) {
                    // Auto-adjust to max stock
                    item.querySelector(".qty-value").textContent = status.stock;
                    const subValEl = item.querySelector(".sub-val");
                    if (subValEl) {
                        const unitPrice = parseFloat(item.dataset.unitPrice);
                        subValEl.textContent = (status.stock * unitPrice).toFixed(2);
                    }
                    syncToServer(status.cartItemId, status.stock);
                    showToast(`Quantity adjusted to available stock (${status.stock})`, true);
                }
            }

            if (availabilityChanged) {
                let badge = item.querySelector(".status-badge");

                if (nowUnavailable) {
                    item.dataset.unavailable = "1";
                    item.classList.add("is-disabled");

                    if (!badge) {
                        badge = document.createElement("div");
                        badge.className = "status-badge";
                        item.appendChild(badge);
                    }
                    badge.textContent = status.statusLabel;

                    item.querySelector(".minus")?.setAttribute("disabled", "true");
                    item.querySelector(".plus")?.setAttribute("disabled", "true");

                    const checkbox = item.querySelector(".item-check");
                    if (checkbox) checkbox.checked = false;

                } else {
                    item.dataset.unavailable = "0";
                    item.classList.remove("is-disabled");
                    badge?.remove();

                    item.querySelector(".minus")?.removeAttribute("disabled");
                    item.querySelector(".plus")?.removeAttribute("disabled");
                }
            }
        });

        if (needsRecalc) {
            recalcSummary();
            refreshProceedButtonState();
        }

    } catch (err) {
        console.error("polling failed", err);
    }
}

// ==================== Polling Control ====================
let pollingInterval = null;

function startPolling() {
    stopPolling();
    pollingInterval = setInterval(pollCartStatus, 3000);
}

function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

// ==================== Event Delegation for Cart Items ====================
function handleCartClick(e) {
    const target = e.target;

    // +/- qty buttons
    const minusBtn  = target.closest(".minus");
    const plusBtn   = target.closest(".plus");
    const removeBtn = target.closest(".btn-remove");

    if (!minusBtn && !plusBtn && !removeBtn) return;

    const item = target.closest(".order-item");
    if (!item) return;

    const cartItemId = item.dataset.id;
    const qtyElem = item.querySelector(".qty-value");
    const subElem = item.querySelector(".sub-val");
    const unitPrice = parseFloat(item.dataset.unitPrice);

    // Remove
    if (removeBtn) {
        removeItem(cartItemId, item);
        return;
    }

    // Quantity change
    if (item.dataset.unavailable === "1") {
        showToast("This item is unavailable", true);
        return;
    }

    let qty = parseInt(qtyElem.textContent, 10) || 0;
    const isUnlimited = parseInt(item.dataset.unlimited) === 1;
    const stock = parseInt(item.dataset.stock);

    if (plusBtn) {
        // Check stock limit before increasing
        if (!isUnlimited && qty >= stock) {
            showToast(`Maximum stock available: ${stock}`, true);
            return;
        }
        qty++;
    } else if (minusBtn) {
        if (qty <= 1) {
            showToast("Minimum quantity is 1", true);
            return;
        }
        qty--;
    }

    qtyElem.textContent = qty;
    if (subElem) {
        subElem.textContent = (qty * unitPrice).toFixed(2);
    }

    recalcSummary();
    syncToServer(cartItemId, qty);
}

function handleCartChange(e) {
    const target = e.target;
    if (!target.classList.contains("item-check")) return;

    recalcSummary();
    refreshProceedButtonState();
}

// ==================== Init ====================
document.addEventListener("DOMContentLoaded", () => {
    const orderMain = document.querySelector(".order-main");
    const btnDeleteSelected = document.getElementById("btnDeleteSelected");
    const checkoutForm = document.getElementById("checkoutForm");

    // Event delegation
    orderMain?.addEventListener("click", handleCartClick);
    orderMain?.addEventListener("change", handleCartChange);

    btnDeleteSelected?.addEventListener("click", deleteSelectedItems);

    // Checkout validation
    checkoutForm?.addEventListener("submit", async (e) => {
        e.preventDefault(); // Always prevent default to validate first

        const btnProceed = document.getElementById("btnProceed");

        // Get selected items
        const selectedItems = Array.from(document.querySelectorAll(".order-item"))
            .filter(item => item.querySelector(".item-check")?.checked);

        const selectedIds = selectedItems.map(item => item.dataset.id);

        // Validation 1: Check if any items selected
        if (selectedIds.length === 0) {
            showToast("Please select at least 1 item", true);
            return;
        }

        // Validation 2: Check for unavailable items in selection
        const hasUnavailable = selectedItems.some(item => item.dataset.unavailable === "1");
        if (hasUnavailable) {
            showToast("Please remove unavailable items before checkout", true);
            return;
        }

        // Validation 3: Server-side validation
        btnProceed.disabled = true;
        const originalText = btnProceed.textContent;
        btnProceed.textContent = "Validating...";

        const validation = await validateCheckout(selectedIds);

        if (!validation.success) {
            btnProceed.disabled = false;
            btnProceed.textContent = originalText;

            // Show specific error message
            if (validation.errors && validation.errors.length > 0) {
                const firstError = validation.errors[0];
                showToast(firstError.message, true);

                // Refresh page to update UI with latest data
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showToast(validation.message || "Validation failed", true);
            }
            return;
        }

        // All validations passed - submit the form
        btnProceed.textContent = "Processing...";
        checkoutForm.submit();
    });

    // Initial calculations
    recalcSummary();
    refreshProceedButtonState();

    // Start polling
    startPolling();

    document.addEventListener("visibilitychange", () => {
        if (document.hidden) {
            stopPolling();
        } else {
            startPolling();
        }
    });
});