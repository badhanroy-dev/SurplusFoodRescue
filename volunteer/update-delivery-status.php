<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../charityGuest/guest/login.html");
    exit();
}
require "db.php";
$user_id = (int)$_SESSION["user_id"];
$message = "";
$message_type = "info";

// 1. Fetch all assigned deliveries for the logged-in rider (for selection dropdown)
$deliveries_sql = "
    SELECT pd.delivery_id, pd.pickup_address, pd.delivery_address, pd.delivery_status, fd.food_name, charity.full_name AS charity_name
    FROM pickup_deliveries pd
    INNER JOIN food_claims fc ON pd.claim_id = fc.claim_id
    INNER JOIN food_donations fd ON fc.donation_id = fd.donation_id
    INNER JOIN users charity ON fc.charity_id = charity.user_id
    WHERE pd.volunteer_id = ?
    ORDER BY pd.updated_at DESC
";
$stmt_del = mysqli_prepare($conn, $deliveries_sql);
mysqli_stmt_bind_param($stmt_del, "i", $user_id);
mysqli_stmt_execute($stmt_del);
$res_del = mysqli_stmt_get_result($stmt_del);
$assigned_deliveries = $res_del ? mysqli_fetch_all($res_del, MYSQLI_ASSOC) : [];

// 2. Determine target delivery_id
$delivery_id = isset($_GET["delivery_id"]) ? (int)$_GET["delivery_id"] : (isset($_POST["delivery_id"]) ? (int)$_POST["delivery_id"] : 0);

if ($delivery_id === 0 && count($assigned_deliveries) > 0) {
    // Pick first active (non-completed) delivery if available, else pick first
    foreach ($assigned_deliveries as $del_item) {
        if (!in_array($del_item['delivery_status'], ['Completed', 'Delivered', 'Cancelled'])) {
            $delivery_id = (int)$del_item['delivery_id'];
            break;
        }
    }
    if ($delivery_id === 0) {
        $delivery_id = (int)$assigned_deliveries[0]['delivery_id'];
    }
}

// 3. Fetch current delivery assignment details
$assignment = null;
if ($delivery_id > 0) {
    $get_sql = "
        SELECT pd.delivery_id, pd.claim_id, pd.delivery_status, pd.pickup_address, pd.delivery_address, pd.scheduled_time, pd.pickup_proof_image,
               fd.donation_id, fd.food_name, fd.quantity, fd.quantity_unit,
               donor.full_name AS donor_name, charity.full_name AS charity_name
        FROM pickup_deliveries pd
        INNER JOIN food_claims fc ON pd.claim_id = fc.claim_id
        INNER JOIN food_donations fd ON fc.donation_id = fd.donation_id
        INNER JOIN users donor ON fd.donor_id = donor.user_id
        INNER JOIN users charity ON fc.charity_id = charity.user_id
        WHERE pd.delivery_id = ? AND pd.volunteer_id = ?
    ";
    $get = mysqli_prepare($conn, $get_sql);
    mysqli_stmt_bind_param($get, "ii", $delivery_id, $user_id);
    mysqli_stmt_execute($get);
    $res_get = mysqli_stmt_get_result($get);
    $assignment = $res_get ? mysqli_fetch_assoc($res_get) : null;
}

