<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../charityGuest/guest/login.html");
    exit();
}
require "db.php";
$user_id = (int)$_SESSION["user_id"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delivery_id"])) {
    $delivery_id = (int)$_POST["delivery_id"];
    
    $update = mysqli_prepare($conn, "UPDATE pickup_deliveries SET volunteer_id=?, delivery_status='Accepted', assigned_at=NOW() WHERE delivery_id=? AND delivery_status='Available'");
    mysqli_stmt_bind_param($update, "ii", $user_id, $delivery_id);
    if (mysqli_stmt_execute($update) && mysqli_stmt_affected_rows($update) === 1) {
        $message = "Task accepted successfully!";
    } else {
        $message = "This task is no longer available or was already accepted.";
    }
}

$tasks_sql = "
    SELECT pd.delivery_id, pd.pickup_address, pd.delivery_address, pd.scheduled_time, pd.pickup_date, pd.special_instructions,
           fd.food_name, fd.quantity, fd.quantity_unit, fd.expiry_date,
           donor.full_name AS donor_name, charity.full_name AS charity_name
    FROM pickup_deliveries pd
    INNER JOIN food_claims fc ON pd.claim_id = fc.claim_id
    INNER JOIN food_donations fd ON fc.donation_id = fd.donation_id
    INNER JOIN users donor ON fd.donor_id = donor.user_id
    INNER JOIN users charity ON fc.charity_id = charity.user_id
    WHERE pd.delivery_status = 'Available'
    ORDER BY pd.created_at DESC
";
$tasks = mysqli_query($conn, $tasks_sql);
$task_count = $tasks ? mysqli_num_rows($tasks) : 0;

// Fetch Broadcast Notifications for Volunteers
$vol_notif_res = mysqli_query($conn, "SELECT title, message, created_at FROM broadcasts WHERE status = 'Sent' AND audience IN ('All Users', 'Volunteers Only') ORDER BY created_at DESC LIMIT 5");
$volunteer_notifications = $vol_notif_res ? mysqli_fetch_all($vol_notif_res, MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rider Portal - Available Tasks</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="available-tasks.css">
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
            <li class="menu-item active"><a href="available-tasks.php">Available Tasks</a></li>
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
                <h2 class="page-heading">Available Tasks</h2>
                <p class="page-subtitle">Browse and accept new food rescue missions.</p>
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
        <?php if ($message !== "") { ?>
            <p style="padding: 10px 15px; background: #d4edda; color: #155724; border-radius: 6px; margin-bottom: 15px;"><?= htmlspecialchars($message) ?></p>
        <?php } ?>
        <div class="filter-bar">
            <input type="text" class="search-input" placeholder="Search tasks..." id="task-search">
            <div class="active-count"><span class="count-number"><?= $task_count ?></span> Active Tasks Nearby</div>
        </div>
        <div class="tasks-grid">
            <?php if ($tasks && $task_count > 0) { ?>
                <?php while ($task = mysqli_fetch_assoc($tasks)) { ?>
                    <div class="task-card">
                        <div class="card-image bakery"></div>
                        <h4><?= htmlspecialchars($task["food_name"]) ?></h4>
                        <p class="card-sub"><?= htmlspecialchars($task["donor_name"]) ?> • <?= htmlspecialchars($task["pickup_address"]) ?></p>
                        <div class="card-details">
                            <div class="detail-col">
                                <span class="label">RECIPIENT CHARITY</span>
                                <span class="value"><?= htmlspecialchars($task["charity_name"]) ?></span>
                            </div>
                            <div class="detail-col">
                                <span class="label">QUANTITY</span>
                                <span class="value"><?= htmlspecialchars($task["quantity"]) ?> <?= htmlspecialchars($task["quantity_unit"]) ?></span>
                            </div>
                            <div class="detail-col">
                                <span class="label">PICKUP TIME</span>
                                <span class="value"><?= htmlspecialchars($task["scheduled_time"]) ?></span>
                            </div>
                            <div class="detail-col">
                                <span class="label">EXPIRY</span>
                                <span class="value"><?= htmlspecialchars($task["expiry_date"]) ?></span>
                            </div>
                            <div class="detail-col">
                                <span class="label">DROP-OFF</span>
                                <span class="value"><?= htmlspecialchars($task["delivery_address"]) ?></span>
                            </div>
                            <div class="detail-col">
                                <span class="label">INSTRUCTIONS</span>
                                <span class="value"><?= htmlspecialchars($task["special_instructions"] ?? 'None') ?></span>
                            </div>
                        </div>
                        <div class="card-buttons">
                            <a href="task-details.php?delivery_id=<?= (int)$task["delivery_id"] ?>" class="btn btn-outline" style="text-decoration: none; padding: 8px 16px; border: 1px solid #FA8145; color: #FA8145; border-radius: 6px; font-weight: 600;">View Details</a>
                            <form method="post" style="display: inline-block;">
                                <input type="hidden" name="delivery_id" value="<?= (int)$task["delivery_id"] ?>">
                                <button type="submit" class="btn btn-primary">Accept Task</button>
                            </form>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p style="grid-column: 1/-1; padding: 20px; color: #666;">No available pickup tasks at this time.</p>
            <?php } ?>
        </div>
    </div>
</div>
<script>
document.getElementById('task-search').addEventListener('input', function () {
    var cards = document.getElementsByClassName('task-card');
    for (var i = 0; i < cards.length; i++) {
        cards[i].style.display = cards[i].innerText.toLowerCase().includes(this.value.toLowerCase()) ? '' : 'none';
    }
});
</script>
</body>
</html>
