<?php
session_start();
if (!isset($_SESSION["user_id"])) { die("Please log in first."); }
require "db.php";
$user_id = (int)$_SESSION["user_id"];
$name = isset($_SESSION["name"]) ? $_SESSION["name"] : "Volunteer";
$available = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tasks WHERE status='available'");
$available_count = mysqli_fetch_assoc($available)["total"];
$assigned = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM task_assignments WHERE user_id=? AND status IN ('accepted','picked_up','in_transit')");
mysqli_stmt_bind_param($assigned, "i", $user_id); mysqli_stmt_execute($assigned);
$assigned_count = mysqli_fetch_assoc(mysqli_stmt_get_result($assigned))["total"];
$completed = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM task_assignments WHERE user_id=? AND status='delivered'");
mysqli_stmt_bind_param($completed, "i", $user_id); mysqli_stmt_execute($completed);
$completed_count = mysqli_fetch_assoc(mysqli_stmt_get_result($completed))["total"];
$recent = mysqli_query($conn, "SELECT task_id,title,donor_name,quantity FROM tasks WHERE status='available' ORDER BY created_at DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Rider Portal - Dashboard</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><link rel="stylesheet" href="dashboard.css"></head><body>
<div class="container"><div class="sidebar"><div class="sidebar-title"><h1>Rider Portal</h1><p>Mission-Driven Service</p></div><ul class="menu">
<li class="menu-item active"><a href="dashboard.php">Dashboard</a></li><li class="menu-item"><a href="available-tasks.php">Available Tasks</a></li><li class="menu-item"><a href="assigned-tasks.php">Assigned Tasks</a></li><li class="menu-item"><a href="otp-verification.php">OTP Verification</a></li><li class="menu-item"><a href="update-delivery-status.php">Update Delivery Status</a></li><li class="menu-item"><a href="report-delay.php">Report Delay</a></li><li class="menu-item"><a href="volunteer-chat.php">Chat</a></li></ul><ul class="menu bottom-menu"><li class="menu-item"><a href="#">Settings</a></li><li class="menu-item"><a href="#">Logout</a></li></ul></div>
<div class="main"><div class="page-header"><div><h2 class="page-heading">Volunteer Dashboard</h2><p class="page-subtitle">Welcome back, <?= htmlspecialchars($name) ?>.</p></div><div class="profile-section"><i class="fa-solid fa-bell"></i><img src="https://i.pravatar.cc/50" class="profile-img" alt="profile"></div></div>
<div class="stats-grid"><div class="stat-card"><div class="stat-number"><?= (int)$available_count ?></div><div class="stat-label">AVAILABLE TASKS</div></div><div class="stat-card"><div class="stat-number"><?= (int)$assigned_count ?></div><div class="stat-label">ASSIGNED TASKS</div></div><div class="stat-card"><div class="stat-number"><?= (int)$completed_count ?></div><div class="stat-label">COMPLETED DELIVERIES</div></div><div class="stat-card"><div class="stat-number"><?= (int)$assigned_count ?></div><div class="stat-label">PENDING DELIVERIES</div></div></div>
<div class="content-box"><div class="box-header"><h3>New Available Tasks</h3><a href="available-tasks.php" class="view-link">View all available</a></div>
<?php while ($task = mysqli_fetch_assoc($recent)) { ?><div class="task-row"><div class="task-info"><div><p class="task-name"><?= htmlspecialchars($task["donor_name"]) ?></p><p class="task-meta"><?= htmlspecialchars($task["title"]) ?> &nbsp; <?= htmlspecialchars($task["quantity"]) ?></p></div></div><a href="available-tasks.php" class="btn btn-secondary">View Tasks</a></div><?php } ?>
</div></div></div></body></html>
