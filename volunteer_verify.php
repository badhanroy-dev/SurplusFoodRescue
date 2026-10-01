<?php

include "db.php";
if(isset($_POST['action']))
    {
        $user_id=$_POST['user_id'];
        $action=$_POST['action'];

        if($action=='verify')
            {
                $verify_sql="UPDATE users SET account_status='Verified' WHERE user_id=$user_id AND
                role='Volunteer'";

                mysqli_query($conn,$verify_sql);
            }
           elseif ($action == 'reject')
    {
        $reject_sql = "UPDATE users SET account_status = 'Rejected' WHERE user_id = $user_id
         AND role = 'Volunteer'";

        mysqli_query($conn, $reject_sql);
    }

    elseif ($action == 'delete')
    {
        $delete_sql = "DELETE FROM users WHERE user_id = $user_id AND role = 'Volunteer'";

        mysqli_query($conn, $delete_sql);
    }
    }
$volunteer_sql = "SELECT users.user_id,users.full_name,users.phone,users.reg_nid_number,
users.nid_image_path,users.account_status FROM users WHERE users.role = 'Volunteer'";

$volunteer_result = mysqli_query($conn, $volunteer_sql);


$pending_sql = "SELECT COUNT(*) AS total FROM users WHERE role = 'Volunteer'
                AND account_status = 'Pending'";

$pending_result = mysqli_query($conn, $pending_sql);
$pending = mysqli_fetch_assoc($pending_result);


$verified_sql = "SELECT COUNT(*) AS total FROM users WHERE role = 'Volunteer'
                 AND account_status = 'Verified'";

$verified_result = mysqli_query($conn, $verified_sql);
$verified = mysqli_fetch_assoc($verified_result);


$rejected_sql = "SELECT COUNT(*) AS total
                 FROM users
                 WHERE role = 'Volunteer'
                 AND account_status = 'Rejected'";

$rejected_result = mysqli_query($conn, $rejected_sql);
$rejected = mysqli_fetch_assoc($rejected_result);


$total_sql = "SELECT COUNT(*) AS total  FROM users WHERE role = 'Volunteer'";

$total_result = mysqli_query($conn, $total_sql);
$total = mysqli_fetch_assoc($total_result);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Rescue Admin Dashboard</title>
    <link rel="stylesheet" href="css/verification.css">
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
            <li class="active"><a href="volunteer_verify.php">Volunteer Verification</a></li>
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
        <h1>Volunteer Verification</h1>
        <p>Manage volunteers</p>
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
        <div class="card">
    <h2><?php echo $pending['total']; ?></h2>
<p>Pending Requests</p>
</div>
<div class="card">
    <h2><?php echo $verified['total']; ?></h2>
<p>Verified Volunteers</p>
</div>
<div class="card">
    <h2><?php echo $rejected['total']; ?></h2>
<p>Rejected Applications</p>
</div>
<div class="card">
    <h2><?php echo $total['total']; ?></h2>
    <p>Total Volunteers</p>
</div>

</section>

<section class="volunteer-verify">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>NID Number</th>
                <th>NID Image</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($volunteer= mysqli_fetch_assoc($volunteer_result)) { ?>

<tr>
    <td>
            <?php echo $volunteer['full_name']; ?>
        </td>

        <td>
            <?php echo $volunteer['phone']; ?>
        </td>

        <td>
            <?php echo $volunteer['reg_nid_number']; ?>
        </td>

        <td>
    <a href="<?php echo $volunteer['nid_image_path']; ?>" target="_blank">
        View NID
    </a>
</td>
        <td>
            <?php if($volunteer['account_status']=='Verified') { ?>
               <span class="status-verified">
                Active
               </span>

               <?php } elseif ($volunteer['account_status'] == 'Pending') { ?>
        <span class="status-pending">
            Pending
        </span>

    <?php } elseif ($volunteer['account_status'] == 'Rejected') { ?>
        <span class="status-rejected">
            Rejected
        </span>
            <?php } ?>
        </td>

        <td>

    <a href="edit_volunteer.php?id=<?php echo $volunteer['user_id']; ?>"
   class="edit-button"
   style="background-color: #FA8145 !important; color: white !important;">
    Edit
</a>

    <?php if ($volunteer['account_status'] == 'Pending') { ?>

        <form method="POST">

            <input type="hidden"
                   name="user_id"
                   value="<?php echo $volunteer['user_id']; ?>">

            <button type="submit"
                    name="action"
                    value="verify"
                    class="verify-button">
                Verify
            </button>
            <br>

            <button type="submit"
                    name="action"
                    value="reject"
                    class="reject-button">
                Reject
            </button>

        </form>

    <?php } elseif ($volunteer['account_status'] == 'Verified') { ?>

        <form method="POST">

            <input type="hidden"
                   name="user_id"
                   value="<?php echo $volunteer['user_id']; ?>">

            <button type="submit"
                    name="action"
                    value="delete"
                    class="delete-button">
                Delete
            </button>

        </form>

    <?php } elseif ($volunteer['account_status'] == 'Rejected') { ?>

        <form method="POST">

            <input type="hidden"
                   name="user_id"
                   value="<?php echo $volunteer['user_id']; ?>">

            <button type="submit"
                    name="action"
                    value="verify"
                    class="verify-button">
                Verify
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