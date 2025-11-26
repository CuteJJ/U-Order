document.addEventListener("DOMContentLoaded", () => {
    // --- Elements ---
    const qtyDisplays = document.querySelectorAll(".qty-display");
    const qtyInputs = document.querySelectorAll("#final-qty");
    const displayTotals = document.querySelectorAll(".display-total");
    const timePills = document.querySelectorAll(".time-pill");
    const noteInputs = document.querySelectorAll(".note-input");
    
    // Hidden Form Inputs
    const hiddenQty = document.getElementById("final-qty");
    const hiddenTime = document.getElementById("final-time");
    const hiddenNote = document.getElementById("final-note");
    const unitPrice = parseFloat(document.getElementById("unit-price").value);

    // Mobile Sheet Elements
    const sheetOverlay = document.querySelector(".sheet-overlay");
    const bottomSheet = document.querySelector(".bottom-sheet");
    const closeSheetBtn = document.querySelector(".close-sheet");
    const triggerSheetBtn = document.getElementById("trigger-sheet-btn");
    
    // --- State ---
    let currentQty = parseInt(hiddenQty.value) || 1;

    // --- 1. Quantity Logic ---
    function updateQuantity(change) {
        const newQty = currentQty + change;
        if (newQty < 1) return;
        
        currentQty = newQty;
        
        // Update all visual displays (desktop & mobile)
        qtyDisplays.forEach(el => el.textContent = currentQty);
        
        // Update hidden input
        hiddenQty.value = currentQty;
        
        // Update Total Price
        const total = (currentQty * unitPrice).toFixed(2);
        displayTotals.forEach(el => el.textContent = total);
    }

    document.querySelectorAll(".qty-minus").forEach(btn => {
        btn.addEventListener("click", () => updateQuantity(-1));
    });

    document.querySelectorAll(".qty-plus").forEach(btn => {
        btn.addEventListener("click", () => updateQuantity(1));
    });

    // --- 2. Time Selection Logic ---
    timePills.forEach(pill => {
        pill.addEventListener("click", function() {
            // Remove active class from all pills
            timePills.forEach(p => p.classList.remove("selected"));
            
            // Add to clicked pill (and matching pills in other view)
            const timeValue = this.dataset.time;
            
            // Sync Desktop & Mobile selections
            document.querySelectorAll(`.time-pill[data-time="${timeValue}"]`)
                .forEach(p => p.classList.add("selected"));

            // Update hidden input
            hiddenTime.value = timeValue;
        });
    });

    // --- 3. Note Sync Logic ---
    // Syncs what you type in desktop to mobile and vice versa
    noteInputs.forEach(input => {
        input.addEventListener("input", (e) => {
            const val = e.target.value;
            noteInputs.forEach(el => el.value = val);
            hiddenNote.value = val;
        });
    });

    // --- 4. Mobile Bottom Sheet Logic ---
    function openSheet() {
        sheetOverlay.classList.add("active");
        bottomSheet.classList.add("active");
        document.body.style.overflow = "hidden"; // Prevent background scroll
    }

    function closeSheet() {
        sheetOverlay.classList.remove("active");
        bottomSheet.classList.remove("active");
        document.body.style.overflow = "";
    }

    if (triggerSheetBtn) triggerSheetBtn.addEventListener("click", openSheet);
    if (closeSheetBtn) closeSheetBtn.addEventListener("click", closeSheet);
    if (sheetOverlay) sheetOverlay.addEventListener("click", closeSheet);

    // --- 5. Form Submission ---
    // Handle both Desktop and Mobile submit buttons
    document.querySelectorAll(".submit-order-btn").forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            // Optional: Validation
            if (!hiddenTime.value) {
                alert("Please select a pickup time");
                return;
            }
            document.getElementById("order-form").submit();
        });
    });
});