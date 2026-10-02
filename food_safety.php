<?php

include "db.php";
include "admin_auth.php";
$admin_id = $_SESSION['admin_user_id'];

if (isset($_POST['add_guideline'])) {

    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $bullet_points = mysqli_real_escape_string($conn, $_POST['bullet_points']);

    $order_sql = "SELECT MAX(display_order) AS max_order
                  FROM food_safety_guidelines
                  WHERE category = '$category'
                  AND status = 'Active'";

    $order_result = mysqli_query($conn, $order_sql);
    $order_data = mysqli_fetch_assoc($order_result);

    if ($order_data['max_order'] !== null) {
        $display_order = $order_data['max_order'] + 1;
    } else {
        $display_order = 1;
    }

    $sql = "INSERT INTO food_safety_guidelines
            (
                admin_id,
                category,
                title,
                description,
                bullet_points,
                display_order,
                status
            )
            VALUES
            (
                '$admin_id',
                '$category',
                '$title',
                '$description',
                '$bullet_points',
                '$display_order',
                'Active'
            )";

    if (mysqli_query($conn, $sql)) {

        echo "<script>
                alert('Guideline added successfully!');
                window.location.href='food_safety.php';
              </script>";
        exit();

    } else {

        echo "Error: " . mysqli_error($conn);
    }
}


if (isset($_POST['update_guideline'])) {

    $guideline_id = $_POST['guideline_id'];

    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $bullet_points = mysqli_real_escape_string($conn, $_POST['bullet_points']);
    $display_order = intval($_POST['display_order']);

    $sql = "UPDATE food_safety_guidelines
            SET
                category = '$category',
                title = '$title',
                description = '$description',
                bullet_points = '$bullet_points',
                display_order = '$display_order'
            WHERE guideline_id = '$guideline_id'";

    if (mysqli_query($conn, $sql)) {

        echo "<script>
                alert('Guideline updated successfully!');
                window.location.href='food_safety.php';
              </script>";
        exit();

    } else {

        echo "Error: " . mysqli_error($conn);
    }
}


if (isset($_GET['delete'])) {

    $guideline_id = intval($_GET['delete']);



    $sql = "UPDATE food_safety_guidelines
            SET status = 'Inactive'
            WHERE guideline_id = '$guideline_id'";

    if (mysqli_query($conn, $sql)) {

        echo "<script>
                alert('Guideline deleted successfully!');
                window.location.href='food_safety.php';
              </script>";
        exit();

    } else {

        echo "Error: " . mysqli_error($conn);
    }
}


$expiry_sql = "SELECT *
               FROM food_safety_guidelines
               WHERE category = 'Safety Status'
               AND status = 'Active'
               ORDER BY display_order, guideline_id";

$expiry_result = mysqli_query($conn, $expiry_sql);



$handling_sql = "SELECT *
                 FROM food_safety_guidelines
                 WHERE category = 'Handling Guideline'
                 AND status = 'Active'
                 ORDER BY display_order, guideline_id";

$handling_result = mysqli_query($conn, $handling_sql);


$agreement_sql = "SELECT *
                  FROM food_safety_guidelines
                  WHERE category = 'Compliance & Agreement'
                  AND status = 'Active'
                  ORDER BY display_order, guideline_id
                  LIMIT 1";

$agreement_result = mysqli_query($conn, $agreement_sql);

$agreement = mysqli_fetch_assoc($agreement_result);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Food Safety & Guidelines</title>

    <link rel="stylesheet" href="css/food_safety.css">

</head>

<body>


