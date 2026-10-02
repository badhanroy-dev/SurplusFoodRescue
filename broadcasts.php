<?php
include "db.php";

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $title = $_POST['title'];
    $message = $_POST['message'];
    $audience = $_POST['audience'];
    $schedule_date = $_POST['schedule_date'];
    $schedule_time = $_POST['schedule_time'];

    $admin_id = 1;

    if (isset($_POST['sendnow'])) {
       if ($schedule_date == "" || $schedule_time == ""){

        $status = "Sent";
        $scheduled_at = NULL;

        $send_sql = "INSERT INTO broadcasts
                    (admin_id, title, message, audience, scheduled_at, status)
                    VALUES
                    ($admin_id, '$title', '$message', '$audience', NULL, '$status')";

        $send_result = mysqli_query($conn, $send_sql);

        if ($send_result) {
            echo "<script>alert('Broadcast sent successfully!');</script>";
        } else {
            echo "<script>alert('Error sending broadcast: " . mysqli_error($conn) . "');</script>";
        }
    } else if ($schedule_date != "" && $schedule_time != ""){

       $status = "Scheduled";
       $scheduled_at = $schedule_date . " " . $schedule_time;

        $send_sql = "INSERT INTO broadcasts
                    (admin_id, title, message, audience, scheduled_at, status)
                    VALUES
                    ($admin_id, '$title', '$message', '$audience', NULL, '$status')";

        $send_result = mysqli_query($conn, $send_sql);

        if ($send_result) {
            echo "<script>alert('Broadcast scheduled successfully!');</script>";
        } else {
            echo "<script>alert('Error sending broadcast: " . mysqli_error($conn) . "');</script>";
        }
    }

    } elseif (isset($_POST['savedraft'])) {

        $status = "Draft";

        if ($schedule_date != "" && $schedule_time != "") {
            $scheduled_at = $schedule_date . " " . $schedule_time;
        } else {
            $scheduled_at = NULL;
        }

        if ($scheduled_at == NULL) {
            $draft_sql = "INSERT INTO broadcasts
                         (admin_id, title, message, audience, scheduled_at, status)
                         VALUES
                         ($admin_id, '$title', '$message', '$audience', NULL, '$status')";
        } else {
            $draft_sql = "INSERT INTO broadcasts
                         (admin_id, title, message, audience, scheduled_at, status)
                         VALUES
                         ($admin_id, '$title', '$message', '$audience', '$scheduled_at', '$status')";
        }

        $draft_result = mysqli_query($conn, $draft_sql);

        if ($draft_result) {
            echo "<script>alert('Broadcast saved as draft successfully!');</script>";
        } else {
            echo "<script>alert('Error saving draft: " . mysqli_error($conn) . "');</script>";
        }
    }
}
$recent_sql = "SELECT broadcasts.title, broadcasts.audience, broadcasts.created_at FROM broadcasts
ORDER BY created_at DESC LIMIT 3";

$recent_result = mysqli_query($conn, $recent_sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcast Alert Campaign</title>
    <link rel="stylesheet" href="css/broadcasts.css">
</head>

<body>
    <div class="webpage">
    <aside>
    <div class="sidebar-top">
        <h2>Food Rescue</h2>
        <p>Admin Portal</p>

        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="donoracc.php">Donor Accounts</a></li>
            <li><a href="volunteer_verify.php">Volunteer Verification</a></li>
            <li><a href="foodcategories.php">Food Categories</a></li>
            <li><a href="analytics.php">Analytics</a></li>
            <li class="active"><a href="broadcasts.php">Broadcasts</a></li>
            <li><a href="chat.php">Chat</a></li>
        </ul>
    </div>

    <div class="sidebar-bottom">
        <ul>
            <li><a href="settings.php">Settings</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
</aside>

    <main>
         <section class="header">
    <div class="header-left">
        <h1>Broadcasts</h1>
        <p>Create alerts, campaigns and manage submitted reports.</p>
    </div>
    <div class="header-right">
    <div class="admin-profile">
        <img src="images/notifications.png" class="notification-icon">
        <img src="images/profile.png" class="profile-icon">
        <div>
            <h4>Admin</h4>
        </div>
    </div>
</div>
</section>

        <nav class="tabs">
            <a href="broadcasts.php" class="tab active-tab">
                Urgent Alert
            </a>
            <a href="campaign.php" class="tab">
                Awareness Campaign
            </a>
            <a href="reports.php" class="tab">
                Reports
            </a>
        </nav>
        <section class="broadcast-container">
          <form class="broadcast-form" method="POST">
                <h2>Create Broadcast</h2>
                <label>Title</label>
                <input type="text" name="title" placeholder="Enter broadcast title">
                <label>Message</label>
                <textarea maxlength="500" name="message" placeholder="Write your alert or announcement here..."></textarea>
                <div class="character-count"> 0 / 500
                </div>

                <label>Send To</label>
                <select name="audience">
                    <option value="All Users">All Users</option>
                    <option value="Donors Only">Donors Only</option>
                    <option value="Volunteers Only">Volunteers Only</option>
                </select>
                <label>Schedule (Optional)</label>
                <div class="schedule-row">
                    <input type="date" name="schedule_date">
                    <input type="time" name="schedule_time">
                </div>

        <div class="button-row">
                    <button class="send-btn" name="sendnow">
                        Send Alert Now
                    </button>
                    <button class="draft-btn" name="savedraft">
                        Save as Draft
                    </button>
                </div>
</form>
        <div class="recent-broadcasts">
                <div class="recent-header">
                    <h2>Recent Broadcasts</h2>
                </div>
                <?php while ($broadcast = mysqli_fetch_assoc($recent_result)) { ?>
                <div class="broadcast-card urgent">
                    <div class="broadcast-info">
                        <h3><?php echo $broadcast['title']; ?></h3>
                        <p>Sent to <?php echo $broadcast['audience']; ?></p>
                        <span><?php echo date("d F Y", strtotime($broadcast['created_at'])); ?></span>
                    </div>
                </div>
                <?php } ?>

                <a href="broadcasts_all.php" class="view-all">
                    View All Broadcasts
                </a>
            </div>
        </section>
    </main>
    </div>
    <script src="js/broadcasts.js"></script>
</body>
</html>