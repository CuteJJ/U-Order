document.addEventListener("DOMContentLoaded", () => {

    const slip = document.getElementById("order-slip");

    function loadSlip() {
        fetch("/U-Order/order/order_slip.php?x=" + Date.now(), {
            cache: "no-store"
        })
        .then(res => {
            if (!res.ok) throw new Error("404");
            return res.text();
        })
        .then(html => {
            if (!html.trim()) {
                slip?.classList.remove("show");
                return;
            }

            document.getElementById("order-slip-wrapper").innerHTML = html;
            document.querySelector("#order-slip")?.classList.add("show");
        })
        .catch(err => {
            console.log("Slip load failed:", err);
        });
    }

    loadSlip();
    setInterval(loadSlip, 3000);
});
