document.addEventListener("DOMContentLoaded", () => {

    let lastStatus = null;   
    let hideTimer = null;    
    let slipShown = false;   // 是否已经显示过（避免重复显示）

    function loadSlip() {
        fetch("/U-Order/order/order_slip.php?x=" + Date.now(), {
            cache: "no-store"
        })
        .then(res => res.text())
        .then(html => {

            const wrapper = document.getElementById("order-slip-wrapper");

            // 没订单 → 清空状态
            if (!html.trim()) {
                wrapper.innerHTML = "";
                lastStatus = null;
                slipShown = false;
                return;
            }

            // 插入 HTML（不自动 show！）
            // 如果 slip 已存在 → 更新内部文字，不要破坏元素
if (wrapper.firstElementChild) {
    const temp = document.createElement("div");
    temp.innerHTML = html;
    const newSlip = temp.firstElementChild;

    // 更新三个区域的内容，不破坏节点本身
    wrapper.querySelector(".slip-orderid").innerHTML =
        newSlip.querySelector(".slip-orderid").innerHTML;

    wrapper.querySelector(".slip-stall").innerHTML =
        newSlip.querySelector(".slip-stall").innerHTML;

    wrapper.querySelector(".slip-meta").innerHTML =
        newSlip.querySelector(".slip-meta").innerHTML;
}
else {
    wrapper.innerHTML = html; // 第一次创建 slip
}

            const slip = document.querySelector("#order-slip");

            // 取得当前状态（例如：pending / preparing / ready to pick up）
            let newStatus = slip.querySelector(".slip-status")?.textContent.trim().toLowerCase();

            // 第一次加载：弹出一次
            if (lastStatus === null && !slipShown) {
                showSlipTemporarily(slip);
                slipShown = true;
            }
            // 状态改变：再弹一次
            else if (lastStatus !== null && newStatus !== lastStatus) {
                showSlipTemporarily(slip);
            }

            lastStatus = newStatus;
        })
        .catch(err => console.error(err));
    }

    // 显示 slip 5 秒然后消失
    function showSlipTemporarily(slip) {
        slip.classList.add("show");

        if (hideTimer) clearTimeout(hideTimer);

        hideTimer = setTimeout(() => {
           slip.classList.remove("show");
slip.classList.add("hide");

        }, 5000);
    }

    loadSlip();
    setInterval(loadSlip, 3000);
});
