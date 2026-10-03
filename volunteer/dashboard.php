<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../charityGuest/guest/login.html");
    exit();
}
require "db.php";
$user_id = (int)$_SESSION["user_id"];
$name = isset($_SESSION["full_name"]) ? $_SESSION["full_name"] : (isset($_SESSION["name"]) ? $_SESSION["name"] : "Volunteer");

$available_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pickup_deliveries WHERE delivery_status='Available'");
$available_assoc = $available_res ? mysqli_fetch_assoc($available_res) : ['total' => 0];
$available_count = $available_assoc['total'];

$assigned_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pickup_deliveries WHERE volunteer_id=$user_id AND delivery_status IN ('Assigned', 'Accepted', 'Picked Up', 'In Transit')");
$assigned_assoc = $assigned_res ? mysqli_fetch_assoc($assigned_res) : ['total' => 0];
$assigned_count = $assigned_assoc['total'];

$completed_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pickup_deliveries WHERE volunteer_id=$user_id AND delivery_status IN ('Delivered', 'Completed')");
$completed_assoc = $completed_res ? mysqli_fetch_assoc($completed_res) : ['total' => 0];
$completed_count = $completed_assoc['total'];

$recent_sql = "
    SELECT pd.delivery_id, pd.pickup_address, pd.delivery_address, pd.scheduled_time, fd.food_name, fd.quantity, fd.quantity_unit, u.full_name AS donor_name
    FROM pickup_deliveries pd
    INNER JOIN food_claims fc ON pd.claim_id = fc.claim_id
    INNER JOIN food_donations fd ON fc.donation_id = fd.donation_id
    INNER JOIN users u ON fd.donor_id = u.user_id
    WHERE pd.delivery_status = 'Available'
    ORDER BY pd.created_at DESC
    LIMIT 3
";
$recent = mysqli_query($conn, $recent_sql);

// Fetch Broadcast Notifications for Volunteers
$vol_notif_res = mysqli_query($conn, "SELECT title, message, created_at FROM broadcasts WHERE status = 'Sent' AND audience IN ('All Users', 'Volunteers Only') ORDER BY created_at DESC LIMIT 5");
$volunteer_notifications = $vol_notif_res ? mysqli_fetch_all($vol_notif_res, MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rider Portal - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
<div class="container">
    <div class="sidebar">
        <div class="sidebar-title">
            <h1>Rider Portal</h1>
            <p>Mission-Driven Service</p>
        </div>
        <ul class="menu">
            <li class="menu-item active"><a href="dashboard.php">Dashboard</a></li>
            <li class="menu-item"><a href="available-tasks.php">Available Tasks</a></li>
            <li class="menu-item"><a href="assigned-tasks.php">Assigned Tasks</a></li>
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
                <h2 class="page-heading">Volunteer Dashboard</h2>
                <p class="page-subtitle">Welcome back, <?= htmlspecialchars($name) ?>.</p>
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
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= (int)$available_count ?></div>
                <div class="stat-label">AVAILABLE TASKS</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= (int)$assigned_count ?></div>
                <div class="stat-label">ASSIGNED TASKS</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= (int)$completed_count ?></div>
                <div class="stat-label">COMPLETED DELIVERIES</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= (int)$assigned_count ?></div>
                <div class="stat-label">PENDING DELIVERIES</div>
            </div>
        </div>
        <div class="content-box">
            <div class="box-header">
                <h3>New Available Tasks</h3>
                <a href="available-tasks.php" class="view-link">View all available</a>
            </div>
            <?php if ($recent && mysqli_num_rows($recent) > 0) { ?>
                <?php while ($task = mysqli_fetch_assoc($recent)) { ?>
                    <div class="task-row">
                        <div class="task-info">
                            <div>
                                <p class="task-name"><?= htmlspecialchars($task["donor_name"]) ?> - <?= htmlspecialchars($task["food_name"]) ?></p>
                                <p class="task-meta"><?= htmlspecialchars($task["pickup_address"]) ?> &nbsp; <?= htmlspecialchars($task["quantity"]) ?> <?= htmlspecialchars($task["quantity_unit"]) ?></p>
                            </div>
                        </div>
                        <a href="available-tasks.php" class="btn btn-secondary">View Tasks</a>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p style="padding: 15px; color: #666;">No new available pickup tasks at the moment.</p>
            <?php } ?>
        </div>
    </div>
</div>
</body>
</html>