<div class="page">


    <div class="page-header">

        <div>

            <h1>Food Safety & Guidelines</h1>

            <p>
                Manage food safety rules, handling instructions,
                and compliance information.
            </p>

        </div>


        <button
            class="add-btn"
            onclick="openAddForm('Handling Guideline')">

            + Add Guideline

        </button>

    </div>


    <div class="section">


        <div class="section-title">

            <h2>Food Safety & Expiry</h2>


            <button
                class="add-small-btn"
                onclick="openAddForm('Safety Status')">

                + Add Status

            </button>

        </div>


        <div class="cards">


            <?php if (mysqli_num_rows($expiry_result) > 0) { ?>


                <?php while ($guideline = mysqli_fetch_assoc($expiry_result)) { ?>


                    <div class="card">


                        <div class="card-header">


                            <h3>
                                <?php
                                echo htmlspecialchars(
                                    $guideline['title']
                                );
                                ?>
                            </h3>


                            <div class="actions">


                                <button
                                    class="edit-btn"
                                    onclick='openEditForm(
                                        <?php
                                        echo json_encode($guideline);
                                        ?>
                                    )'>

                                    Edit

                                </button>


                                <a
                                    href="food_safety.php?delete=<?php echo $guideline['guideline_id']; ?>"
                                    class="delete-btn"
                                    onclick="return confirm('Are you sure you want to delete this guideline?');">

                                    Delete

                                </a>


                            </div>


                        </div>


                        <p>

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $guideline['description']
                                )
                            );
                            ?>

                        </p>


                        <?php if (!empty($guideline['bullet_points'])) { ?>

                            <ul>

                                <?php

                                $points = explode(
                                    "\n",
                                    $guideline['bullet_points']
                                );

                                foreach ($points as $point) {

                                    $point = trim($point);

                                    if ($point != '') {

                                        echo "<li>" .
                                            htmlspecialchars($point) .
                                            "</li>";
                                    }
                                }

                                ?>

                            </ul>

                        <?php } ?>


                    </div>


                <?php } ?>


            <?php } else { ?>


                <div class="empty-message">

                    No safety status guidelines available.

                </div>


            <?php } ?>


        </div>


    </div>





    <div class="section">


        <div class="section-title">

            <h2>Food Handling Guidelines</h2>


            <button
                class="add-small-btn"
                onclick="openAddForm('Handling Guideline')">

                + Add Guideline

            </button>

        </div>


        <div class="guideline-list">


            <?php if (mysqli_num_rows($handling_result) > 0) { ?>


                <?php
                $number = 1;

                while ($guideline = mysqli_fetch_assoc($handling_result)) {
                ?>


                    <div class="guideline">


                        <span>

                            <?php echo $number; ?>

                        </span>


                        <div class="guideline-content">


                            <div class="guideline-header">


                                <h3>

                                    <?php
                                    echo htmlspecialchars(
                                        $guideline['title']
                                    );
                                    ?>

                                </h3>


                                <div class="actions">


                                    <button
                                        class="edit-btn"
                                        onclick='openEditForm(
                                            <?php
                                            echo json_encode($guideline);
                                            ?>
                                        )'>

                                        Edit

                                    </button>


                                    <a
                                        href="food_safety.php?delete=<?php echo $guideline['guideline_id']; ?>"
                                        class="delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this guideline?');">

                                        Delete

                                    </a>


                                </div>


                            </div>


                            <p>

                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $guideline['description']
                                    )
                                );
                                ?>

                            </p>


                            <?php if (!empty($guideline['bullet_points'])) { ?>

                                <ul>

                                    <?php

                                    $points = explode(
                                        "\n",
                                        $guideline['bullet_points']
                                    );

                                    foreach ($points as $point) {

                                        $point = trim($point);

                                        if ($point != '') {

                                            echo "<li>" .
                                                htmlspecialchars($point) .
                                                "</li>";
                                        }
                                    }

                                    ?>

                                </ul>

                            <?php } ?>


                        </div>


                    </div>


                <?php

                    $number++;

                }

                ?>


            <?php } else { ?>


                <div class="empty-message">

                    No food handling guidelines available.

                </div>


            <?php } ?>


        </div>


    </div>


    <div class="agreement">


        <div class="agreement-header">


            <div>

                <h2>Compliance & Agreement</h2>


                <?php if ($agreement) { ?>


                    <h3 class="agreement-title">

                        <?php
                        echo htmlspecialchars(
                            $agreement['title']
                        );
                        ?>

                    </h3>


                <?php } ?>


            </div>


            <?php if ($agreement) { ?>


                <button
                    class="edit-btn"
                    onclick='openEditForm(
                        <?php
                        echo json_encode($agreement);
                        ?>
                    )'>

                    Edit

                </button>


            <?php } else { ?>


                <button
                    class="add-small-btn"
                    onclick="openAddForm('Compliance & Agreement')">

                    + Add

                </button>


            <?php } ?>


        </div>


        <?php if ($agreement) { ?>


            <p>

                <?php
                echo nl2br(
                    htmlspecialchars(
                        $agreement['description']
                    )
                );
                ?>

            </p>


            <?php if (!empty($agreement['bullet_points'])) { ?>


                <ul>

                    <?php

                    $points = explode(
                        "\n",
                        $agreement['bullet_points']
                    );

                    foreach ($points as $point) {

                        $point = trim($point);

                        if ($point != '') {

                            echo "<li>" .
                                htmlspecialchars($point) .
                                "</li>";
                        }
                    }

                    ?>

                </ul>


            <?php } ?>


        <?php } else { ?>


            <p>

                No compliance and agreement information
                has been added yet.

            </p>


        <?php } ?>


    </div>


    <div class="footer">

        <p>
            All donated food must meet the required
            food safety and handling standards.
        </p>


    </div>


