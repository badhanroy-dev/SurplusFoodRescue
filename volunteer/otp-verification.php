<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: ../charityGuest/guest/login.html");
    exit();
}
require "db.php";
$user_id = (int)$_SESSION["user_id"];
$delivery_id = isset($_GET["delivery_id"]) ? (int)$_GET["delivery_id"] : (isset($_POST["delivery_id"]) ? (int)$_POST["delivery_id"] : 0);
$message = "";
$message_type = "info";
$show_alert = false;

// Fetch assigned active deliveries for dropdown selection
$deliveries_sql = "
    SELECT pd.delivery_id, pd.pickup_address, pd.delivery_address, pd.delivery_status, fd.food_name, charity.full_name AS charity_name
    FROM pickup_deliveries pd
    INNER JOIN food_claims fc ON pd.claim_id = fc.claim_id
    INNER JOIN food_donations fd ON fc.donation_id = fd.donation_id
    INNER JOIN users charity ON fc.charity_id = charity.user_id
    WHERE pd.volunteer_id = ? AND pd.delivery_status IN ('Accepted', 'Picked Up', 'In Transit')
    ORDER BY pd.updated_at DESC
";
$stmt_del = mysqli_prepare($conn, $deliveries_sql);
mysqli_stmt_bind_param($stmt_del, "i", $user_id);
mysqli_stmt_execute($stmt_del);
$deliveries_res = mysqli_stmt_get_result($stmt_del);
$assigned_deliveries = $deliveries_res ? mysqli_fetch_all($deliveries_res, MYSQLI_ASSOC) : [];

if ($delivery_id === 0 && count($assigned_deliveries) > 0) {
    $delivery_id = (int)$assigned_deliveries[0]['delivery_id'];
}

// 1. ACTION: Generate New Random OTP (Hidden from Volunteer, Sent to DB for Charity)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["generate_otp"]) && $delivery_id > 0) {
    $random_otp = sprintf("%06d", mt_rand(100000, 999999));
    
    // Check if record exists in database
    $check_otp = mysqli_query($conn, "SELECT otp_id FROM otp_verifications WHERE delivery_id = $delivery_id");
    if ($check_otp && mysqli_num_rows($check_otp) > 0) {
        $update_otp = "UPDATE otp_verifications SET otp_code = '$random_otp', status = 'Pending', expiry_time = DATE_ADD(NOW(), INTERVAL 1 HOUR), generated_at = NOW() WHERE delivery_id = $delivery_id";
        mysqli_query($conn, $update_otp);
    } else {
        $insert_otp = "INSERT INTO otp_verifications (delivery_id, otp_code, status, expiry_time) VALUES ($delivery_id, '$random_otp', 'Pending', DATE_ADD(NOW(), INTERVAL 1 HOUR))";
        mysqli_query($conn, $insert_otp);
    }
    
    $message = "Secure OTP code generated and sent to the recipient charity for verification!";
    $message_type = "success";
    $show_alert = true;
}

// 2. ACTION: Verify Handover OTP
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["verify_otp"]) && $delivery_id > 0) {
    $input_otp = trim($_POST["otp_code"] ?? '');
    
    $fetch_otp = mysqli_query($conn, "SELECT otp_code, status FROM otp_verifications WHERE delivery_id = $delivery_id AND status = 'Pending' ORDER BY otp_id DESC LIMIT 1");
    if ($fetch_otp && $otp_row = mysqli_fetch_assoc($fetch_otp)) {
        if ($otp_row['otp_code'] === $input_otp) {
            mysqli_query($conn, "UPDATE otp_verifications SET status = 'Verified', verified_at = NOW() WHERE delivery_id = $delivery_id");
            
            $get_claim = mysqli_query($conn, "SELECT claim_id FROM pickup_deliveries WHERE delivery_id = $delivery_id");
            if ($get_claim && $claim_row = mysqli_fetch_assoc($get_claim)) {
                $claim_id = (int)$claim_row['claim_id'];
                mysqli_query($conn, "UPDATE pickup_deliveries SET delivery_status = 'Completed', delivered_at = NOW(), completed_at = NOW() WHERE delivery_id = $delivery_id");
                mysqli_query($conn, "UPDATE food_claims SET status = 'Completed' WHERE claim_id = $claim_id");
                mysqli_query($conn, "UPDATE food_donations fd INNER JOIN food_claims fc ON fd.donation_id = fc.donation_id SET fd.status = 'Completed' WHERE fc.claim_id = $claim_id");
            }
            
            $message = "Handover verified successfully! Delivery marked as Completed.";
            $message_type = "success";
        } else {
            $message = "Incorrect OTP code. Please ask the charity for the correct 6-digit verification code.";
            $message_type = "error";
        }
    } else {
        $message = "No pending OTP found for this delivery. Please click 'Generate OTP Code' to send a code to the recipient.";
        $message_type = "error";
    }
}

