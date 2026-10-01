<?php
include "db.php";

$admin_id = 1;

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $title = $_POST['title'];
    $campaign_type = $_POST['campaign_type'];
    $message = $_POST['message'];
    $target_audience = $_POST['target_audience'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $call_to_action = $_POST['call_to_action'];

    if (isset($_POST['savedraft'])) {

        $status = "Draft";

        $campaign_sql = "INSERT INTO campaigns
                        (admin_id, title, campaign_type, message, target_audience, start_date, end_date, call_to_action, status)
                        VALUES
                        ($admin_id, '$title', '$campaign_type', '$message', '$target_audience', '$start_date', '$end_date', '$call_to_action', '$status')";

        $campaign_result = mysqli_query($conn, $campaign_sql);

        if ($campaign_result) {
            echo "<script>alert('Campaign saved as draft successfully!');</script>";
        } else {
            echo "<script>alert('Error saving campaign: " . mysqli_error($conn) . "');</script>";
        }

    } elseif (isset($_POST['publish'])) {

        $status = "Published";

        $campaign_sql = "INSERT INTO campaigns
                        (admin_id, title, campaign_type, message, target_audience, start_date, end_date, call_to_action, status)
                        VALUES
                        ($admin_id, '$title', '$campaign_type', '$message', '$target_audience', '$start_date', '$end_date', '$call_to_action', '$status')";

        $campaign_result = mysqli_query($conn, $campaign_sql);

        if ($campaign_result) {
            echo "<script>alert('Campaign published successfully!');</script>";
        } else {
            echo "<script>alert('Error publishing campaign: " . mysqli_error($conn) . "');</script>";
        }
    }
}

$recent_sql = "SELECT title, target_audience, status, created_at
               FROM campaigns
               ORDER BY created_at DESC
               LIMIT 3";

$recent_result = mysqli_query($conn, $recent_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Awareness Campaign</title>
    <link rel="stylesheet" href="css/campaign.css">
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
            <li><a href="chat.html">Chat</a></li>
        </ul>
    </div>

    <div class="sidebar-bottom">
        <ul>
            <li><a href="settings.html">Settings</a></li>
            <li><a href="logout.html">Logout</a></li>
        </ul>
    </div>
</aside>

    <main>

        <section class="header">
            <div class="header-left">
                <h1>Campaigns</h1>
                <p>Campaigns to spread awareness among others</p>
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
            <a href="broadcasts.php" class="tab">
                Urgent Alert
            </a>

            <a href="campaign.php" class="tab active-tab">
                Awareness Campaign
            </a>

            <a href="reports.html" class="tab">
                Reports
            </a>
        </nav>

        <section class="campaign-container">

            <div class="campaign-form">

                <h2>Create Awareness Campaign</h2>

                <form method="POST">

                    <label for="title">
                        Campaign Title
                    </label>

                    <input type="text" name="title" placeholder="Enter campaign title">

                    <label>
                        Campaign Type
                    </label>

                    <select name="campaign_type">

                        <option>Select campaign type</option>
                        <option>Food Waste Awareness</option>
                        <option>Food Donation Drive</option>
                        <option>Community Awareness</option>
                        <option>Food Safety Awareness</option>

                    </select>

                    <label for="message">
                        Message
                    </label>

                    <textarea name="message" maxlength="500" placeholder="Write your campaign message here..."></textarea>

                    <div class="character-count">
                        0 / 500
                    </div>

                    <label>
                        Target Audience
                    </label>

                    <select name="target_audience">

                        <option>All Users</option>
                        <option>Donors Only</option>
                        <option>Volunteers Only</option>

                    </select>

                    <label>
                        Campaign Date
                    </label>

                    <div class="date-row">
                        <input type="date" name="start_date">
                        <input type="date" name="end_date">
                    </div>

                    <label for="action">
                        Call to Action
                    </label>

                    <input type="text" name="call_to_action" placeholder="e.g. Donate Food Now">

                    <div class="button-row">

                        <button class="draft-btn" name="savedraft">
                            Save as Draft
                        </button>

                        <button class="publish-btn" name="publish">
                            Publish Campaign
                        </button>

                    </div>

                </form>

            </div>

            <div class="recent-campaigns">

                <h2>Recent Campaigns</h2>

                <?php while ($campaign = mysqli_fetch_assoc($recent_result)) { ?>

                    <div class="campaign-card">

                        <div>

                            <h3>
                                <?php echo $campaign['title']; ?>
                            </h3>

                            <p>
                                Sent to <?php echo $campaign['target_audience']; ?>
                            </p>

                            <span>
                                <?php echo date("d F Y", strtotime($campaign['created_at'])); ?>
                            </span>

                        </div>

                        <?php if ($campaign['status'] == "Published") { ?>

                            <small class="published">
                                Published
                            </small>

                        <?php } else { ?>

                            <small class="draft">
                                Draft
                            </small>

                        <?php } ?>

                    </div>

                <?php } ?>

                <a href="campaign_all.php" class="view-all">
                    View All Campaigns
                </a>

            </div>

        </section>

    </main>

</div>
</body>
</html>