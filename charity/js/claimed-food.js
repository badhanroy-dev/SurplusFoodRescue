// Function to fetch and render claimed food items and stats from PHP backend
async function loadClaimedFood() {
    // 1. Fetch data from PHP backend inside php/ folder
    let response = await fetch('php/claimed_food_process.php');
    let data = await response.json();

    // 2. Redirect to login if user is not logged in
    if (data.error === 'unauthorized') {
        window.location.href = data.redirect;
        return;
    }

    // 3. Update Stat Cards
    if (data.success) {
        let statTotal = document.getElementById('stat-total');
        if (statTotal) statTotal.textContent = data.total_count;

        let statTransit = document.getElementById('stat-transit');
        if (statTransit) statTransit.textContent = data.transit_count;

        let statAwaiting = document.getElementById('stat-awaiting');
        if (statAwaiting) statAwaiting.textContent = data.awaiting_count;

        let statReady = document.getElementById('stat-ready');
        if (statReady) statReady.textContent = data.ready_count;

        // 4. Update Claimed Food Table Rows
        let container = document.getElementById('claimed-food-rows');
        if (!container) return;

        if (data.claimed_items && data.claimed_items.length > 0) {
            let rowsHtml = '';

            for (let item of data.claimed_items) {
                let expiryClass = item.is_urgent ? 'expiry urgent' : 'expiry';

                rowsHtml += `
                    <div class="table-row">
                        <!-- ITEM -->
                        <div class="item-detail">
                            <div class="item-detail-info">
                                <h3>${item.food_name}</h3>
                                <p>Claimed ${item.claim_date_formatted}</p>
                            </div>
                        </div>

                        <!-- DONOR -->
                        <div class="donor">
                            <div>
                                <strong>${item.donor_name}</strong>
                            </div>
                        </div>

                        <!-- QUANTITY -->
                        <div class="quantity">
                            <strong>${item.quantity} ${item.quantity_unit}</strong>
                        </div>

                        <!-- EXPIRY -->
                        <div class="${expiryClass}">
                            <strong>
                                <i class="fa-solid fa-clock"></i>
                                ${item.expiry_text}
                            </strong>
                            <span>left</span>
                        </div>

                        <!-- STATUS -->
                        <div>
                            <span class="status ${item.status_class}">
                                <i class="fa-solid ${item.status_icon}"></i>
                                ${item.status_text}
                            </span>
                        </div>

                        <!-- OTP -->
                        <div class="otp">
                            ${item.otp_code}
                        </div>
                    </div>
                `;
            }

            container.innerHTML = rowsHtml;
        } else {
            container.innerHTML = `
                <div class="table-row" style="grid-template-columns: 1fr; text-align: center; padding: 30px;">
                    <p style="color: #888;">No claimed food items found.</p>
                </div>
            `;
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

// Run function when page loads
loadClaimedFood();

