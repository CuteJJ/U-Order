// /assets/js/product.js

document.addEventListener("DOMContentLoaded", () => {
    // =========================
    // 基本 DOM 引用
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
    // Modal 弹窗
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
    // Inline Error 处理
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
    // 数量 & 金额
    // =========================
    let currentQty = parseInt(hiddenQty.value, 10) || 1;
    let serverStock = null; // 从 verifyProduct 拿到的库存

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

        // 如果已知库存，则不允许超过库存
        if (serverStock !== null && serverStock > 0 && nextQty > serverStock) {
            // 给一个温和提示，不用弹窗
            setInlineError(`Only ${serverStock} left in stock.`);
            nextQty = serverStock;
        } else {
            // 如果数量回到正常范围，清除之前的库存错误
            if (serverStock !== null) {
                clearInlineError();
            }
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

    // 初始化显示
    refreshQtyDisplays();
    refreshTotal();

    // =========================
    // Pickup Time 选择
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
    // Note 同步 (Desktop + Mobile)
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
    // Mobile Bottom Sheet 打开/关闭
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
            showPopup       = false,  // 是否弹 modal
            affectButtons   = false,  // 是否根据结果启用/禁用按钮
            softInlineError = false   // true: 只提示，不强行 disable
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

            serverStock = stock; // 保存下来给数量限制用

            if (stallOpen === 0) {
                const msg = "This stall is currently CLOSED.";
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

            if (productOpen === 0) {
                const msg = "This item is unavailable.";
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

            if (stock !== null && stock <= 0) {
                const msg = "Out of stock.";
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

            if (stock !== null && currentQty > stock) {
                const msg = `Only ${stock} left in stock.`;
                setInlineError(msg);

                // 自动把数量拉回 stock
                currentQty = stock;
                refreshQtyDisplays();
                refreshTotal();

                if (!softInlineError && affectButtons && showPopup) {
                    showModal(msg);
                }

                // 不算致命错误，只是不让超过库存，所以返回 true
                if (affectButtons) setButtonsDisabled(false);
                return true;
            }

            // 一切正常
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
    // 页面加载 & Polling
    // =========================

    // 页面加载时做一次“软检查”：
    // 只显示 warning，不锁按钮（避免你说的“一进来就不能 Add”）
    verifyProduct({ showPopup: false, affectButtons: false, softInlineError: true });

    // 每 3 秒轮询一次（同样只做软提示）
    setInterval(() => {
        verifyProduct({ showPopup: false, affectButtons: false, softInlineError: true });
    }, 3000);

    // =========================
    // Submit 逻辑
    // =========================
    submitButtons.forEach(btn => {
        btn.addEventListener("click", async e => {
            e.preventDefault();

            if (!hiddenTime.value) {
                showModal("Please select pickup time.");
                return;
            }

            // 这里是“硬检查”：真正决定能不能提交
            const ok = await verifyProduct({
                showPopup: true,
                affectButtons: true,
                softInlineError: false
            });

            if (!ok) return;

            // 通过验证，提交表单
            orderForm.submit();
        });
    });
});
