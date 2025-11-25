// assets/js/menu.js
(function () {
    if (typeof MENU_STATE === "undefined") return;

    const grid = document.getElementById("menu-grid");
    if (!grid) return;

    // 根据后台返回的 IsAvailable 状态，更新卡片样式
    function applyAvailability(data) {
        if (!data || !Array.isArray(data.items)) return;

        const statusMap = {};
        data.items.forEach(item => {
            statusMap[item.ProductId] =
                item.IsAvailable === 1 ||
                item.IsAvailable === "1"; // 兼容字符串
        });

        const cards = grid.querySelectorAll(".card");
        cards.forEach(card => {
            const id = card.dataset.id;
            if (!id || !(id in statusMap)) return;

            const available = statusMap[id];
            const overlay = card.querySelector(".unavailable-layer");

            if (available) {
                card.classList.remove("unavailable");
                card.style.pointerEvents = "auto";
                if (overlay) overlay.classList.add("hidden-unavailable");
            } else {
                card.classList.add("unavailable");
                card.style.pointerEvents = "none"; // 禁止点击
                if (overlay) overlay.classList.remove("hidden-unavailable");
            }
        });
    }

    async function pollAvailability() {
        try {
            const params = new URLSearchParams({
                stallid: MENU_STATE.stallId,
                search: MENU_STATE.search || "",
                category: MENU_STATE.category || ""
            });

            const res = await fetch(`menu_polling.php?${params.toString()}`, {
                cache: "no-store"
            });

            if (!res.ok) return;

            const data = await res.json();
            if (data && data.success) {
                applyAvailability(data);
            }
        } catch (err) {
            // 静默失败就好，不要挡正常使用
            // console.error(err);
        }
    }

    // 先跑一次，之后每 5 秒轮询
    pollAvailability();
    setInterval(pollAvailability, 5000);
})();
