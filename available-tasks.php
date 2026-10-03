<?php
session_start();
if (!isset($_SESSION["user_id"])) { die("Please log in first."); }
require "db.php";
$user_id = (int)$_SESSION["user_id"];
$message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["task_id"])) {
    $task_id = (int)$_POST["task_id"];
    $check = mysqli_prepare($conn, "SELECT task_id FROM tasks WHERE task_id=? AND status='available'");
    mysqli_stmt_bind_param($check, "i", $task_id); mysqli_stmt_execute($check);
    $task_exists = mysqli_stmt_get_result($check);
    $already = mysqli_prepare($conn, "SELECT assignment_id FROM task_assignments WHERE task_id=? AND user_id=?");
    mysqli_stmt_bind_param($already, "ii", $task_id, $user_id); mysqli_stmt_execute($already);
    if (mysqli_num_rows($task_exists) > 0 && mysqli_num_rows(mysqli_stmt_get_result($already)) === 0) {
        mysqli_begin_transaction($conn);
        $insert = mysqli_prepare($conn, "INSERT INTO task_assignments (task_id,user_id,status) VALUES (?,?,'accepted')");
        mysqli_stmt_bind_param($insert, "ii", $task_id, $user_id);
        $update = mysqli_prepare($conn, "UPDATE tasks SET status='assigned' WHERE task_id=? AND status='available'");
        mysqli_stmt_bind_param($update, "i", $task_id);
        if (mysqli_stmt_execute($insert) && mysqli_stmt_execute($update) && mysqli_affected_rows($conn) === 1) { mysqli_commit($conn); $message = "Task accepted successfully."; }
        else { mysqli_rollback($conn); $message = "This task is no longer available."; }
    } else { $message = "This task is no longer available or you already accepted it."; }
}
$tasks = mysqli_query($conn, "SELECT * FROM tasks WHERE status='available' ORDER BY created_at DESC");
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Rider Portal - Available Tasks</title><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><link rel="stylesheet" href="available-tasks.css"></head><body><div class="container">
<div class="sidebar"><div class="sidebar-title"><h1>Rider Portal</h1><p>Mission-Driven Service</p></div><ul class="menu"><li class="menu-item"><a href="dashboard.php">Dashboard</a></li><li class="menu-item active"><a href="available-tasks.php">Available Tasks</a></li><li class="menu-item"><a href="assigned-tasks.php">Assigned Tasks</a></li><li class="menu-item"><a href="otp-verification.php">OTP Verification</a></li><li class="menu-item"><a href="update-delivery-status.php">Update Delivery Status</a></li><li class="menu-item"><a href="report-delay.php">Report Delay</a></li><li class="menu-item"><a href="volunteer-chat.php">Chat</a></li></ul><ul class="menu bottom-menu"><li class="menu-item"><a href="#">Settings</a></li><li class="menu-item"><a href="#">Logout</a></li></ul></div>
<div class="main"><div class="page-header"><div><h2 class="page-heading">Available Tasks</h2><p class="page-subtitle">Browse and accept new food rescue missions.</p></div><div class="profile-section"><i class="fa-solid fa-bell"></i><img src="https://i.pravatar.cc/50" class="profile-img" alt="profile"></div></div>
<?php if ($message !== "") { ?><p><?= htmlspecialchars($message) ?></p><?php } ?>
<div class="filter-bar"><input type="text" class="search-input" placeholder="Search tasks..." id="task-search"><select class="form-select"><option>All Regions</option></select><select class="form-select"><option>Any Time</option></select><select class="form-select"><option>All Types</option></select><button class="btn btn-primary" type="button" onclick="document.getElementById('task-search').dispatchEvent(new Event('input'))">Apply Filters</button><div class="active-count"><span class="count-number"><?= mysqli_num_rows($tasks) ?></span> Active Tasks Nearby</div></div>
<div class="tasks-grid"><?php while ($task = mysqli_fetch_assoc($tasks)) { ?><div class="task-card"><div class="card-image bakery"></div><h4><?= htmlspecialchars($task["title"]) ?></h4><p class="card-sub"><?= htmlspecialchars($task["donor_name"]) ?> • <?= htmlspecialchars($task["pickup_address"]) ?></p><div class="card-details"><div class="detail-col"><span class="label">RECIPIENT CHARITY</span><span class="value"><?= htmlspecialchars($task["charity_name"]) ?></span></div><div class="detail-col"><span class="label">QUANTITY</span><span class="value"><?= htmlspecialchars($task["quantity"]) ?></span></div><div class="detail-col"><span class="label">PICKUP TIME</span><span class="value"><?= htmlspecialchars($task["pickup_time"]) ?></span></div><div class="detail-col"><span class="label">EXPIRY</span><span class="value"><?= htmlspecialchars($task["expiry_time"]) ?></span></div><div class="detail-col"><span class="label">DROP-OFF</span><span class="value"><?= htmlspecialchars($task["dropoff_address"]) ?></span></div><div class="detail-col"><span class="label">INSTRUCTIONS</span><span class="value"><?= htmlspecialchars($task["instructions"]) ?></span></div></div><div class="card-buttons"><a href="task-details.php?task_id=<?= (int)$task["task_id"] ?>" class="btn btn-outline">View Details</a><form method="post"><input type="hidden" name="task_id" value="<?= (int)$task["task_id"] ?>"><button type="submit" class="btn btn-primary">Accept Task</button></form></div></div><?php } ?></div>
</div></div><script>document.getElementById('task-search').addEventListener('input', function () { var cards=document.getElementsByClassName('task-card'); for(var i=0;i<cards.length;i++){cards[i].style.display=cards[i].innerText.toLowerCase().includes(this.value.toLowerCase())?'':'none';} });</script></body></html>
