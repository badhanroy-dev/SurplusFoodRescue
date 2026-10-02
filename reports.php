<?php
include "db.php";
include "admin_auth.php";


$sql = "SELECT ir.issue_id, u.full_name, u.role, ir.report_type, ir.description,
ir.related_donation_id, ir.related_delivery_id, ir.status,ir.resolution_details,
ir.created_at FROM issue_reports ir JOIN users u ON ir.reporter_id = u.user_id
ORDER BY ir.created_at DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="stylesheet" href="css/reports.css">
</head>

<body>
<div class="webpage">

<aside>
    <div class="sidebar-top">
        <h2>Food Rescue</h2>
        <p>Admin Portal</p>

        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="donoracc.php">Donor Accounts</a></li>
            <li><a href="volunteer_verify.php">Volunteer Verification</a></li>
            <li><a href="foodcategories.php">Food Categories</a></li>
            <li><a href="analytics.php">Analytics</a></li>
            <li class="active"><a href="broadcasts.php">Broadcasts</a></li>
            <li><a href="chat.php">Chat</a></li>
        </ul>
    </div>

    <div class="sidebar-bottom">
        <ul>
            <li><a href="settings.php">Settings</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
</aside>

<main>

<section class="header">
    <div class="header-left">
        <h1>Reports</h1>
        <p>View reports submitted by donors and volunteers</p>
    </div>

    <div class="header-right">
        <div class="admin-profile">
            <img src="images/notifications.png" class="notification-icon">
            <img src="images/profile.png" class="profile-icon">
            <div>
                <h4>Admin</h4>
            </div>
        </div>
    </div>
</section>

<nav class="tabs">
    <a href="broadcasts.php" class="tab">
        Urgent Alert
    </a>

    <a href="campaign.php" class="tab">
        Awareness Campaign
    </a>

    <a href="reports.php" class="tab active-tab">
        Reports
    </a>
</nav>

<section class="reports-container">

    <div class="reports-header">
        <h2>Submitted Reports</h2>
        <p>Reports received from donors and volunteers</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Submitted By</th>
                <th>Role</th>
                <th>Report Type</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>

        <?php
        if (mysqli_num_rows($result) > 0) {

            while ($report = mysqli_fetch_assoc($result)) {

                $status_class = "status-reviewed";

                if ($report['status'] == "Pending") {
                    $status_text = "New";
                    $status_class = "status-new";
                } else {
                    $status_text = "Reviewed";
                }

                $date = date("d M Y", strtotime($report['created_at']));
        ?>

            <tr>
                <td><?php echo htmlspecialchars($report['full_name']); ?></td>

                <td><?php echo htmlspecialchars($report['role']); ?></td>

                <td><?php echo htmlspecialchars($report['report_type']); ?></td>

                <td><?php echo $date; ?></td>

                <td>
                    <span class="<?php echo $status_class; ?>">
                        <?php echo $status_text; ?>
                    </span>
                </td>

                <td>
                    <button
                        class="view-button"
                        onclick='viewReport(
                            <?php echo json_encode($report["full_name"]); ?>,
                            <?php echo json_encode($report["role"]); ?>,
                            <?php echo json_encode($report["report_type"]); ?>,
                            <?php echo json_encode($report["description"]); ?>,
                            <?php echo json_encode($report["status"]); ?>,
                            <?php echo json_encode($report["resolution_details"]); ?>,
                            <?php echo json_encode($report["related_donation_id"]); ?>,
                            <?php echo json_encode($report["related_delivery_id"]); ?>,
                            <?php echo json_encode($date); ?>
                        )'>
                        View
                    </button>
                </td>
            </tr>

        <?php
            }

        } else {
        ?>

            <tr>
                <td colspan="6">No reports submitted yet.</td>
            </tr>

        <?php
        }
        ?>

        </tbody>
    </table>

</section>

</main>
</div>

<script src="js/reports.js"></script>
</body>
</html>