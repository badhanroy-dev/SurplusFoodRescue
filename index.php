<?php
include "db.php";

$volunteer_sql = "SELECT COUNT(*) AS total_v
        FROM users
        WHERE role = 'Volunteer'
        AND account_status = 'Verified'";

$volunteer_result = mysqli_query($conn, $volunteer_sql);
$volunteer_assoc = mysqli_fetch_assoc($volunteer_result);
$active_volunteers = $volunteer_assoc['total_v'];

$charity_sql = "SELECT COUNT(*) AS total_c
        FROM users
        WHERE role = 'Charity'
        AND account_status = 'Active'";

$charity_result = mysqli_query($conn, $charity_sql);
$charity_assoc = mysqli_fetch_assoc($charity_result);
$active_charities = $charity_assoc['total_c'];

$meal_sql = "SELECT SUM(people_served) AS total_meals
        FROM food_distributions";
$meal_result = mysqli_query($conn, $meal_sql);
$meal_assoc = mysqli_fetch_assoc($meal_result);
$meals_delivered = $meal_assoc['total_meals'];

$food_sql = "SELECT SUM(quantity_distributed) AS total_food
        FROM food_distributions
        WHERE quantity_unit = 'kg'";
$food_result = mysqli_query($conn, $food_sql);
$food_assoc = mysqli_fetch_assoc($food_result);
$food_saved = $food_assoc['total_food'];
$selected_month = $_GET['month'] ?? '2026-08';
$month_start = $selected_month . '-01';
$month_end = date('Y-m-d', strtotime($month_start . ' +1 month'));
$donation_sql = "SELECT COUNT(*) AS total_donations
        FROM food_donations
        WHERE created_at >= '$month_start'
        AND created_at < '$month_end'";
$donation_result = mysqli_query($conn, $donation_sql);
$donation_assoc = mysqli_fetch_assoc($donation_result);
$total_donations = $donation_assoc['total_donations'];

$collected_sql = "SELECT SUM(quantity_claimed) AS total_collected
        FROM food_claims
        WHERE claim_date >= '$month_start'
        AND claim_date < '$month_end'";

$collected_result = mysqli_query($conn, $collected_sql);
$collected_assoc = mysqli_fetch_assoc($collected_result);
$food_collected = $collected_assoc['total_collected'];
$distributed_sql = "SELECT SUM(quantity_distributed) AS total_distributed
        FROM food_distributions
        WHERE quantity_unit = 'kg'
        AND distributed_at >= '$month_start'
        AND distributed_at < '$month_end'";
$distributed_result = mysqli_query($conn, $distributed_sql);
$distributed_assoc = mysqli_fetch_assoc($distributed_result);
$food_distributed = $distributed_assoc['total_distributed'];

$meals_sql = "SELECT SUM(people_served) AS total_meals
        FROM food_distributions
        WHERE distributed_at >= '$month_start'
        AND distributed_at < '$month_end'";
$meals_result = mysqli_query($conn, $meals_sql);
$meals_assoc = mysqli_fetch_assoc($meals_result);
$meals_provided = $meals_assoc['total_meals'];


$donor_sql = "SELECT COUNT(*) AS total_donors
        FROM users
        WHERE role = 'Donor'
        AND account_status = 'Active'";

$donor_result = mysqli_query($conn, $donor_sql);
$donor_assoc = mysqli_fetch_assoc($donor_result);
$active_donors = $donor_assoc['total_donors'];


$recent_donation_sql = "SELECT u.full_name, d.food_name, d.quantity, d.quantity_unit
        FROM food_donations d
        JOIN users u ON d.donor_id = u.user_id
        ORDER BY d.created_at DESC
        LIMIT 1";

$recent_donation_result = mysqli_query($conn, $recent_donation_sql);
$recent_donation = mysqli_fetch_assoc($recent_donation_result);


$recent_volunteer_sql = "SELECT full_name
        FROM users
        WHERE role = 'Volunteer'
        AND account_status = 'Verified'
        ORDER BY updated_at DESC
        LIMIT 1";

$recent_volunteer_result = mysqli_query($conn, $recent_volunteer_sql);
$recent_volunteer = mysqli_fetch_assoc($recent_volunteer_result);


$recent_claim_sql = "SELECT u.full_name, c.claim_date
        FROM food_claims c
        JOIN users u ON c.charity_id = u.user_id
        ORDER BY c.claim_date DESC
        LIMIT 1";

$recent_claim_result = mysqli_query($conn, $recent_claim_sql);
$recent_claim = mysqli_fetch_assoc($recent_claim_result);


$recent_broadcast_sql = "SELECT title, audience
        FROM broadcasts
        WHERE status = 'Sent'
        ORDER BY created_at DESC
        LIMIT 1";

