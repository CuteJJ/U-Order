// Notification Page JavaScript
$(document).ready(function() {
    let lastCheckTime = Date.now();
    let currentNotifications = INITIAL_NOTIFICATIONS || [];
    
    // Status configurations
    const statusConfig = {
        'pending': {
            icon: 'fa-clock',
            colorClass: 'status-pending',
            label: 'Pending',
            message: 'Your order has been placed'
        },
        'preparing': {
            icon: 'fa-fire',
            colorClass: 'status-preparing',
            label: 'Preparing',
            message: 'Your order is being prepared'
        },
        'ready': {
            icon: 'fa-check-circle',
            colorClass: 'status-ready',
            label: 'Ready for Pickup',
            message: 'Your order is ready for pickup!'
        },
        'complete': {
            icon: 'fa-check-double',
            colorClass: 'status-complete',
            label: 'Completed',
            message: 'Order completed. Thank you!'
        },
        'cancelled': {
            icon: 'fa-times-circle',
            colorClass: 'status-cancelled',
            label: 'Cancelled',
            message: 'Order was cancelled'
        }
    };
    
    // Format timestamp to relative time
    function formatTime(timestamp) {
        const now = new Date();
        const date = new Date(timestamp);
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        
        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        
        const diffHours = Math.floor(diffMins / 60);
        if (diffHours < 24) return `${diffHours}h ago`;
        
        const diffDays = Math.floor(diffHours / 24);
        return `${diffDays}d ago`;
    }
    
    // Create notification card HTML
    function createNotificationCard(notification) {
        const config = statusConfig[notification.Status] || statusConfig['pending'];
        const isNew = new Date(notification.CreatedAt) > new Date(lastCheckTime);
        const newClass = isNew ? 'new' : '';
        
        return `
            <div class="notification-card ${newClass}" data-order-id="${notification.OrderId}">
                <div class="notification-body">
                    <!-- Status Icon -->
                    <div class="status-icon ${config.colorClass}">
                        <i class="fas ${config.icon}"></i>
                    </div>
                    
                    <!-- Content -->
                    <div class="notification-content">
                        <div class="notification-header-row">
                            <div class="notification-title">
                                <h3>Order #${notification.OrderId}</h3>
                                <p>${notification.StallName}</p>
                            </div>
                            <span class="status-badge ${config.colorClass}">
                                ${config.label}
                            </span>
                        </div>
                        
                        <p class="notification-message">
                            ${notification.Notes || config.message}
                        </p>
                        
                        <div class="notification-footer">
                            <span class="notification-time">
                                <i class="far fa-clock"></i>
                                ${formatTime(notification.CreatedAt)}
                            </span>
                            
                            ${notification.Status === 'ready' ? `
                                <a href="order_detail.php?id=${notification.OrderId}" class="view-order-btn">
                                    View Order
                                </a>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- New Badge -->
                    ${isNew ? '<div class="new-badge"></div>' : ''}
                </div>
            </div>
        `;
    }
    
    // Render notifications
    function renderNotifications(notifications) {
        const container = $('#notificationsContainer');
        const loadingState = $('#loadingState');
        const emptyState = $('#emptyState');
        
        loadingState.addClass('d-none');
        
        if (notifications.length === 0) {
            container.addClass('d-none');
            emptyState.removeClass('d-none');
        } else {
            container.removeClass('d-none');
            emptyState.addClass('d-none');
            
            const html = notifications.map(n => createNotificationCard(n)).join('');
            container.html(html);
        }
    }
    
    // Update notification count
    function updateNotificationCount(notifications) {
        const countElement = $('#notificationCount');
        const newCount = notifications.filter(n => 
            new Date(n.CreatedAt) > new Date(lastCheckTime)
        ).length;
        
        if (newCount > 0) {
            countElement.text(newCount).removeClass('d-none');
        } else {
            countElement.addClass('d-none');
        }
    }
    
    // Fetch notifications from server
    function fetchNotifications() {
        $.ajax({
            url: '../ajax/get_notifications.php',
            type: 'GET',
            data: { userId: USER_ID },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Check if there are new notifications
                    const hasNewNotifications = response.notifications.some(newNotif => {
                        return !currentNotifications.some(oldNotif => 
                            oldNotif.OrderId === newNotif.OrderId && 
                            oldNotif.Status === newNotif.Status
                        );
                    });
                    
                    // If there are new notifications, show them at the top
                    if (hasNewNotifications) {
                        // Play sound or show toast notification (optional)
                        showNewNotificationToast();
                    }
                    
                    currentNotifications = response.notifications;
                    renderNotifications(response.notifications);
                    updateNotificationCount(response.notifications);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching notifications:', error);
            }
        });
    }
    
    // Show toast notification for new updates
    function showNewNotificationToast() {
        // Create a simple toast notification
        const toast = $(`
            <div class="notification-toast">
                <i class="fas fa-bell"></i>
                <span>New order update!</span>
            </div>
        `);
        
        $('body').append(toast);
        
        setTimeout(() => {
            toast.addClass('show');
        }, 100);
        
        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Initial render
    renderNotifications(currentNotifications);
    updateNotificationCount(currentNotifications);
    
    // Poll for new notifications every 10 seconds
    setInterval(fetchNotifications, 10000);
    
    // Update last check time when page becomes visible
    $(document).on('visibilitychange', function() {
        if (!document.hidden) {
            lastCheckTime = Date.now();
            fetchNotifications();
        }
    });
});

// Add toast styles dynamically
const toastStyles = `
    <style>
        .notification-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4c6ef5;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            z-index: 9999;
            font-weight: 600;
        }
        
        .notification-toast.show {
            transform: translateX(0);
        }
        
        .notification-toast i {
            font-size: 1.25rem;
        }
    </style>
`;
$('head').append(toastStyles);