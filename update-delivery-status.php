<?php
session_start();
if (!isset($_SESSION["user_id"])) { die("Please log in first."); }
require "db.php";
$user_id = (int)$_SESSION["user_id"];
$task_id = isset($_GET["task_id"]) ? (int)$_GET["task_id"] : (isset($_POST["task_id"]) ? (int)$_POST["task_id"] : 0);
if ($task_id === 0) {
    $default_task = mysqli_prepare($conn, "SELECT task_id FROM task_assignments WHERE user_id=? AND status<>'delivered' ORDER BY assigned_at DESC LIMIT 1");
    mysqli_stmt_bind_param($default_task, "i", $user_id);
    mysqli_stmt_execute($default_task);
    $default_row = mysqli_fetch_assoc(mysqli_stmt_get_result($default_task));
    if ($default_row) { $task_id = (int)$default_row["task_id"]; }
}
$message = "";
$allowed = array("accepted", "picked_up", "in_transit", "delivered");
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["status"]) && in_array($_POST["status"], $allowed, true)) {
    $status = $_POST["status"];
    if ($status === "delivered") {
        $query = mysqli_prepare($conn, "UPDATE task_assignments SET status=?, completed_at=NOW() WHERE task_id=? AND user_id=?");
    } else {
        $query = mysqli_prepare($conn, "UPDATE task_assignments SET status=? WHERE task_id=? AND user_id=?");
    }
    mysqli_stmt_bind_param($query, "sii", $status, $task_id, $user_id);
    if (mysqli_stmt_execute($query) && mysqli_stmt_affected_rows($query) > 0) {
        $task_update = mysqli_prepare($conn, "UPDATE tasks SET status=? WHERE task_id=?");
        mysqli_stmt_bind_param($task_update, "si", $status, $task_id); mysqli_stmt_execute($task_update);
        $message = "Delivery status updated successfully.";
    } else { $message = "Could not update this assignment."; }
}
$get = mysqli_prepare($conn, "SELECT t.title,a.status FROM task_assignments a JOIN tasks t ON a.task_id=t.task_id WHERE a.task_id=? AND a.user_id=?");
mysqli_stmt_bind_param($get, "ii", $task_id, $user_id); mysqli_stmt_execute($get); $assignment = mysqli_fetch_assoc(mysqli_stmt_get_result($get));
if (!$assignment) { die("Task not found or it is not assigned to you."); }
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Rider Portal - Update Delivery Status</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><link rel="stylesheet" href="update-delivery-status.css"></head><body><div class="container">
<div class="sidebar"><div class="sidebar-title"><h1>Rider Portal</h1><p>Mission-Driven Service</p></div><ul class="menu"><li class="menu-item"><a href="dashboard.php">Dashboard</a></li><li class="menu-item"><a href="available-tasks.php">Available Tasks</a></li><li class="menu-item"><a href="assigned-tasks.php">Assigned Tasks</a></li><li class="menu-item"><a href="otp-verification.php">OTP Verification</a></li><li class="menu-item active"><a href="update-delivery-status.php">Update Delivery Status</a></li><li class="menu-item"><a href="report-delay.php">Report Delay</a></li><li class="menu-item"><a href="volunteer-chat.php">Chat</a></li></ul><ul class="menu bottom-menu"><li class="menu-item"><a href="#">Settings</a></li><li class="menu-item"><a href="#">Logout</a></li></ul></div>
<div class="main"><div class="page-header"><div><a href="assigned-tasks.php" class="back-link">Assigned Tasks &gt;</a><h2 class="page-heading">Delivery Progress</h2><p class="page-subtitle"><?= htmlspecialchars($assignment["title"]) ?> · <?= htmlspecialchars($assignment["status"]) ?></p></div><div class="profile-section"><i class="fa-solid fa-bell"></i><img src="https://i.pravatar.cc/50" class="profile-img" alt="profile"></div></div>
<div class="content-box"><h3 class="section-title">Tracking Roadmap</h3><div class="roadmap"><?php $steps=array("accepted"=>"Order Accepted","picked_up"=>"Picked Up","in_transit"=>"In Transit","delivered"=>"Delivered"); $i=1; foreach($steps as $key=>$label){ ?><div class="roadmap-step <?= $assignment["status"]===$key?'current':($i<array_search($assignment["status"],array_keys($steps))+1?'done':'pending') ?>"><div class="step-icon"><?= $i ?></div><div class="step-text"><p class="step-title"><?= $label ?></p><span class="badge badge-active"><?= $assignment["status"]===$key?'CURRENT':($key==="accepted"?'COMPLETED':'') ?></span></div></div><?php $i++; } ?></div>
<?php if ($message !== "") { ?><p><?= htmlspecialchars($message) ?></p><?php } ?><form method="post" class="action-buttons"><input type="hidden" name="task_id" value="<?= $task_id ?>"><button name="status" value="picked_up" class="btn btn-primary action-btn">Mark Picked Up</button><button name="status" value="in_transit" class="btn btn-outline action-btn">Mark In Transit</button><button name="status" value="delivered" class="btn btn-outline action-btn">Mark Delivered</button></form></div></div></div></body></html>
