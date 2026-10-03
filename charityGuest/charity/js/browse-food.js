let currentSort = 'asc';

// Main function to fetch and render food cards
async function loadBrowseFood(area = '', type = '', date = '') {
    // Build simple URL to send filters to PHP backend
    let url = `php/browse_food_process.php?area=${area}&type=${type}&date=${date}&sort=${currentSort}`;

    // 1. Fetch data from PHP backend
    let response = await fetch(url);
    let data = await response.json();

    // 2. Redirect to login if user is not logged in
    if (data.error === 'unauthorized') {
        window.location.href = data.redirect;
        return;
    }

    if (data.success) {
        // 3. Update count text
        let countElement = document.getElementById('results-count');
        if (countElement) {
            countElement.textContent = data.total_count + ' food rescue opportunities available';
        }

        // 4. Populate Food Cards Grid
        let container = document.getElementById('food-grid-container');
        if (!container) return;

        if (data.donations.length > 0) {
            let cardsHtml = '';

            for (let item of data.donations) {
                let imgSrc = item.image_path;

                cardsHtml += `
                    <div class="food-card">
                        <div class="food-image">
                            <img src="${imgSrc}" alt="${item.food_name}" onerror="this.onerror=null; ">
                            <span class="expiry-tag">${item.expiry_tag}</span>
                        </div>

                        <div class="food-card-content">
                            <div class="food-card-title">
                                <div>
                                    <h2>${item.food_name}</h2>
                                </div>
                                <span class="food-type">${item.category_name}</span>
                            </div>

                            <div class="food-info">
                                <div>
                                    <span>FOOD ID</span>
                                    <strong>FD-${item.donation_id}</strong>
                                </div>
                                <div>
                                    <span>QUANTITY</span>
                                    <strong>${item.quantity} ${item.quantity_unit}</strong>
                                </div>
                                <div>
                                    <span>STATUS</span>
                                    <strong>${item.status}</strong>
                                </div>
                            </div>

                            <div class="donor-info">
                                <div>
                                    <span>DONOR</span>
                                    <strong>${item.donor_name}</strong>
                                </div>
                            </div>

                            <div class="pickup-info">
                                <div>
                                    <span>
                                        <i class="fa-solid fa-location-dot"></i>
                                        AREA
                                    </span>
                                    <strong>${item.pickup_address}</strong>
                                </div>
                                <div>
                                    <span>
                                        <i class="fa-regular fa-clock"></i>
                                        PICKUP
                                    </span>
                                    <strong class="pickup-time">${item.preferred_time}</strong>
                                </div>
                            </div>

                            <div class="x">
                                <a href="fooddetails.html?id=${item.donation_id}">
                                    <button class="claim-button">Details</button>
                                </a>
                                <a href="claimConfirmation.html?id=${item.donation_id}">
                                    <button class="claim-button">
                                        Claim Food <i class="fa-solid fa-arrow-right"></i>
                                    </button>
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            }

            container.innerHTML = cardsHtml;
        } else {
            container.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #777;">
                    <h3>No Available Food Donations Found</h3>
                    <p style="margin-top: 8px;">Check back later or try adjusting your search filters.</p>
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

// Function 1: Separate function to handle Search button click (called by onclick="searchFood()" in HTML)
function searchFood() {
    let areaInput = document.getElementById('filter-area');
    let typeInput = document.getElementById('filter-type');
    let dateInput = document.getElementById('filter-date');

    let area = areaInput ? areaInput.value : '';
    let type = typeInput ? typeInput.value : '';
    let date = dateInput ? dateInput.value : '';

    loadBrowseFood(area, type, date);
}

// Function 2: Separate function to handle Sort button click (called by onclick="sortFood()" in HTML)
function sortFood() {
    if (currentSort === 'asc') {
        currentSort = 'desc';
    } else {
        currentSort = 'asc';
    }

    let sortBtn = document.getElementById('sort-btn');
    if (sortBtn) {
        sortBtn.innerHTML = '<i class="fa-solid fa-arrow-down-wide-short"></i> Sort by Expiry (' + currentSort.toUpperCase() + ')';
    }

    // Perform search with updated sort order
    searchFood();
}

// Toggle notification dropdown
function toggleNotif() {
    let box = document.getElementById('notif-dropdown');
    if (box) {
        box.style.display = (box.style.display === 'block') ? 'none' : 'block';
    }
}

// Initial load when page first opens
loadBrowseFood();

