<?php

session_start();

include "db.php";


/* =========================================================
   CHECK LOGIN
   ========================================================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: ../charityGuest/guest/login.html");

    exit();

}


$user_id = $_SESSION['user_id'];



/* =========================================================
   TOTAL ITEMS
   ========================================================= */

$total_items = 0;

$sql_total_items = "
    SELECT COALESCE(SUM(fd.quantity), 0) AS total_items

    FROM food_donations fd

    INNER JOIN food_claims fc
        ON fd.donation_id = fc.donation_id

    WHERE fd.donor_id = ?

    AND fd.status = 'Completed'

    AND fc.status = 'Completed'
";


$stmt_total_items = mysqli_prepare(
    $conn,
    $sql_total_items
);


mysqli_stmt_bind_param(
    $stmt_total_items,
    "i",
    $user_id
);


mysqli_stmt_execute(
    $stmt_total_items
);


$result_total_items = mysqli_stmt_get_result(
    $stmt_total_items
);


$row_total_items = mysqli_fetch_assoc(
    $result_total_items
);


$total_items = $row_total_items['total_items'] ?? 0;



/* =========================================================
   NGOS REACHED
   ========================================================= */

$ngos_reached = 0;


$sql_ngos = "
    SELECT COUNT(DISTINCT fc.charity_id) AS ngos_reached

    FROM food_donations fd

    INNER JOIN food_claims fc
        ON fd.donation_id = fc.donation_id

    WHERE fd.donor_id = ?

    AND fd.status = 'Completed'

    AND fc.status = 'Completed'
";


$stmt_ngos = mysqli_prepare(
    $conn,
    $sql_ngos
);


mysqli_stmt_bind_param(
    $stmt_ngos,
    "i",
    $user_id
);


mysqli_stmt_execute(
    $stmt_ngos
);


$result_ngos = mysqli_stmt_get_result(
    $stmt_ngos
);


$row_ngos = mysqli_fetch_assoc(
    $result_ngos
);


$ngos_reached = $row_ngos['ngos_reached'] ?? 0;



/* =========================================================
   COMPLETED CYCLES
   ========================================================= */

$completed_cycles = 0;


$sql_cycles = "
    SELECT COUNT(*) AS completed_cycles

    FROM food_donations fd

    INNER JOIN food_claims fc
        ON fd.donation_id = fc.donation_id

    WHERE fd.donor_id = ?

    AND fd.status = 'Completed'

    AND fc.status = 'Completed'
";


$stmt_cycles = mysqli_prepare(
    $conn,
    $sql_cycles
);


mysqli_stmt_bind_param(
    $stmt_cycles,
    "i",
    $user_id
);


mysqli_stmt_execute(
    $stmt_cycles
);


$result_cycles = mysqli_stmt_get_result(
    $stmt_cycles
);


$row_cycles = mysqli_fetch_assoc(
    $result_cycles
);


$completed_cycles = $row_cycles['completed_cycles'] ?? 0;



/* =========================================================
   MEALS SHARED
   ========================================================= */

$meals_shared = 0;


$sql_meals = "
    SELECT COALESCE(SUM(fd.people_served), 0) AS meals_shared

    FROM food_distributions fd

    INNER JOIN food_claims fc
        ON fd.claim_id = fc.claim_id

    INNER JOIN food_donations don
        ON fc.donation_id = don.donation_id

    WHERE don.donor_id = ?

    AND fc.status = 'Completed'
";


$stmt_meals = mysqli_prepare(
    $conn,
    $sql_meals
);


mysqli_stmt_bind_param(
    $stmt_meals,
    "i",
    $user_id
);


mysqli_stmt_execute(
    $stmt_meals
);


$result_meals = mysqli_stmt_get_result(
    $stmt_meals
);


$row_meals = mysqli_fetch_assoc(
    $result_meals
);


$meals_shared = $row_meals['meals_shared'] ?? 0;



/* =========================================================
   DONATION HISTORY
   ========================================================= */

$sql_history = "
    SELECT
        don.donation_id,
        don.food_name,
        don.quantity,
        don.quantity_unit,
        don.created_at,
        don.status AS donation_status,
        fc_category.category_name,
        charity.full_name AS charity_name,
        claim.claim_id,
        claim.claim_date,
        claim.status AS claim_status,
        pd.delivery_id,
        pd.delivery_status,
        receipt.receipt_id

    FROM food_donations don

    LEFT JOIN food_categories fc_category
        ON don.category_id = fc_category.category_id

    LEFT JOIN food_claims claim
        ON don.donation_id = claim.donation_id

    LEFT JOIN users charity
        ON claim.charity_id = charity.user_id

    LEFT JOIN pickup_deliveries pd
        ON claim.claim_id = pd.claim_id

    LEFT JOIN receipts receipt
        ON claim.claim_id = receipt.claim_id

    WHERE don.donor_id = ?

    ORDER BY COALESCE(claim.claim_date, don.created_at) DESC, don.donation_id DESC
