<?php

session_start();

include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$message = "";
$message_type = "";


/* =========================================
   HANDLE PICKUP SCHEDULING
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $claim_id = isset($_POST['claim_id']) ? (int) $_POST['claim_id'] : 0;
    $building = trim($_POST['building'] ?? "");
    $special_instructions = trim($_POST['special_instructions'] ?? "");
    $scheduled_time = $_POST['scheduled_time'] ?? "";

    if ($claim_id <= 0) {

        $message = "Please select a donation.";
        $message_type = "error";

    } elseif ($scheduled_time === "") {

        $message = "Please select a pickup time.";
        $message_type = "error";

    } else {

        /*
         * Get the selected claim.
         * The claim must belong to a donation owned by
         * the currently logged-in donor.
         */
        $claim_sql = "
            SELECT
                fc.claim_id,
                fc.status AS claim_status,
                fd.donation_id,
                fd.food_name,
                fd.quantity,
                fd.quantity_unit,
                fd.pickup_date,
                fd.pickup_address
            FROM food_claims fc
            INNER JOIN food_donations fd
                ON fc.donation_id = fd.donation_id
            WHERE fc.claim_id = ?
            AND fd.donor_id = ?
            LIMIT 1
        ";

        $claim_stmt = mysqli_prepare($conn, $claim_sql);

        mysqli_stmt_bind_param(
            $claim_stmt,
            "ii",
            $claim_id,
            $user_id
        );

        mysqli_stmt_execute($claim_stmt);

        $claim_result = mysqli_stmt_get_result($claim_stmt);

        $claim = mysqli_fetch_assoc($claim_result);

        mysqli_stmt_close($claim_stmt);


        if (!$claim) {

            $message = "The selected donation could not be found.";
            $message_type = "error";

        } elseif ($claim['claim_status'] !== 'Claimed') {

            $message = "This donation is not currently available for pickup scheduling.";
            $message_type = "error";

        } else {

            /*
             * Check whether a pickup delivery already exists
             * for this claim.
             */
            $check_sql = "
                SELECT
                    delivery_id,
                    delivery_status
                FROM pickup_deliveries
                WHERE claim_id = ?
                LIMIT 1
            ";

            $check_stmt = mysqli_prepare($conn, $check_sql);

            mysqli_stmt_bind_param(
                $check_stmt,
                "i",
                $claim_id
            );

            mysqli_stmt_execute($check_stmt);

            $check_result = mysqli_stmt_get_result($check_stmt);

            $existing_delivery = mysqli_fetch_assoc($check_result);

            mysqli_stmt_close($check_stmt);


            if ($existing_delivery) {

                $message = "A pickup schedule already exists for this donation.";
                $message_type = "error";

            } else {

                /*
                 * Build the final pickup address.
                 *
                 * The original donation already has the main
                 * pickup address in food_donations.
                 *
                 * The building/floor/suite information from
                 * this page is added to that address.
                 */
                $pickup_address = $claim['pickup_address'];

                if ($building !== "") {
                    $pickup_address .= ", " . $building;
                }


                /*
                 * Insert the pickup schedule.
                 */
                $insert_sql = "
                    INSERT INTO pickup_deliveries
                    (
                        claim_id,
                        pickup_address,
                        delivery_address,
                        pickup_date,
                        scheduled_time,
                        special_instructions,
                        delivery_status
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'Available'
                    )
                ";

                $insert_stmt = mysqli_prepare($conn, $insert_sql);

                /*
                 * The donation's pickup address is also used
                 * as the delivery address because this page
                 * only configures the donor's pickup logistics.
                 *
                 * The charity/destination address is not stored
                 * in food_donations, so it should not be invented.
                 */
                $delivery_address = $claim['pickup_address'];

                mysqli_stmt_bind_param(
                    $insert_stmt,
                    "isssss",
                    $claim_id,
                    $pickup_address,
                    $delivery_address,
                    $claim['pickup_date'],
                    $scheduled_time,
                    $special_instructions
                );

                if (mysqli_stmt_execute($insert_stmt)) {

                    $message = "Pickup has been scheduled successfully.";
                    $message_type = "success";

                } else {

                    $message = "Failed to schedule the pickup.";
                    $message_type = "error";
                }

                mysqli_stmt_close($insert_stmt);
            }
        }
    }
}


