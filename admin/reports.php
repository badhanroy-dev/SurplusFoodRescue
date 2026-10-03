<?php
include "db.php";
include "admin_auth.php";

// Handle POST request to update report status and resolution details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_issue_id'])) {
    $issue_id = (int)$_POST['update_issue_id'];
    $status = trim($_POST['status'] ?? 'Pending');
    $resolution_details = trim($_POST['resolution_details'] ?? '');

    $status_esc = mysqli_real_escape_string($conn, $status);
    $res_esc    = mysqli_real_escape_string($conn, $resolution_details);

    $update_sql = "UPDATE issue_reports 
                   SET status = '$status_esc', resolution_details = '$res_esc', updated_at = NOW() 
                   WHERE issue_id = $issue_id";
    mysqli_query($conn, $update_sql);

    header("Location: reports.php");
    exit();
}

$sql = "SELECT ir.issue_id, u.full_name, u.role, ir.report_type, ir.description,
ir.related_donation_id, ir.related_delivery_id, ir.status, ir.resolution_details,
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
            <li><a href="../charityGuest/guest/logout.php">Logout</a></li>
        </ul>
    </div>
</aside>

<main>

<section class="header">
    <div class="header-left">
        <h1>Reports</h1>
        <p>View reports submitted by donors, charities, and volunteers</p>
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
        <p>Reports received from donors, charities, and volunteers</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Submitted By</th>
                <th>Role</th>
                <th>Report Type</th>
                <th>Date</th>
                <th>Status</th>
                <th>Update Status & Resolution</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>

        <?php
        if (mysqli_num_rows($result) > 0) {

            while ($report = mysqli_fetch_assoc($result)) {

                $status = $report['status'];
                $status_class = "status-reviewed";
                $status_text  = $status;

                if ($status == "Pending") {
                    $status_text = "Pending";
                    $status_class = "status-new";
                } elseif ($status == "Under Review") {
                    $status_text = "Under Review";
                    $status_class = "status-reviewing";
                } elseif ($status == "Resolved") {
                    $status_text = "Resolved";
                    $status_class = "status-reviewed";
                } elseif ($status == "Closed") {
                    $status_text = "Closed";
                    $status_class = "status-closed";
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
                        <?php echo htmlspecialchars($status_text); ?>
                    </span>
                </td>

                <td>
                    <form method="POST" action="reports.php" class="update-status-form">
                        <input type="hidden" name="update_issue_id" value="<?php echo $report['issue_id']; ?>">
                        
                        <select name="status" class="status-select">
                            <option value="Pending" <?php if($status=='Pending') echo 'selected'; ?>>Pending</option>
                            <option value="Under Review" <?php if($status=='Under Review') echo 'selected'; ?>>Under Review</option>
                            <option value="Resolved" <?php if($status=='Resolved') echo 'selected'; ?>>Resolved</option>
                            <option value="Closed" <?php if($status=='Closed') echo 'selected'; ?>>Closed</option>
                        </select>
                        
                        <input type="text" name="resolution_details" placeholder="Resolution notes..." value="<?php echo htmlspecialchars($report['resolution_details'] ?? ''); ?>" class="resolution-input">
                        
                        <button type="submit" class="save-status-btn">Save</button>
                    </form>
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
                <td colspan="7">No reports submitted yet.</td>
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