$recent_broadcast_result = mysqli_query($conn, $recent_broadcast_sql);
$recent_broadcast = mysqli_fetch_assoc($recent_broadcast_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Rescue Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="webpage">
    <aside>
    <div class="sidebar-top">
        <h2>Food Rescue</h2>
        <p>Admin Portal</p>
        <ul>
            <li class="active"><a href="index.php">Dashboard</a></li>
            <li><a href="donoracc.php">Donor Accounts</a></li>
            <li><a href="volunteer_verify.php">Volunteer Verification</a></li>
            <li><a href="foodcategories.php">Food Categories</a></li>
            <li><a href="analytics.php">Analytics</a></li>
            <li><a href="broadcasts.html">Broadcasts</a></li>
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
        <h1>Dashboard</h1>
        <p>Manage food donations, volunteers and reports</p>
    </div>
     <input type="search" placeholder="Search...">
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
    <section class="dashboard-cards">
        <div class="card">
            <img src="images/diet.png">
          
    <h3>Food Saved[kg]</h3>
   <h2><?php echo $food_saved; ?> kg</h2>
    
   
</div>
<div class="card">
     <img src="images/volunteer.png">
     
    <h3>Active Volunteers</h3>
    <h2><?php echo $active_volunteers; ?></h2>
    
     
</div>
<div class="card">
     <img src="images/food-donation.png">
    <h3>Active Charities</h3>
   <h2><?php echo $active_charities; ?></h2>
    
     
</div>
<div class="card">
     <img src="images/fried-rice.png">
     
    <h3>Meals Delivered</h3>
    <h2><?php echo $meals_delivered; ?></h2>
    
    
</div>
    </section>
    <section class="monthly-report">

    <div class="report-select">

        <h2>Monthly Report Overview</h2>

        <select onchange="window.location.href='index.php?month=' + this.value">

            <option value="2026-08" <?php if ($selected_month == '2026-08') echo 'selected'; ?>>
                August 2026
            </option>

            <option value="2026-07" <?php if ($selected_month == '2026-07') echo 'selected'; ?>>
                July 2026
            </option>

            <option value="2026-06" <?php if ($selected_month == '2026-06') echo 'selected'; ?>>
                June 2026
            </option>

            <option value="2026-05" <?php if ($selected_month == '2026-05') echo 'selected'; ?>>
                May 2026
            </option>

        </select>

    </div>


    <div class="report-box">

        <h3>Report Summary</h3>

        <div class="report-stats">

            <div class="report-stat">
                <span>Total Donations</span>
                <strong><?php echo $total_donations; ?></strong>
            </div>

            <div class="report-stat">
                <span>Food Collected</span>
                <strong><?php echo $food_collected; ?> kg</strong>
            </div>

            <div class="report-stat">
                <span>Food Distributed</span>
                <strong><?php echo $food_distributed; ?> kg</strong>
            </div>

            <div class="report-stat">
                <span>Meals Provided</span>
                <strong><?php echo $meals_provided; ?></strong>
            </div>

            <div class="report-stat">
                <span>Active Donors</span>
                <strong><?php echo $active_donors; ?></strong>
            </div>

            <div class="report-stat">
                <span>Active Volunteers</span>
                <strong><?php echo $active_volunteers; ?></strong>
            </div>

        </div>

        <p class="report-note">
            This report shows the donation and distribution activities recorded during
            <?php echo date('F Y', strtotime($month_start)); ?>.
        </p>

    </div>

</section>
    <section class="recent-activity">
    <h2>Recent Activity</h2>

    <?php if ($recent_donation) { ?>
        <div class="activity">
            <p>
                -<?php echo $recent_donation['full_name']; ?>
                donated
                <?php echo $recent_donation['quantity']; ?>
                <?php echo $recent_donation['quantity_unit']; ?>
                of <?php echo $recent_donation['food_name']; ?>.
            </p>
            <br>
        </div>
    <?php } ?>


    <?php if ($recent_volunteer) { ?>
        <div class="activity">
            <p>
                -Volunteer <?php echo $recent_volunteer['full_name']; ?>
                has been verified.
            </p>
            <br>
        </div>
    <?php } ?>


    <?php if ($recent_claim) { ?>
        <div class="activity">
            <p>
                -<?php echo $recent_claim['full_name']; ?>
                accepted a donation.
            </p>
            <br>
        </div>
    <?php } ?>


    <?php if ($recent_broadcast) { ?>
        <div class="activity">
            <p>
                -New broadcast "<?php echo $recent_broadcast['title']; ?>"
                has been sent to
                <?php echo $recent_broadcast['audience']; ?>.
            </p>
        </div>
    <?php } ?>

</section>
    
    </main>
    </div>
</body>
</html>