// Check current OTP status in database (without exposing raw numeric code to volunteer)
$has_pending_otp = false;
if ($delivery_id > 0) {
    $res_current = mysqli_query($conn, "SELECT status FROM otp_verifications WHERE delivery_id = $delivery_id AND status = 'Pending' ORDER BY otp_id DESC LIMIT 1");
    if ($res_current && mysqli_num_rows($res_current) > 0) {
        $has_pending_otp = true;
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
    <title>Rider Portal - OTP Verification</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="otp-verification.css">
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
            <li class="menu-item active"><a href="otp-verification.php">OTP Verification</a></li>
            <li class="menu-item"><a href="update-delivery-status.php">Update Delivery Status</a></li>
            <li class="menu-item"><a href="report-delay.php">Report Delay</a></li>
            <li class="menu-item"><a href="volunteer-chat.php">Chat</a></li>
        </ul>
        <ul class="menu bottom-menu">
            <li class="menu-item"><a href="../charityGuest/guest/settings.html">Settings</a></li>
            <li class="menu-item"><a href="../charityGuest/guest/logout.php">Logout</a></li>
        </ul>
    </div>
    <div class="main">
        <div class="page-header">
            <div>
                <h2 class="page-heading">Confirm Handover</h2>
                <p class="page-subtitle">Generate secure OTP code for recipient charity or verify handover.</p>
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

        <?php if ($message !== "") { ?>
            <div style="padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; <?= $message_type === 'success' ? 'background:#d4edda; color:#155724;' : 'background:#f8d7da; color:#721c24;' ?>">
                <?= $message ?>
            </div>
        <?php } ?>

        <div class="otp-grid">
            <!-- Left Box: Select Task & Generate OTP -->
            <div class="content-box">
                <h3 style="color: #333; margin-bottom: 15px;">Active Task Selection</h3>
                
                <form method="get" style="margin-bottom: 20px;">
                    <label style="font-size: 13px; color: #666; display: block; margin-bottom: 5px;">SELECT ASSIGNED DELIVERY:</label>
                    <select name="delivery_id" onchange="this.form.submit()" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px;">
                        <?php if (count($assigned_deliveries) > 0) { ?>
                            <?php foreach ($assigned_deliveries as $del) { ?>
                                <option value="<?= $del['delivery_id'] ?>" <?= $del['delivery_id'] == $delivery_id ? 'selected' : '' ?>>
                                    #<?= $del['delivery_id'] ?>: <?= htmlspecialchars($del['food_name']) ?>
                                </option>
                            <?php } ?>
                        <?php } else { ?>
                            <option value="0">No active assigned deliveries</option>
                        <?php } ?>
                    </select>
                </form>

                <div class="task-tag-row" style="margin-bottom: 15px;">
                    <?php if ($has_pending_otp) { ?>
                        <span class="badge badge-active" style="font-size: 14px; padding: 8px 16px; background: #e2f0d9; color: #2e7d32; border-radius: 20px; font-weight: bold;">
                            <i class="fa-solid fa-paper-plane"></i> OTP SENT TO RECIPIENT CHARITY
                        </span>
                    <?php } else { ?>
                        <span class="badge badge-warning" style="font-size: 14px; padding: 6px 12px; background: #fff3cd; color: #856404; border-radius: 20px;">
                            OTP NOT GENERATED YET
                        </span>
                    <?php } ?>
                </div>

                <form method="post" style="margin-top: 20px;">
                    <input type="hidden" name="delivery_id" value="<?= $delivery_id ?>">
                    <button type="submit" name="generate_otp" class="btn btn-primary btn-full" style="padding: 12px; font-size: 15px; font-weight: bold;" <?= $delivery_id <= 0 ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-key"></i> Generate OTP Code
                    </button>
                </form>
                <p style="font-size: 12px; color: #777; margin-top: 10px;">Generates a secure 6-digit code in the database. The recipient charity receives this code on their portal to verify delivery.</p>
            </div>

            <!-- Right Box: Enter OTP & Verify Handover -->
            <div class="content-box otp-card">
                <h3>Security Verification</h3>
                <p class="otp-instruction">Enter the 6-digit handover code provided by the charity to complete delivery.</p>
                
                <form method="post" id="otp-form">
                    <input type="hidden" name="delivery_id" value="<?= $delivery_id ?>">
                    <input type="hidden" name="verify_otp" value="1">
                    <input type="hidden" name="otp_code" id="otp_code_hidden">
                    
                    <div class="otp-inputs">
                        <input type="text" maxlength="1" class="otp-box" inputmode="numeric">
                        <input type="text" maxlength="1" class="otp-box" inputmode="numeric">
                        <input type="text" maxlength="1" class="otp-box" inputmode="numeric">
                        <input type="text" maxlength="1" class="otp-box" inputmode="numeric">
                        <input type="text" maxlength="1" class="otp-box" inputmode="numeric">
                        <input type="text" maxlength="1" class="otp-box" inputmode="numeric">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full" style="margin-top: 15px;">Verify Handover</button>
                </form>

                <a href="update-delivery-status.php?delivery_id=<?= $delivery_id ?>" class="btn btn-outline btn-full" style="margin-top: 10px; text-decoration: none; display: block; text-align: center;">Back to Delivery Status</a>
            </div>
        </div>
    </div>
</div>

<script>
<?php if ($show_alert) { ?>
    alert("OTP code generated and sent to the recipient charity!");
<?php } ?>

var boxes = document.getElementsByClassName('otp-box');
for (var i = 0; i < boxes.length; i++) {
    boxes[i].addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 1);
        if (this.value && this.nextElementSibling) {
            this.nextElementSibling.focus();
        }
    });
}

document.getElementById('otp-form').addEventListener('submit', function(e) {
    var code = '';
    for (var i = 0; i < boxes.length; i++) {
        code += boxes[i].value;
    }
    document.getElementById('otp_code_hidden').value = code;
});
</script>
</body>
</html>
