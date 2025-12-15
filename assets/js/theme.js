/* assets/js/theme.js */

// 1. GLOBAL: Apply the theme immediately (Runs on every page)
(function() {
    // Check localStorage or default to light
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
})();

// 2. PROFILE PAGE ONLY: Handle the toggle switch
document.addEventListener('DOMContentLoaded', () => {
    const themeToggleBtn = document.getElementById('theme-toggle');
    
    // Only run this logic if the button actually exists on the current page
    if (themeToggleBtn) {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        
        // Set initial switch state (checked = dark)
        themeToggleBtn.checked = (currentTheme === 'dark');
        
        // Listen for changes
        themeToggleBtn.addEventListener('change', function(e) {
            const newTheme = e.target.checked ? 'dark' : 'light';
            
            // Apply visual change
            document.documentElement.setAttribute('data-theme', newTheme);
            
            // Save to memory for other pages
            localStorage.setItem('theme', newTheme);
        });
    }
});