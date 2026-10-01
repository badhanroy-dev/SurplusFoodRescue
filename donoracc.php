<?php

include "db.php";

if (isset($_POST['action'])) {

    $user_id = $_POST['user_id'];
    $action = $_POST['action'];


    if ($action == 'disable') {

        $update_sql = "UPDATE users SET account_status = 'Disabled' WHERE account_status = 'Active'
                       AND user_id = $user_id";

        mysqli_query($conn, $update_sql);


    } elseif ($action == 'activate') {

        $update_sql = "UPDATE users SET account_status = 'Active' WHERE (account_status = 'Pending' OR account_status = 'Disabled')
                       AND user_id = $user_id";

        mysqli_query($conn, $update_sql);

    }

}
$donor_sql = "SELECT users.user_id, users.full_name, users.organization_type, users.phone,
users.account_status FROM users WHERE role = 'Donor'";

$donor_result = mysqli_query($conn, $donor_sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Rescue Admin Dashboard</title>
    <link rel="stylesheet" href="css/donoracc.css">
</head>
<body>
    <div class="webpage">
    <aside>
    <div class="sidebar-top">
        <h2>Food Rescue</h2>
        <p>Admin Portal</p>

        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li class="active"><a href="donoracc.php">Donor Accounts</a></li>
            <li><a href="volunteer_verify.php">Volunteer Verification</a></li>
            <li><a href="foodcategories.php">Food Categories</a></li>
            <li><a href="analytics.php">Analytics</a></li>
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
        <h1>Food Donor Accounts</h1>
        <p>Manage food donations</p>
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

<section class="donor-accounts">
    <table>
        <thead>
            <tr>
                <th>Organization</th>
                <th>Type</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($donor = mysqli_fetch_assoc($donor_result)) { ?>

<tr>

        <td>
            <?php echo $donor['full_name']; ?>
        </td>

        <td>
            <?php echo $donor['organization_type']; ?>
        </td>

        <td>
            <?php echo $donor['phone']; ?>
        </td>

        <td>

    <?php if ($donor['account_status'] == 'Active') { ?>

        <span class="status-active">
            Active
        </span>

    <?php } elseif ($donor['account_status'] == 'Pending') { ?>

        <span class="status-pending">
            Pending
        </span>

    <?php } elseif ($donor['account_status'] == 'Disabled') { ?>

        <span class="status-disabled">
            Disabled
        </span>

    <?php } ?>

</td>

        <td>

    <?php if ($donor['account_status'] == 'Active') { ?>

        <form method="POST">
            <input type="hidden" name="user_id" value="<?php echo $donor['user_id']; ?>">
            <input type="hidden" name="action" value="disable">

            <button type="submit" class="disable">
                Disable
            </button>
        </form>

    <?php } elseif ($donor['account_status'] == 'Pending') { ?>

        <form method="POST">
            <input type="hidden" name="user_id" value="<?php echo $donor['user_id']; ?>">
            <input type="hidden" name="action" value="activate">

            <button type="submit" class="activate">
                Activate
            </button>
        </form>

    <?php } elseif ($donor['account_status'] == 'Disabled') { ?>

        <form method="POST">
            <input type="hidden" name="user_id" value="<?php echo $donor['user_id']; ?>">
            <input type="hidden" name="action" value="activate"> 

            <button type="submit" class="activate">
                Activate
            </button>
        </form>

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