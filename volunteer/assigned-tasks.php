<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../charityGuest/guest/login.html");
    exit();
}
require "db.php";
$user_id = (int)$_SESSION["user_id"];

$query_sql = "
    SELECT pd.delivery_id, pd.pickup_address, pd.delivery_address, pd.scheduled_time, pd.delivery_status,
           fd.food_name, fd.quantity, fd.quantity_unit,
           donor.full_name AS donor_name, charity.full_name AS charity_name
    FROM pickup_deliveries pd
    INNER JOIN food_claims fc ON pd.claim_id = fc.claim_id
    INNER JOIN food_donations fd ON fc.donation_id = fd.donation_id
    INNER JOIN users donor ON fd.donor_id = donor.user_id
    INNER JOIN users charity ON fc.charity_id = charity.user_id
    WHERE pd.volunteer_id = ?
    ORDER BY pd.updated_at DESC
";
$query = mysqli_prepare($conn, $query_sql);
mysqli_stmt_bind_param($query, "i", $user_id);
mysqli_stmt_execute($query);
$tasks = mysqli_stmt_get_result($query);

// Fetch Broadcast Notifications for Volunteers
$vol_notif_res = mysqli_query($conn, "SELECT title, message, created_at FROM broadcasts WHERE status = 'Sent' AND audience IN ('All Users', 'Volunteers Only') ORDER BY created_at DESC LIMIT 5");
$volunteer_notifications = $vol_notif_res ? mysqli_fetch_all($vol_notif_res, MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rider Portal - Assigned Tasks</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assigned-tasks.css">
</head>
<body>
<div class="container">
    <div class="sidebar">
        <div class="sidebar-title">
            <h1>Rider Portal</h1>
            <p>Mission-Driven Service</p>
        </div>
        <ul class="menu">
            <li class="menu-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="menu-item"><a href="available-tasks.php">Available Tasks</a></li>
            <li class="menu-item active"><a href="assigned-tasks.php">Assigned Tasks</a></li>
            <li class="menu-item"><a href="otp-verification.php">OTP Verification</a></li>
            <li class="menu-item"><a href="update-delivery-status.php">Update Delivery Status</a></li>
            <li class="menu-item"><a href="report-delay.php">Report Delay</a></li>
            <li class="menu-item"><a href="volunteer-chat.php">Chat</a></li>
        </ul>
        <ul class="menu bottom-menu">
            <li class="menu-item"><a href="../charityGuest/guest/settings.html">Settings</a></li>
            <li class="menu-item"><a href="../charityGuest/guest/logout.php">Logout</a></li>
        </ul>
    </div>
    <div class="main">
        <div class="page-header">
            <div>
                <h2 class="page-heading">Assigned Tasks</h2>
                <p class="page-subtitle">Tasks you have accepted and need to complete.</p>
            </div>
            <div class="profile-section" style="position: relative;">
                <div class="notif-container" style="position: relative; cursor: pointer; display: inline-block;" onclick="toggleVolNotif(event)">
                    <i class="fa-solid fa-bell" style="font-size: 18px; color: #A33215;"></i>
                    <?php if (count($volunteer_notifications) > 0) { ?>
                        <span style="position: absolute; top: -8px; right: -8px; background: #e71c16; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: bold; line-height: 1;"><?php echo count($volunteer_notifications); ?></span>
                    <?php } ?>
                    <div id="vol-notif-dropdown" class="notif-dropdown" style="display: none; position: absolute; right: 0; top: 30px; width: 260px; background: white; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; padding: 12px; text-align: left;">
                        <h4 style="margin: 0 0 8px 0; color: #A33215; font-size: 13px; border-bottom: 1px solid #eee; padding-bottom: 6px;">Broadcast Notifications</h4>
                        <?php if (count($volunteer_notifications) > 0) { 
                            foreach ($volunteer_notifications as $notif) { ?>
                                <div class="notif-item" style="padding: 8px 0; border-bottom: 1px solid #f5f5f5;">
                                    <strong style="font-size: 12px; color: #C54716; display: block;"><?php echo htmlspecialchars($notif['title']); ?></strong>
                                    <p style="font-size: 11px; color: #555; margin: 3px 0 0 0; line-height: 1.4;"><?php echo htmlspecialchars($notif['message']); ?></p>
                                </div>
                        <?php } } else { ?>
                            <p style="font-size: 12px; color: #888; margin: 0;">No new notifications</p>
                        <?php } ?>
                    </div>
                </div>
                <i class="fa-solid fa-circle-user" style="font-size: 18px; color: #A33215;"></i>
            </div>
        </div>
        <script>
        function toggleVolNotif(e) {
            if (e) e.stopPropagation();
            var box = document.getElementById('vol-notif-dropdown');
            if (box) {
                box.style.display = (box.style.display === 'block') ? 'none' : 'block';
            }
        }
        document.addEventListener('click', function(e) {
            var box = document.getElementById('vol-notif-dropdown');
            if (box && box.style.display === 'block') {
                var notifContainer = e.target.closest('.notif-container');
                if (!notifContainer) {
                    box.style.display = 'none';
                }
            }
        });
        </script>
        <div class="content-box">
            <div class="box-header">
                <h3>Active Assignments</h3>
                <div class="header-actions">
                    <button class="btn btn-outline" id="filter-tasks" type="button">Filter Active</button>
                </div>
            </div>
            <?php if ($tasks && mysqli_num_rows($tasks) > 0) { ?>
                <?php while ($task = mysqli_fetch_assoc($tasks)) { ?>
                    <div class="assigned-row" data-status="<?= htmlspecialchars(strtolower($task["delivery_status"])) ?>" data-pickup-time="<?= htmlspecialchars($task["scheduled_time"]) ?>">
                        <div class="assigned-info">
                            <p class="assigned-name"><?= htmlspecialchars($task["food_name"]) ?> — <?= htmlspecialchars($task["donor_name"]) ?></p>
                            <p class="assigned-meta">Pickup: <?= htmlspecialchars($task["pickup_address"]) ?> &nbsp; | &nbsp; Dropoff: <?= htmlspecialchars($task["delivery_address"]) ?> &nbsp; | &nbsp; Status: <strong><?= htmlspecialchars($task["delivery_status"]) ?></strong></p>
                        </div>
                        <div class="row-buttons">
                            <a href="task-details.php?delivery_id=<?= (int)$task["delivery_id"] ?>" class="btn btn-outline">View Details</a>
                            <?php if (in_array($task["delivery_status"], ['Completed', 'Delivered'], true)) { ?>
                                <span class="btn" style="background: #e2f0d9; color: #2e7d32; font-weight: bold;">✔ Completed</span>
                            <?php } else { ?>
                                <a href="update-delivery-status.php?delivery_id=<?= (int)$task["delivery_id"] ?>" class="btn btn-primary">Update Status</a>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p style="padding: 20px; color: #666;">You currently have no assigned tasks.</p>
            <?php } ?>
        </div>
    </div>
</div>
<script>
var filterButton = document.getElementById('filter-tasks');
var assignments = document.getElementsByClassName('assigned-row');
var activeOnly = false;

filterButton.addEventListener('click', function() {
    activeOnly = !activeOnly;
    for (var i = 0; i < assignments.length; i++) {
        if (activeOnly) {
            var st = assignments[i].getAttribute('data-status');
            assignments[i].style.display = (st === 'completed' || st === 'delivered') ? 'none' : '';
        } else {
            assignments[i].style.display = '';
        }
    }
    filterButton.textContent = activeOnly ? 'Show All' : 'Filter Active';
});
</script>
</body>
</html>
