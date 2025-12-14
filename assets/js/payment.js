// /assets/js/payment.js

document.addEventListener('DOMContentLoaded', () => {
    
    // --- UI SWITCHING LOGIC ---
    const methods = document.querySelectorAll('.method-option');
    const views = document.querySelectorAll('.method-view');
    const hiddenInput = document.getElementById('selectedMethod');
    const submitBtn = document.getElementById('submitButton');
    const btnText = submitBtn.querySelector('.btn-text');

    methods.forEach(method => {
        method.addEventListener('click', () => {
            // 1. Visual Selection
            methods.forEach(m => m.classList.remove('active'));
            method.classList.add('active');

            // 2. Logic Update
            const selectedType = method.dataset.method;
            hiddenInput.value = selectedType;

            // 3. View Toggle
            views.forEach(view => view.classList.remove('active'));
            document.getElementById(`view-${selectedType}`).classList.add('active');

            // 4. Update Button Text
            if(selectedType === 'cash') {
                btnText.textContent = "Place Order (Pay Cash)";
                submitBtn.style.backgroundColor = "var(--nord14)"; // Green
            } else {
                btnText.textContent = "Confirm Payment";
                submitBtn.style.backgroundColor = "var(--nord10)"; // Blue
            }
        });
    });

    // --- STRIPE LOGIC ---
    // Replace with your actual Publishable Key
    const stripe = Stripe('pk_test_51SX1myLUKR77BP7mQd7X6amafGv985Qn9UGbOcA0RVRSbE2cIfCiTXkDbfXifnKfJRYs2IvkBWrZF6Smi1LRMS2l00bfmNBJee'); 
    const elements = stripe.elements();
    
    const style = {
        base: {
            color: '#2E3440', // Nord0
            fontFamily: '"Urbanist", sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': { color: '#aab7c4' }
        },
        invalid: { color: '#bf616a', iconColor: '#bf616a' } // Nord11
    };

    const card = elements.create('card', { style: style, hidePostalCode: true });
    card.mount('#card-element');

    card.on('change', ({error}) => {
        const displayError = document.getElementById('card-errors');
        if (error) {
            displayError.textContent = error.message;
            displayError.style.color = "var(--nord11)";
            displayError.style.marginTop = "10px";
            displayError.style.fontSize = "0.85rem";
        } else {
            displayError.textContent = '';
        }
    });

});