/* =========================================
   GET DONOR'S CLAIMED DONATIONS
========================================= */

$claims_sql = "
    SELECT
        fc.claim_id,
        fc.status AS claim_status,
        fd.donation_id,
        fd.food_name,
        fd.quantity,
        fd.quantity_unit,
        fd.pickup_date,
        fd.pickup_address,
        fc.claim_date
    FROM food_claims fc
    INNER JOIN food_donations fd
        ON fc.donation_id = fd.donation_id
    WHERE fd.donor_id = ?
    AND fc.status = 'Claimed'
    ORDER BY fd.pickup_date ASC, fc.claim_date ASC
";

$claims_stmt = mysqli_prepare($conn, $claims_sql);

mysqli_stmt_bind_param(
    $claims_stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($claims_stmt);

$claims_result = mysqli_stmt_get_result($claims_stmt);


/* =========================================
   SELECTED CLAIM
========================================= */

$selected_claim_id = isset($_GET['claim_id'])
    ? (int) $_GET['claim_id']
    : 0;

$selected_claim = null;


/*
 * If a claim_id was supplied through the URL,
 * find that claim from the database.
 */
if ($selected_claim_id > 0) {

    $selected_sql = "
        SELECT
            fc.claim_id,
            fd.food_name,
            fd.quantity,
            fd.quantity_unit,
            fd.pickup_date,
            fd.pickup_address
        FROM food_claims fc
        INNER JOIN food_donations fd
            ON fc.donation_id = fd.donation_id
        WHERE fc.claim_id = ?
        AND fd.donor_id = ?
        AND fc.status = 'Claimed'
        LIMIT 1
    ";

    $selected_stmt = mysqli_prepare($conn, $selected_sql);

    mysqli_stmt_bind_param(
        $selected_stmt,
        "ii",
        $selected_claim_id,
        $user_id
    );

    mysqli_stmt_execute($selected_stmt);

    $selected_result = mysqli_stmt_get_result($selected_stmt);

    $selected_claim = mysqli_fetch_assoc($selected_result);

    mysqli_stmt_close($selected_stmt);
}

?>

<!DOCTYPE html>

<html>

<head>

    <title>FoodRescue - Schedule Pickup</title>

    <link rel="stylesheet" href="shedule.css">

</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">

            <h2>FoodRescue</h2>

            <p class="donorportaltext">
                Donor Portal
            </p>

        </div>


        <nav class="menu">

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="post.php">
                Post Food
            </a>

            <a href="shedule.php" class="active">
                Pickup Requests
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

            <a href="multracking.php">
                Pickup Status
            </a>

        </nav>


        <div class="bottom-menu">

            <a href="#">
                Settings
            </a>

            <a href="logout.php">
                Logout
            </a>

        </div>

    </aside>



    <!-- MAIN -->

    <div class="main">

        <!-- TOP BAR -->

        <header class="topbar">

            <div class="top-info">
                🔔 &nbsp; 👤
            </div>

        </header>



        <!-- CONTENT -->

        <main class="content">

            <div class="title">

                <div>

                    <h1>
                        Schedule Pickup
                    </h1>

                    <p>
                        Configure your donation details and logistics preferences
                    </p>

                </div>

                <div class="search2">
                    Search orders...
                </div>

            </div>


            <?php if ($message !== "") { ?>

                <div class="message <?php echo $message_type; ?>">

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php } ?>



            <form method="POST" action="shedule.php">

                <div class="grid">


                    <!-- ADDRESS DETAILS -->

                    <div class="card">

                        <h2>
                            Address Details
                        </h2>


                        <label>
                            DONATION
                        </label>

                        <select
                            name="claim_id"
                            id="claim_id"
                            required
                        >

                            <option value="">
                                Select a donation
                            </option>


                            <?php while ($claim = mysqli_fetch_assoc($claims_result)) { ?>

                                <option
                                    value="<?php echo $claim['claim_id']; ?>"
                                    <?php
                                    if (
                                        $selected_claim_id == $claim['claim_id']
                                    ) {
                                        echo "selected";
                                    }
                                    ?>
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $claim['food_name']
                                    );

                                    echo " - ";

                                    echo htmlspecialchars(
                                        $claim['quantity']
                                    );

                                    echo " ";

                                    echo htmlspecialchars(
                                        $claim['quantity_unit']
                                    );

                                    echo " - Pickup ";

                                    echo htmlspecialchars(
                                        $claim['pickup_date']
                                    );
                                    ?>

                                </option>

                            <?php } ?>

                        </select>


                        <label>
                            PICKUP ADDRESS
                        </label>

                        <input
                            type="text"
                            value="<?php
                            echo $selected_claim
                                ? htmlspecialchars(
                                    $selected_claim['pickup_address']
                                )
                                : '';
                            ?>"
                            placeholder="Select a donation"
                            readonly
                        >


                        <label>
                            BUILDING / FLOOR / SUITE
                        </label>

                        <input
                            type="text"
                            name="building"
                            placeholder="Building / Floor / Suite"
                        >


                        <label>
                            SPECIAL INSTRUCTIONS
                        </label>

                        <textarea
                            name="special_instructions"
                            placeholder="Add special pickup instructions"
                        ></textarea>


                        <div class="notice">
                            Pickup information is saved with your selected donation and made available for volunteer logistics.
                        </div>

                    </div>



                    <!-- SCHEDULE TIME -->

                    <div class="card">

                        <h2>
                            Schedule Time
                        </h2>


                        <label>
                            SELECT PICKUP TIME
                        </label>

                        <input
                            type="time"
                            name="scheduled_time"
                            class="time-input"
                            required
                        >


                        <?php if ($selected_claim) { ?>

                            <label>
                                PICKUP DATE
                            </label>

                            <input
                                type="date"
                                value="<?php
                                echo htmlspecialchars(
                                    $selected_claim['pickup_date']
                                );
                                ?>"
                                readonly
                            >

                        <?php } ?>


                    </div>



                    <!-- PICKUP SUMMARY -->

                    <div class="card summary">

                        <h2>

                            Pickup Summary

                            <em>
                                Draft Booking
                            </em>

                        </h2>


                        <label>
                            LOCATION
                        </label>


                        <?php if ($selected_claim) { ?>

                            <strong>
                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $selected_claim['pickup_address']
                                    )
                                );
                                ?>
                            </strong>

                            <small>
                                <?php
                                echo htmlspecialchars(
                                    $selected_claim['food_name']
                                );
                                ?>
                            </small>


                            <hr>


                            <label>
                                VOLUME
                            </label>

                            <strong>

                                <?php
                                echo htmlspecialchars(
                                    $selected_claim['quantity']
                                );
                                ?>

                                <?php
                                echo htmlspecialchars(
                                    $selected_claim['quantity_unit']
                                );
                                ?>

                            </strong>

                            <small>
                                Donation quantity
                            </small>


                        <?php } else { ?>

                            <strong>
                                Select a donation
                            </strong>

                            <small>
                                The pickup location will appear here.
                            </small>


                            <hr>


                            <label>
                                VOLUME
                            </label>

                            <strong>
                                Select a donation
                            </strong>

                            <small>
                                Donation quantity will appear here.
                            </small>

                        <?php } ?>


                        <button type="submit">
                            Confirm Pickup
                        </button>

                    </div>

                </div>

            </form>

        </main>

    </div>

</div>

</body>

</html>