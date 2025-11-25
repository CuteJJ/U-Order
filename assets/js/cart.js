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

    document.querySelector("#sumQty").textContent = totalQty + " items";
    document.querySelector("#sumTotal").textContent = totalPrice.toFixed(2);
}

// ==================== Banner ====================
function refreshProceedButtonState() {
    const hasUnavailable = document.querySelector('.order-item[data-unavailable="1"]');
    const banner = document.getElementById("cartBanner");

    if (hasUnavailable) banner.classList.add("show");
    else banner.classList.remove("show");
}

// ==================== Sync Qty to server ====================
async function syncToServer(cartItemId, newQty) {
    try {
        await fetch("../cart/cartquant.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `cartItemId=${cartItemId}&newQty=${newQty}`
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
            body: `cartItemId=${cartItemId}`
        });

        const data = await res.json();

        if (!data.success) {
            if (!silent) showToast("Failed to remove item", true);
            return;
        }

        const panel = domItem.closest(".stall-panel");
        domItem.remove();

        if (panel && !panel.querySelector(".order-item")) panel.remove();

        recalcSummary();
        refreshProceedButtonState();

        if (!silent) showToast("Item removed", false);

    } catch (err) {
        console.error(err);
    }
}

// ==================== Init Card ====================
function initCartItems() {
    document.querySelectorAll(".order-item").forEach(item => {
        const minus = item.querySelector(".minus");
        const plus = item.querySelector(".plus");
        const qtyElem = item.querySelector(".qty-value");
        const subElem = item.querySelector(".sub-val");
        const btnEdit = item.querySelector(".btn-edit");
        const btnRemove = item.querySelector(".btn-remove");

        const cartItemId = item.dataset.id;
        const productId = item.dataset.productId;

        // 加
        plus?.addEventListener("click", () => {
            if (item.dataset.unavailable === "1") return;

            let qty = parseInt(qtyElem.textContent);
            qty++;
            qtyElem.textContent = qty;

            subElem.textContent = "RM " + (qty * item.dataset.unitPrice).toFixed(2);
            recalcSummary();
            syncToServer(cartItemId, qty);
        });

        // 减
        minus?.addEventListener("click", () => {
            if (item.dataset.unavailable === "1") return;

            let qty = parseInt(qtyElem.textContent);
            if (qty <= 1) return;

            qty--;
            qtyElem.textContent = qty;

            subElem.textContent = "RM " + (qty * item.dataset.unitPrice).toFixed(2);
            recalcSummary();
            syncToServer(cartItemId, qty);
        });

        // Edit
        btnEdit?.addEventListener("click", () => {
            if (item.dataset.unavailable === "1") return;

            window.location.href =
                `../pages/product_details.php?product_id=${productId}&cart_item_id=${cartItemId}`;
        });

        // Remove
        btnRemove?.addEventListener("click", () => {
            removeItem(cartItemId, item);
        });
    });

    recalcSummary();
    refreshProceedButtonState();
}

// ==================== Polling (FIXED!!) ====================
async function pollCartStatus() {

    // 收集所有 cartItemId
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

        data.items.forEach(status => {

            const item = document.querySelector(`.order-item[data-id="${status.cartItemId}"]`);
            if (!item) return;

            // 更新价格
            item.dataset.unitPrice = status.unitPrice;
            item.querySelector(".unit-val").textContent = status.unitPrice.toFixed(2);

            // 更新 subtotal
            const qty = parseInt(item.querySelector(".qty-value").textContent);
            item.querySelector(".sub-val").textContent =
                "RM " + (qty * status.unitPrice).toFixed(2);

            // 更新库存
            item.dataset.stock = status.stock;

            // 更新可用性
            const nowUnavailable = status.isUnavailable;

            let pill = item.querySelector(".item-status-pill");

            if (nowUnavailable) {
                item.dataset.unavailable = "1";
                item.classList.add("is-disabled");

                if (!pill) {
                    pill = document.createElement("div");
                    pill.className = "item-status-pill";
                    item.appendChild(pill);
                }
                pill.textContent = status.statusLabel;

                item.querySelector(".minus")?.setAttribute("disabled", true);
                item.querySelector(".plus")?.setAttribute("disabled", true);
                item.querySelector(".btn-edit")?.setAttribute("disabled", true);

            } else {
                item.dataset.unavailable = "0";
                item.classList.remove("is-disabled");

                pill?.remove();

                item.querySelector(".minus")?.removeAttribute("disabled");
                item.querySelector(".plus")?.removeAttribute("disabled");
                item.querySelector(".btn-edit")?.removeAttribute("disabled");
            }
        });

        recalcSummary();
        refreshProceedButtonState();

    } catch (err) {
        console.error("polling failed", err);
    }
}

// ==================== Init ====================
document.addEventListener("DOMContentLoaded", () => {
    initCartItems();

    // 每 3 秒自动更新 status
    setInterval(pollCartStatus, 3000);
});
