// Function to fetch and load report issue data (food list & issue reports)
async function loadReportIssueData() {
    let response = await fetch('php/report_issue_process.php');
    let data = await response.json();

    // Redirect to login if user is unauthorized
    if (data.error === 'unauthorized') {
        window.location.href = data.redirect;
        return;
    }

    if (data.success) {
        // 1. Populate Related Food dropdown
        let foodSelect = document.getElementById('related-food');
        if (foodSelect && data.food_listings) {
            let optionsHtml = '<option value="">Select food listing</option>';
            for (let item of data.food_listings) {
                optionsHtml += `<option value="${item.donation_id}">${item.food_name} - FD - ${item.donation_id}</option>`;
            }
            foodSelect.innerHTML = optionsHtml;
        }

        // 2. Populate Reports Table rows
        let container = document.getElementById('reports-list');
        if (container) {
            if (data.reports && data.reports.length > 0) {
                let rowsHtml = '';

                for (let item of data.reports) {
                    rowsHtml += `
                        <div class="report-row">
                            <div class="report-id">
                                #RPT-${item.issue_id}
                            </div>

                            <div class="issue-info">
                                <div>
                                    <strong>${item.report_type}</strong>
                                    <p>${item.food_name}</p>
                                </div>
                            </div>

                            <div class="report-date">
                                ${item.date_formatted}
                            </div>

                            <div>
                                <span class="status ${item.status_class}">
                                    <i class="fa-solid ${item.status_icon}"></i>
                                    ${item.status_text}
                                </span>
                            </div>

                            <div class="resolution ${item.resolution_class}">
                                ${item.resolution_details}
                            </div>
                        </div>
                    `;
                }

                container.innerHTML = rowsHtml;
            } else {
                container.innerHTML = `
                    <div class="report-row" style="grid-template-columns: 1fr; text-align: center; padding: 25px;">
                        <p style="color: #888;">No issue reports found.</p>
                    </div>
                `;
            }
        }
    }
}

// Function to handle "Submit Report" button click (called by onclick="submitReport()" in HTML)
async function submitReport() {
    let reportType  = document.getElementById('report-type').value;
    let relatedFood = document.getElementById('related-food').value;
    let description = document.getElementById('description').value;
    let fileInput   = document.getElementById('evidence-file');

    

    let formData = new FormData();
    formData.append('report_type', reportType);
    formData.append('related_donation_id', relatedFood);
    formData.append('description', description);

    if (fileInput && fileInput.files.length > 0) {
        formData.append('evidence_file', fileInput.files[0]);
    }

    let response = await fetch('php/report_issue_process.php', {
        method: 'POST',
        body: formData
    });

    let data = await response.json();

    if (data.error === 'unauthorized') {
        window.location.href = data.redirect;
        return;
    }

    if (data.success) {
        alert(data.message);
        document.getElementById('report-type').value = '';
        document.getElementById('related-food').value = '';
        document.getElementById('description').value = '';
        fileInput.value = '';
        loadReportIssueData();
    } else {
        alert(data.message);
    }
}

// Run function when page loads
loadReportIssueData();
