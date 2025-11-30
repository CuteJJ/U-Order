document.addEventListener("DOMContentLoaded", () => {

    const categorySelect = document.getElementById("filter-category");
    const pickupSelect   = document.getElementById("filter-pickup");

    const wrappers = document.querySelectorAll(".order-item-wrapper");

    function applyFilters() {
        const catVal = categorySelect.value;
        const pickup = pickupSelect.value;
        const now    = new Date();

        wrappers.forEach(w => {
            const itemCategory = w.dataset.category;
            const pickupStr    = w.dataset.pickup;

            let visible = true;

            // Category filter
            if (catVal !== "all" && itemCategory !== catVal) {
                visible = false;
            }

            // Pickup filter
            if (pickup !== "all") {

                const hasTime = !!pickupStr;

                if (pickup === "now") {
                    // now = ASAP + 未来 30 min
                    if (!hasTime) {
                        // ASAP -> ok
                        visible = true;
                    } else {
                        const pk = new Date(pickupStr.replace(" ", "T"));
                        const diffMin = (pk - now) / 1000 / 60;
                        if (diffMin < 0 || diffMin > 30) {
                            visible = false;
                        }
                    }

                } else {
                    // 1h / 2h 必须有时间
                    if (!hasTime) {
                        visible = false;
                    } else {
                        const pk = new Date(pickupStr.replace(" ", "T"));
                        const diffMin = (pk - now) / 1000 / 60;

                        if (pickup === "1h") {
                            if (diffMin < 0 || diffMin > 60) visible = false;
                        } else if (pickup === "2h") {
                            if (diffMin < 0 || diffMin > 120) visible = false;
                        }
                    }
                }
            }

            w.style.display = visible ? "" : "none";
        });

        updateCounts();
    }

    function updateCounts() {
        function countVisible(selector) {
            return Array.from(document.querySelectorAll(selector))
                .filter(w => w.style.display !== "none").length;
        }

        document.getElementById("count-pending").textContent   =
            countVisible("#col-pending .order-item-wrapper");
        document.getElementById("count-preparing").textContent =
            countVisible("#col-preparing .order-item-wrapper");
        document.getElementById("count-ready").textContent     =
            countVisible("#col-ready .order-item-wrapper");
    }

    categorySelect.addEventListener("change", applyFilters);
    pickupSelect.addEventListener("change", applyFilters);

    applyFilters();
});
