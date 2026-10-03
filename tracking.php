<?php

session_start();

include "db.php";


// ----------------------------------------------------
// CHECK LOGIN
// ----------------------------------------------------

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


// ----------------------------------------------------
// GET USER NAME
// ----------------------------------------------------

$user_sql = "
    SELECT full_name
    FROM users
    WHERE user_id = ?
    LIMIT 1
";

$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);

$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);

$full_name = $user['full_name'] ?? "User";


// ----------------------------------------------------
// GET DELIVERY ID / CLAIM ID
// ----------------------------------------------------

$delivery_id = isset($_GET['delivery_id'])
    ? (int)$_GET['delivery_id']
    : 0;

$claim_id = isset($_GET['claim_id'])
    ? (int)$_GET['claim_id']
    : 0;


// ----------------------------------------------------
// UPDATE DELIVERY STATUS
// ----------------------------------------------------
//
// This page uses normal PHP forms.
// Therefore JavaScript is not required.
//
// ----------------------------------------------------

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $posted_delivery_id = isset($_POST['delivery_id'])
        ? (int)$_POST['delivery_id']
        : 0;

    $action = $_POST['action'] ?? "";


    if ($posted_delivery_id > 0) {

        // --------------------------------------------
        // VERIFY THAT THIS DELIVERY BELONGS TO USER
        // --------------------------------------------

        $verify_sql = "
            SELECT
                pd.delivery_id,
                pd.delivery_status
            FROM pickup_deliveries pd

            INNER JOIN food_claims fc
                ON pd.claim_id = fc.claim_id

            INNER JOIN food_donations fd
                ON fc.donation_id = fd.donation_id

            WHERE pd.delivery_id = ?
            AND fd.donor_id = ?

            LIMIT 1
        ";

        $verify_stmt = mysqli_prepare($conn, $verify_sql);

        mysqli_stmt_bind_param(
            $verify_stmt,
            "ii",
            $posted_delivery_id,
            $user_id
        );

        mysqli_stmt_execute($verify_stmt);

        $verify_result = mysqli_stmt_get_result($verify_stmt);

        $verify_delivery = mysqli_fetch_assoc($verify_result);


        if (!$verify_delivery) {

            $error = "Delivery record not found.";

        } else {

            // ----------------------------------------
            // MARK PICKED UP
            // ----------------------------------------

            if ($action === "picked_up") {

                $update_sql = "
                    UPDATE pickup_deliveries

                    SET
                        delivery_status = 'Picked Up',
                        picked_up_at = NOW()

                    WHERE delivery_id = ?
                ";

                $update_stmt = mysqli_prepare(
                    $conn,
                    $update_sql
                );

                mysqli_stmt_bind_param(
                    $update_stmt,
                    "i",
                    $posted_delivery_id
                );

                if (mysqli_stmt_execute($update_stmt)) {

                    $message = "Delivery marked as Picked Up.";

                } else {

                    $error = "Unable to update delivery status.";
                }
            }


            // ----------------------------------------
            // MARK IN TRANSIT
            // ----------------------------------------

            elseif ($action === "in_transit") {

                $update_sql = "
                    UPDATE pickup_deliveries

                    SET
                        delivery_status = 'In Transit',
                        in_transit_at = NOW()

                    WHERE delivery_id = ?
                ";

                $update_stmt = mysqli_prepare(
                    $conn,
                    $update_sql
                );

                mysqli_stmt_bind_param(
                    $update_stmt,
                    "i",
                    $posted_delivery_id
                );

                if (mysqli_stmt_execute($update_stmt)) {

                    $message = "Delivery marked as In Transit.";

                } else {

                    $error = "Unable to update delivery status.";
                }
            }


            // ----------------------------------------
            // MARK DELIVERED
            // ----------------------------------------
            //
            // The supplied design uses OTP verification
            // before delivery is completed.
            //
            // Therefore we redirect to the OTP page
            // instead of directly changing Delivered.
            // ----------------------------------------

            elseif ($action === "delivered") {

                header(
                    "Location: otp-verification.php?delivery_id="
                    . $posted_delivery_id
                );

                exit();
            }
        }
    }
}


// ----------------------------------------------------
// GET DELIVERY DATA
// ----------------------------------------------------

$row = null;


// ----------------------------------------------------
// SEARCH BY DELIVERY ID
// ----------------------------------------------------

