// Button templates
const BTN_CONFIRM_PICKUP = `
    <button class="confirm-btn" id="confirmPickupBtn">
        <i class="fas fa-check-circle"></i>
        <span>Confirm Pickup</span>
    </button>
`;

const BTN_ORDER_AGAIN = `
    <a href="/U-Order/index.php" 
       class="confirm-btn"
       style="background:#4C6EF5; text-decoration: none;">
        <i class="fas fa-redo"></i>
        <span>Order Again</span>
    </a>
`;

$(document).ready(function() {
    const STEPS = ["pending", "preparing", "ready", "complete"];
    let lastStatus = INITIAL_STATUS;
    let lastPaymentStatus = INITIAL_PAYMENT_STATUS;
    let pollingInterval = null;

    /**
     * Update order status UI with animations
     */
    function updateOrderStatus(status, paymentStatus) {
        status = status.toLowerCase();
        paymentStatus = paymentStatus.toLowerCase();

        const rank = STEPS.indexOf(status);
        if (rank === -1) {
            console.warn('Invalid status:', status);
            return;
        }

        const percentage = (rank / (STEPS.length - 1)) * 80 + 10;

        // Update progress fill
        const progressFill = $("#progress-fill");
        progressFill.removeClass("pending preparing ready complete");
        progressFill.addClass(status);
        progressFill.css('width', percentage + '%');

        // Update steps
        $(".step").each(function() {
            const stepStatus = $(this).data("status");
            const stepRank = STEPS.indexOf(stepStatus);

            $(this).removeClass("active current pending preparing ready complete completing");

            if (stepRank < rank) {
                $(this).addClass("active " + stepStatus);
                $(this).find(".step-circle").html('<i class="fas fa-check"></i>');
            } else if (stepRank === rank) {
                $(this).addClass("active current " + status);
                $(this).find(".step-circle").html('<i class="fas fa-circle"></i>');
            } else {
                $(this).find(".step-circle").text(stepRank + 1);
            }
        });

        // Update status badge
        const badge = $("#status-badge");
        badge.removeClass("pending preparing ready complete");
        badge.addClass(status);
        $("#status-text").text(status.charAt(0).toUpperCase() + status.slice(1));

        // Update payment status badge
        const paymentBadge = $("#payment-status-badge");
        paymentBadge.removeClass("paid pending");
        paymentBadge.addClass(paymentStatus);
        $("#payment-status-text").text(paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1));

        // Add animation if changed
        if (status !== lastStatus || paymentStatus !== lastPaymentStatus) {
            badge.addClass("updating");
            paymentBadge.addClass("updating");
            setTimeout(() => {
                badge.removeClass("updating");
                paymentBadge.removeClass("updating");
            }, 1500);
            
            // Handle dynamic button display based on status change
            if (status !== lastStatus) {
                handleStatusChange(status, lastStatus);
            }
            
            lastStatus = status;
            lastPaymentStatus = paymentStatus;
        }
    }

    /**
     * Handle dynamic button display when status changes
     */
    function handleStatusChange(newStatus, oldStatus) {
        console.log(`Status changed from ${oldStatus} to ${newStatus}`);
        
        // When status changes to "ready", show confirm pickup button (if not already visible from PHP)
        if (newStatus === 'ready' && oldStatus !== 'ready') {
            // Only add dynamically if PHP didn't render it
            if ($("#confirm-pickup-section").length === 0) {
                const confirmSection = $('<div class="section" id="confirm-pickup-section"></div>');
                confirmSection.html(BTN_CONFIRM_PICKUP).hide();
                $("#dynamic-action-section").before(confirmSection);
                confirmSection.fadeIn(400);
                console.log('Confirm Pickup button shown dynamically - Status changed to ready');
            }
        }
        
        // When status changes to "complete", stop polling and show order again button
        if (newStatus === 'complete' && oldStatus !== 'complete') {
            // Add celebration animation to status container
            $(".status-container").addClass("completing");
            
            // Add pop animation to complete step
            $(".step[data-status='complete']").addClass("completing");
            
            setTimeout(() => {
                $(".status-container").removeClass("completing");
                $(".step[data-status='complete']").removeClass("completing");
            }, 600);
            
            // Stop polling
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
                console.log('Polling stopped - Order complete');
            }
            
            // Remove confirm pickup button if exists (whether from PHP or dynamic)
            $("#confirm-pickup-section").fadeOut(300, function() {
                $(this).remove();
            });
            
            // Show order again button in dynamic section
            const dynamicSection = $("#dynamic-action-section");
            if (!dynamicSection.find("a[href*='reorder']").length) {
                dynamicSection.html(BTN_ORDER_AGAIN).hide().fadeIn(400);
                console.log('Order Again button shown - Status changed to complete');
            }
        }
    }

    /**
     * Poll server for order status updates
     */
    function pollOrderStatus() {
        $.ajax({
            url: `./order_detail.php?action=get_status&id=${ORDER_ID}`,
            method: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status && res.status !== 'unknown') {
                    updateOrderStatus(
                        res.status,
                        res.paymentStatus || 'pending'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('Polling error:', error);
            }
        });
    }

    /**
     * Handle confirm pickup button click - Stripe-style animation
     */
    $(document).on("click", "#confirmPickupBtn", function() {
        const btn = $(this);
        
        // Step 1: Show loading spinner
        btn.addClass("loading");
        btn.html('<div class="btn-spinner"></div><span>Processing...</span>');
        
        console.log(" initiating AJAX request for Order ID:", ORDER_ID);

        $.ajax({
            url: `./order_detail.php?action=confirm_pickup&id=${ORDER_ID}`,
            method: "GET",
            dataType: "json",
            success: function(res) {
                
                console.log("Server Response:", res);

                if (res.success) {
                    // Step 2: Show success checkmark
                    btn.removeClass("loading").addClass("success");
                    btn.html('<div class="btn-checkmark"><i class="fas fa-check"></i></div><span>Confirmed!</span>');
                    
                    // Step 3: Wait a moment, then update UI
                    setTimeout(() => {
                        // Force UI update immediately
                        updateOrderStatus("complete", lastPaymentStatus);

                        // Remove button with fade animation after showing success
                        setTimeout(() => {
                            $("#confirm-pickup-section").fadeOut(400, function() {
                                $(this).remove();
                            });
                        }, 800);
                    }, 600);
                } 
                else {
                 // [DEBUG] Logic reached server but returned success: false
                console.error("Server returned success: false", res);
                alert("Server Error: " + (res.error || "Unknown error"));
                btn.removeClass("loading"); // Reset button
            }
            },
            error: function(xhr, status, error) {
            // [DEBUG] This is where most local environment errors show up
            console.error("AJAX Request Failed!");
            console.error("Status:", status);
            console.error("Error:", error);
            console.log("Raw Response Text (check for HTML PHP errors):", xhr.responseText);
                // Reset button on error
                btn.removeClass("loading success");
                btn.html('<i class="fas fa-check-circle"></i><span>Confirm Pickup</span>');
                alert("Failed to update order. Please try again.");
            }
        });
    });

    // Initialize order status display
    updateOrderStatus(lastStatus, lastPaymentStatus);

    // Handle initial button display based on current status
    if (lastStatus === 'complete') {
        console.log('Order is complete - polling not started');
        // Show order again button immediately if already complete
        const dynamicSection = $("#dynamic-action-section");
        if (!dynamicSection.find("a[href*='reorder']").length) {
            dynamicSection.html(BTN_ORDER_AGAIN);
            console.log('Order Again button shown - Initial status is complete');
        }
    } else {
        // Start polling only if order is not complete
        pollingInterval = setInterval(pollOrderStatus, 5000);
        console.log('Polling started - checking every 5 seconds');
    }
});