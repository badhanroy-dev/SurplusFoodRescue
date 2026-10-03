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
            <div class="profile-section">
                <i class="fa-solid fa-bell"></i>
                <img src="https://i.pravatar.cc/50" class="profile-img" alt="profile">
            </div>
        </div>
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
                            <p class="assigned-meta">Pickup: <?= htmlspecialchars($task["pickup_address"]) ?> &nbsp; | &nbsp; Dropoff: <?= htmlspecialchars($task["delivery_address"]) ?> &nbsp; | &nbsp; Charity: <?= htmlspecialchars($task["charity_name"]) ?> &nbsp; | &nbsp; Status: <strong><?= htmlspecialchars($task["delivery_status"]) ?></strong></p>
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
