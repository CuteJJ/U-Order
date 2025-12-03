$(document).ready(function() {
    
    // Toggle Cart
    $('#cartToggle').click(function() {
        $('#cartSidebar').addClass('open');
        $('#cartOverlay').fadeIn(200);
    });

    // Close Cart (X button or Overlay click)
    $('#closeCart, #cartOverlay').click(function() {
        $('#cartSidebar').removeClass('open');
        $('#cartOverlay').fadeOut(200);
    });

    // Simple visual feedback for Add Buttons
    $('.add-btn').click(function(e) {
        // Prevent clicking the card behind it
        e.stopPropagation(); 
        
        let btn = $(this);
        let originalContent = btn.html();
        
        // Show checkmark
        btn.html('<i class="fas fa-check"></i>').css('background-color', 'var(--nord14)'); // Green
        
        // Revert after 1 second
        setTimeout(function() {
            btn.html(originalContent).css('background-color', 'var(--nord0)');
        }, 1000);
        
        // Increment cart badge (Visual simulation)
        let badge = $('.cart-badge');
        let count = parseInt(badge.text());
        badge.text(count + 1);
    });

    // Order History Toggle
    $('.card-header-toggle').on('click', function() {
        // 1. Find the list specifically inside this card
        var list = $(this).closest('.history-card').find('.order-items-list');
        
        // 2. Slide Toggle the list
        list.slideToggle(300); // 300ms animation speed
        
        // 3. Rotate the chevron icon
        $(this).find('.toggle-icon').toggleClass('rotate');
    });
});