document.addEventListener("DOMContentLoaded", () => {

    loadLatestOrder();
    loadHistory();

    // 每 5 秒检查一次订单状态
    setInterval(pollOrderStatus, 5000);
});

function loadLatestOrder() {
    fetch("/order/get_latest_order.php")
        .then(r => r.json())
        .then(data => {
            const box = document.getElementById("latestOrderBox");

            if (!data.hasOrder) {
                box.innerHTML = `<p>No active orders.</p>`;
                box.classList.add("empty");
                return;
            }

            box.classList.remove("empty");

            const o = data.order;
            const items = data.items;

            let html = `
                <div class="order-status-box">
                    <div class="status">
                        <strong>Status:</strong> ${o.Status}
                    </div>
                    <div class="created-at">Created: ${o.CreatedAt}</div>
                    <hr/>
                    <div class="items-list">
            `;

            items.forEach(i => {
                html += `
                    <div class="item-row">
                        <span>${i.ProductName} x ${i.Quantity}</span>
                        <span>RM ${(i.UnitPrice * i.Quantity).toFixed(2)}</span>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;

            box.innerHTML = html;
        });
}

function loadHistory() {
    fetch("/order/get_order_history.php")
        .then(r => r.json())
        .then(data => {
            const box = document.getElementById("historyList");
            box.innerHTML = "";

            data.history.forEach(h => {
                box.innerHTML += `
                    <div class="history-card">
                        <div class="top">
                            <div>${h.StallName}</div>
                            <div>Status: ${h.Status}</div>
                        </div>
                        <div class="date">${h.CreatedAt}</div>
                    </div>
                `;
            });
        });
}

function pollOrderStatus() {
    fetch("/order/poll_order_status.php")
        .then(r => r.json())
        .then(data => {
            if (!data.status) return;

            // 实时更新最新订单 UI
            loadLatestOrder();
        });
}
