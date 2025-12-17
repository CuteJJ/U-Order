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
        await fetch("../cart/cartquant.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `cartItemId=${encodeURIComponent(cartItemId)}&newQty=${encodeURIComponent(newQty)}`
        });
    } catch (err) {
        console.error(err);
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

    selectedItems.forEach(item => {
        const cartItemId = item.dataset.id;
        removeItem(cartItemId, item, { silent: true });
    });

    showToast(`${selectedItems.length} item(s) removed`, false);
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
    if (item.dataset.unavailable === "1") return;

    let qty = parseInt(qtyElem.textContent, 10) || 0;

    if (plusBtn) {
        qty++;
    } else if (minusBtn) {
        if (qty <= 1) return;
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
    const btnProceed = document.getElementById("btnProceed");

    // 事件代理：整块 order-main 只绑一次
    orderMain?.addEventListener("click", handleCartClick);
    orderMain?.addEventListener("change", handleCartChange);

    btnDeleteSelected?.addEventListener("click", deleteSelectedItems);

  btnProceed?.addEventListener("click", (e) => {
 
    
    const selectedIds = Array.from(document.querySelectorAll(".order-item"))
        .filter(item => item.querySelector(".item-check")?.checked)
        .map(item => item.dataset.id);

    if (selectedIds.length === 0) {
        e.preventDefault();
        showToast("Please select at least 1 item", true);
        return;
    }

    // redirect with selected cartItemIds
    const params = selectedIds.join(",");
    window.location.href = `../pages/payment.php?items=${params}`;
});

    // 初始计算
    recalcSummary();
    refreshProceedButtonState();

    // 启动轮询
    startPolling();

    document.addEventListener("visibilitychange", () => {
        if (document.hidden) {
            stopPolling();
        } else {
            startPolling();
        }
    });
});
