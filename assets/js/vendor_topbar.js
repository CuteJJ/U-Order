document.addEventListener("DOMContentLoaded", () => {

    const toggle = document.getElementById("stallToggle");
    const badge  = document.querySelector(".badge");

    // KPI elements
    const kpiPending   = document.getElementById("kpi-pending");
    const kpiPreparing = document.getElementById("kpi-preparing");
    const kpiDone      = document.getElementById("kpi-done");


    // ================================================================
    // 1. Toggle 开门 / 关门（即时更新数据库 + 刷新 UI）
    // ================================================================
    if (toggle) {
        toggle.addEventListener("change", async () => {

            const status = toggle.checked ? 1 : 0;

            const form = new FormData();
            form.append("status", status);

            // 写入数据库
            await fetch("../pages/update_stall_status.php", {
                method: "POST",
                body: form
            });

            // 立即刷新展示
            updateTopbar();
        });
    }


    // ================================================================
    // 2. 每 5 秒自动刷新
    // ================================================================
    setInterval(() => {
        updateTopbar();
    }, 5000);


    // ================================================================
    // 3. 拉最新 KPI + Open/Closed 状态并更新 UI
    // ================================================================
    async function updateTopbar() {
        try {
            const res = await fetch("../pages/vendor_topbar_data.php");
            const data = await res.json();

            if (!data) return;

            // ① Toggle
            if (toggle) {
                toggle.checked = data.isOpen == 1;
            }

            // ② Badge
            if (badge) {
                if (data.isOpen == 1) {
                    badge.className = "badge badge--open";
                    badge.textContent = "Open";
                } else {
                    badge.className = "badge badge--closed";
                    badge.textContent = "Closed";
                }
            }

            // ③ KPI
            if (kpiPending)   kpiPending.textContent   = data.pending;
            if (kpiPreparing) kpiPreparing.textContent = data.preparing;
            if (kpiDone)      kpiDone.textContent      = data.done;

        } catch (e) {
            console.error("Failed to update topbar:", e);
        }
    }

});
