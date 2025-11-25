$(document).ready(function() {
    
    let unitPrice = parseFloat($('#unit-price').val());
    let quantity = 1;
    let selectedTime = "ASAP"; // Default to ASAP

    function updateState() {
        $('.qty-display, .sheet-qty-val').text(quantity);
        
        let total = (unitPrice * quantity).toFixed(2);
        $('.display-total').text(total);

        $('#final-qty').val(quantity);
        $('#final-time').val(selectedTime);
    }

    $('.qty-plus').click(function() {
        if (quantity < 20) {
            quantity++;
            updateState();
        }
    });

    $('.qty-minus').click(function() {
        if (quantity > 1) {
            quantity--;
            updateState();
        }
    });

    $('.time-pill').click(function() {
        $('.time-pill').removeClass('selected');
        let time = $(this).data('time');
        selectedTime = time;
        
        // Select match on both desktop & mobile
        $(`.time-pill[data-time="${time}"]`).addClass('selected');
        updateState();
    });

    $('#trigger-sheet-btn').click(function() {
        $('.sheet-overlay').fadeIn(200);
        $('.bottom-sheet').addClass('active');
    });

    $('.close-sheet, .sheet-overlay').click(function() {
        $('.bottom-sheet').removeClass('active');
        $('.sheet-overlay').fadeOut(200);
    });

    $('.submit-order-btn').click(function() {
        $('#order-form').submit();
    });
});