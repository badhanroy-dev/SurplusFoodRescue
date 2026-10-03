<?php

session_start();

include "db.php";


// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


// Get all receipts belonging to this donor
$sql = "
    SELECT
        r.receipt_id,
        r.generated_at,
        fc.claim_id,
        fd.donation_id,
        fd.food_name,
        fd.quantity,
        fd.quantity_unit,
        fd.pickup_date,
        u.full_name AS charity_name
    FROM receipts r

    INNER JOIN food_claims fc
        ON r.claim_id = fc.claim_id

    INNER JOIN food_donations fd
        ON fc.donation_id = fd.donation_id

    LEFT JOIN users u
        ON fc.charity_id = u.user_id

    WHERE fd.donor_id = ?

    ORDER BY r.generated_at DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database query failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $user_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>FoodRescue - Receipts</title>

    <link rel="stylesheet" href="mulreceipt.css">

</head>

<body>

<div class="dashboard">


    <!-- SIDEBAR -->

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

            <a href="mulreceipt.php" class="active">
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


        <!-- TOP BAR -->

        <header class="topbar">

            <div class="top-icons">

                <span>
                    🔔 &nbsp; 👤
                </span>

            </div>

        </header>



        <!-- RECEIPTS -->

        <div class="receipt-page">

            <h1>
                Receipts
            </h1>

            <p class="sub">
                View and download your donation receipts.
            </p>



            <div class="receipt-list">


                <?php if (mysqli_num_rows($result) > 0): ?>


                    <?php while ($receipt = mysqli_fetch_assoc($result)): ?>


                        <div class="receipt-card">


                            <div class="receipt-info">

                                <div>

                                    <b>
                                        Receipt ID:
                                        <?php echo htmlspecialchars($receipt['receipt_id']); ?>
                                    </b>

                                    <small>
                                        Date:
                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime($receipt['generated_at'])
                                        );
                                        ?>
                                    </small>

                                </div>

                            </div>



                            <div class="receipt-buttons">


                                <a
                                    href="mulreceipt.php?receipt_id=<?php echo urlencode($receipt['receipt_id']); ?>"
                                    class="view"
                                >
                                    View
                                </a>


                                <a
                                    href="mulreceipt.php?receipt_id=<?php echo urlencode($receipt['receipt_id']); ?>&download=1"
                                    class="download"
                                >
                                    Download
                                </a>


                            </div>


                        </div>


                    <?php endwhile; ?>


                <?php else: ?>


                    <div class="empty-receipts">

                        <h3>
                            No receipts found
                        </h3>

                        <p>
                            Your completed donation receipts will appear here.
                        </p>

                    </div>


                <?php endif; ?>


            </div>

        </div>

    </main>

</div>

</body>

</html>

<?php

mysqli_stmt_close($stmt);

?>