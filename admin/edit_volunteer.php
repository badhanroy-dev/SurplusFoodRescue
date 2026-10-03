<?php
 $user_id=$_GET['id'];
 include "db.php";
include "admin_auth.php";

 if(isset($_POST['action']) && $_POST['action']=="update")
    {
        $full_name = $_POST['full_name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $address= $_POST['address'];
        $reg_nid_number = $_POST['reg_nid_number'];

        $update_sql = "UPDATE users SET full_name = '$full_name', phone = '$phone',
         email = '$email', address = '$address', reg_nid_number = '$reg_nid_number'
          WHERE user_id = $user_id AND role = 'Volunteer'";

$update_result = mysqli_query($conn, $update_sql);
if ($update_result) {
   echo "<script>
        alert('Volunteer information updated successfully!');
        window.location.href = 'volunteer_verify.php';
      </script>";
exit();
}
    }
 $info_sql = "SELECT full_name, phone, email, address, reg_nid_number FROM users
WHERE user_id = $user_id AND role = 'Volunteer'";

$info_result = mysqli_query($conn, $info_sql);
$volunteer = mysqli_fetch_assoc($info_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Volunteer</title>
    <link rel="stylesheet" href="css/edit_volunteer.css">
</head>
<body>

<div class="page">
   <div class="header">
            <div>
                <h1>Edit Volunteer Information</h1>
             <p>Update the volunteer's information below with new information below:</p>
            </div>
           <a href="volunteer_verify.php" class="back-button">Back</a>
        </div>
         <div class="form-card">
    <form method="POST">
        <label>Volunteer Name</label>
        <input type="text" name="full_name" value="<?php echo $volunteer['full_name'] ?>">

        <label>Phone Number</label>
        <input type="text" name="phone" value="<?php echo $volunteer['phone'] ?>">

        <label>Email</label>
        <input type="email" name="email" value="<?php echo $volunteer['email'] ?>">

        <label>Address</label>
        <input type="text" name="address" value="<?php echo $volunteer['address'] ?>">

        <label for="nid">NID Number</label>
        <input type="text" name="reg_nid_number" value="<?php echo $volunteer['reg_nid_number'] ?>">

               <div class="actions">
                    <button type="submit" name="action" value="update" class="add-btn">Save Changes</button>
                </div>
    </form>
    </div>
    </div>


</body>
</html>

