let currentDonationId = 0;

// Function to load claim details from backend PHP API
async function loadClaimDetails() {
    // 1. Get donation ID from URL query parameters (e.g. claimConfirmation.html?id=3)
    let urlParams = new URLSearchParams(window.location.search);
    let donationId = urlParams.get('id');

    if (!donationId) {
        console.log('No food donation ID provided in URL');
        return;
    }

    currentDonationId = donationId;

    // 2. Fetch data from PHP backend inside php/ folder
    let url = `php/claim_confirmation_process.php?id=${donationId}`;
    let response = await fetch(url);
    let data = await response.json();

    // 3. Redirect if unauthorized
    if (data.error === 'unauthorized') {
        window.location.href = data.redirect;
        return;
    }

    // 4. Populate Claim Details on page
    if (data.success && data.donation) {
        let item = data.donation;

        // Image
        let imgElem = document.getElementById('claim-food-img');
        if (imgElem) {
            imgElem.src = item.image_path;
            imgElem.alt = item.food_name;
        }

        // Selected Food Section
        let foodName = document.getElementById('claim-food-name');
        if (foodName) foodName.textContent = item.food_name;

        let donorName = document.getElementById('claim-donor-name');
        if (donorName) donorName.textContent = 'Donor: ' + item.donor_name;

        let quantity = document.getElementById('claim-quantity');
        if (quantity) quantity.textContent = `${item.quantity} ${item.quantity_unit}`;

        // Impact
        let impactPeople = document.getElementById('claim-impact-people');
        if (impactPeople) impactPeople.textContent = item.estimated_impact;

        // Pickup Details
        let pickupDate = document.getElementById('claim-pickup-date');
        if (pickupDate) pickupDate.textContent = item.pickup_date;

        let pickupTime = document.getElementById('claim-pickup-time');
        if (pickupTime) pickupTime.textContent = item.preferred_time;

        let pickupLocation = document.getElementById('claim-pickup-location');
        if (pickupLocation) pickupLocation.textContent = item.pickup_address;
    }
}

// Function to handle "Schedule Pickup / Confirm Claim" button click
async function confirmClaim() {
    let checkbox = document.getElementById('compliance-checkbox');
    if (checkbox && !checkbox.checked) {
        alert('Please check the compliance checkbox to confirm food safety guidelines.');
        return;
    }

    if (!currentDonationId || currentDonationId <= 0) {
        alert('Invalid donation selection.');
        return;
    }

    // Send request to backend process to record claim in database
    let response = await fetch(`php/claim_confirmation_process.php?action=confirm&id=${currentDonationId}`);
    let data = await response.json();

    if (data.error === 'unauthorized') {
        window.location.href = data.redirect;
        return;
    }

    if (data.success) {
        alert(data.message);
        window.location.href = data.redirect || 'claimed-food.html';
    } else {
        alert(data.message || 'Failed to claim food donation.');
    }
}

// Run when page loads
loadClaimDetails();
