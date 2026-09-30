// Function to load food details from backend PHP API
async function loadFoodDetails() {
    // 1. Get donation ID from URL query parameters (e.g. fooddetails.html?id=3)
    let urlParams = new URLSearchParams(window.location.search);
    let donationId = urlParams.get('id');

    if (!donationId) {
        console.log('No food donation ID provided in URL');
        return;
    }

    // 2. Fetch data from PHP backend inside php/ folder
    let url = `php/food_details_process.php?id=${donationId}`;
    let response = await fetch(url);
    let data = await response.json();

    // 3. Redirect to login if user is not logged in
    if (data.error === 'unauthorized') {
        window.location.href = data.redirect;
        return;
    }

    // 4. Populate Food Details on page
    if (data.success && data.donation) {
        let item = data.donation;

        let headingTitle = document.getElementById('food-heading-title');
        if (headingTitle) headingTitle.textContent = item.food_name;

        let breadcrumbName = document.getElementById('food-breadcrumb-name');
        if (breadcrumbName) breadcrumbName.textContent = item.food_name;

        // Image
        let imgElem = document.getElementById('food-detail-img');
        if (imgElem) {
            imgElem.src = item.image_path;
            imgElem.alt = item.food_name;
        }

        // Badges
        let statusBadge = document.getElementById('food-status-badge');
        if (statusBadge) {
            statusBadge.innerHTML = `<i class="fa-solid fa-circle"></i> ${item.status === 'AVAILABLE' ? 'AVAILABLE NOW' : item.status}`;
        }

        let expiryBadge = document.getElementById('food-expiry-badge');
        if (expiryBadge) {
            expiryBadge.innerHTML = `<i class="fa-regular fa-clock"></i> ${item.expiry_tag}`;
        }

        let categoryBadge = document.getElementById('food-category-badge');
        if (categoryBadge) {
            categoryBadge.innerHTML = `<i class="fa-solid fa-box"></i> ${item.category_name}`;
        }

        // Information Cards
        let safetyText = document.getElementById('food-safety-text');
        if (safetyText) safetyText.textContent = item.food_safety_info;

        let descText = document.getElementById('food-description-text');
        if (descText) descText.textContent = item.description;

        // Right side Rescue Card
        let rescueTitle = document.getElementById('rescue-food-name');
        if (rescueTitle) rescueTitle.textContent = item.food_name;

        let rescueDonor = document.getElementById('rescue-donor-name');
        if (rescueDonor) rescueDonor.textContent = item.donor_name;

        let rescueCategory = document.getElementById('rescue-food-type');
        if (rescueCategory) rescueCategory.textContent = item.category_name;

        let rescueQuantity = document.getElementById('rescue-quantity');
        if (rescueQuantity) rescueQuantity.textContent = `${item.quantity} ${item.quantity_unit}`;

        let rescueArea = document.getElementById('rescue-pickup-area');
        if (rescueArea) rescueArea.textContent = item.pickup_address;

        let rescueTime = document.getElementById('rescue-pickup-time');
        if (rescueTime) rescueTime.textContent = item.preferred_time;

        // Claim Button Link
        let claimLink = document.getElementById('claim-button-link');
        if (claimLink) {
            claimLink.href = `claimConfirmation.html?id=${item.donation_id}`;
        }
    }
}

// Initial load when page opens
loadFoodDetails();
