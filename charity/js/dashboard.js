async function loadDashboard() {
    // 1. Fetch data from PHP backend inside php/ folder
    let response = await fetch('php/dashboard_process.php');
    let data = await response.json();

    // 2. Redirect to login if user is not logged in
    if (data.error === 'unauthorized') {
        window.location.href = data.redirect;
        return;
    }

    // 3. Update Welcome Header & 4 Stat Cards
    if (data.success) {
        document.getElementById('welcome-title').textContent = 'Welcome, ' + data.charity_name;
        document.getElementById('stat-available').textContent = data.available_count;
        document.getElementById('stat-active').textContent    = data.active_count;
        document.getElementById('stat-pickups').textContent   = data.pickups_count;
        document.getElementById('stat-served').textContent    = data.served_count;

        // 4. Update Recent Claimed Food Table Rows
        let container = document.getElementById('recent-food-rows');

        if (data.recent_claims.length > 0) {
            let rowsHtml = '';

            // Simple loop through each food item
            for (let item of data.recent_claims) {
                // Apply brown badge for COMPLETED, orange badge for all active claims
                let badgeClass = (item.status === 'COMPLETED') ? 'completed' : 'claimed';

                rowsHtml += `
                    <div class="food-row">
                        <div class="food-details">
                            <div>
                                <h3>${item.food_name}</h3>
                                <p>${item.quantity_claimed} ${item.quantity_unit}</p>
                            </div>
                        </div>
                        <div>${item.donor_name}</div>
                        <div>${item.claim_date_formatted}<br>${item.claim_time_formatted}</div>
                        <div>
                            <span class="status ${badgeClass}">${item.status}</span>
                        </div>
                    </div>
                `;
            }

            container.innerHTML = rowsHtml;
        } else {
            container.innerHTML = `
                <div class="food-row" style="grid-template-columns: 1fr; text-align: center;">
                    <p style="padding: 10px; color: #888;">No recent claimed food records found.</p>
                </div>
            `;
        }
    }
}

// Run function when page loads
loadDashboard();