// 4. Handle POST Status Update Request
$allowed_statuses = array("Accepted", "Picked Up", "In Transit", "Delivered", "Completed");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["status"]) && $assignment) {
    $new_status = trim($_POST["status"]);
    $current_status = $assignment['delivery_status'];

    if (in_array($current_status, ['Completed', 'Delivered', 'Cancelled'], true)) {
        $message = "This delivery task is already " . htmlspecialchars($current_status) . " and cannot be modified further.";
        $message_type = "error";
    } elseif (in_array($new_status, $allowed_statuses, true)) {
        $claim_id = (int)$assignment['claim_id'];
        $donation_id = (int)$assignment['donation_id'];

        // Process food photo upload if attached
        $proof_image_path = null;
        if (isset($_FILES['pickup_image']) && $_FILES['pickup_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['pickup_image']['tmp_name'];
            $file_name = $_FILES['pickup_image']['name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed, true)) {
                $target_dir = __DIR__ . '/../uploads/proof/';
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $new_file_name = 'pickup_' . $delivery_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file_tmp, $target_dir . $new_file_name)) {
                    $proof_image_path = 'uploads/proof/' . $new_file_name;
                    $save_img_sql = "UPDATE pickup_deliveries SET pickup_proof_image=? WHERE delivery_id=? AND volunteer_id=?";
                    $stmt_img = mysqli_prepare($conn, $save_img_sql);
                    mysqli_stmt_bind_param($stmt_img, "sii", $proof_image_path, $delivery_id, $user_id);
                    mysqli_stmt_execute($stmt_img);
                }
            }
        }

        if ($new_status === "Picked Up") {
            $update_sql = "UPDATE pickup_deliveries SET delivery_status='Picked Up', picked_up_at=NOW() WHERE delivery_id=$delivery_id AND volunteer_id=$user_id";
            mysqli_query($conn, $update_sql);
            
            $update_claim = "UPDATE food_claims SET status='In Transit' WHERE claim_id=$claim_id";
            mysqli_query($conn, $update_claim);
            
            $update_donation = "UPDATE food_donations SET status='Picked Up' WHERE donation_id=$donation_id";
            mysqli_query($conn, $update_donation);

            $message = "Status updated to Picked Up successfully!" . ($proof_image_path ? " (Food photo uploaded)" : "");
            $message_type = "success";
        } elseif ($new_status === "In Transit") {
            $update_sql = "UPDATE pickup_deliveries SET delivery_status='In Transit', in_transit_at=NOW() WHERE delivery_id=$delivery_id AND volunteer_id=$user_id";
            mysqli_query($conn, $update_sql);

            $update_claim = "UPDATE food_claims SET status='In Transit' WHERE claim_id=$claim_id";
            mysqli_query($conn, $update_claim);

            $message = "Status updated to In Transit successfully!" . ($proof_image_path ? " (Food photo uploaded)" : "");
            $message_type = "success";
        } elseif ($new_status === "Delivered" || $new_status === "Completed") {
            // Mark Completed across pickup_deliveries, food_claims, and food_donations
            $update_sql = "UPDATE pickup_deliveries SET delivery_status='Completed', delivered_at=NOW(), completed_at=NOW() WHERE delivery_id=$delivery_id AND volunteer_id=$user_id";
            mysqli_query($conn, $update_sql);

            $update_claim = "UPDATE food_claims SET status='Completed' WHERE claim_id=$claim_id";
            mysqli_query($conn, $update_claim);

            $update_donation = "UPDATE food_donations SET status='Completed' WHERE donation_id=$donation_id";
            mysqli_query($conn, $update_donation);

            // Mark any pending OTP for this delivery as Verified so it vanishes from Charity view
            mysqli_query($conn, "UPDATE otp_verifications SET status='Verified', verified_at=NOW() WHERE delivery_id=$delivery_id AND status='Pending'");

            $message = "Delivery marked as Completed! All records updated successfully.";
            $message_type = "success";
        }

        // Re-fetch updated details
        $get = mysqli_prepare($conn, $get_sql);
        mysqli_stmt_bind_param($get, "ii", $delivery_id, $user_id);
        mysqli_stmt_execute($get);
        $res_get = mysqli_stmt_get_result($get);
        $assignment = $res_get ? mysqli_fetch_assoc($res_get) : null;
    }
}

