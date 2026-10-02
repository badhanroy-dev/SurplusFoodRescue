// Function to load completed claimed items into dropdown menu
async function loadAvailableItems() {
    // 1. Fetch data from PHP backend
    let response = await fetch('php/add_distribution_process.php');
    let data = await response.json();

    // 2. Redirect to login if unauthorized
    if (data.error === 'unauthorized') {
        window.location.href = data.redirect;
        return;
    }

    // 3. Populate select dropdown options
    if (data.success && data.items) {
        let selectElem = document.getElementById('food-item');
        if (!selectElem) return;

        if (data.items.length > 0) {
            let optionsHtml = '<option value="">Select a completed item...</option>';
            for (let item of data.items) {
                optionsHtml += `<option value="${item.claim_id}">${item.food_name} (${item.quantity} ${item.quantity_unit})</option>`;
            }
            selectElem.innerHTML = optionsHtml;
        } else {
            selectElem.innerHTML = '<option value="">No completed food items available to log</option>';
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
}

// Toggle notification dropdown
function toggleNotif() {
    let box = document.getElementById('notif-dropdown');
    if (box) {
        box.style.display = (box.style.display === 'block') ? 'none' : 'block';
    }
}

// Function to handle "Save Record" button click (called by onclick="saveDistribution()" in HTML)
async function saveDistribution() {
    let claimId  = document.getElementById('food-item').value;
    let quantity = document.getElementById('quantity').value;
    let unit     = document.getElementById('quantity-unit').value;
    let quality  = document.getElementById('quality').value;
    let people   = document.getElementById('people').value;
    let remarks  = document.getElementById('remarks').value;

    if (!claimId) {
        alert('Please select a completed food item.');
        return;
    }

    if (!quantity || parseFloat(quantity) <= 0) {
        alert('Please enter a valid distributed quantity.');
        return;
    }

    if (!people || parseInt(people) < 0) {
        alert('Please enter a valid number of people served.');
        return;
    }

    // Send request to backend PHP process
    let url = `php/add_distribution_process.php?action=save&claim_id=${claimId}&quantity=${quantity}&quantity_unit=${unit}&quality=${quality}&people_served=${people}&remarks=${remarks}`;
    let response = await fetch(url);
    let data = await response.json();

    if (data.error === 'unauthorized') {
        window.location.href = data.redirect;
        return;
    }

    if (data.success) {
        alert(data.message);
        window.location.href = data.redirect || 'distribution-log.html';
    } else {
        alert(data.message || 'Failed to save distribution record.');
    }
}

// Initial load when page opens
loadAvailableItems();

