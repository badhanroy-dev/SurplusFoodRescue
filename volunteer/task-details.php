<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../charityGuest/guest/login.html");
    exit();
}
require "db.php";
$user_id = (int)$_SESSION["user_id"];
$delivery_id = isset($_GET["delivery_id"]) ? (int)$_GET["delivery_id"] : (isset($_GET["task_id"]) ? (int)$_GET["task_id"] : (isset($_POST["delivery_id"]) ? (int)$_POST["delivery_id"] : (isset($_POST["task_id"]) ? (int)$_POST["task_id"] : 0)));
$message = "";
$message_type = "info";

// Check if volunteer currently has an active task assigned or in progress
$active_check = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pickup_deliveries WHERE volunteer_id=$user_id AND delivery_status IN ('Assigned', 'Accepted', 'Picked Up', 'In Transit')");
$active_assoc = $active_check ? mysqli_fetch_assoc($active_check) : ['total' => 0];
$has_active_task = ((int)$active_assoc['total'] > 0);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accept_task"])) {
    $delivery_id = isset($_POST["delivery_id"]) ? (int)$_POST["delivery_id"] : (isset($_POST["task_id"]) ? (int)$_POST["task_id"] : 0);
    
    if ($has_active_task) {
        $message = "You already have an active task in progress. You cannot take more than one task at a time.";
        $message_type = "error";
    } else {
        $update = mysqli_prepare($conn, "UPDATE pickup_deliveries SET volunteer_id=?, delivery_status='Accepted', assigned_at=NOW() WHERE delivery_id=? AND delivery_status='Available'");
        mysqli_stmt_bind_param($update, "ii", $user_id, $delivery_id);
        if (mysqli_stmt_execute($update) && mysqli_stmt_affected_rows($update) > 0) {
            header("Location: task-details.php?delivery_id=" . $delivery_id . "&accepted=1");
            exit();
        } else {
            $message = "This task is no longer available or was already accepted.";
            $message_type = "error";
        }
    }
}

$query_sql = "
    SELECT pd.delivery_id, pd.pickup_address, pd.delivery_address, pd.scheduled_time, pd.pickup_date, pd.special_instructions, pd.delivery_status, pd.volunteer_id, pd.pickup_proof_image,
           fd.food_name, fd.quantity, fd.quantity_unit, fd.expiry_date, fd.image_path,
           donor.full_name AS donor_name, charity.full_name AS charity_name
    FROM pickup_deliveries pd
    INNER JOIN food_claims fc ON pd.claim_id = fc.claim_id
    INNER JOIN food_donations fd ON fc.donation_id = fd.donation_id
    INNER JOIN users donor ON fd.donor_id = donor.user_id
    INNER JOIN users charity ON fc.charity_id = charity.user_id
    WHERE pd.delivery_id = ?
";
$query = mysqli_prepare($conn, $query_sql);
mysqli_stmt_bind_param($query, "i", $delivery_id);
mysqli_stmt_execute($query);
$res = mysqli_stmt_get_result($query);
$task = $res ? mysqli_fetch_assoc($res) : null;

if (!$task) {
    die("<div style='padding:40px; font-family:sans-serif;'><h2>Task Not Found</h2><p>Invalid delivery task ID or task no longer exists.</p><a href='available-tasks.php'>Back to Available Tasks</a></div>");
}

if (isset($_GET["accepted"])) {
    $message = "Task accepted successfully!";
}

$is_assigned = ((int)$task["volunteer_id"] === $user_id);
$task_status = $task["delivery_status"];