if ($delivery_id > 0) {

    $sql = "
        SELECT
            pd.delivery_id,
            pd.claim_id,
            pd.pickup_address,
            pd.delivery_address,
            pd.pickup_date,
            pd.scheduled_time,
            pd.special_instructions,
            pd.delivery_status,

            pd.assigned_at,
            pd.picked_up_at,
            pd.in_transit_at,
            pd.delivered_at,
            pd.completed_at,

            fc.claim_date,
            fc.status AS claim_status,

            fd.donation_id,
            fd.food_name,
            fd.quantity,
            fd.quantity_unit,

            charity.full_name AS charity_name

        FROM pickup_deliveries pd

        INNER JOIN food_claims fc
            ON pd.claim_id = fc.claim_id

        INNER JOIN food_donations fd
            ON fc.donation_id = fd.donation_id

        LEFT JOIN users charity
            ON fc.charity_id = charity.user_id

        WHERE pd.delivery_id = ?
        AND fd.donor_id = ?

        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $delivery_id,
        $user_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row = mysqli_fetch_assoc($result);
}


// ----------------------------------------------------
// SEARCH BY CLAIM ID
// ----------------------------------------------------

elseif ($claim_id > 0) {

    $sql = "
        SELECT
            pd.delivery_id,
            pd.claim_id,
            pd.pickup_address,
            pd.delivery_address,
            pd.pickup_date,
            pd.scheduled_time,
            pd.special_instructions,
            pd.delivery_status,

            pd.assigned_at,
            pd.picked_up_at,
            pd.in_transit_at,
            pd.delivered_at,
            pd.completed_at,

            fc.claim_date,
            fc.status AS claim_status,

            fd.donation_id,
            fd.food_name,
            fd.quantity,
            fd.quantity_unit,

            charity.full_name AS charity_name

        FROM pickup_deliveries pd

        INNER JOIN food_claims fc
            ON pd.claim_id = fc.claim_id

        INNER JOIN food_donations fd
            ON fc.donation_id = fd.donation_id

        LEFT JOIN users charity
            ON fc.charity_id = charity.user_id

        WHERE fc.claim_id = ?
        AND fd.donor_id = ?

        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $claim_id,
        $user_id
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $row = mysqli_fetch_assoc($result);
}


// ----------------------------------------------------
// NO DELIVERY FOUND
// ----------------------------------------------------

if (!$row) {

    ?>

    <!DOCTYPE html>

    <html lang="en">

    <head>

        <meta charset="UTF-8">

        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Tracking - FoodRescue</title>

        <link rel="stylesheet" href="tracking.css">

    </head>

    <body>

        <div class="error-page">

            <div class="error-box">

                <h2>Tracking Not Found</h2>

                <p>
                    The requested delivery could not be found.
                </p>

                <a href="multracking.php">
                    Back to Tracking
                </a>

            </div>

        </div>

    </body>

    </html>

    <?php

    exit();
}


// ----------------------------------------------------
// CURRENT STATUS
// ----------------------------------------------------

$current_status = $row['delivery_status'];


// ----------------------------------------------------
// DETERMINE ROADMAP STATUS
// ----------------------------------------------------
//
// 0 = pending
// 1 = current
// 2 = completed
//
// ----------------------------------------------------

$step1 = 0;
$step2 = 0;
$step3 = 0;
$step4 = 0;


switch ($current_status) {

    case "Available":

        $step1 = 1;

        break;


    case "Assigned":

    case "Accepted":

        $step1 = 2;
        $step2 = 1;

        break;


    case "Picked Up":

        $step1 = 2;
        $step2 = 2;
        $step3 = 1;

        break;


    case "In Transit":

        $step1 = 2;
        $step2 = 2;
        $step3 = 1;

        break;


    case "Delivered":

        $step1 = 2;
        $step2 = 2;
        $step3 = 2;
        $step4 = 1;

        break;


    case "Completed":

        $step1 = 2;
        $step2 = 2;
        $step3 = 2;
        $step4 = 2;

        break;


    case "Cancelled":

        $step1 = 2;

        break;
}


// ----------------------------------------------------
// FORMAT DATE/TIME
// ----------------------------------------------------

function formatTrackingDateTime($date)
{
    if (empty($date)) {
        return "Pending";
    }

    return date("d M Y, h:i A", strtotime($date));
}


// ----------------------------------------------------
// STEP DATE/TIME
// ----------------------------------------------------

// Order Accepted uses claim_date because the database
// does not have a separate accepted_at field.

$accepted_time = $row['claim_date'];

$picked_up_time = $row['picked_up_at'];

$in_transit_time = $row['in_transit_at'];

$delivered_time = $row['delivered_at'];

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Delivery Progress - FoodRescue</title>

    <link rel="stylesheet" href="tracking.css">

</head>


<body>


<!-- ====================================================
     SIDEBAR