</div>


<div id="guidelineModal" class="modal">


    <div class="modal-box">


        <div class="modal-header">


            <h2 id="modalTitle">
                Add Guideline
            </h2>


            <button
                class="close-btn"
                onclick="closeForm()">

                X

            </button>


        </div>


        <form method="POST">


            <input
                type="hidden"
                name="guideline_id"
                id="guideline_id">


            <label>
                Category
            </label>


            <select
                name="category"
                id="category"
                required>

                <option value="">
                    Select Category
                </option>

                <option value="Safety Status">
                    Safety Status
                </option>

                <option value="Handling Guideline">
                    Handling Guideline
                </option>

                <option value="Compliance & Agreement">
                    Compliance & Agreement
                </option>

            </select>


            <label>
                Title
            </label>


            <input
                type="text"
                name="title"
                id="title"
                placeholder="Enter guideline title"
                required>


            <label>
                Description
            </label>


            <textarea
                name="description"
                id="description"
                rows="4"
                placeholder="Enter guideline description..."
                required></textarea>


            <label>
                Bullet Points
                <small>(one point per line)</small>
            </label>


            <textarea
                name="bullet_points"
                id="bullet_points"
                rows="5"
                placeholder="Example:
Store food properly
Keep food covered
Check expiry date"></textarea>


            <label>
                Display Order
            </label>


            <input
                type="number"
                name="display_order"
                id="display_order"
                value="1"
                min="1">



            <div class="modal-actions">


                <button
                    type="button"
                    class="cancel-btn"
                    onclick="closeForm()">

                    Cancel

                </button>


                <button
                    type="submit"
                    name="add_guideline"
                    id="saveButton"
                    class="save-btn">

                    Add Guideline

                </button>


            </div>


        </form>


    </div>

</div>



<script>


function openAddForm(category) {

    document.getElementById("guidelineModal").style.display = "flex";

    document.getElementById("modalTitle").innerText =
        "Add Guideline";

    document.getElementById("saveButton").innerText =
        "Add Guideline";

    document.getElementById("saveButton").name =
        "add_guideline";


    document.getElementById("guideline_id").value = "";

    document.getElementById("category").value = category;

    document.getElementById("title").value = "";

    document.getElementById("description").value = "";

    document.getElementById("bullet_points").value = "";

    document.getElementById("display_order").value = "1";
}



function openEditForm(guideline) {

    document.getElementById("guidelineModal").style.display = "flex";

    document.getElementById("modalTitle").innerText =
        "Edit Guideline";

    document.getElementById("saveButton").innerText =
        "Update Guideline";

    document.getElementById("saveButton").name =
        "update_guideline";


    document.getElementById("guideline_id").value =
        guideline.guideline_id;

    document.getElementById("category").value =
        guideline.category;

    document.getElementById("title").value =
        guideline.title;

    document.getElementById("description").value =
        guideline.description;

    document.getElementById("bullet_points").value =
        guideline.bullet_points || "";

    document.getElementById("display_order").value =
        guideline.display_order;
}



function closeForm() {

    document.getElementById("guidelineModal").style.display =
        "none";
}



window.onclick = function(event) {

    var modal =
        document.getElementById("guidelineModal");

    if (event.target === modal) {

        closeForm();

    }

};


</script>


</body>

</html>