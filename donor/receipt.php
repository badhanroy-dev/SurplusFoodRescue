<?php

session_start();

include "db.php";


// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../charityGuest/guest/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Auto-generate receipts for any completed claims for this donor
$auto_gen_sql = "
    INSERT IGNORE INTO receipts (receipt_id, claim_id, generated_at)
    SELECT CONCAT('REC-', fc.claim_id), fc.claim_id, NOW()
    FROM food_claims fc
    INNER JOIN food_donations fd ON fc.donation_id = fd.donation_id
    LEFT JOIN pickup_deliveries pd ON fc.claim_id = pd.claim_id
    WHERE fd.donor_id = $user_id
    AND (fc.status = 'Completed' OR pd.delivery_status IN ('Delivered', 'Completed'))
";
mysqli_query($conn, $auto_gen_sql);


// Check receipt ID
if (!isset($_GET['receipt_id']) || empty($_GET['receipt_id'])) {
    die("Receipt ID is missing.");
}

$receipt_id = $_GET['receipt_id'];


// Get receipt information
$sql = "
    SELECT
        r.receipt_id,
        r.generated_at,

        fd.food_name,
        fd.quantity,
        fd.quantity_unit,
        fd.pickup_date,

        donor.full_name AS donor_name,
        donor.email AS donor_email,

        charity.full_name AS charity_name,
        charity.email AS charity_email

    FROM receipts r

    INNER JOIN food_claims fc
        ON r.claim_id = fc.claim_id

    INNER JOIN food_donations fd
        ON fc.donation_id = fd.donation_id

    INNER JOIN users donor
        ON fd.donor_id = donor.user_id

    LEFT JOIN users charity
        ON fc.charity_id = charity.user_id

    WHERE r.receipt_id = ?
    AND fd.donor_id = ?

    LIMIT 1
";


$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database query failed: " . mysqli_error($conn));
}


mysqli_stmt_bind_param(
    $stmt,
    "si",
    $receipt_id,
    $user_id
);


mysqli_stmt_execute($stmt);


$result = mysqli_stmt_get_result($stmt);


$receipt = mysqli_fetch_assoc($result);


// Check if receipt exists and belongs to logged-in donor
if (!$receipt) {
    die("Receipt not found or you do not have permission to view this receipt.");
}


// Format date
$pickup_date = "";

if (!empty($receipt['pickup_date'])) {

    $pickup_date = date(
        "d M, Y",
        strtotime($receipt['pickup_date'])
    );

}


// Format quantity
$quantity = $receipt['quantity'];

if (!empty($receipt['quantity_unit'])) {

    $quantity .= " " . $receipt['quantity_unit'];

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
        FoodRescue - Receipt
    </title>

    <link
        rel="stylesheet"
        href="receipt.css"
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

            <a
                href="mulreceipt.php"
                class="active"
            >
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

    <main class="main">


        <!-- HEADER -->

        <header class="topbar">

            <input
                type="search"
                placeholder="Search..."
            >


            <div class="top-icons">

                <span>
                    🔔 &nbsp; 👤
                </span>

            </div>

        </header>



        <!-- RECEIPT PAGE -->

        <div class="receipt-page">


            <div class="receipt">


                <div class="inside">


                    <h1>
                        Surplus Food
                    </h1>


                    <h4>
                        RESCUE & DONATION NETWORK
                    </h4>


                    <div class="line"></div>



                    <div class="receipt-row">

                        <b>
                            Receipt ID
                        </b>

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $receipt['receipt_id']
                            );
                            ?>
                        </span>

                    </div>



                    <div class="receipt-row">

                        <b>
                            Donate To
                        </b>

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $receipt['charity_name']
                                ?? 'N/A'
                            );
                            ?>
                        </span>

                    </div>



                    <div class="receipt-row">

                        <b>
                            Food Name
                        </b>

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $receipt['food_name']
                            );
                            ?>
                        </span>

                    </div>



                    <div class="receipt-row">

                        <b>
                            Quantity
                        </b>

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $quantity
                            );
                            ?>
                        </span>

                    </div>



                    <div class="receipt-row">

                        <b>
                            Pickup Date
                        </b>

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $pickup_date
                            );
                            ?>
                        </span>

                    </div>



                    <div class="receipt-row">

                        <b>
                            Charity Email
                        </b>

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $receipt['charity_email']
                                ?? 'N/A'
                            );
                            ?>
                        </span>

                    </div>



                    <div class="receipt-row">

                        <b>
                            Donor Name
                        </b>

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $receipt['donor_name']
                            );
                            ?>
                        </span>

                    </div>



                    <div class="receipt-row">

                        <b>
                            Donor Email
                        </b>

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $receipt['donor_email']
                            );
                            ?>
                        </span>

                    </div>



                    <div class="buttons">

                        <button type="button" onclick="downloadReceipt()">
                            Download PDF
                        </button>

                        <button type="button" class="share" onclick="shareReceipt()">
                            Share
                        </button>

                    </div>


                </div>

            </div>

        </div>

    </main>

</div>


<script src="receipt.js"></script>

</body>

</html>


<?php

mysqli_stmt_close($stmt);

?>