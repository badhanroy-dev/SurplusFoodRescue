<?php

include "db.php";
include "admin_auth.php";

$food_sql = "SELECT food_donations.donation_id, food_donations.food_name, food_donations.quantity,
 food_donations.quantity_unit, food_donations.expiry_date, food_donations.food_safety_info,
 food_donations.image_path,food_categories.category_name,
 users.full_name FROM food_donations JOIN food_categories  ON food_donations.category_id = food_categories.category_id
 JOIN users ON food_donations.donor_id = users.user_id";

 if (isset($_GET['category_id']) && $_GET['category_id'] != '') {
    $category_id = $_GET['category_id'];

    $food_sql .= " WHERE food_donations.category_id = $category_id";
}

$food_result = mysqli_query($conn, $food_sql);

$stats_sql = "SELECT
                COUNT(*) AS total_food,
                SUM(CASE WHEN expiry_date > NOW() + INTERVAL 2 DAY THEN 1 ELSE 0 END) AS fresh_food,
                SUM(CASE WHEN expiry_date >= NOW() 
                         AND expiry_date <= NOW() + INTERVAL 2 DAY THEN 1 ELSE 0 END) AS expiring_food,
                SUM(CASE WHEN expiry_date < NOW() THEN 1 ELSE 0 END) AS expired_food
              FROM food_donations";

$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);
$category_sql = "SELECT category_id, category_name  FROM food_categories  WHERE status = 'Active'
 ORDER BY category_name";

$category_result = mysqli_query($conn, $category_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Rescue Admin Dashboard</title>
    <link rel="stylesheet" href="css/foodcategories.css">
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
<li class="active">Food Categories</li> 
<li><a href="analytics.php">Analytics</a></li> 
<li><a href="broadcasts.php">Broadcasts</a></li>
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
        <h1>Food Categories</h1>
        <p>Manage donated food, safety status and expiry information</p>
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
     <img src="images/lunch-box.png" alt="Total Food">   
    <h4>Total Food</h4>
    <h2><?php echo $stats['total_food']; ?></h2>
    <p>All food items</p>
    </div>
<div class="card B">
    <img src="images/lettuce.png" alt="Fresh Food">
    <h4>Fresh Food</h4>
    <h2><?php echo $stats['fresh_food']; ?></h2>
    <p>Safe and Fresh</p>
    
</div>
<div class="card C">
     <img src="images/clock.png" alt="Expiring Soon">
     <h4>Expiring Soon</h4>
    <h2><?php echo $stats['expiring_food']; ?></h2>
    <p>Within 2 days</p>
</div>

<div class="card D">
    <img src="images/danger.png" alt="Expired Food">
    <h4>Expired Food</h4>
    <h2><?php echo $stats['expired_food']; ?></h2>
    <p>Expired Items</p>
</div>

<div class="safety-link">
     <a href="food_safety.php">View Food Safety Rules and Policies</a>
</div>

</section>
<section class="food-status">
    <div class="food-inv">
        <h3>Food Inventory</h3>
        <div class="right-food">
            <form method="GET">
    <select name="category_id" onchange="this.form.submit()">
        <option value="">All Categories</option>

        <?php while ($category = mysqli_fetch_assoc($category_result)) { ?>
            <option value="<?php echo $category['category_id']; ?>"
                <?php if (isset($_GET['category_id']) && $_GET['category_id'] == $category['category_id']) echo "selected"; ?>>
                <?php echo $category['category_name']; ?>
            </option>
        <?php } ?>
    </select>
</form>
            <a href="add_food.php" class="add-btn">+ Add Food</a>
        </div>
    </div>
    <table>
        <thead>
        <tr>
        <th>Food Name</th>
         <th>Category</th>
        <th>Donor</th>
        <th>Quantity</th>
        <th>Expiry Date</th>
        <th>Safety Status</th>
       <th>Safety Guidelines</th>
       <th>Action</th>
            </tr>
        </thead>
        <tbody>


<?php 


while ($food = mysqli_fetch_assoc($food_result)) { 
$expiry_date = strtotime($food['expiry_date']);
$today = strtotime(date('Y-m-d'));
$two_days = strtotime('+2 days');

if ($expiry_date < $today) {

    $safety_status = "Expired";
    $safety_class = "expired";

} elseif ($expiry_date <= $two_days) {

    $safety_status = "Expiring Soon";
    $safety_class = "expiring";

} else {

    $safety_status = "Safe";
    $safety_class = "safe";

}
?>
<tr>

    <td>
        <?php echo $food['food_name']; ?>
    </td>

    <td>
        <?php echo $food['category_name']; ?>
    </td>

    <td>
        <?php echo $food['full_name']; ?>
    </td>

    <td>
        <?php echo $food['quantity']; ?>
        <?php echo $food['quantity_unit']; ?>
    </td>

    <td>
        <?php echo date("M d, Y", strtotime($food['expiry_date'])); ?>
    </td>

    <td>
        <span class="<?php echo $safety_class; ?>">
    <?php echo $safety_status; ?>
</span>
    </td>

    <td>
        <?php echo $food['food_safety_info']; ?>
    </td>

    <td>

        <?php if (!empty($food['image_path'])) { ?>

            <a href="<?php echo $food['image_path']; ?>"
               target="_blank"
               class="view">
                View
            </a>

        <?php } else { ?>

            <span>No Image</span>

        <?php } ?>

    </td>

</tr>

<?php } ?>

</tbody>
    </table>
</section>
    
    
    </main>
    </div>
</body>
</html>