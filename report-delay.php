<?php
session_start();
if (!isset($_SESSION["user_id"])) { die("Please log in first."); }
require "db.php";
$user_id = (int)$_SESSION["user_id"];
$task_id = isset($_GET["task_id"]) ? (int)$_GET["task_id"] : (isset($_POST["task_id"]) ? (int)$_POST["task_id"] : 0);
$message = "";
$get = mysqli_prepare($conn, "SELECT a.assignment_id,t.title,t.dropoff_address,t.pickup_time FROM task_assignments a JOIN tasks t ON a.task_id=t.task_id WHERE a.task_id=? AND a.user_id=?");
mysqli_stmt_bind_param($get, "ii", $task_id, $user_id); mysqli_stmt_execute($get); $assignment = mysqli_fetch_assoc(mysqli_stmt_get_result($get));
if (!$assignment) { die("Task not found or it is not assigned to you."); }
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $reason = isset($_POST["reason"]) ? $_POST["reason"] : "";
    $minutes = isset($_POST["minutes"]) ? (int)$_POST["minutes"] : 0;
    $comments = isset($_POST["comments"]) ? trim($_POST["comments"]) : "";
    $insert = mysqli_prepare($conn, "INSERT INTO delay_reports (assignment_id,reason,estimated_delay_minutes,comments) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($insert, "isis", $assignment["assignment_id"], $reason, $minutes, $comments);
    if ($minutes > 0 && mysqli_stmt_execute($insert)) { $message = "Delay report submitted successfully."; }
    else { $message = "Please choose a reason and a delay greater than zero."; }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Rider Portal - Report Delay</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><link rel="stylesheet" href="report-delay.css"></head><body><div class="container">
<div class="sidebar"><div class="sidebar-title"><h1>Rider Portal</h1><p>Mission-Driven Service</p></div><ul class="menu"><li class="menu-item"><a href="dashboard.php">Dashboard</a></li><li class="menu-item"><a href="available-tasks.php">Available Tasks</a></li><li class="menu-item"><a href="assigned-tasks.php">Assigned Tasks</a></li><li class="menu-item"><a href="otp-verification.php">OTP Verification</a></li><li class="menu-item"><a href="update-delivery-status.php">Update Delivery Status</a></li><li class="menu-item active"><a href="report-delay.php">Report Delay</a></li><li class="menu-item"><a href="volunteer-chat.php">Chat</a></li></ul><ul class="menu bottom-menu"><li class="menu-item"><a href="#">Settings</a></li><li class="menu-item"><a href="#">Logout</a></li></ul></div>
<div class="main"><div class="page-header"><div><h2 class="page-heading">Report Service Delay</h2><p class="page-subtitle">Provide accurate details regarding your current delay.</p></div><div class="profile-section"><i class="fa-solid fa-bell"></i><img src="https://i.pravatar.cc/50" class="profile-img" alt="profile"></div></div>
<div class="delay-grid"><div class="content-box"><?php if($message!==""){ ?><p><?= htmlspecialchars($message) ?></p><?php } ?><form method="post"><input type="hidden" name="task_id" value="<?= $task_id ?>"><div class="form-group"><label>DELAY REASON</label><select class="form-select-full" name="reason" required><option value="">Select primary cause...</option><option>Traffic / Road Closure</option><option>Vehicle Breakdown</option><option>Weather Conditions</option><option>Restaurant Delay</option><option>Other</option></select></div><div class="form-group"><label>ESTIMATED DELAY TIME</label><div class="time-buttons"><button type="button" class="btn btn-outline time-btn" data-minutes="15">15m</button><button type="button" class="btn btn-outline time-btn" data-minutes="30">30m</button><button type="button" class="btn btn-outline time-btn" data-minutes="45">45m</button><button type="button" class="btn btn-outline time-btn" data-minutes="60">60m+</button><input type="hidden" name="minutes" id="minutes" required></div></div><div class="form-group"><label>ADDITIONAL COMMENTS</label><textarea class="form-textarea" name="comments" placeholder="Provide specific details..."></textarea></div><button type="submit" class="btn btn-primary btn-full">Submit</button><p class="note-text">Information will be recorded for this task.</p></form></div><div class="content-box order-info"><p class="info-label">Task</p><p class="info-value"><?= htmlspecialchars($assignment["title"]) ?></p><p class="info-label">Destination</p><p class="info-value"><?= htmlspecialchars($assignment["dropoff_address"]) ?></p><p class="info-label">Pickup Time</p><p class="info-value"><?= htmlspecialchars($assignment["pickup_time"]) ?></p></div></div></div></div>
<script>var buttons=document.getElementsByClassName('time-btn');for(var i=0;i<buttons.length;i++){buttons[i].addEventListener('click',function(){document.getElementById('minutes').value=this.getAttribute('data-minutes');});}</script></body></html>
