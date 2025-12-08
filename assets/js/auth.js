$(document).ready(function() {
    
    // 1. Password Visibility Toggle
    $('.password-toggle').on('click', function() {
        const input = $(this).siblings('input');
        const icon = $(this);
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // 2. Input Focus Animation (Optional Enhancement)
    $('input').focus(function() {
        $(this).parent('.input-wrapper').addClass('focused');
    }).blur(function() {
        $(this).parent('.input-wrapper').removeClass('focused');
    });

    // 3. Simple Client Side Validation Shake
    $('form').on('submit', function(e) {
        let isValid = true;
        $(this).find('input[required]').each(function() {
            if ($(this).val() === '') {
                isValid = false;
                $(this).addClass('shake-error');
                setTimeout(() => { $(this).removeClass('shake-error'); }, 500);
            }
        });
        
        if (!isValid) e.preventDefault();
    });

    /* Dark Mode Toggle Logic (Ready to trigger) */
    // if (localStorage.getItem('theme') === 'dark') {
    //     $('html').attr('data-theme', 'dark');
    // }
});