function viewReport(name, role, type, description, status, resolution, donation, delivery, date) {

    let details = "";

    details += "Submitted By: " + name + "\n";
    details += "Role: " + role + "\n";
    details += "Report Type: " + type + "\n";
    details += "Date: " + date + "\n";
    details += "Status: " + status + "\n\n";
    details += "Description:\n" + description + "\n";

    if (donation) {
        details += "\nRelated Donation ID: " + donation;
    }

    if (delivery) {
        details += "\nRelated Delivery ID: " + delivery;
    }

    if (resolution) {
        details += "\n\nResolution:\n" + resolution;
    }

    alert(details);
}