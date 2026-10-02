<?php
include "db.php";
include "admin_auth.php";

/* Get all broadcasts */
$all_sql = "SELECT title, message, audience, status, scheduled_at, created_at
            FROM broadcasts
            ORDER BY created_at DESC";

$all_result = mysqli_query($conn, $all_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>All Broadcasts</title>

    <link rel="stylesheet" href="css/broadcasts_all.css">
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

        <div class="header">

            <div class="header-left">
                <h1>All Broadcasts</h1>
                <p>View all alerts and announcements sent by the administrator.</p>
            </div>

            <a href="broadcasts.php" class="back-btn">
                Back to Broadcasts
            </a>

        </div>

        <div class="broadcast-container">

            <div class="section-header">
                <h2>Broadcast History</h2>
            </div>


            <?php if (mysqli_num_rows($all_result) > 0) { ?>

                <div class="broadcast-list">

                    <?php while ($broadcast = mysqli_fetch_assoc($all_result)) { ?>

                        <div class="broadcast-card">

                            <div class="broadcast-top">

                                <div>
                                    <h3>
                                        <?php echo htmlspecialchars($broadcast['title']); ?>
                                    </h3>

                                    <p class="audience">
                                        Sent to <?php echo htmlspecialchars($broadcast['audience']); ?>
                                    </p>
                                </div>


                                <span class="status
                                    <?php echo strtolower($broadcast['status']); ?>">
                                    <?php echo htmlspecialchars($broadcast['status']); ?>
                                </span>

                            </div>


                            <div class="broadcast-message">

                                <p>
                                    <?php echo nl2br(htmlspecialchars($broadcast['message'])); ?>
                                </p>

                            </div>


                            <div class="broadcast-footer">

                                <div>

                                    <?php if ($broadcast['status'] == 'Scheduled' && $broadcast['scheduled_at'] != NULL) { ?>

                                        <span>
                                            Scheduled:
                                            <?php echo date("d F Y, h:i A", strtotime($broadcast['scheduled_at'])); ?>
                                        </span>

                                    <?php } else { ?>

                                        <span>
                                            Created:
                                            <?php echo date("d F Y, h:i A", strtotime($broadcast['created_at'])); ?>
                                        </span>

                                    <?php } ?>

                                </div>

                            </div>

                        </div>

                    <?php } ?>

                </div>

            <?php } else { ?>

                <div class="no-broadcasts">
                    <h3>No Broadcasts Found</h3>
                    <p>There are currently no broadcasts in the database.</p>
                </div>

            <?php } ?>

        </div>

    </main>

</div>


</body>
</html>