<?php

session_start();

include "db.php";


/*
|--------------------------------------------------------------------------
| Check Login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


$user_id = $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| Get Logged-in User
|--------------------------------------------------------------------------
*/

$user_sql = "
    SELECT
        user_id,
        full_name,
        email,
        role
    FROM users
    WHERE user_id = ?
    LIMIT 1
";

$user_stmt = mysqli_prepare($conn, $user_sql);

mysqli_stmt_bind_param(
    $user_stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($user_stmt);

$user_result = mysqli_stmt_get_result($user_stmt);

$user = mysqli_fetch_assoc($user_result);


if (!$user) {
    session_destroy();

    header("Location: login.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| Dashboard Search
|--------------------------------------------------------------------------
*/

$search = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}


/*
|--------------------------------------------------------------------------
| Recent Donations
|--------------------------------------------------------------------------
|
| If search is entered:
| Search is performed against the donor's own donations.
|
*/

if ($search != "") {

    $donation_sql = "
        SELECT
            fd.donation_id,
            fd.food_name,
            fd.quantity,
            fd.quantity_unit,
            fd.expiry_date,
            fd.pickup_date,
            fd.preferred_time,
            fd.status,
            fc.category_name
        FROM food_donations fd
        INNER JOIN food_categories fc
            ON fd.category_id = fc.category_id
        WHERE fd.donor_id = ?
        AND (
            fd.food_name LIKE ?
            OR fc.category_name LIKE ?
            OR fd.status LIKE ?
        )
        ORDER BY fd.created_at DESC
        LIMIT 10
    ";

    $donation_stmt = mysqli_prepare(
        $conn,
        $donation_sql
    );

    $search_value = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $donation_stmt,
        "isss",
        $user_id,
        $search_value,
        $search_value,
        $search_value
    );

} else {

    $donation_sql = "
        SELECT
            fd.donation_id,
            fd.food_name,
            fd.quantity,
            fd.quantity_unit,
            fd.expiry_date,
            fd.pickup_date,
            fd.preferred_time,
            fd.status,
            fc.category_name
        FROM food_donations fd
        INNER JOIN food_categories fc
            ON fd.category_id = fc.category_id
        WHERE fd.donor_id = ?
        ORDER BY fd.created_at DESC
        LIMIT 10
    ";

    $donation_stmt = mysqli_prepare(
        $conn,
        $donation_sql
    );

    mysqli_stmt_bind_param(
        $donation_stmt,
        "i",
        $user_id
    );
}


mysqli_stmt_execute($donation_stmt);

$donation_result = mysqli_stmt_get_result(
    $donation_stmt
);


/*
|--------------------------------------------------------------------------
| Recent Messages
|--------------------------------------------------------------------------
|
| Show messages sent by or received by the logged-in user.
|
*/

$message_sql = "
    SELECT
        m.message_id,
        m.sender_id,
        m.receiver_id,
        m.message_text,
        m.is_read,
        m.sent_at,
        sender.full_name AS sender_name,
        receiver.full_name AS receiver_name
    FROM messages m

    INNER JOIN users sender
        ON m.sender_id = sender.user_id

    INNER JOIN users receiver
        ON m.receiver_id = receiver.user_id

    WHERE m.sender_id = ?
       OR m.receiver_id = ?

    ORDER BY m.sent_at DESC

    LIMIT 4
";

$message_stmt = mysqli_prepare(
    $conn,
    $message_sql
);

mysqli_stmt_bind_param(
    $message_stmt,
    "ii",
    $user_id,
    $user_id
);

mysqli_stmt_execute($message_stmt);

$message_result = mysqli_stmt_get_result(
    $message_stmt
);


/*
|--------------------------------------------------------------------------
| Helper Function
|--------------------------------------------------------------------------
*/

function getTimeText($date_time)
{
    $time = strtotime($date_time);

    if (!$time) {
        return "";
    }

    $difference = time() - $time;

    if ($difference < 60) {
        return "Just now";
    }

    if ($difference < 3600) {
        $minutes = floor($difference / 60);

        return $minutes . " minute" .
            ($minutes != 1 ? "s" : "") .
            " ago";
    }

    if ($difference < 86400) {
        $hours = floor($difference / 3600);

        return $hours . " hour" .
            ($hours != 1 ? "s" : "") .
            " ago";
    }

    if ($difference < 172800) {
        return "Yesterday";
    }

    return date("M d", $time);
}


/*
|--------------------------------------------------------------------------
| Helper Function For Donation Status
|--------------------------------------------------------------------------
*/

function getStatusClass($status)
{
    if ($status == "Available") {
        return "available";
    }

    if ($status == "Claimed") {
        return "reserved";
    }

    if ($status == "Picked Up") {
        return "reserved";
    }

    if ($status == "Completed") {
        return "completed";
    }

    if ($status == "Expired") {
        return "completed";
    }

    if ($status == "Cancelled") {
        return "completed";
    }

    return "completed";
}


/*
|--------------------------------------------------------------------------
| Helper Function For Pickup Date
|--------------------------------------------------------------------------
*/

function getPickupDateText($pickup_date)
{
    $today = date("Y-m-d");

    $tomorrow = date(
        "Y-m-d",
        strtotime("+1 day")
    );

    if ($pickup_date == $today) {
        return "Today";
    }

    if ($pickup_date == $tomorrow) {
        return "Tomorrow";
    }

    return date(
        "M d",
        strtotime($pickup_date)
    );
}

?>

<!DOCTYPE html>

<html>

<head>

    <title>Dashboard - FoodRescue</title>

    <link
        rel="stylesheet"
        href="dashboard.css"
    >

</head>


<body>


<header>


    <div class="logo">

        FoodRescue

    </div>


    <nav>

        <a
            href="dashboard.php"
            class="active"
        >
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

        <a href="multracking.php">
            Pickup Status
        </a>

    </nav>


    <form
        class="search"
        method="GET"
        action="dashboard.php"
    >

        <input
            type="text"
            name="search"
            value="<?php echo htmlspecialchars($search); ?>"
            placeholder="⌕ Search..."
        >

    </form>


    <div class="user">

        🔔

        👤

        <?php
        echo htmlspecialchars($user['full_name']);
        ?>

    </div>


</header>



<main>


    <div class="welcome">


        <div>

            <p class="small">
                WELCOME BACK
            </p>


            <h1>
                Help fight food waste, one donation at a time.
            </h1>


            <p>

                Your donations are helping families and communities
                while keeping good food out of landfills.

            </p>


            <a
                href="post.php"
                class="donate-btn"
            >

                + Donate Food

            </a>


        </div>


        <div class="welcome-icon">

            ♻

        </div>


    </div>



    <div class="section-title">

        <h2>
            Recent Donations
        </h2>


        <a href="history.php">
            View All
        </a>

    </div>



    <div class="donation-grid">


        <?php

        if (mysqli_num_rows($donation_result) > 0) {

            while ($donation = mysqli_fetch_assoc($donation_result)) {

                $status_class = getStatusClass(
                    $donation['status']
                );

                $pickup_text = getPickupDateText(
                    $donation['pickup_date']
                );

        ?>


                <div class="donation-card">


                    <div class="food-info">


                        <h3>

                            <?php
                            echo htmlspecialchars(
                                $donation['food_name']
                            );
                            ?>

                        </h3>


                        <p>

                            <?php
                            echo htmlspecialchars(
                                $donation['quantity']
                            );
                            ?>

                            <?php
                            echo htmlspecialchars(
                                $donation['quantity_unit']
                            );
                            ?>

                            •

                            <?php
                            echo htmlspecialchars(
                                $donation['category_name']
                            );
                            ?>

                        </p>


                        <span
                            class="<?php echo $status_class; ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                $donation['status']
                            );
                            ?>

                        </span>


                    </div>


                    <div class="card-right">


                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $pickup_text
                            );
                            ?>

                        </strong>


                        <?php

                        if (
                            $donation['status'] == "Available"
                        ) {

                        ?>

                            <a
                                href="pickup.php?donation_id=<?php echo $donation['donation_id']; ?>"
                            >
                                Schedule Pickup
                            </a>

                        <?php

                        } elseif (
                            $donation['status'] == "Completed"
                        ) {

                        ?>

                            <a
                                href="mulreceipt.php?donation_id=<?php echo $donation['donation_id']; ?>"
                            >
                                View Receipt
                            </a>

                        <?php

                        } else {

                        ?>

                            <a
                                href="donation_details.php?id=<?php echo $donation['donation_id']; ?>"
                            >
                                View Details
                            </a>

                        <?php

                        }


                        ?>

                    </div>


                </div>


        <?php

            }

        } else {

        ?>


            <div class="donation-card">

                <div class="food-info">

                    <h3>
                        No donations found
                    </h3>

                    <p>

                        <?php

                        if ($search != "") {

                            echo "No donation matched your search.";

                        } else {

                            echo "You have not posted any food donations yet.";

                        }

                        ?>

                    </p>

                </div>

            </div>


        <?php

        }

        ?>


    </div>



    <section class="section-title">

        <h2>
            Messages
        </h2>

        <a href="message.php">
            View All
        </a>

    </section>



    <div class="message-grid">


        <?php

        if (mysqli_num_rows($message_result) > 0) {

            while ($message = mysqli_fetch_assoc($message_result)) {


                if (
                    $message['sender_id'] == $user_id
                ) {

                    $person_name =
                        $message['receiver_name'];

                } else {

                    $person_name =
                        $message['sender_name'];

                }

        ?>


                <div class="message-card">


                    <div class="message-icon">
                        ✉
                    </div>


                    <div>


                        <h3>

                            <?php
                            echo htmlspecialchars(
                                $person_name
                            );
                            ?>

                        </h3>


                        <p>

                            <?php
                            echo htmlspecialchars(
                                $message['message_text']
                            );
                            ?>

                        </p>


                        <span>

                            <?php
                            echo htmlspecialchars(
                                getTimeText(
                                    $message['sent_at']
                                )
                            );
                            ?>

                        </span>


                    </div>


                </div>


        <?php

            }

        } else {

        ?>


            <div class="message-card">


                <div class="message-icon">
                    ✉
                </div>


                <div>

                    <h3>
                        No messages
                    </h3>

                    <p>
                        You do not have any messages yet.
                    </p>

                </div>


            </div>


        <?php

        }

        ?>


    </div>



    <section class="features">


        <div>

            <div class="feature-icon">
                🍽
            </div>

            <h3>
                Rescue Food
            </h3>

            <p>
                Give surplus food another purpose.
            </p>

        </div>


        <div>

            <div class="feature-icon">
                🤝
            </div>

            <h3>
                Help Communities
            </h3>

            <p>
                Connect food with people who need it.
            </p>

        </div>


        <div>

            <div class="feature-icon">
                🌱
            </div>

            <h3>
                Protect The Planet
            </h3>

            <p>
                Reduce food waste and carbon emissions.
            </p>

        </div>


    </section>


</main>



<script src="dashboard.js"></script>


</body>

</html>