";



$stmt_history = mysqli_prepare(
    $conn,
    $sql_history
);


mysqli_stmt_bind_param(
    $stmt_history,
    "i",
    $user_id
);


mysqli_stmt_execute(
    $stmt_history
);


$history_result = mysqli_stmt_get_result(
    $stmt_history
);



/* =========================================================
   FORMAT QUANTITY
   ========================================================= */

function format_quantity($quantity)
{

    if (floor($quantity) == $quantity) {

        return number_format(
            $quantity,
            0
        );

    }


    return number_format(
        $quantity,
        2
    );

}



/* =========================================================
   FORMAT DATE
   ========================================================= */

function format_history_date($date)
{

    if (empty($date)) {

        return "-";

    }


    return date(
        "M d, Y",
        strtotime($date)
    );

}

?>



<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        FoodRescue - Donation History
    </title>

    <link
        rel="stylesheet"
        href="history.css"
    >

</head>


<body>


<div class="dashboard">


    <!-- SIDEBAR -->

    <aside class="sidebar">


        <div class="logo">

            <h2>
                FoodRescue
            </h2>

            <p>
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


            <a href="impact_summary.php">
                Impact Summary
            </a>


            <a href="mulreceipt.php">
                Receipts
            </a>


            <a href="message.php">
                Messages
            </a>


            <a
                href="history.php"
                class="active"
            >
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


            <a href="#">
                Logout
            </a>


        </div>


    </aside>



    <!-- MAIN -->

    <main class="main">


        <!-- TOP BAR -->

        <header class="topbar">


            <div class="top-icons">

                <span>
                    🔔 &nbsp; 👤
                </span>

            </div>


        </header>



        <!-- CONTENT -->

        <section class="content">


            <h1>
                Donation History
            </h1>



            <p class="sub">

                Review your organization's contributions, track impact milestones,
                and manage tax-deductible receipts in one centralized dashboard.

            </p>



            <!-- EXPORT BUTTON -->

            <button
                class="export"
                type="button"
            >
                Export PDF
            </button>



            <!-- SUMMARY CARDS -->

            <div class="cards">


                <!-- TOTAL ITEMS -->

                <div class="card">

                    <small>
                        TOTAL ITEMS
                    </small>


                    <h2>

                        <?php

                        echo number_format(
                            $total_items
                        );

                        ?>

                    </h2>

                </div>



                <!-- NGOS REACHED -->

                <div class="card">

                    <small>
                        NGOS REACHED
                    </small>


                    <h2>

                        <?php

                        echo number_format(
                            $ngos_reached
                        );

                        ?>

                    </h2>

                </div>



                <!-- COMPLETED CYCLES -->

                <div class="card">

                    <small>
                        COMPLETED CYCLES
                    </small>


                    <h2>

                        <?php

                        echo number_format(
                            $completed_cycles
                        );

                        ?>

                    </h2>

                </div>



                <!-- MEALS SHARED -->

                <div class="card">

                    <small>
                        MEALS SHARED
                    </small>


                    <h2 class="orangeText">


                        <?php

                        if ($meals_shared >= 1000) {

                            echo number_format(
                                $meals_shared / 1000,
                                1
                            );

                            echo "k";

                        } else {

                            echo number_format(
                                $meals_shared
                            );

                        }

                        ?>


                    </h2>

                </div>


            </div>



            <br>

            <br>



            <!-- DONATION HISTORY TABLE -->

            <div class="table">


                <!-- TABLE HEADING -->

                <div class="row heading">


                    <span>
                        FOOD ITEM
                    </span>


                    <span>
                        QUANTITY
                    </span>


                    <span>
                        NGO (RECIPIENT)
                    </span>


                    <span>
                        DATE
                    </span>


                    <span>
                        STATUS
                    </span>


                    <span>
                        ACTIONS
                    </span>


                </div>



                <?php if (mysqli_num_rows($history_result) > 0): ?>


                    <?php while ($history = mysqli_fetch_assoc($history_result)):
                        // Determine status text & CSS class dynamically
                        $del_status = $history['delivery_status'] ?? '';
                        $c_status = $history['claim_status'] ?? '';
                        $d_status = $history['donation_status'] ?? '';

                        // 1. COMPLETED Check (highest priority)
                        if ($d_status === 'Completed' || $c_status === 'Completed' || $del_status === 'Completed') {
                            $status_text = 'COMPLETED';
                            $status_class = 'completed';
                        }
                        // 2. CANCELLED / EXPIRED Check
                        elseif ($d_status === 'Cancelled' || $c_status === 'Cancelled' || $del_status === 'Cancelled' || $d_status === 'Expired') {
                            $status_text = ($d_status === 'Expired') ? 'EXPIRED' : 'CANCELLED';
                            $status_class = ($d_status === 'Expired') ? 'expired' : 'cancelled';
                        }
                        // 3. IN TRANSIT / PICKED UP / DELIVERED Check
                        elseif ($del_status === 'In Transit' || $del_status === 'Picked Up' || $del_status === 'Delivered' || $c_status === 'In Transit' || $d_status === 'Picked Up') {
                            if ($del_status === 'Picked Up' || $d_status === 'Picked Up') {
                                $status_text = 'PICKED UP';
                                $status_class = 'pickup';
                            } elseif ($del_status === 'In Transit' || $c_status === 'In Transit') {
                                $status_text = 'IN TRANSIT';
                                $status_class = 'transit';
                            } else {
                                $status_text = 'ON THE WAY';
                                $status_class = 'way';
                            }
                        }
                        // 4. RIDER ASSIGNED Check
                        elseif ($del_status === 'Assigned' || $del_status === 'Accepted') {
                            $status_text = 'RIDER ASSIGNED';
                            $status_class = 'assigned';
                        }
                        // 5. CLAIMED / SCHEDULED Check
                        elseif (!empty($history['claim_id']) || $c_status === 'Scheduled' || $d_status === 'Claimed' || $c_status === 'Claimed') {
                            if ($c_status === 'Scheduled') {
                                $status_text = 'SCHEDULED';
                                $status_class = 'scheduled';
                            } else {
                                $status_text = 'CLAIMED';
                                $status_class = 'claimed';
                            }
                        }
                        // 6. DEFAULT: AVAILABLE
                        else {
                            $status_text = 'AVAILABLE';
                            $status_class = 'available';
                        }

                        $display_date = !empty($history['claim_date']) ? $history['claim_date'] : ($history['created_at'] ?? '');
                    ?>


                        <!-- HISTORY ROW -->

                        <div class="row">


                            <!-- FOOD ITEM -->

                            <b>


                                <?php

                                echo htmlspecialchars(
                                    $history['food_name'] ?? ''
                                );

                                ?>


                                <small>


                                    <?php

                                    echo htmlspecialchars(
                                        $history['category_name'] ?? 'General'
                                    );

                                    ?>


                                </small>


                            </b>



                            <!-- QUANTITY -->

                            <span>


                                <?php

                                echo format_quantity(
                                    $history['quantity']
                                );

                                ?>


                                <?php

                                echo htmlspecialchars(
                                    $history['quantity_unit'] ?? ''
                                );

                                ?>


                            </span>



                            <!-- NGO -->

                            <span>


                                <?php if (!empty($history['charity_name'])): ?>
                                    <?php echo htmlspecialchars($history['charity_name']); ?>
                                <?php else: ?>
                                    <span style="color: #999; font-style: italic;">Not Claimed Yet</span>
                                <?php endif; ?>


                            </span>



                            <!-- DATE -->

                            <span>


                                <?php

                                echo format_history_date(
                                    $display_date
                                );

                                ?>


                            </span>



                            <!-- STATUS -->

                            <span class="complete <?php echo htmlspecialchars($status_class); ?>">


                                <?php

                                echo htmlspecialchars($status_text);

                                ?>


                            </span>



                            <!-- ACTION -->

                            <span>


                                <?php if (!empty($history['receipt_id'])): ?>


                                    <a
                                        href="mulreceipt.php?receipt_id=<?php echo urlencode($history['receipt_id']); ?>"
                                        class="download-link"
                                    >
                                        Download
                                    </a>


                                <?php elseif (!empty($history['delivery_id'])): ?>


                                    <a
                                        href="tracking.php?delivery_id=<?php echo urlencode($history['delivery_id']); ?>"
                                        class="download-link"
                                    >
                                        Track
                                    </a>


                                <?php elseif (!empty($history['claim_id'])): ?>


                                    <a
                                        href="tracking.php?claim_id=<?php echo urlencode($history['claim_id']); ?>"
                                        class="download-link"
                                    >
                                        Track
                                    </a>


                                <?php else: ?>


                                    <span class="no-receipt">
                                        No Receipt
                                    </span>


                                <?php endif; ?>


                            </span>


                        </div>


                    <?php endwhile; ?>


                <?php else: ?>


                    <!-- NO HISTORY -->

                    <div class="empty-history">

                        No donation history found.

                    </div>


                <?php endif; ?>


            </div>


        </section>


    </main>


</div>



<!-- JAVASCRIPT -->

<script src="history.js"></script>


</body>

</html>