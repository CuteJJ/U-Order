document.addEventListener("DOMContentLoaded", () => {

    let lastStatus = null;   
    let hideTimer = null;    
    let slipShown = false;

    const wrapper = document.getElementById("order-slip-wrapper");

    // Check if slip was pre-rendered by PHP on page load
    const preRenderedSlip = wrapper.querySelector("#order-slip");
    if (preRenderedSlip) {
        // ⭐ FIX: Get status from class, not text
        const statusElement = preRenderedSlip.querySelector(".slip-status");
        const statusClasses = statusElement?.classList;
        
        for (let className of statusClasses) {
            if (['pending', 'preparing', 'ready'].includes(className)) {
                lastStatus = className;
                break;
            }
        }
        
        slipShown = true; // Mark as shown to avoid showing again on first fetch
        console.log(`[Order Slip] Pre-rendered with status: "${lastStatus}"`);
        
        // Show it temporarily on page load
        showSlipTemporarily(preRenderedSlip);
    }

    function loadSlip() {
        fetch("/U-Order/order/order_slip.php?x=" + Date.now(), {
            cache: "no-store"
        })
        .then(res => res.text())
        .then(html => {

            // 没订单 → 清空状态
            if (!html.trim()) {
                wrapper.innerHTML = "";
                lastStatus = null;
                slipShown = false;
                return;
            }

            // 解析新的 HTML 以获取状态
            const temp = document.createElement("div");
            temp.innerHTML = html;
            const newSlip = temp.firstElementChild;
            
            // ⭐ FIX: Get the actual status from the class, not the display text!
            // The .slip-status element has both a class (actual status) and text (display text)
            const statusElement = newSlip.querySelector(".slip-status");
            const statusClasses = statusElement?.classList;
            
            // Extract the status class (pending, preparing, or ready)
            let newStatus = null;
            if (statusClasses) {
                for (let className of statusClasses) {
                    if (['pending', 'preparing', 'ready'].includes(className)) {
                        newStatus = className;
                        break;
                    }
                }
            }
            
            // Fallback to text content if no class found
            if (!newStatus) {
                newStatus = statusElement?.textContent.trim().toLowerCase();
            }

            console.log(`[Order Slip] Current: "${lastStatus}" | New: "${newStatus}"`); // Debug log

            // 检查是否需要显示动画
            let shouldShow = false;
            
            // 第一次加载 (but not if pre-rendered)
            if (lastStatus === null && !slipShown) {
                shouldShow = true;
                slipShown = true;
                console.log('[Order Slip] First load - showing slip');
            }
            // 状态改变 - THIS IS THE KEY PART
            else if (lastStatus !== null && newStatus !== lastStatus) {
                shouldShow = true;
                console.log(`[Order Slip] ✨ Status changed: ${lastStatus} → ${newStatus}`); // Debug log
            } else {
                console.log('[Order Slip] No change, not showing');
            }

            // 更新 DOM
            if (wrapper.firstElementChild) {
                const existingSlip = wrapper.querySelector("#order-slip");
                
                // 如果需要显示动画，先移除所有类以重置状态
                if (shouldShow) {
                    existingSlip.classList.remove("show", "hide");
                    // Force reflow to reset animation
                    void existingSlip.offsetHeight;
                }
                
                // 更新内容（不破坏节点）
                wrapper.querySelector(".slip-orderid").innerHTML =
                    newSlip.querySelector(".slip-orderid").innerHTML;

                wrapper.querySelector(".slip-stall").innerHTML =
                    newSlip.querySelector(".slip-stall").innerHTML;

                wrapper.querySelector(".slip-meta").innerHTML =
                    newSlip.querySelector(".slip-meta").innerHTML;
            }
            else {
                // 第一次创建
                wrapper.innerHTML = html;
            }

            // 更新状态
            lastStatus = newStatus;

            // 在 DOM 更新后，获取实际的 slip 元素并显示
            if (shouldShow) {
                // 使用 requestAnimationFrame 确保 DOM 已更新
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        const slip = wrapper.querySelector("#order-slip");
                        if (slip) {
                            showSlipTemporarily(slip);
                        }
                    });
                });
            }
        })
        .catch(err => console.error("Error loading slip:", err));
    }

    // 显示 slip 5 秒然后消失
    function showSlipTemporarily(slip) {
        // 移除 hide 类（如果存在）
        slip.classList.remove("hide");
        
        // 强制重排以确保动画触发
        void slip.offsetWidth;
        
        // 添加 show 类
        slip.classList.add("show");

        if (hideTimer) clearTimeout(hideTimer);

        hideTimer = setTimeout(() => {
            slip.classList.remove("show");
            slip.classList.add("hide");
        }, 5000);
    }

    // Start polling after initial check
    loadSlip();
    setInterval(loadSlip, 3000);
});