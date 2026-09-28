<?php
// Start session for status/error messages
session_start();

// Include procedural database connection using absolute directory path
require_once __DIR__ . '/../db.php';

// Check if form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Retrieve and sanitize input fields
    $role_input       = isset($_POST['role']) ? trim($_POST['role']) : '';
    $full_name        = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $reg_nid_number   = isset($_POST['reg_nid_number']) ? trim($_POST['reg_nid_number']) : '';
    $email            = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone            = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address          = isset($_POST['address']) ? trim($_POST['address']) : '';
    $password         = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Map account type selection to MySQL database role ENUM ('Donor', 'Charity', 'Volunteer')
    $role = '';
    if ($role_input === 'Food Donor') {
        $role = 'Donor';
    } elseif ($role_input === 'Charity Organization') {
        $role = 'Charity';
    } elseif ($role_input === 'Volunteer Rider') {
        $role = 'Volunteer';
    }

    // 1. Backend Validation
    if (empty($role_input) || empty($full_name) || empty($email) || empty($phone) || empty($address) || empty($password) || empty($reg_nid_number)) {
        die("<script>alert('Please fill in all required fields.'); window.history.back();</script>");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("<script>alert('Invalid email format. Please enter a valid email.'); window.history.back();</script>");
    }

    if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/", $password)) {
        die("<script>alert('Password must be at least 8 characters and contain an uppercase letter, lowercase letter, and number.'); window.history.back();</script>");
    }

    if ($password !== $confirm_password) {
        die("<script>alert('Passwords do not match. Please try again.'); window.history.back();</script>");
    }

    

    // 2. Check if email already exists using prepared statement
    $check_sql = "SELECT user_id FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    if (!$stmt) {
       die("<script>alert('Database error'); window.history.back();</script>");
    }
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        die("<script>alert('This email address is already registered. Please use another email or log in.'); window.history.back();</script>");
    }
    mysqli_stmt_close($stmt);

    // 3. Handle NID Image Upload
    $nid_image_path = null;
    if (isset($_FILES['nid_image']) && $_FILES['nid_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp   = $_FILES['nid_image']['tmp_name'];
        $file_name  = $_FILES['nid_image']['name'];
        $file_ext   = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_exts = array('jpg', 'jpeg', 'png', 'webp');
        if (in_array($file_ext, $allowed_exts)) {
            $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
            $upload_dir    = __DIR__ . '/../uploads/nid/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $destination = $upload_dir . $new_file_name;
            if (move_uploaded_file($file_tmp, $destination)) {
                $nid_image_path = 'uploads/nid/' . $new_file_name;
            }
        }
    }

    // 4. Securely hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Set default values for organization_type and account_status
    $organization_type = ($role === 'Charity') ? 'Foundation' : (($role === 'Donor') ? 'Restaurant' : 'Individual');
    $account_status    = 'Pending';

    // 5. Insert user using prepared statement
    $insert_sql = "INSERT INTO users (full_name, email, password_hash, phone, role, organization_type, reg_nid_number, nid_image_path, address, account_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $insert_sql);
    if (!$stmt) {
        die("<script>alert('Database error on user insert'); window.history.back();</script>");
    }

    mysqli_stmt_bind_param($stmt, "ssssssssss", $full_name, $email, $password_hash, $phone, $role, $organization_type, $reg_nid_number, $nid_image_path, $address, $account_status);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        // Success popup and redirect to home page
        echo "<script>
            alert('Registration Successful! Please wait for the Admin pannel to review your account. Redirecting to home page...');
            window.location.href = 'home.html';
        </script>";
        exit();
    } else {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        die("<script>alert('Error registering user'); window.history.back();</script>");
    }

} else {
    // If not POST request, redirect back to signup page
    header("Location: signup.html");
    exit();
}
?>