// Fetch Broadcast Notifications for Volunteers
$vol_notif_res = mysqli_query($conn, "SELECT title, message, created_at FROM broadcasts WHERE status = 'Sent' AND audience IN ('All Users', 'Volunteers Only') ORDER BY created_at DESC LIMIT 5");
$volunteer_notifications = $vol_notif_res ? mysqli_fetch_all($vol_notif_res, MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rider Portal - Task Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="task-details.css">
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
            <li class="menu-item <?= !$is_assigned ? 'active' : '' ?>"><a href="available-tasks.php">Available Tasks</a></li>
            <li class="menu-item <?= $is_assigned ? 'active' : '' ?>"><a href="assigned-tasks.php">Assigned Tasks</a></li>
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
                <a href="<?= $is_assigned ? 'assigned-tasks.php' : 'available-tasks.php' ?>" class="back-link">&lt; <?= $is_assigned ? 'Assigned Tasks' : 'Available Tasks' ?></a>
                <h2 class="page-heading"><?= htmlspecialchars($task["food_name"]) ?></h2>
                <p class="page-subtitle"><?= htmlspecialchars($task["pickup_address"]) ?> → <?= htmlspecialchars($task["delivery_address"]) ?></p>
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
            <?php if ($message !== "") { ?>
                <p style="padding: 10px 15px; background: #d4edda; color: #155724; border-radius: 6px; margin-bottom: 15px;"><?= htmlspecialchars($message) ?></p>
            <?php } ?>
            <div class="banner-row">
                <span class="badge badge-warning">STATUS: <?= htmlspecialchars($task_status) ?></span>
                <div class="expiry-box">
                    <p class="expiry-label">EXPIRY TIME</p>
                    <p class="expiry-time"><?= htmlspecialchars($task["expiry_date"]) ?></p>
                </div>
            </div>
            <h3 class="section-title">Order Details</h3>
            <div class="detail-list">
                <div class="detail-item">
                    <span>Total Quantity</span>
                    <span><?= htmlspecialchars($task["quantity"]) ?> <?= htmlspecialchars($task["quantity_unit"]) ?></span>
                </div>
                <div class="detail-item">
                    <span>Scheduled Pickup Window</span>
                    <span><?= htmlspecialchars($task["scheduled_time"]) ?></span>
                </div>
                <div class="detail-item">
                    <span>Current Status</span>
                    <span><?= htmlspecialchars($task_status) ?></span>
                </div>
            </div>
            <div class="two-col-cards">
                <div class="mini-card">
                    <p class="mini-label">DONOR / RESTAURANT</p>
                    <p class="mini-value"><?= htmlspecialchars($task["donor_name"]) ?></p>
                    <p class="mini-sub"><?= htmlspecialchars($task["pickup_address"]) ?></p>
                </div>
                <div class="mini-card">
                    <p class="mini-label">RECIPIENT CHARITY</p>
                    <p class="mini-value"><?= htmlspecialchars($task["charity_name"]) ?></p>
                    <p class="mini-sub"><?= htmlspecialchars($task["delivery_address"]) ?></p>
                </div>
            </div>
            <h3 class="section-title">Route Overview</h3>
            <div class="route-list">
                <p>Pickup Address: <strong><?= htmlspecialchars($task["pickup_address"]) ?></strong></p>
                <p>Drop-off Address: <strong><?= htmlspecialchars($task["delivery_address"]) ?></strong></p>
            </div>
            <h3 class="section-title">Rider Instructions</h3>
            <div class="instructions-box">
                <?= nl2br(htmlspecialchars($task["special_instructions"] ?? 'Standard safety and handover protocols apply.')) ?>
            </div>

            <?php if (!empty($task['pickup_proof_image']) || !empty($task['image_path'])) { ?>
                <h3 class="section-title">Food Photos</h3>
                <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px;">
                    <?php if (!empty($task['pickup_proof_image'])) { ?>
                        <div style="flex: 1; min-width: 200px; background: #fff8f5; border: 1px solid #ffd9c7; padding: 12px; border-radius: 8px; text-align: center;">
                            <span style="display: block; font-size: 13px; font-weight: bold; color: #A33215; margin-bottom: 8px;">
                                <i class="fa-solid fa-camera"></i> Received Food Photo (Rider Upload)
                            </span>
                            <a href="../<?= htmlspecialchars($task['pickup_proof_image']) ?>" target="_blank">
                                <img src="../<?= htmlspecialchars($task['pickup_proof_image']) ?>" alt="Received Food Photo" style="width: 100%; max-height: 220px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;">
                            </a>
                        </div>
                    <?php } ?>
                    <?php if (!empty($task['image_path'])) { ?>
                        <div style="flex: 1; min-width: 200px; background: #f8f9fa; border: 1px solid #e9ecef; padding: 12px; border-radius: 8px; text-align: center;">
                            <span style="display: block; font-size: 13px; font-weight: bold; color: #555; margin-bottom: 8px;">
                                <i class="fa-solid fa-utensils"></i> Original Donation Photo
                            </span>
                            <a href="../<?= htmlspecialchars($task['image_path']) ?>" target="_blank">
                                <img src="../<?= htmlspecialchars($task['image_path']) ?>" alt="Donor Food Photo" style="width: 100%; max-height: 220px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;">
                            </a>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
            <?php if ($is_assigned) { ?>
                <?php if (in_array(strtolower($task_status), ['completed', 'delivered'], true)) { ?>
                    <div style="margin-top: 20px; padding: 14px; background: #e2f0d9; color: #2e7d32; text-align: center; border-radius: 8px; font-weight: bold; border: 1px solid #c3e6cb; font-size: 15px;">
                        <i class="fa-solid fa-circle-check" style="margin-right: 6px;"></i> Delivery Completed
                    </div>
                <?php } else { ?>
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <a href="update-delivery-status.php?delivery_id=<?= $delivery_id ?>" class="btn btn-primary btn-full">Update Delivery Status</a>
                        <a href="report-delay.php?delivery_id=<?= $delivery_id ?>" class="btn btn-outline btn-full">Report Delay</a>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <?php if ($has_active_task) { ?>
                    <div style="margin-top: 20px; padding: 14px; background: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 8px; text-align: center; font-weight: 500; font-size: 14px;">
                        <i class="fa-solid fa-triangle-exclamation" style="margin-right: 6px;"></i> You already have an active task in progress. You cannot take more than one task at a time.
                    </div>
                <?php } else { ?>
                    <form method="post" style="margin-top: 20px;">
                        <input type="hidden" name="delivery_id" value="<?= $delivery_id ?>">
                        <button type="submit" name="accept_task" value="1" class="btn btn-primary btn-full">Accept Task</button>
                    </form>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
</div>
</body>
</html>
