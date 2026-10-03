let currentDonationId = 0;

// Function to load pickup schedule details from backend PHP API
async function loadPickupDetails() {
    // 1. Get donation ID from URL query parameters (e.g. schedule-pickup.html?id=3)
    let urlParams = new URLSearchParams(window.location.search);
    let donationId = urlParams.get('id');

    if (!donationId) {
        console.log('No food donation ID provided in URL');
        return;
    }

    currentDonationId = donationId;

    // 2. Fetch data from PHP backend inside php/ folder
    let url = `php/schedule_pickup_process.php?id=${donationId}`;
    let response = await fetch(url);
    let data = await response.json();

    // 3. Redirect if unauthorized
    if (data.error === 'unauthorized') {
        window.location.href = data.redirect;
        return;
    }

    // 4. Populate Pickup Details on page
    if (data.success && data.donation) {
        let item = data.donation;
        currentDonationId = item.donation_id;

        let foodTitle = document.getElementById('pickup-food-title');
        if (foodTitle) foodTitle.textContent = item.food_name;

        let foodDesc = document.getElementById('pickup-food-desc');
        if (foodDesc) foodDesc.textContent = item.description;

        let quantityElem = document.getElementById('pickup-quantity');
        if (quantityElem) quantityElem.innerHTML = `<strong>${item.quantity}</strong> ${item.quantity_unit}`;

        let foodIdElem = document.getElementById('pickup-food-id');
        if (foodIdElem) foodIdElem.innerHTML = `<strong>FD-${item.donation_id}</strong>`;

        let availability = document.getElementById('donor-availability');
        if (availability) availability.textContent = item.preferred_time;

        let expiryElem = document.getElementById('expiry-window');
        if (expiryElem) expiryElem.textContent = item.expiry_window;

        let statusElem = document.getElementById('pickup-status');
        if (statusElem) statusElem.textContent = item.status || 'Pending Pickup';
    }

    // Populate Notifications
    if (data.notifications && data.notifications.length > 0) {
        let html = '';
        for (let n of data.notifications) {
            html += '<div class="notif-item"><h4>' + n.title + '</h4><p>' + n.message + '</p></div>';
        }
        let notifList = document.getElementById('notif-list');
        if (notifList) notifList.innerHTML = html;
    }
}

// Toggle notification dropdown
function toggleNotif() {
    let box = document.getElementById('notif-dropdown');
    if (box) {
        box.style.display = (box.style.display === 'block') ? 'none' : 'block';
    }
}

// Function to handle "Confirm Schedule" button click
async function confirmSchedule() {
    let timeInput = document.getElementById('pickup-time');
    let selectedTime = timeInput ? timeInput.value : '';

    if (!selectedTime) {
        alert('Please select a pickup time.');
        return;
    }

    if (!currentDonationId || currentDonationId <= 0) {
        alert('Invalid donation selection.');
        return;
    }

    // Send request to backend process to record pickup schedule in database
    let url = `php/schedule_pickup_process.php?action=confirm&id=${currentDonationId}&time=${encodeURIComponent(selectedTime)}`;
    let response = await fetch(url);
    let data = await response.json();

    if (data.error === 'unauthorized') {
        window.location.href = data.redirect;
        return;
    }

    if (data.success) {
        alert(data.message);
        window.location.href = data.redirect || 'claimed-food.html';
    } else {
        alert(data.message || 'Failed to schedule pickup.');
    }
}

// Run when page loads
loadPickupDetails();