===================================================== -->

<div class="sidebar">

    <div class="logo-section">

        <div class="logo-icon">
            F
        </div>

        <div class="logo-text">

            <h2>FoodRescue</h2>

            <p>Food Donation Network</p>

        </div>

    </div>


    <div class="menu">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="post.php">
            Post Food
        </a>

        <a href="shedule.php">
            Schedule Pickup
        </a>

        <a href="impact_summary.php">
            Impact Summary
        </a>

        <a href="mulreceipt.php">
            Receipts
        </a>

        <a href="message.php">
            Messages
        </a>

        <a href="history.php">
            Donation History
        </a>

        <a href="multracking.php" class="active">
            Track Donations
        </a>

    </div>


    <div class="sidebar-bottom">

        <div class="profile-circle">

            <?php
            echo strtoupper(
                substr($full_name, 0, 1)
            );
            ?>

        </div>

        <div class="profile-name">

            <?php
            echo htmlspecialchars($full_name);
            ?>

        </div>

        <a href="logout.php" class="logout">
            Logout
        </a>

    </div>

</div>



<!-- ====================================================
     MAIN CONTENT
===================================================== -->

<div class="main-content">


    <!-- HEADER -->

    <div class="top-header">

        <a href="multracking.php" class="back-link">
            Pickup Requests
        </a>

        <h1>
            Delivery Progress
        </h1>

        <p>
            Track the progress of your food donation
        </p>

    </div>



    <!-- SUCCESS / ERROR -->

    <?php if (!empty($message)): ?>

        <div class="message success-message">

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>


    <?php if (!empty($error)): ?>

        <div class="message error-message">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>



    <!-- =================================================
         DELIVERY SUMMARY
    ================================================== -->

    <div class="delivery-summary">

        <div>

            <span class="small-label">
                Donation
            </span>

            <h2>
                #<?php echo (int)$row['donation_id']; ?>
            </h2>

        </div>


        <div>

            <span class="small-label">
                Food
            </span>

            <strong>
                <?php
                echo htmlspecialchars(
                    $row['food_name']
                );
                ?>
            </strong>

        </div>


        <div>

            <span class="small-label">
                Quantity
            </span>

            <strong>

                <?php
                echo htmlspecialchars(
                    $row['quantity']
                );
                ?>

                <?php
                echo htmlspecialchars(
                    $row['quantity_unit']
                );
                ?>

            </strong>

        </div>


        <div>

            <span class="small-label">
                Charity
            </span>

            <strong>

                <?php
                echo htmlspecialchars(
                    $row['charity_name']
                    ?? "Charity"
                );
                ?>

            </strong>

        </div>

    </div>



    <!-- =================================================
         ROADMAP
    ================================================== -->

    <div class="progress-card">

        <h2>Delivery Progress</h2>


        <div class="roadmap">


            <!-- STEP 1 -->

            <div class="roadmap-step
                <?php
                if ($step1 === 2) {
                    echo "done";
                } elseif ($step1 === 1) {
                    echo "current";
                }
                ?>
            ">

                <div class="step-circle">

                    <?php if ($step1 === 2): ?>

                        ✓

                    <?php else: ?>

                        1

                    <?php endif; ?>

                </div>


                <div class="step-content">

                    <h3>
                        Order Accepted
                    </h3>

                    <?php if (!empty($accepted_time)): ?>

                        <p>
                            <?php
                            echo formatTrackingDateTime(
                                $accepted_time
                            );
                            ?>
                        </p>

                    <?php else: ?>

                        <p>
                            Waiting for claim
                        </p>

                    <?php endif; ?>


                    <span>

                        <?php

                        if ($step1 === 2) {
                            echo "COMPLETED";
                        } elseif ($step1 === 1) {
                            echo "IN PROGRESS";
                        } else {
                            echo "PENDING";
                        }

                        ?>

                    </span>

                </div>

            </div>



            <!-- STEP 2 -->

            <div class="roadmap-step
                <?php
                if ($step2 === 2) {
                    echo "done";
                } elseif ($step2 === 1) {
                    echo "current";
                }
                ?>
            ">

                <div class="step-circle">

                    <?php if ($step2 === 2): ?>

                        ✓

                    <?php else: ?>

                        2

                    <?php endif; ?>

                </div>


                <div class="step-content">

                    <h3>
                        Picked Up
                    </h3>


                    <?php if (!empty($picked_up_time)): ?>

                        <p>
                            <?php
                            echo formatTrackingDateTime(
                                $picked_up_time
                            );
                            ?>
                        </p>

                    <?php else: ?>

                        <p>
                            Awaiting collection
                        </p>

                    <?php endif; ?>


                    <span>

                        <?php

                        if ($step2 === 2) {
                            echo "COMPLETED";
                        } elseif ($step2 === 1) {
                            echo "IN PROGRESS";
                        } else {
                            echo "PENDING";
                        }

                        ?>

                    </span>

                </div>

            </div>



            <!-- STEP 3 -->

            <div class="roadmap-step
                <?php
                if ($step3 === 2) {
                    echo "done";
                } elseif ($step3 === 1) {
                    echo "current";
                }
                ?>
            ">

                <div class="step-circle">

                    <?php if ($step3 === 2): ?>

                        ✓

                    <?php else: ?>

                        3

                    <?php endif; ?>

                </div>


                <div class="step-content">

                    <h3>
                        In Transit
                    </h3>


                    <?php if (!empty($in_transit_time)): ?>

                        <p>
                            <?php
                            echo formatTrackingDateTime(
                                $in_transit_time
                            );
                            ?>
                        </p>

                    <?php else: ?>

                        <p>
                            Pending previous step
                        </p>

                    <?php endif; ?>


                    <span>

                        <?php

                        if ($step3 === 2) {
                            echo "COMPLETED";
                        } elseif ($step3 === 1) {
                            echo "IN PROGRESS";
                        } else {
                            echo "PENDING";
                        }

                        ?>

                    </span>

                </div>

            </div>



            <!-- STEP 4 -->

            <div class="roadmap-step
                <?php
                if ($step4 === 2) {
                    echo "done";
                } elseif ($step4 === 1) {
                    echo "current";
                }
                ?>
            ">

                <div class="step-circle">

                    <?php if ($step4 === 2): ?>

                        ✓

                    <?php else: ?>

                        4

                    <?php endif; ?>

                </div>


                <div class="step-content">

                    <h3>
                        Delivered
                    </h3>


                    <?php if (!empty($delivered_time)): ?>

                        <p>
                            <?php
                            echo formatTrackingDateTime(
                                $delivered_time
                            );
                            ?>
                        </p>

                    <?php else: ?>

                        <p>

                            <?php
                            echo htmlspecialchars(
                                $row['delivery_address']
                                ?: "Destination pending"
                            );
                            ?>

                        </p>

                    <?php endif; ?>


                    <span>

                        <?php

                        if ($step4 === 2) {
                            echo "COMPLETED";
                        } elseif ($step4 === 1) {
                            echo "IN PROGRESS";
                        } else {
                            echo "PENDING";
                        }

                        ?>

                    </span>

                </div>

            </div>


        </div>

    </div>



    <!-- =================================================
         ACTION BUTTONS
    ================================================== -->

    <div class="action-card">

        <h2>Update Delivery</h2>

        <p>
            Update the current delivery status.
        </p>


        <div class="action-buttons">


            <!-- PICKED UP -->

            <?php if (
                $current_status !== "Picked Up"
                && $current_status !== "In Transit"
                && $current_status !== "Delivered"
                && $current_status !== "Completed"
                && $current_status !== "Cancelled"
            ): ?>

                <form method="POST">

                    <input
                        type="hidden"
                        name="delivery_id"
                        value="<?php echo (int)$row['delivery_id']; ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="picked_up"
                    >

                    <button
                        type="submit"
                        class="action-btn"
                    >
                        Mark Picked Up
                    </button>

                </form>

            <?php endif; ?>



            <!-- IN TRANSIT -->

            <?php if (
                $current_status === "Picked Up"
                || $current_status === "Assigned"
                || $current_status === "Accepted"
            ): ?>

                <form method="POST">

                    <input
                        type="hidden"
                        name="delivery_id"
                        value="<?php echo (int)$row['delivery_id']; ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="in_transit"
                    >

                    <button
                        type="submit"
                        class="action-btn"
                    >
                        Mark In Transit
                    </button>

                </form>

            <?php endif; ?>



            <!-- DELIVERED / OTP -->

            <?php if (
                $current_status === "In Transit"
                || $current_status === "Picked Up"
            ): ?>

                <form method="POST">

                    <input
                        type="hidden"
                        name="delivery_id"
                        value="<?php echo (int)$row['delivery_id']; ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="delivered"
                    >

                    <button
                        type="submit"
                        class="action-btn primary"
                    >
                        Mark Delivered
                    </button>

                </form>

            <?php endif; ?>


        </div>

    </div>



    <!-- BACK -->

    <div class="back-container">

        <a href="multracking.php">
            ← Back to All Tracking
        </a>

    </div>


</div>


</body>

</html>