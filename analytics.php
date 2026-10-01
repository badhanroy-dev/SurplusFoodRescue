
<?php
include "db.php";
$total_sql="SELECT COUNT(*) AS total_donations
FROM food_donations;";

$total_result=mysqli_query($conn,$total_sql);
$total_donations=mysqli_fetch_assoc($total_result);

$distributed_sql = "SELECT COUNT(*) AS food_distributed_today FROM food_distributions
WHERE DATE(distributed_at) = CURDATE()";

$distributed_result = mysqli_query($conn, $distributed_sql);

$distributed_data = mysqli_fetch_assoc($distributed_result);
$people_sql = "SELECT SUM(people_served) AS num_of_people FROM food_distributions WHERE DATE(distributed_at) = CURDATE()";

$people_result = mysqli_query($conn, $people_sql);

$people_data = mysqli_fetch_assoc($people_result);
$wastage_sql = "SELECT SUM(quantity_distributed) AS food_distributed FROM food_distributions
 WHERE DATE(distributed_at) = CURDATE() AND quantity_unit = 'kg'";

$wastage_result = mysqli_query($conn, $wastage_sql);

$wastage_data = mysqli_fetch_assoc($wastage_result);

$donor_sql = "SELECT u.full_name, SUM(d.quantity) AS total_donated FROM users u
JOIN food_donations d ON u.user_id = d.donor_id GROUP BY u.full_name ORDER BY total_donated DESC
LIMIT 3";

$donor_result = mysqli_query($conn, $donor_sql);
$charity_sql = "SELECT u.full_name, SUM(d.quantity) AS total_claimed
FROM users u JOIN food_claims c ON u.user_id = c.charity_id JOIN food_donations d
ON c.donation_id = d.donation_id GROUP BY u.full_name ORDER BY total_claimed DESC LIMIT 3";

$charity_result = mysqli_query($conn, $charity_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Rescue Admin Dashboard</title>
    <link rel="stylesheet" href="css/analytics.css">
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
            <li class="active"><a href="analytics.php">Analytics</a></li>
            <li><a href="broadcasts.php">Broadcasts</a></li>
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
        <h1>Analytics</h1>
        <p>Overview of food donations,distributions and impacts</p>
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

<section class="stat-cards">
    
    <div class="card A">
     <img src="images/heart.png">   
    <h4>Total donations</h4>
    <h2><?php echo $total_donations['total_donations']; ?></h2>
    <p>All time donations</p>
    </div>

<div class="card B">
    <img src="images/iftar.png">
    <h4>Food Distributed Today</h4>
    <h2><?php echo $distributed_data['food_distributed_today']; ?></h2>
    <p>Successfully Distributed</p>
    
</div>

<div class="card C">
     <img src="images/people.png">
    <h4>Number of Beneficiaries</h4>
    <h2><?php echo $people_data['num_of_people'] ?? 0; ?></h2>
    <p>People Served Today</p>
</div>


<div class="card D">
    <img src="images/bag.png">
    <h4>Wastage Reduced</h4>
    <h2><?php echo $wastage_data['food_distributed'] ?? 0; ?>kg</h2>
    <p>Today</p>
</div>


</section>

<section class="bottom-section">

    <div class="topdonors">
    <div class="heading">
        <img src="images/blood-donation.png">
        <h3>Top Donors</h3>
    </div>

    <?php while ($donor = mysqli_fetch_assoc($donor_result)) { ?>

        <div class="d">
            <p><?php echo $donor['full_name']; ?></p>
            <p><?php echo $donor['total_donated']; ?> kg</p>
        </div>

    <?php } ?>

</div>

    <div class="topcharities">
    <div class="heading">
        <img src="images/generous.png">
        <h3>Top Charities</h3>
    </div>

    <?php while ($charity = mysqli_fetch_assoc($charity_result)) { ?>

        <div class="d">
            <p><?php echo $charity['full_name']; ?></p>
            <p><?php echo $charity['total_claimed']; ?> kg</p>
        </div>

    <?php } ?>

</div>
</section>


    
    
    </main>
    </div>
</body>
</html>