// Fetch Broadcast Notifications for Volunteers
$vol_notif_res = mysqli_query($conn, "SELECT title, message, created_at FROM broadcasts WHERE status = 'Sent' AND audience IN ('All Users', 'Volunteers Only') ORDER BY created_at DESC LIMIT 5");
$volunteer_notifications = $vol_notif_res ? mysqli_fetch_all($vol_notif_res, MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rider Portal - Update Delivery Status</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="update-delivery-status.css">
</head>
<body>
<div class="container">
    <div class="sidebar">
        <div class="sidebar-title">
            <h1>Rider Portal</h1>
            <p>Mission-Driven Service</p>
        </div>
        <ul class="menu">
            <li class="menu-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="menu-item"><a href="available-tasks.php">Available Tasks</a></li>
            <li class="menu-item"><a href="assigned-tasks.php">Assigned Tasks</a></li>
            <li class="menu-item"><a href="otp-verification.php">OTP Verification</a></li>
            <li class="menu-item active"><a href="update-delivery-status.php">Update Delivery Status</a></li>
            <li class="menu-item"><a href="report-delay.php">Report Delay</a></li>
            <li class="menu-item"><a href="volunteer-chat.php">Chat</a></li>
        </ul>
        <ul class="menu bottom-menu">
            <li class="menu-item"><a href="../charityGuest/guest/settings.html">Settings</a></li>
            <li class="menu-item"><a href="../charityGuest/guest/logout.php">Logout</a></li>
        </ul>
    </div>
    <div class="main">
        <?php if (!$assignment) { ?>
            <div class="page-header">
                <div>
                    <h2 class="page-heading">Delivery Progress</h2>
                    <p class="page-subtitle">No active assigned delivery selected.</p>
                </div>
                <div class="profile-section" style="position: relative;">
                    <div class="notif-container" style="position: relative; cursor: pointer; display: inline-block;" onclick="toggleVolNotif(event)">
                        <i class="fa-solid fa-bell" style="font-size: 18px; color: #A33215;"></i>
                        <?php if (count($volunteer_notifications) > 0) { ?>
                            <span style="position: absolute; top: -8px; right: -8px; background: #e71c16; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: bold; line-height: 1;"><?php echo count($volunteer_notifications); ?></span>
                        <?php } ?>
                        <div id="vol-notif-dropdown" class="notif-dropdown" style="display: none; position: absolute; right: 0; top: 30px; width: 260px; background: white; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; padding: 12px; text-align: left;">
                            <h4 style="margin: 0 0 8px 0; color: #A33215; font-size: 13px; border-bottom: 1px solid #eee; padding-bottom: 6px;">Broadcast Notifications</h4>
                            <?php if (count($volunteer_notifications) > 0) { 
                                foreach ($volunteer_notifications as $notif) { ?>
                                    <div class="notif-item" style="padding: 8px 0; border-bottom: 1px solid #f5f5f5;">
                                        <strong style="font-size: 12px; color: #C54716; display: block;"><?php echo htmlspecialchars($notif['title']); ?></strong>
                                        <p style="font-size: 11px; color: #555; margin: 3px 0 0 0; line-height: 1.4;"><?php echo htmlspecialchars($notif['message']); ?></p>
                                    </div>
                            <?php } } else { ?>
                                <p style="font-size: 12px; color: #888; margin: 0;">No new notifications</p>
                            <?php } ?>
                        </div>
                    </div>
                    <i class="fa-solid fa-circle-user" style="font-size: 18px; color: #A33215;"></i>
                </div>
            </div>
            <div class="content-box" style="padding: 20px;">
                <p>Please accept a task from <a href="available-tasks.php">Available Tasks</a> or select one from <a href="assigned-tasks.php">Assigned Tasks</a> first.</p>
            </div>
        <?php } else { ?>
            <div class="page-header">
                <div>
                    <a href="assigned-tasks.php" class="back-link">&lt; Assigned Tasks</a>
                    <h2 class="page-heading">Delivery Progress</h2>
                    <p class="page-subtitle"><?= htmlspecialchars($assignment["food_name"]) ?> (<?= htmlspecialchars($assignment["quantity"]) ?> <?= htmlspecialchars($assignment["quantity_unit"]) ?>)</p>
                </div>
                <div class="profile-section" style="position: relative;">
                    <div class="notif-container" style="position: relative; cursor: pointer; display: inline-block;" onclick="toggleVolNotif(event)">
                        <i class="fa-solid fa-bell" style="font-size: 18px; color: #A33215;"></i>
                        <?php if (count($volunteer_notifications) > 0) { ?>
                            <span style="position: absolute; top: -8px; right: -8px; background: #e71c16; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: bold; line-height: 1;"><?php echo count($volunteer_notifications); ?></span>
                        <?php } ?>
                        <div id="vol-notif-dropdown" class="notif-dropdown" style="display: none; position: absolute; right: 0; top: 30px; width: 260px; background: white; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; padding: 12px; text-align: left;">
                            <h4 style="margin: 0 0 8px 0; color: #A33215; font-size: 13px; border-bottom: 1px solid #eee; padding-bottom: 6px;">Broadcast Notifications</h4>
                            <?php if (count($volunteer_notifications) > 0) { 
                                foreach ($volunteer_notifications as $notif) { ?>
                                    <div class="notif-item" style="padding: 8px 0; border-bottom: 1px solid #f5f5f5;">
                                        <strong style="font-size: 12px; color: #C54716; display: block;"><?php echo htmlspecialchars($notif['title']); ?></strong>
                                        <p style="font-size: 11px; color: #555; margin: 3px 0 0 0; line-height: 1.4;"><?php echo htmlspecialchars($notif['message']); ?></p>
                                    </div>
                            <?php } } else { ?>
                                <p style="font-size: 12px; color: #888; margin: 0;">No new notifications</p>
                            <?php } ?>
                        </div>
                    </div>
                    <i class="fa-solid fa-circle-user" style="font-size: 18px; color: #A33215;"></i>
                </div>
            </div>
            <script>
            function toggleVolNotif(e) {
                if (e) e.stopPropagation();
                var box = document.getElementById('vol-notif-dropdown');
                if (box) {
                    box.style.display = (box.style.display === 'block') ? 'none' : 'block';
                }
            }
            document.addEventListener('click', function(e) {
                var box = document.getElementById('vol-notif-dropdown');
                if (box && box.style.display === 'block') {
                    var notifContainer = e.target.closest('.notif-container');
                    if (!notifContainer) {
                        box.style.display = 'none';
                    }
                }
            });
            </script>

            <!-- Task Selector Dropdown -->
            <div class="content-box" style="margin-bottom: 20px;">
                <form method="get">
                    <label style="font-size: 13px; color: #666; display: block; margin-bottom: 5px;">SELECT ASSIGNED TASK TO UPDATE:</label>
                    <select name="delivery_id" onchange="this.form.submit()" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px;">
                        <?php foreach ($assigned_deliveries as $del_item) { ?>
                            <option value="<?= $del_item['delivery_id'] ?>" <?= $del_item['delivery_id'] == $delivery_id ? 'selected' : '' ?>>
                                #<?= $del_item['delivery_id'] ?>: <?= htmlspecialchars($del_item['food_name']) ?> — Status: <?= htmlspecialchars($del_item['delivery_status']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </form>
            </div>

            <div class="content-box">
                <h3 class="section-title">Tracking Roadmap</h3>
                
                <?php if ($message !== "") { ?>
                    <div style="padding: 10px 15px; border-radius: 6px; margin-bottom: 15px; font-weight: 500; <?= $message_type === 'success' ? 'background:#d4edda; color:#155724;' : 'background:#f8d7da; color:#721c24;' ?>">
                        <?= $message ?>
                    </div>
                <?php } ?>

                <?php 
                $is_completed = in_array($assignment['delivery_status'], ['Completed', 'Delivered', 'Cancelled'], true);
                ?>

                <div class="roadmap" style="margin-bottom: 25px;">
                    <div class="roadmap-step <?= in_array($assignment['delivery_status'], ['Accepted', 'Picked Up', 'In Transit', 'Completed', 'Delivered']) ? 'done' : 'pending' ?>">
                        <div class="step-icon">1</div>
                        <div class="step-text">
                            <p class="step-title">Accepted</p>
                            <span class="badge badge-active">ACCEPTED</span>
                        </div>
                    </div>
                    <div class="roadmap-step <?= in_array($assignment['delivery_status'], ['Picked Up', 'In Transit', 'Completed', 'Delivered']) ? 'done' : 'pending' ?>">
                        <div class="step-icon">2</div>
                        <div class="step-text">
                            <p class="step-title">Picked Up</p>
                            <span class="badge badge-active">PICKED UP</span>
                        </div>
                    </div>
                    <div class="roadmap-step <?= in_array($assignment['delivery_status'], ['In Transit', 'Completed', 'Delivered']) ? 'done' : 'pending' ?>">
                        <div class="step-icon">3</div>
                        <div class="step-text">
                            <p class="step-title">In Transit</p>
                            <span class="badge badge-active">IN TRANSIT</span>
                        </div>
                    </div>
                    <div class="roadmap-step <?= in_array($assignment['delivery_status'], ['Completed', 'Delivered']) ? 'done' : 'pending' ?>">
                        <div class="step-icon">4</div>
                        <div class="step-text">
                            <p class="step-title">Completed</p>
                            <span class="badge badge-active">DELIVERED</span>
                        </div>
                    </div>
                </div>

                <?php if ($is_completed) { ?>
                    <div style="padding: 15px; background: #e2f0d9; color: #2e7d32; border-radius: 8px; text-align: center; font-weight: bold; font-size: 16px;">
                        <i class="fa-solid fa-circle-check"></i> Delivery Task Completed &amp; Verified
                    </div>
                <?php } else { ?>
                    <form method="post" enctype="multipart/form-data" class="action-buttons" style="display: block;">
                        <input type="hidden" name="delivery_id" value="<?= $delivery_id ?>">
                        
                        <div style="margin-bottom: 20px; text-align: left; background: #fff8f5; padding: 15px; border-radius: 8px; border: 1px solid #ffd9c7;">
                            <label style="display: block; font-size: 14px; font-weight: bold; color: #A33215; margin-bottom: 6px;">
                                <i class="fa-solid fa-camera" style="margin-right: 6px;"></i> Upload Food Photo (Received at Pickup):
                            </label>
                            <input type="file" name="pickup_image" accept="image/*" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; background: white; font-size: 13px; cursor: pointer;">
                            <small style="color: #666; display: block; margin-top: 5px;">Attach a picture of the food items you received from the donor when marking status as Picked Up.</small>

                            <?php if (!empty($assignment['pickup_proof_image'])) { ?>
                                <div style="margin-top: 12px; padding: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; display: flex; align-items: center; gap: 12px;">
                                    <img src="../<?= htmlspecialchars($assignment['pickup_proof_image']) ?>" alt="Received Food Photo" style="width: 55px; height: 55px; object-fit: cover; border-radius: 6px; border: 1px solid #ccc;">
                                    <div>
                                        <strong style="color: #166534; font-size: 13px; display: block;"><i class="fa-solid fa-circle-check"></i> Photo Currently Attached</strong>
                                        <a href="../<?= htmlspecialchars($assignment['pickup_proof_image']) ?>" target="_blank" style="color: #FA8145; font-size: 12px; text-decoration: underline; font-weight: 500;">View Uploaded Photo</a>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button name="status" value="Picked Up" class="btn btn-primary action-btn" <?= $assignment['delivery_status'] === 'Picked Up' ? 'style="background:#666;"' : '' ?>>
                                Mark Picked Up
                            </button>
                            <button name="status" value="In Transit" class="btn btn-outline action-btn" <?= $assignment['delivery_status'] === 'In Transit' ? 'style="background:#666; color:white;"' : '' ?>>
                                Mark In Transit
                            </button>
                            <button name="status" value="Completed" class="btn btn-outline action-btn" style="border-color: #2e7d32; color: #2e7d32;">
                                Mark Completed
                            </button>
                        </div>
                    </form>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>
</body>
</html>
