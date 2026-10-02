<?php
include "db.php";

$all_sql = "SELECT title, campaign_type, message, target_audience, start_date, end_date, call_to_action, status, created_at
            FROM campaigns
            ORDER BY created_at DESC";

$all_result = mysqli_query($conn, $all_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Campaigns</title>
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
                <h1>All Campaigns</h1>
                <p>View all awareness campaigns</p>
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

            <a href="reports.php" class="tab">
                Reports
            </a>

        </nav>

        <section class="recent-campaigns">

            <h2>Campaign History</h2>

            <?php if (mysqli_num_rows($all_result) > 0) { ?>

                <?php while ($campaign = mysqli_fetch_assoc($all_result)) { ?>

                    <div class="campaign-card">

                        <div>

                            <h3>
                                <?php echo htmlspecialchars($campaign['title']); ?>
                            </h3>

                            <p>
                                <?php echo htmlspecialchars($campaign['campaign_type']); ?>
                            </p>

                            <p>
                                Sent to <?php echo htmlspecialchars($campaign['target_audience']); ?>
                            </p>

                            <span>
                                <?php echo date("d F Y", strtotime($campaign['created_at'])); ?>
                            </span>

                            <p>
                                <?php echo htmlspecialchars($campaign['message']); ?>
                            </p>

                            <?php if (!empty($campaign['call_to_action'])) { ?>

                                <p>
                                    CTA: <?php echo htmlspecialchars($campaign['call_to_action']); ?>
                                </p>

                            <?php } ?>

                            <p>
                                Campaign:
                                <?php echo date("d F Y", strtotime($campaign['start_date'])); ?>
                                -
                                <?php echo date("d F Y", strtotime($campaign['end_date'])); ?>
                            </p>

                        </div>

                        <?php if ($campaign['status'] == "Published") { ?>

                            <small class="published">
                                Published
                            </small>

                        <?php } else { ?>

                            <small class="draft">
                                <?php echo htmlspecialchars($campaign['status']); ?>
                            </small>

                        <?php } ?>

                    </div>

                <?php } ?>

            <?php } else { ?>

                <div class="campaign-card">
                    <div>
                        <h3>No Campaigns Found</h3>
                        <p>There are currently no awareness campaigns.</p>
                    </div>
                </div>

            <?php } ?>

        </section>

    </main>

</div>

</body>
</html>