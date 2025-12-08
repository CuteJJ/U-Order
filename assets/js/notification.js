$(document).ready(function () {
    let currentNotifications = INITIAL_NOTIFICATIONS || [];
    let lastCheckTime = Number(localStorage.getItem("notif_last_seen")) || Date.now();
    let isPolling = true;

    const statusConfig = {
        pending: {
            icon: "fa-clock",
            label: "Pending",
            colorClass: "pending",
            message: "Your order is pending confirmation"
        },
        preparing: {
            icon: "fa-fire",
            label: "Preparing",
            colorClass: "preparing",
            message: "Your order is being prepared"
        },
        ready: {
            icon: "fa-box-open",
            label: "Ready for Pickup",
            colorClass: "ready",
            message: "Your order is ready for pickup!"
        },
        cancelled: {
            icon: "fa-times-circle",
            label: "Cancelled",
            colorClass: "cancelled",
            message: "Order was cancelled"
        }
    };

    function formatTime(datetimeString) {
        const date = new Date(datetimeString);
        const now = new Date();
        const diff = now - date;

        const secs = Math.floor(diff / 1000);
        if (secs < 60) return "Just now";

        const mins = Math.floor(diff / 60000);
        if (mins < 60) return `${mins}m ago`;

        const hrs = Math.floor(mins / 60);
        if (hrs < 24) return `${hrs}h ago`;

        const days = Math.floor(hrs / 24);
        return `${days}d ago`;
    }

 function createNotificationCard(n) {
    const cfg = statusConfig[n.Status] || statusConfig.pending;
    const created = new Date(n.CreatedAt).getTime();
    const isNew = created > lastCheckTime;

    return `
        <div class="notif-card ${isNew ? 'new-notification' : ''}" data-order-id="${n.OrderId}" data-status="${n.Status}">
            <div class="notif-icon ${cfg.colorClass}">
                <i class="fas ${cfg.icon}"></i>
            </div>

            <div class="notif-content">
                <div class="notif-header-row">
                    <div class="notif-title">
                        <h3>Order #${n.OrderId}</h3>
                        <p class="stall-name">${n.StallName}</p>
                    </div>

                    ${n.Status !== 'ready' ? `
                        <span class="notif-status-pill ${cfg.colorClass}">
                            ${cfg.label}
                        </span>
                    ` : ''}
                </div>

                <p class="status-message">${cfg.message}</p>

                <div class="notif-footer">
                    <span class="notif-time">
                        <i class="far fa-clock"></i>
                        ${formatTime(n.CreatedAt)}
                    </span>

                    <a href="order_detail.php?id=${n.OrderId}" class="view-order-btn">
                        <i class="fas fa-eye"></i> View Order
                    </a>
                </div>
            </div>

            ${isNew ? '<div class="new-badge"></div>' : ''}
        </div>
    `;
}


    function renderNotifications(list) {
        const container = $("#notificationsContainer");

        if (!list || list.length === 0) {
            container.html(`
                <div class="empty-center">
                    <div class="empty-icon">
                        <i class="fas fa-bell-slash"></i>
                    </div>
                    <h2>No notifications yet</h2>
                    <p>Your order notifications will appear here</p>
                </div>
            `);
            return;
        }

        const html = list.map(createNotificationCard).join("");
        container.html(html);

        setTimeout(() => {
            $(".notif-card.new-notification").addClass("slide-in");
        }, 100);
    }

    function updateNotificationCount(list) {
        const badge = $("#notificationCount");
        const newCount = list.filter(n =>
            new Date(n.CreatedAt).getTime() > lastCheckTime
        ).length;

        if (newCount > 0) {
            badge.text(newCount).removeClass("d-none").addClass("pulse");
        } else {
            badge.addClass("d-none").removeClass("pulse");
        }
    }

function showToast(orderId) {
    const toast = $("#notificationToast");
    toast.find("span").text(`New update on Order #${orderId}`);
    toast.addClass("show");

    setTimeout(() => {
        toast.removeClass("show");
    }, 3000);
}


function fetchNotifications() {
    if (!isPolling) return;

    $.ajax({
        url: "../order/get_notifications.php",
        type: "GET",
        dataType: "json",
        success: function (response) {
            if (!response.success) return;

            const newNotifications = response.notifications;

            let changedOrderId = null;

            // 检查差异
            newNotifications.forEach(newNotif => {
                const old = currentNotifications.find(o => o.OrderId == newNotif.OrderId);
                if (!old || old.Status !== newNotif.Status) {
                    changedOrderId = newNotif.OrderId;
                }
            });

            // 更新记忆体
            currentNotifications = newNotifications;
            renderNotifications(currentNotifications);

            if (changedOrderId) {
                showToast(changedOrderId);

                // === 强制将这个卡片移动到最顶 ===
                const card = $(`.notif-card[data-order-id="${changedOrderId}"]`);
                const container = $("#notificationsContainer");

                // 从原来位置移除
                card.detach();

                // 插入容器最上面
                container.prepend(card);

                // 播放动画
                card.addClass("updated");

                setTimeout(() => card.removeClass("updated"), 800);
            }

            updateNotificationCount(currentNotifications);
        }
    });
}



    $(document).on("click", ".view-order-btn", function() {
        const orderId = $(this).closest(".notif-card").data("order-id");
        $(`.notif-card[data-order-id="${orderId}"]`).removeClass("new-notification");
    });

    renderNotifications(currentNotifications);
    updateNotificationCount(currentNotifications);
fetchNotifications();
    const pollingInterval = setInterval(fetchNotifications, 5000);

    document.addEventListener("visibilitychange", () => {
        if (!document.hidden) {
            lastCheckTime = Date.now();
            localStorage.setItem("notif_last_seen", lastCheckTime);
            fetchNotifications();
        }
    });

    window.addEventListener("beforeunload", () => {
        isPolling = false;
        clearInterval(pollingInterval);
    });

    $(".header-bell").on("click", function() {
        lastCheckTime = Date.now();
        localStorage.setItem("notif_last_seen", lastCheckTime);
        fetchNotifications();
        updateNotificationCount([]);
    });
});
