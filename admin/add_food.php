<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $food_name = $_POST['food_name'];
    $category_id = $_POST['category_id'];
    $donor_id = $_POST['donor_id'];
    $quantity = $_POST['quantity'];
    $quantity_unit = $_POST['quantity_unit'];
    $expiry_date = $_POST['expiry_date'];
    $pickup_date = $_POST['pickup_date'];
    $preferred_time = $_POST['preferred_time'];
    $pickup_address = $_POST['pickup_address'];
    $food_safety_info = $_POST['food_safety_info'];
    $description = $_POST['description'];
    $expiry_date = str_replace("T", " ", $expiry_date);

    $sql = "INSERT INTO food_donations
        (donor_id, category_id, food_name, quantity, quantity_unit,
         expiry_date, description, food_safety_info,
         pickup_date, preferred_time, pickup_address)
        VALUES
        ('$donor_id', '$category_id', '$food_name', '$quantity',
         '$quantity_unit', '$expiry_date', '$description',
         '$food_safety_info', '$pickup_date', '$preferred_time',
         '$pickup_address')";

if (mysqli_query($conn, $sql)) {

    echo "<script>
            alert('Food added successfully!');
            window.location.href='foodcategories.php';
          </script>";

    exit();

} else {

    echo "Error: " . mysqli_error($conn);
}
}

$category_sql = "SELECT category_id, category_name
                 FROM food_categories
                 WHERE status = 'Active'
                 ORDER BY category_name";

$category_result = mysqli_query($conn, $category_sql);


$donor_sql = "SELECT user_id, full_name
              FROM users
              WHERE role = 'Donor'
              AND account_status IN ('Active', 'Verified')
              ORDER BY full_name";

$donor_result = mysqli_query($conn, $donor_sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Food</title>

    <link rel="stylesheet" href="css/addfood.css">

</head>

<body>

    <div class="page">

        <div class="header">

            <div>

                <h1>Add Food</h1>

                <p>Add a new food donation and safety information:</p>

            </div>

            <a href="foodcategories.php" class="back-btn">Back</a>

        </div>


        <div class="form-card">

            <form method="POST">

                <div class="form-group">

                    <label>Food Name</label>

                    <input type="text" name="food_name" placeholder="Enter food name" required>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label>Category</label>

                        <select name="category_id" required>

                            <option value="">Select category</option>

                            <?php
                            while ($category = mysqli_fetch_assoc($category_result)) {
                            ?>

                                <option value="<?php echo $category['category_id']; ?>">

                                    <?php echo $category['category_name']; ?>

                                </option>

                            <?php
                            }
                            ?>

                        </select>

                    </div>


                    <div class="form-group">

                        <label>Donor</label>

                        <select name="donor_id" required>

                            <option value="">Select donor</option>

                            <?php
                            while ($donor = mysqli_fetch_assoc($donor_result)) {
                            ?>

                                <option value="<?php echo $donor['user_id']; ?>">

                                    <?php echo $donor['full_name']; ?>

                                </option>

                            <?php
                            }
                            ?>

                        </select>

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label>Quantity</label>

                        <input type="number" name="quantity" placeholder="e.g. 20" min="1" step="0.01" required>

                    </div>


                    <div class="form-group">

                        <label>Quantity Unit</label>

                        <select name="quantity_unit" required>
                            <option value="">Select unit</option>
                            <option value="kg">Kg</option>
                            <option value="pcs">Pieces</option>
                            <option value="litre">Litre</option>
                            <option value="pack">Pack</option>
                            <option value="unit">Unit</option>

                        </select>

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label for="expiry">Expiry Date</label>

                        <input type="datetime-local" name="expiry_date" id="expiry" required>

                    </div>


                    <div class="form-group">

                        <label>Pickup Date</label>

                        <input type="date" name="pickup_date" required>

                    </div>

                </div>


                <div class="form-group">

                    <label>Preferred Pickup Time</label>

                    <input type="text" name="preferred_time" placeholder="e.g. 5 PM - 7 PM" required>

                </div>


                <div class="form-group">

                    <label>Pickup Address</label>

                    <textarea name="pickup_address" rows="3" placeholder="Enter pickup address" required></textarea>

                </div>


                <div class="form-group">

                    <label>Food Safety Information</label>

                    <textarea name="food_safety_info" rows="4" placeholder="Enter storage instructions, allergens, or safety notes..."></textarea>

                </div>


                <div class="form-group">

                    <label>Description</label>

                    <textarea name="description" rows="3" placeholder="Brief description of the food..."></textarea>

                </div>


                <div class="actions">

                    <button type="reset" class="cancel-btn">
                        Cancel
                    </button>

                    <button type="submit" class="add-btn">
                        + Add Food
                    </button>

                </div>

            </form>

        </div>

    </div>

</body>

</html>