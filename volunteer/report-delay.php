<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../charityGuest/guest/login.html");
    exit();
}
require "db.php";
$user_id = (int)$_SESSION["user_id"];
$delivery_id = isset($_GET["delivery_id"]) ? (int)$_GET["delivery_id"] : (isset($_POST["delivery_id"]) ? (int)$_POST["delivery_id"] : 0);
$message = "";

if ($delivery_id === 0) {
    $default_query = mysqli_prepare($conn, "SELECT delivery_id FROM pickup_deliveries WHERE volunteer_id=? AND delivery_status NOT IN ('Delivered', 'Completed', 'Cancelled') ORDER BY updated_at DESC LIMIT 1");
    mysqli_stmt_bind_param($default_query, "i", $user_id);
    mysqli_stmt_execute($default_query);
    $res_default = mysqli_stmt_get_result($default_query);
    $default_row = $res_default ? mysqli_fetch_assoc($res_default) : null;
    if ($default_row) {
        $delivery_id = (int)$default_row["delivery_id"];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $delivery_id > 0) {
    $reason = isset($_POST["reason"]) ? $_POST["reason"] : "";
    $minutes = isset($_POST["minutes"]) ? (int)$_POST["minutes"] : 0;
    $comments = isset($_POST["comments"]) ? trim($_POST["comments"]) : "";
    
    if ($minutes > 0 && !empty($reason)) {
        $insert = mysqli_prepare($conn, "INSERT INTO delay_reports (delivery_id, volunteer_id, delay_reason, estimated_delay_minutes, additional_comments) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($insert, "iisis", $delivery_id, $user_id, $reason, $minutes, $comments);
        if (mysqli_stmt_execute($insert)) {
            $message = "Delay report submitted successfully.";
        } else {
            $message = "Could not submit delay report.";
        }
    } else {
        $message = "Please select a delay reason and time duration.";
    }
}

$get_sql = "
    SELECT pd.delivery_id, pd.pickup_address, pd.delivery_address, pd.scheduled_time, fd.food_name
    FROM pickup_deliveries pd
    INNER JOIN food_claims fc ON pd.claim_id = fc.claim_id
    INNER JOIN food_donations fd ON fc.donation_id = fd.donation_id
    WHERE pd.delivery_id = ? AND pd.volunteer_id = ?
";
$get = mysqli_prepare($conn, $get_sql);
mysqli_stmt_bind_param($get, "ii", $delivery_id, $user_id);
mysqli_stmt_execute($get);
$res_get = mysqli_stmt_get_result($get);
$assignment = $res_get ? mysqli_fetch_assoc($res_get) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rider Portal - Report Delay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="report-delay.css">
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
            <li class="menu-item"><a href="assigned-tasks.php">Assigned Tasks</a></li>
            <li class="menu-item"><a href="otp-verification.php">OTP Verification</a></li>
            <li class="menu-item"><a href="update-delivery-status.php">Update Delivery Status</a></li>
            <li class="menu-item active"><a href="report-delay.php">Report Delay</a></li>
            <li class="menu-item"><a href="volunteer-chat.php">Chat</a></li>
        </ul>
        <ul class="menu bottom-menu">
            <li class="menu-item"><a href="../charityGuest/guest/settings.html">Settings</a></li>
            <li class="menu-item"><a href="../charityGuest/guest/logout.php">Logout</a></li>
        </ul>
    </div>
    <div class="main">
        <?php if (!$assignment) { ?>
            <div class="page-header">
                <div>
                    <h2 class="page-heading">Report Service Delay</h2>
                    <p class="page-subtitle">No active assigned delivery selected.</p>
                </div>
            </div>
            <div class="content-box" style="padding: 20px;">
                <p>Please select an assigned task from <a href="assigned-tasks.php">Assigned Tasks</a> first.</p>
            </div>
        <?php } else { ?>
            <div class="page-header">
                <div>
                    <h2 class="page-heading">Report Service Delay</h2>
                    <p class="page-subtitle">Provide accurate details regarding your current delay.</p>
                </div>
                <div class="profile-section">
                    <i class="fa-solid fa-bell"></i>
                    <img src="https://i.pravatar.cc/50" class="profile-img" alt="profile">
                </div>
            </div>
            <div class="delay-grid">
                <div class="content-box">
                    <?php if ($message !== "") { ?>
                        <p style="padding: 10px 15px; background: #d4edda; color: #155724; border-radius: 6px; margin-bottom: 15px;"><?= htmlspecialchars($message) ?></p>
                    <?php } ?>
                    <form method="post" id="delay-form">
                        <input type="hidden" name="delivery_id" value="<?= $delivery_id ?>">
                        <div class="form-group">
                            <label>DELAY REASON</label>
                            <select class="form-select-full" name="reason" required>
                                <option value="">Select primary cause...</option>
                                <option>Traffic / Road Closure</option>
                                <option>Vehicle Breakdown</option>
                                <option>Weather Conditions</option>
                                <option>Restaurant Delay</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>ESTIMATED DELAY TIME</label>
                            <div class="time-buttons">
                                <button type="button" class="btn btn-outline time-btn" data-minutes="15">15m</button>
                                <button type="button" class="btn btn-outline time-btn" data-minutes="30">30m</button>
                                <button type="button" class="btn btn-outline time-btn" data-minutes="45">45m</button>
                                <button type="button" class="btn btn-outline time-btn" data-minutes="60">60m+</button>
                                <input type="hidden" name="minutes" id="minutes">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>ADDITIONAL COMMENTS</label>
                            <textarea class="form-textarea" name="comments" placeholder="Provide specific details..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">Submit</button>
                    </form>
                </div>
                <div class="content-box order-info">
                    <p class="info-label">Task</p>
                    <p class="info-value"><?= htmlspecialchars($assignment["food_name"]) ?></p>
                    <p class="info-label">Destination</p>
                    <p class="info-value"><?= htmlspecialchars($assignment["delivery_address"]) ?></p>
                    <p class="info-label">Pickup Time</p>
                    <p class="info-value"><?= htmlspecialchars($assignment["scheduled_time"]) ?></p>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
<script>
var buttons = document.getElementsByClassName('time-btn');
for (var i = 0; i < buttons.length; i++) {
    buttons[i].addEventListener('click', function() {
        document.getElementById('minutes').value = this.getAttribute('data-minutes');
        for (var j = 0; j < buttons.length; j++) {
            buttons[j].style.backgroundColor = '';
            buttons[j].style.color = '';
        }
        this.style.backgroundColor = '#FA8145';
        this.style.color = '#ffffff';
    });
}
document.getElementById('delay-form').addEventListener('submit', function(event) {
    if (document.getElementById('minutes').value === '') {
        event.preventDefault();
        alert('Please select an estimated delay time.');
    }
});
</script>
</body>
</html>
