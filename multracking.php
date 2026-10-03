<?php

session_start();

include "db.php";


// ==================================================
// CHECK LOGIN
// ==================================================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


// ==================================================
// GET DONATION TRACKING DATA
// ==================================================

$sql = "
    SELECT
        fd.donation_id,
        fd.food_name,
        fd.quantity,
        fd.quantity_unit,
        fd.pickup_date,
        fd.status AS donation_status,

        fc.claim_id,

        charity.full_name AS charity_name,

        pd.delivery_id,
        pd.delivery_status

    FROM food_donations fd

    LEFT JOIN food_claims fc
        ON fd.donation_id = fc.donation_id

    LEFT JOIN users charity
        ON fc.charity_id = charity.user_id

    LEFT JOIN pickup_deliveries pd
        ON fc.claim_id = pd.claim_id

    WHERE fd.donor_id = ?

    ORDER BY fd.created_at DESC
";


$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>FoodRescue - Pickup Status</title>

    <link
        rel="stylesheet"
        href="multracking.css"
    >

</head>


<body>


<div class="dashboard">


    <!-- ==========================================
         SIDEBAR
    =========================================== -->

    <aside class="sidebar">


        <div class="logo">

            <h2>FoodRescue</h2>

            <p>Donor Portal</p>

        </div>


        <nav class="menu">


            <a href="dashboard.php">
                Dashboard
            </a>


            <a href="post.php">
                Post Food
            </a>


            <a href="shedule.php">
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


            <a
                href="multracking.php"
                class="active"
            >
                Pickup Status
            </a>


        </nav>


        <div class="bottom-menu">


            <a href="profile.php">
                Settings
            </a>


            <a href="logout.php">
                Logout
            </a>


        </div>


    </aside>



    <!-- ==========================================
         MAIN
    =========================================== -->

    <main class="main">


        <!-- ======================================
             TOP BAR
        ======================================= -->

        <header class="topbar">


            <div class="top-icons">

                <span>
                    🔔 &nbsp; 👤
                </span>

            </div>


        </header>



        <!-- ======================================
             TRACKING PAGE
        ======================================= -->

        <div class="tracking-page">


            <h1>
                Pickup Status
            </h1>


            <p class="sub">
                Track the status of your food donations
            </p>



            <!-- ==================================
                 DONATION LIST
            =================================== -->

            <div class="donation-list">


                <?php


                if (mysqli_num_rows($result) > 0) {


                    while ($row = mysqli_fetch_assoc($result)) {


                        // ----------------------------------
                        // DEFAULT STATUS
                        // ----------------------------------

                        $status_text = "Donation Posted";

                        $status_class = "posted";

                        $tracking_url = "";



                        // ----------------------------------
                        // DELIVERY EXISTS
                        // ----------------------------------

                        if (!empty($row['delivery_id'])) {


                            $delivery_status =
                                $row['delivery_status'];



                            // COMPLETED

                            if (
                                $delivery_status == "Completed"
                            ) {

                                $status_text = "Completed";

                                $status_class = "completed";

                            }



                            // ON THE WAY

                            elseif (
                                $delivery_status == "Delivered" ||
                                $delivery_status == "In Transit" ||
                                $delivery_status == "Picked Up"
                            ) {

                                $status_text = "On The Way";

                                $status_class = "way";

                            }



                            // RIDER ASSIGNED

                            elseif (
                                $delivery_status == "Assigned" ||
                                $delivery_status == "Accepted"
                            ) {

                                $status_text = "Rider Assigned";

                                $status_class = "assigned";

                            }



                            // DELIVERY AVAILABLE

                            elseif (
                                $delivery_status == "Available"
                            ) {

                                $status_text = "Donation Posted";

                                $status_class = "posted";

                            }



                            // CANCELLED

                            elseif (
                                $delivery_status == "Cancelled"
                            ) {

                                $status_text = "Cancelled";

                                $status_class = "posted";

                            }



                            $tracking_url =
                                "tracking.php?delivery_id=" .
                                urlencode(
                                    $row['delivery_id']
                                );

                        }



                        // ----------------------------------
                        // CLAIMED BUT NO DELIVERY
                        // ----------------------------------

                        elseif (!empty($row['claim_id'])) {


                            $status_text =
                                "Pickup Pending";

                            $status_class =
                                "posted";


                            $tracking_url =
                                "tracking.php?claim_id=" .
                                urlencode(
                                    $row['claim_id']
                                );

                        }



                        // ----------------------------------
                        // NOT CLAIMED
                        // ----------------------------------

                        else {


                            $status_text =
                                "Donation Posted";

                            $status_class =
                                "posted";


                        }


                ?>


                        <!-- ==================================
                             DONATION CARD
                        =================================== -->

                        <div class="donation-card">


                            <!-- ==============================
                                 DONATION INFORMATION
                            =============================== -->

                            <div class="donation-info">


                                <h2>

                                    <?php

                                    echo htmlspecialchars(
                                        $row['food_name']
                                    );

                                    ?>

                                </h2>



                                <p>

                                    <b>Donation ID:</b>

                                    <?php

                                    echo htmlspecialchars(
                                        $row['donation_id']
                                    );

                                    ?>

                                </p>



                                <p>

                                    <b>Charity:</b>

                                    <?php


                                    if (
                                        !empty(
                                            $row['charity_name']
                                        )
                                    ) {

                                        echo htmlspecialchars(
                                            $row['charity_name']
                                        );

                                    } else {

                                        echo "Not Claimed Yet";

                                    }


                                    ?>

                                </p>



                                <!-- ==========================
                                     DETAILS
                                =========================== -->

                                <div class="details">


                                    <!-- QUANTITY -->

                                    <span>

                                        <b>QUANTITY</b>

                                        <?php

                                        echo htmlspecialchars(
                                            $row['quantity']
                                        );

                                        ?>

                                        <?php

                                        echo " " .
                                            htmlspecialchars(
                                                $row['quantity_unit']
                                            );

                                        ?>

                                    </span>



                                    <!-- PICKUP DATE -->

                                    <span>

                                        <b>PICKUP DATE</b>

                                        <?php


                                        if (
                                            !empty(
                                                $row['pickup_date']
                                            )
                                        ) {

                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $row['pickup_date']
                                                )
                                            );

                                        } else {

                                            echo "Not Scheduled";

                                        }


                                        ?>

                                    </span>



                                    <!-- STATUS -->

                                    <span>

                                        <b>STATUS</b>

                                        <em
                                            class="<?php
                                                echo $status_class;
                                            ?>"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $status_text
                                            );

                                            ?>

                                        </em>

                                    </span>


                                </div>


                            </div>



                            <!-- ==============================
                                 TRACK BUTTON
                            =============================== -->

                            <?php

                            if (!empty($tracking_url)) {

                            ?>

                                <a
                                    href="<?php
                                        echo htmlspecialchars(
                                            $tracking_url
                                        );
                                    ?>"
                                    class="track-btn"
                                >
                                    View Tracking
                                </a>

                            <?php

                            } else {

                            ?>

                                <span class="track-btn">
                                    Awaiting Claim
                                </span>

                            <?php

                            }

                            ?>


                        </div>


                <?php

                    }


                } else {

                ?>


                    <!-- ==================================
                         EMPTY STATE
                    =================================== -->

                    <div class="donation-card">


                        <div class="donation-info">


                            <h2>
                                No Donations Found
                            </h2>


                            <p>
                                You have not posted any food
                                donations yet.
                            </p>


                        </div>


                    </div>


                <?php

                }


                ?>


            </div>


        </div>


    </main>


</div>


</body>

</html>


<?php

mysqli_stmt_close($stmt);

mysqli_close($conn);

?>