<?php

session_start();

include "db.php";


// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../charityGuest/guest/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];


// Variables
$success_message = "";
$error_message = "";


// Get food categories from database
$category_sql = "
    SELECT
        category_id,
        category_name
    FROM food_categories
    WHERE status = 'Active'
    ORDER BY category_name ASC
";

$category_result = mysqli_query($conn, $category_sql);

if (!$category_result) {
    die("Category query failed: " . mysqli_error($conn));
}


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $food_name = trim($_POST['food_name'] ?? "");
    $category_id = $_POST['category_id'] ?? "";
    $quantity = $_POST['quantity'] ?? "";
    $quantity_unit = $_POST['quantity_unit'] ?? "";
    $expiry_date = $_POST['expiry_date'] ?? "";
    $description = trim($_POST['description'] ?? "");
    $food_safety_info = trim($_POST['food_safety_info'] ?? "");
    $pickup_date = $_POST['pickup_date'] ?? "";
    $preferred_time = $_POST['preferred_time'] ?? "";
    $pickup_address = trim($_POST['pickup_address'] ?? "");

    $image_path = "";


    // Validate required fields
    if (
        $food_name === "" ||
        $category_id === "" ||
        $quantity === "" ||
        $quantity_unit === "" ||
        $expiry_date === "" ||
        $pickup_date === "" ||
        $preferred_time === "" ||
        $pickup_address === ""
    ) {

        $error_message = "Please fill in all required fields.";

    } elseif (!is_numeric($quantity) || $quantity <= 0) {

        $error_message = "Quantity must be greater than 0.";

    } elseif (!in_array($quantity_unit, ["Box", "Unit"], true)) {

        $error_message = "Please select Box or Unit.";

    } else {


        // Check selected category
        $category_check_sql = "
            SELECT category_id
            FROM food_categories
            WHERE category_id = ?
            AND status = 'Active'
            LIMIT 1
        ";

        $category_check_stmt = mysqli_prepare(
            $conn,
            $category_check_sql
        );

        mysqli_stmt_bind_param(
            $category_check_stmt,
            "i",
            $category_id
        );

        mysqli_stmt_execute($category_check_stmt);

        $category_check_result =
            mysqli_stmt_get_result($category_check_stmt);

        if (mysqli_num_rows($category_check_result) === 0) {

            $error_message = "Selected category is not available.";

        } else {


            // Handle uploaded image
            if (
                isset($_FILES['food_image']) &&
                $_FILES['food_image']['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES['food_image']['error'] !== UPLOAD_ERR_OK) {

                    $error_message = "There was a problem uploading the image.";

                } else {

                    $file_size = $_FILES['food_image']['size'];

                    $max_size = 10 * 1024 * 1024;


                    if ($file_size > $max_size) {

                        $error_message =
                            "Image size must be 10MB or less.";

                    } else {

                        $allowed_types = [
                            "image/jpeg",
                            "image/png",
                            "image/jpg"
                        ];

                        $file_type = $_FILES['food_image']['type'];


                        if (!in_array($file_type, $allowed_types, true)) {

                            $error_message =
                                "Only JPG, JPEG and PNG images are allowed.";

                        } else {

                            $upload_folder = "uploads/food/";

                            if (!is_dir($upload_folder)) {

                                mkdir(
                                    $upload_folder,
                                    0777,
                                    true
                                );

                            }


                            $original_name =
                                $_FILES['food_image']['name'];

                            $extension =
                                strtolower(
                                    pathinfo(
                                        $original_name,
                                        PATHINFO_EXTENSION
                                    )
                                );


                            $new_file_name =
                                "food_" .
                                $user_id .
                                "_" .
                                time() .
                                "_" .
                                uniqid() .
                                "." .
                                $extension;


                            $target_file =
                                $upload_folder .
                                $new_file_name;


                            if (
                                move_uploaded_file(
                                    $_FILES['food_image']['tmp_name'],
                                    $target_file
                                )
                            ) {

                                $image_path = $target_file;

                            } else {

                                $error_message =
                                    "Could not save the uploaded image.";

                            }

                        }

                    }

                }

            }


            // Insert donation
            if ($error_message === "") {

                $insert_sql = "
                    INSERT INTO food_donations
                    (
                        donor_id,
                        category_id,
                        food_name,
                        quantity,
                        quantity_unit,
                        expiry_date,
                        description,
                        food_safety_info,
                        pickup_date,
                        preferred_time,
                        pickup_address,
                        image_path,
                        status
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'Available'
                    )
                ";


                $insert_stmt = mysqli_prepare(
                    $conn,
                    $insert_sql
                );


                if (!$insert_stmt) {

                    $error_message =
                        "Database error: " .
                        mysqli_error($conn);

                } else {

                    mysqli_stmt_bind_param(
                        $insert_stmt,
                        "iisdssssssss",
                        $user_id,
                        $category_id,
                        $food_name,
                        $quantity,
                        $quantity_unit,
                        $expiry_date,
                        $description,
                        $food_safety_info,
                        $pickup_date,
                        $preferred_time,
                        $pickup_address,
                        $image_path
                    );


                    if (mysqli_stmt_execute($insert_stmt)) {

                        $success_message =
                            "Food donation posted successfully.";

                        // Clear form values
                        $food_name = "";
                        $category_id = "";
                        $quantity = "";
                        $quantity_unit = "";
                        $expiry_date = "";
                        $description = "";
                        $food_safety_info = "";
                        $pickup_date = "";
                        $preferred_time = "";
                        $pickup_address = "";

                    } else {

                        $error_message =
                            "Could not save the donation: " .
                            mysqli_stmt_error($insert_stmt);

                    }


                    mysqli_stmt_close($insert_stmt);

                }

            }

        }


        mysqli_stmt_close($category_check_stmt);

    }

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
        FoodRescue - Donate Food
    </title>

    <link
        rel="stylesheet"
        href="post.css"
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

            <a
                href="post.php"
                class="active"
            >
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



        <!-- POST FOOD -->

        <div class="post-page">

            <h1>
                Donate Food
            </h1>

            <p class="subtitle">
                Share surplus food and make a meaningful impact in your community.
            </p>


            <?php if ($success_message !== ""): ?>

                <div class="message success-message">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>

            <?php endif; ?>


            <?php if ($error_message !== ""): ?>

                <div class="message error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>

            <?php endif; ?>


            <form
                action="post.php"
                method="POST"
                enctype="multipart/form-data"
                id="donationForm"
            >


                <div class="grid">


                    <!-- LEFT -->

                    <div>


                        <!-- FOOD DETAILS -->

                        <div class="card">

                            <h2>
                                Food Details
                            </h2>


                            <div class="fields">


                                <div>

                                    <label>
                                        FOOD NAME
                                    </label>

                                    <input
                                        type="text"
                                        name="food_name"
                                        placeholder="e.g. Fresh Garden Vegetables"
                                        value="<?php echo htmlspecialchars($food_name ?? ''); ?>"
                                        required
                                    >

                                </div>



                                <div>

                                    <label>
                                        CATEGORY
                                    </label>

                                    <select
                                        name="category_id"
                                        required
                                    >

                                        <option value="">
                                            Select Category
                                        </option>


                                        <?php while ($category = mysqli_fetch_assoc($category_result)): ?>

                                            <option
                                                value="<?php echo $category['category_id']; ?>"
                                                <?php
                                                if (
                                                    isset($category_id) &&
                                                    $category_id == $category['category_id']
                                                ) {
                                                    echo "selected";
                                                }
                                                ?>
                                            >
                                                <?php
                                                echo htmlspecialchars(
                                                    $category['category_name']
                                                );
                                                ?>
                                            </option>

                                        <?php endwhile; ?>

                                    </select>

                                </div>



                                <!-- QUANTITY -->

                                <div>

                                    <label>
                                        QUANTITY
                                    </label>

                                    <input
                                        type="number"
                                        name="quantity"
                                        placeholder="How many?"
                                        min="0.01"
                                        step="0.01"
                                        value="<?php echo htmlspecialchars($quantity ?? ''); ?>"
                                        required
                                    >

                                </div>



                                <!-- AVAILABLE -->

                                <div>

                                    <label>
                                        AVAILABLE
                                    </label>


                                    <div class="available-box">


                                        <div class="available-choice">


                                            <label class="radio-option">

                                                <input
                                                    type="radio"
                                                    name="quantity_unit"
                                                    value="Box"
                                                    <?php
                                                    if (
                                                        isset($quantity_unit) &&
                                                        $quantity_unit === "Box"
                                                    ) {
                                                        echo "checked";
                                                    }
                                                    ?>
                                                    required
                                                >

                                                <span>
                                                    Box
                                                </span>

                                            </label>



                                            <label class="radio-option">

                                                <input
                                                    type="radio"
                                                    name="quantity_unit"
                                                    value="Unit"
                                                    <?php
                                                    if (
                                                        isset($quantity_unit) &&
                                                        $quantity_unit === "Unit"
                                                    ) {
                                                        echo "checked";
                                                    }
                                                    ?>
                                                >

                                                <span>
                                                    Unit
                                                </span>

                                            </label>


                                        </div>


                                    </div>

                                </div>



                                <!-- EXPIRY -->

                                <div>

                                    <label>
                                        EXPIRY WINDOW
                                    </label>

                                    <input
                                        type="date"
                                        name="expiry_date"
                                        value="<?php echo htmlspecialchars($expiry_date ?? ''); ?>"
                                        required
                                    >

                                </div>


                            </div>



                            <!-- DESCRIPTION + FOOD SAFETY -->

                            <div class="description-row">


                                <div>

                                    <label>
                                        DESCRIPTION
                                    </label>

                                    <textarea
                                        name="description"
                                        placeholder="Briefly describe the food items, storage requirements, or any allergen information..."
                                    ><?php echo htmlspecialchars($description ?? ''); ?></textarea>

                                </div>



                                <div>

                                    <label>
                                        FOOD SAFETY INFO
                                    </label>

                                    <textarea
                                        name="food_safety_info"
                                        placeholder="Describe food safety information, preparation date, storage condition, allergens, etc..."
                                    ><?php echo htmlspecialchars($food_safety_info ?? ''); ?></textarea>

                                </div>


                            </div>

                        </div>



                        <!-- PICKUP SCHEDULE -->

                        <div class="card">

                            <h2>
                                Pickup Schedule
                            </h2>


                            <div class="fields">


                                <div>

                                    <label>
                                        PICKUP DATE
                                    </label>

                                    <input
                                        type="date"
                                        name="pickup_date"
                                        value="<?php echo htmlspecialchars($pickup_date ?? ''); ?>"
                                        required
                                    >

                                </div>



                                <div>

                                    <label>
                                        PREFERRED TIME
                                    </label>

                                    <input
                                        type="time"
                                        name="preferred_time"
                                        value="<?php echo htmlspecialchars($preferred_time ?? ''); ?>"
                                        required
                                    >

                                </div>


                            </div>


                            <label>
                                PICKUP ADDRESS
                            </label>

                            <input
                                type="text"
                                name="pickup_address"
                                placeholder="Enter pickup address"
                                value="<?php echo htmlspecialchars($pickup_address ?? ''); ?>"
                                required
                            >

                        </div>


                    </div>



                    <!-- RIGHT -->

                    <div>


                        <!-- UPLOAD PHOTOS -->

                        <div class="card">

                            <h2>
                                Upload Photos
                            </h2>


                            <div class="upload">


                                <div class="upload-icon">
                                    ♧
                                </div>


                                <b>
                                    Drag and drop images
                                </b>


                                <small>
                                    PNG, JPG, or JPEG (MAX 10MB)
                                </small>


                                <input
                                    type="file"
                                    name="food_image"
                                    id="foodImage"
                                    accept=".png,.jpg,.jpeg"
                                    hidden
                                >


                                <button
                                    type="button"
                                    id="browseButton"
                                >
                                    BROWSE FILES
                                </button>


                                <small
                                    id="fileName"
                                    class="file-name"
                                >
                                </small>


                            </div>

                        </div>


                    </div>


                </div>



                <!-- PUBLISH -->

                <button
                    type="submit"
                    class="publish"
                >
                    PUBLISH FOOD DONATION
                </button>


            </form>

        </div>

    </main>

</div>


<script src="post.js"></script>

</body>

</html>