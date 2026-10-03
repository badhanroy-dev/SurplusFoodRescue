<?php

session_start();

include "db.php";


/* =========================================================
   CHECK LOGIN
   ========================================================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");

    exit();

}


$user_id = $_SESSION['user_id'];



/* =========================================================
   CURRENT YEAR
   ========================================================= */

$current_year = date("Y");



/* =========================================================
   MEALS RESCUED
   ========================================================= */

/*
    People served through completed distributions
    connected to the logged-in donor's donations.
*/

$meals_rescued = 0;


$sql_meals = "
    SELECT COALESCE(SUM(dist.people_served), 0) AS meals_rescued

    FROM food_distributions dist

    INNER JOIN food_claims claim
        ON dist.claim_id = claim.claim_id

    INNER JOIN food_donations donation
        ON claim.donation_id = donation.donation_id

    WHERE donation.donor_id = ?

    AND claim.status = 'Completed'

    AND YEAR(dist.distributed_at) = ?
";


$stmt_meals = mysqli_prepare(
    $conn,
    $sql_meals
);


mysqli_stmt_bind_param(
    $stmt_meals,
    "ii",
    $user_id,
    $current_year
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


$meals_rescued = $row_meals['meals_rescued'] ?? 0;



/* =========================================================
   DONATED KG
   ========================================================= */

/*
    Calculates donated quantity where the quantity unit
    is kilograms.

    Other units are not converted because the database
    does not contain a conversion table.
*/

$donated_kg = 0;


$sql_kg = "
    SELECT COALESCE(SUM(quantity), 0) AS donated_kg

    FROM food_donations

    WHERE donor_id = ?

    AND YEAR(created_at) = ?

    AND LOWER(quantity_unit) IN ('kg', 'kgs', 'kilogram', 'kilograms')
";


$stmt_kg = mysqli_prepare(
    $conn,
    $sql_kg
);


mysqli_stmt_bind_param(
    $stmt_kg,
    "ii",
    $user_id,
    $current_year
);


mysqli_stmt_execute(
    $stmt_kg
);


$result_kg = mysqli_stmt_get_result(
    $stmt_kg
);


$row_kg = mysqli_fetch_assoc(
    $result_kg
);


$donated_kg = $row_kg['donated_kg'] ?? 0;



/* =========================================================
   PICKUPS COMPLETED
   ========================================================= */

/*
    Counts completed claims belonging to the donor's
    donations during the current year.
*/

$pickups_completed = 0;


$sql_pickups = "
    SELECT COUNT(*) AS pickups_completed

    FROM food_claims claim

    INNER JOIN food_donations donation
        ON claim.donation_id = donation.donation_id

    WHERE donation.donor_id = ?

    AND claim.status = 'Completed'

    AND YEAR(claim.claim_date) = ?
";


$stmt_pickups = mysqli_prepare(
    $conn,
    $sql_pickups
);


mysqli_stmt_bind_param(
    $stmt_pickups,
    "ii",
    $user_id,
    $current_year
);


mysqli_stmt_execute(
    $stmt_pickups
);


$result_pickups = mysqli_stmt_get_result(
    $stmt_pickups
);


$row_pickups = mysqli_fetch_assoc(
    $result_pickups
);


$pickups_completed = $row_pickups['pickups_completed'] ?? 0;



/* =========================================================
   PERSON HELPED
   ========================================================= */

/*
    Uses people_served from completed distributions.
*/

$people_helped = 0;


$sql_people = "
    SELECT COALESCE(SUM(dist.people_served), 0) AS people_helped

    FROM food_distributions dist

    INNER JOIN food_claims claim
        ON dist.claim_id = claim.claim_id

    INNER JOIN food_donations donation
        ON claim.donation_id = donation.donation_id

    WHERE donation.donor_id = ?

    AND claim.status = 'Completed'

    AND YEAR(dist.distributed_at) = ?
";


$stmt_people = mysqli_prepare(
    $conn,
    $sql_people
);


mysqli_stmt_bind_param(
    $stmt_people,
    "ii",
    $user_id,
    $current_year
);


mysqli_stmt_execute(
    $stmt_people
);


$result_people = mysqli_stmt_get_result(
    $stmt_people
);


$row_people = mysqli_fetch_assoc(
    $result_people
);


$people_helped = $row_people['people_helped'] ?? 0;



/* =========================================================
   MONTHLY DONATION SUMMARY
   ========================================================= */

$sql_monthly = "
    SELECT

        MONTH(created_at) AS month_number,

        COUNT(*) AS donation_count,

        COALESCE(SUM(quantity), 0) AS total_quantity,

        MAX(quantity_unit) AS quantity_unit

    FROM food_donations

    WHERE donor_id = ?

    AND YEAR(created_at) = ?

    GROUP BY MONTH(created_at)

    ORDER BY MONTH(created_at)
";


$stmt_monthly = mysqli_prepare(
    $conn,
    $sql_monthly
);


mysqli_stmt_bind_param(
    $stmt_monthly,
    "ii",
    $user_id,
    $current_year
);


mysqli_stmt_execute(
    $stmt_monthly
);


$monthly_result = mysqli_stmt_get_result(
    $stmt_monthly );


$monthly_data = [];

while ($monthly = mysqli_fetch_assoc($monthly_result)) {

    $monthly_data[
        $monthly['month_number']
    ] = $monthly;

}



/* =========================================================
   FOOD CATEGORIES
   ========================================================= */

/*
    Category percentages are calculated from the total
    quantity donated by the logged-in donor.

    Since different quantity units cannot safely be
    converted into each other, percentages are calculated
    only across donations having the same unit as the
    largest category quantity.
*/

$category_result = null;

$sql_category = "
    SELECT

        category.category_name,

        COALESCE(SUM(donation.quantity), 0) AS category_quantity

    FROM food_donations donation

    INNER JOIN food_categories category
        ON donation.category_id = category.category_id

    WHERE donation.donor_id = ?

    AND YEAR(donation.created_at) = ?

    GROUP BY
        category.category_id,
        category.category_name

    ORDER BY category_quantity DESC
";


$stmt_category = mysqli_prepare(
    $conn,
    $sql_category
);


mysqli_stmt_bind_param(
    $stmt_category,
    "ii",
    $user_id,
    $current_year
);


mysqli_stmt_execute(
    $stmt_category
);


$category_result = mysqli_stmt_get_result(
    $stmt_category );


$categories = [];

while ($category = mysqli_fetch_assoc($category_result)) {

    $categories[] = $category;

}


$total_category_quantity = 0;

foreach ($categories as $category) {

    $total_category_quantity +=
        (float) $category['category_quantity'];

}



/* =========================================================
   TOTAL FOOD DONATIONS
   ========================================================= */

$total_food_donations = 0;


$sql_total_donations = "
    SELECT COUNT(*) AS total_food_donations

    FROM food_donations

    WHERE donor_id = ?

    AND YEAR(created_at) = ?
";


$stmt_total_donations = mysqli_prepare(
    $conn,
    $sql_total_donations
);


mysqli_stmt_bind_param(
    $stmt_total_donations,
    "ii",
    $user_id,
    $current_year
);


mysqli_stmt_execute(
    $stmt_total_donations
);


$result_total_donations = mysqli_stmt_get_result(
    $stmt_total_donations
);


$row_total_donations = mysqli_fetch_assoc(
    $result_total_donations
);


$total_food_donations =
    $row_total_donations['total_food_donations'] ?? 0;



/* =========================================================
   WEEKLY TREND
   ========================================================= */

/*
    Counts this donor's donations for each weekday
    during the current week.
*/

$week_data = [
    "Monday" => 0,
    "Tuesday" => 0,
    "Wednesday" => 0,
    "Thursday" => 0,
    "Friday" => 0,
    "Saturday" => 0,
    "Sunday" => 0
];


$sql_week = "
    SELECT

        DAYNAME(created_at) AS day_name,

        COUNT(*) AS donation_count

    FROM food_donations

    WHERE donor_id = ?

    AND YEARWEEK(created_at, 1) =
        YEARWEEK(CURDATE(), 1)

    GROUP BY DAYNAME(created_at)
";


$stmt_week = mysqli_prepare(
    $conn,
    $sql_week
);


mysqli_stmt_bind_param(
    $stmt_week,
    "i",
    $user_id
);


mysqli_stmt_execute(
    $stmt_week
);


$week_result = mysqli_stmt_get_result(
    $stmt_week
);


while ($week = mysqli_fetch_assoc($week_result)) {

    $day_name = $week['day_name'];

    if (isset($week_data[$day_name])) {

        $week_data[$day_name] =
            (int) $week['donation_count'];

    }

}



/* =========================================================
   PEAK DONATION DAY
   ========================================================= */

$peak_day = "-";

$peak_count = 0;


foreach ($week_data as $day => $count) {

    if ($count > $peak_count) {

        $peak_count = $count;

        $peak_day = $day;

    }

}



/* =========================================================
   TOTAL DONORS THIS MONTH
   ========================================================= */

/*
    This is a system-wide statistic.

    It counts active users who have made at least one
    food donation during the current month.
*/

$total_donors_month = 0;


$sql_donors_month = "
    SELECT COUNT(DISTINCT donor_id) AS total_donors

    FROM food_donations

    WHERE YEAR(created_at) = YEAR(CURDATE())

    AND MONTH(created_at) = MONTH(CURDATE())
";


$result_donors_month = mysqli_query(
    $conn,
    $sql_donors_month
);


if ($result_donors_month) {

    $row_donors_month =
        mysqli_fetch_assoc($result_donors_month);

    $total_donors_month =
        $row_donors_month['total_donors'] ?? 0;

}



/* =========================================================
   NEW DONORS THIS MONTH
   ========================================================= */

/*
    A donor is considered new when their first donation
    happened during the current month.
*/

$new_donors = 0;


$sql_new_donors = "
    SELECT COUNT(*) AS new_donors

    FROM (

        SELECT
            donor_id,
            MIN(created_at) AS first_donation

        FROM food_donations

        GROUP BY donor_id

    ) AS first_donations

    WHERE YEAR(first_donation) = YEAR(CURDATE())

    AND MONTH(first_donation) = MONTH(CURDATE())
";


$result_new_donors = mysqli_query(
    $conn,
    $sql_new_donors
);


if ($result_new_donors) {

    $row_new_donors =
        mysqli_fetch_assoc($result_new_donors);

    $new_donors =
        $row_new_donors['new_donors'] ?? 0;

}



/* =========================================================
   RETURNING DONORS
   ========================================================= */

$returning_donors =
    max(
        0,
        $total_donors_month - $new_donors
    );



/* =========================================================
   CURRENT MONTH DONATIONS
   ========================================================= */

$current_month_donations = 0;


$sql_current_month = "
    SELECT COUNT(*) AS donation_count

    FROM food_donations

    WHERE YEAR(created_at) = YEAR(CURDATE())

    AND MONTH(created_at) = MONTH(CURDATE())
";


$result_current_month = mysqli_query(
    $conn,
    $sql_current_month
);


if ($result_current_month) {

    $row_current_month =
        mysqli_fetch_assoc($result_current_month);

    $current_month_donations =
        $row_current_month['donation_count'] ?? 0;

}



/* =========================================================
   MONTH NAMES
   ========================================================= */

$month_names = [
    1 => "January",
    2 => "February",
    3 => "March",
    4 => "April",
    5 => "May",
    6 => "June",
    7 => "July",
    8 => "August",
    9 => "September",
    10 => "October",
    11 => "November",
    12 => "December"
];

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
        FoodRescue - Impact Summary
    </title>

    <link
        rel="stylesheet"
        href="impact_summary.css"
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


            <a href="shedule.php">
                Pickup Requests
            </a>


            <a
                href="impact_summary.php"
                class="active"
            >
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



        <!-- IMPACT PAGE -->

        <div class="impact-page">


            <!-- TITLE -->

            <div class="title">


                <div>

                    <h1>
                        Impact Summary
                    </h1>


                    <p>
                        Track and analyze your cumulative food donation footprint.
                    </p>

                </div>



                <button
                    class="date"
                    type="button"
                >

                    ▣ Year to Date
                    (<?php echo $current_year; ?>)

                </button>


            </div>



            <!-- TOP 4 STATS -->

            <div class="stats">


                <!-- MEALS RESCUED -->

                <div class="stat">

                    <small>
                        MEALS RESCUED
                    </small>


                    <b>

                        <?php

                        echo number_format(
                            $meals_rescued
                        );

                        ?>

                    </b>

                </div>



                <!-- DONATED KG -->

                <div class="stat">

                    <small>
                        DONATED (KG)
                    </small>


                    <b>

                        <?php

                        echo number_format(
                            $donated_kg,
                            2
                        );

                        ?>

                    </b>

                </div>



                <!-- PICKUPS COMPLETED -->

                <div class="stat">

                    <small>
                        PICKUPS COMPLETED
                    </small>


                    <b>

                        <?php

                        echo number_format(
                            $pickups_completed
                        );

                        ?>

                    </b>

                </div>



                <!-- PERSON HELPED -->

                <div class="stat">

                    <small>
                        PERSON HELPED
                    </small>


                    <b>

                        <?php

                        echo number_format(
                            $people_helped
                        );

                        ?>

                    </b>

                </div>


            </div>



            <!-- DATA SECTION -->

            <div class="data-section">


                <!-- DONATIONS OVER TIME -->

                <div class="card monthly">


                    <h2>
                        Donations Over Time
                    </h2>


                    <small>
                        Monthly donation summary
                    </small>



                    <div class="data-row header-row">

                        <b>
                            Month
                        </b>

                        <b>
                            Donations
                        </b>

                        <b>
                            Quantity
                        </b>

                    </div>



                    <?php for ($month = 1; $month <= 12; $month++): ?>


                        <div class="data-row">


                            <span>

                                <?php

                                echo $month_names[$month];

                                ?>

                            </span>


                            <span>


                                <?php

                                if (isset($monthly_data[$month])) {

                                    echo number_format(
                                        $monthly_data[$month]['donation_count']
                                    );

                                } else {

                                    echo "0";

                                }

                                ?>


                            </span>


                            <span>


                                <?php

                                if (isset($monthly_data[$month])) {

                                    echo number_format(
                                        $monthly_data[$month]['total_quantity'],
                                        2
                                    );

                                    echo " ";

                                    echo htmlspecialchars(
                                        $monthly_data[$month]['quantity_unit']
                                    );

                                } else {

                                    echo "0";

                                }

                                ?>


                            </span>


                        </div>


                    <?php endfor; ?>


                </div>



                <!-- FOOD CATEGORIES -->

                <div class="card categories">


                    <h2>
                        Food Categories
                    </h2>


                    <small>
                        Donated food by category
                    </small>



                    <?php if (count($categories) > 0): ?>


                        <?php

                        $display_categories =
                            array_slice(
                                $categories,
                                0,
                                4
                            );

                        ?>


                        <?php foreach ($display_categories as $category): ?>


                            <?php

                            $percentage = 0;

                            if (
                                $total_category_quantity > 0
                            ) {

                                $percentage =
                                    (
                                        $category['category_quantity']
                                        /
                                        $total_category_quantity
                                    ) * 100;

                            }

                            ?>


                            <div class="category-row">


                                <span>

                                    <?php

                                    echo htmlspecialchars(
                                        $category['category_name']
                                    );

                                    ?>

                                </span>


                                <b>

                                    <?php

                                    echo number_format(
                                        $percentage,
                                        1
                                    );

                                    ?>%

                                </b>


                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div class="category-row">

                            <span>
                                No donation data
                            </span>

                            <b>
                                0%
                            </b>

                        </div>


                    <?php endif; ?>



                    <div class="category-total">


                        <span>
                            Total Food Donations
                        </span>


                        <b>

                            <?php

                            echo number_format(
                                $total_food_donations
                            );

                            ?>

                        </b>


                    </div>


                </div>


            </div>



            <!-- BOTTOM -->

            <div class="bottom">


                <!-- WEEKLY TREND -->

                <div class="card trend">


                    <h2>
                        Weekly Trend
                    </h2>


                    <small>
                        Weekly pickup and donation information
                    </small>



                    <div class="week-row">

                        <span>
                            Monday
                        </span>

                        <b>
                            <?php echo $week_data['Monday']; ?>
                            donations
                        </b>

                    </div>



                    <div class="week-row">

                        <span>
                            Tuesday
                        </span>

                        <b>
                            <?php echo $week_data['Tuesday']; ?>
                            donations
                        </b>

                    </div>



                    <div class="week-row">

                        <span>
                            Wednesday
                        </span>

                        <b>
                            <?php echo $week_data['Wednesday']; ?>
                            donations
                        </b>

                    </div>



                    <div class="week-row">

                        <span>
                            Thursday
                        </span>

                        <b>
                            <?php echo $week_data['Thursday']; ?>
                            donations
                        </b>

                    </div>



                    <div class="week-row">

                        <span>
                            Friday
                        </span>

                        <b>
                            <?php echo $week_data['Friday']; ?>
                            donations
                        </b>

                    </div>



                    <p class="peak">

                        Peak donation day:

                        <strong>
                            <?php echo htmlspecialchars($peak_day); ?>
                        </strong>

                    </p>


                </div>



                <!-- TOTAL DONORS -->

                <div class="card donors">


                    <h2>
                        Total Donors This Month
                    </h2>


                    <small>
                        Donors who contributed food this month
                    </small>



                    <div class="donor-number">

                        <?php

                        echo number_format(
                            $total_donors_month
                        );

                        ?>

                    </div>



                    <div class="donor-info">

                        <span>
                            New Donors
                        </span>

                        <b>

                            <?php

                            echo number_format(
                                $new_donors
                            );

                            ?>

                        </b>

                    </div>



                    <div class="donor-info">

                        <span>
                            Returning Donors
                        </span>

                        <b>

                            <?php

                            echo number_format(
                                $returning_donors
                            );

                            ?>

                        </b>

                    </div>



                    <div class="donor-info">

                        <span>
                            Total Donations
                        </span>

                        <b>

                            <?php

                            echo number_format(
                                $current_month_donations
                            );

                            ?>

                        </b>

                    </div>


                </div>


            </div>


        </div>


    </main>


</div>



<script src="impact_summary.js"></script>


</body>

</html>