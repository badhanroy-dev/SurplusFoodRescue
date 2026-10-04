<?php

include "db.php";
include "admin_auth.php";


if (isset($_POST['action'])) {

    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];


    if ($action == 'disable') {

        $update_sql = "UPDATE users SET account_status = 'Disabled' WHERE user_id = $user_id";
        mysqli_query($conn, $update_sql);

    } elseif ($action == 'activate' || $action == 'approve') {

        $update_sql = "UPDATE users SET account_status = 'Active' WHERE user_id = $user_id";
        mysqli_query($conn, $update_sql);

    } elseif ($action == 'reject') {

        $update_sql = "UPDATE users SET account_status = 'Rejected' WHERE user_id = $user_id";
        mysqli_query($conn, $update_sql);

    }

}
$donor_sql = "SELECT users.user_id, users.full_name, users.organization_type, users.phone,
users.account_status FROM users WHERE role = 'Donor'";

$donor_result = mysqli_query($conn, $donor_sql);

$charity_sql = "SELECT users.user_id, users.full_name, users.organization_type, users.phone,
users.account_status FROM users WHERE role = 'Charity'";

$charity_result = mysqli_query($conn, $charity_sql);

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
            <li><a href="chat.php">Chat</a></li>
        </ul>
    </div>

    <div class="sidebar-bottom">
        <ul>
            <li><a href="settings.php">Settings</a></li>
            <li><a href="../charityGuest/guest/logout.php">Logout</a></li>
        </ul>
    </div>
</aside>
        <main>
            <section class="header">
    <div class="header-left">
        <h1>Donor & Charity Accounts</h1>
        <p>Manage food donors and approve charity organization accounts</p>
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

<!-- Food Donor Accounts Table -->
<section class="donor-accounts">
    <h2 style="color: #A33215; margin-bottom: 15px; font-size: 18px;">Food Donor Accounts</h2>
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
            <?php echo htmlspecialchars($donor['full_name']); ?>
        </td>

        <td>
            <?php echo htmlspecialchars($donor['organization_type']); ?>
        </td>

        <td>
            <?php echo htmlspecialchars($donor['phone']); ?>
        </td>

        <td>

    <?php if ($donor['account_status'] == 'Active' || $donor['account_status'] == 'Verified') { ?>

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

    <?php if ($donor['account_status'] == 'Active' || $donor['account_status'] == 'Verified') { ?>

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

<!-- Charity Accounts Approval Table -->
<section class="donor-accounts" style="margin-top: 30px;">
    <h2 style="color: #A33215; margin-bottom: 15px; font-size: 18px;">Charity Accounts Approval</h2>
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
            <?php if (mysqli_num_rows($charity_result) > 0) { ?>
                <?php while ($charity = mysqli_fetch_assoc($charity_result)) { ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($charity['full_name']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($charity['organization_type']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($charity['phone']); ?>
                        </td>

                        <td>
                            <?php if ($charity['account_status'] == 'Active' || $charity['account_status'] == 'Verified') { ?>
                                <span class="status-active">
                                    Active
                                </span>
                            <?php } elseif ($charity['account_status'] == 'Pending') { ?>
                                <span class="status-pending">
                                    Pending
                                </span>
                            <?php } elseif ($charity['account_status'] == 'Rejected') { ?>
                                <span class="status-rejected">
                                    Rejected
                                </span>
                            <?php } else { ?>
                                <span class="status-disabled">
                                    Disabled
                                </span>
                            <?php } ?>
                        </td>

                        <td>
                            <div class="action-buttons">
                                <?php if ($charity['account_status'] == 'Pending') { ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $charity['user_id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="activate">
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $charity['user_id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="reject">
                                            Reject
                                        </button>
                                    </form>
                                <?php } elseif ($charity['account_status'] == 'Active' || $charity['account_status'] == 'Verified') { ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $charity['user_id']; ?>">
                                        <input type="hidden" name="action" value="disable">
                                        <button type="submit" class="disable">
                                            Disable
                                        </button>
                                    </form>
                                <?php } else { ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $charity['user_id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="activate">
                                            Approve
                                        </button>
                                    </form>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="5" style="text-align:center; color:#777; padding: 20px;">No charity accounts found.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>
    
    </main>
    </div>
</body>
</html>