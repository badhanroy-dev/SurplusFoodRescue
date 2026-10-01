// Function to fetch and render distribution log items from PHP backend
async function loadDistributionLog() {
    // 1. Fetch data from PHP backend
    let response = await fetch('php/distribution_log_process.php');
    let data = await response.json();

    // 2. Redirect to login if user is unauthorized
    if (data.error === 'unauthorized') {
        window.location.href = data.redirect;
        return;
    }

    // 3. Render distribution log table rows
    if (data.success) {
        let container = document.getElementById('distribution-log-rows');
        if (!container) return;

        if (data.distributions && data.distributions.length > 0) {
            let rowsHtml = '';

            for (let item of data.distributions) {
                let remarkHtml = item.remarks
                    ? `<span class="remark-tag">${item.remarks}</span>`
                    : `<span>-</span>`;

                rowsHtml += `
                    <div class="table-row">
                        <!-- FOOD ITEM & ID -->
                        <div class="food-item">
                            <div>
                                <h3>${item.food_item_name}</h3>
                                <p>Food ID: ${item.food_id}</p>
                            </div>
                        </div>

                        <!-- DATE -->
                        <div class="date">
                            <strong>${item.date_month_day}</strong>
                            <span>${item.date_year}</span>
                        </div>

                        <!-- RECEIVED -->
                        <div class="number">
                            ${item.received_qty}
                            <span>${item.quantity_unit}</span>
                        </div>

                        <!-- DISTRIBUTED -->
                        <div class="number">
                            ${item.distributed_qty}
                            <span>${item.quantity_unit}</span>
                        </div>

                        <!-- PEOPLE SERVED -->
                        <div class="number">
                            ${item.people_served}
                        </div>

                        <!-- QUALITY -->
                        <div class="quality">
                            <span>${item.quality}</span>
                        </div>

                        <!-- REMARKS -->
                        <div class="remark">
                            ${remarkHtml}
                        </div>

                        <div class="more"></div>
                    </div>
                `;
            }

            container.innerHTML = rowsHtml;
        } else {
            container.innerHTML = `
                <div class="table-row" style="grid-template-columns: 1fr; text-align: center; padding: 30px;">
                    <p style="color: #888;">No distribution records found.</p>
                </div>
            `;
        }
    }
}

// Function to download / print distribution log
function downloadLog() {
    document.querySelector('.sidebar').style.display = 'none';
    document.querySelector('.topbar').style.display = 'none';
    document.querySelector('.page-header').style.display = 'none';
    document.querySelector('.section-header').style.display = 'none';
    window.print();
    document.querySelector('.sidebar').style.display = '';
    document.querySelector('.topbar').style.display = '';
    document.querySelector('.page-header').style.display = '';
    document.querySelector('.section-header').style.display = '';
}

// Run function when page loads
loadDistributionLog();
