<?php
session_start();
require_once __DIR__ . '/../db.php';

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("<script>window.location.href = 'signup.html';</script>");
}

// Retrieve input fields
$role_input       = trim($_POST['role'] ?? '');
$full_name        = trim($_POST['full_name'] ?? '');
$reg_nid_number   = trim($_POST['reg_nid_number'] ?? '');
$email            = trim($_POST['email'] ?? '');
$phone            = trim($_POST['phone'] ?? '');
$address          = trim($_POST['address'] ?? '');
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Map account type selection to MySQL database role ENUM
$role_map = [
    'Food Donor'           => 'Donor',
    'Charity Organization' => 'Charity',
    'Volunteer Rider'      => 'Volunteer'
];
$role = $role_map[$role_input];

// Validation
if (!$role_input || !$full_name || !$email || !$phone || !$address || !$password || !$reg_nid_number) {
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

// Check if email already exists
$check = mysqli_query($conn, "SELECT user_id FROM users WHERE email = '$email'");
if (mysqli_num_rows($check) > 0) {
    die("<script>alert('This email address is already registered. Please use another email or log in.'); window.history.back();</script>");
}

// Handle NID Image Upload
$nid_image_path = '';
if (!empty($_FILES['nid_image']['name']) && $_FILES['nid_image']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['nid_image']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        $upload_dir = __DIR__ . '/../uploads/nid/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $new_name = time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['nid_image']['tmp_name'], $upload_dir . $new_name)) {
            $nid_image_path = 'uploads/nid/' . $new_name;
        }
    }
}

    // 4. Securely hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Set default values for organization_type and account_status
$organization_type = ($role === 'Charity') ? 'Foundation' : (($role === 'Donor') ? 'Restaurant' : 'Individual');

// Insert user
$insert_sql = "INSERT INTO users (full_name, email, password_hash, phone, role, organization_type, reg_nid_number, nid_image_path, address, account_status) 
               VALUES ('$full_name', '$email', '$password_hash', '$phone', '$role', '$organization_type', '$reg_nid_number', '$nid_image_path', '$address', 'Pending')";

if (mysqli_query($conn, $insert_sql)) {
    die("<script>
        alert('Registration Successful! Please wait for the Admin pannel to review your account. Redirecting to home page...');
        window.location.href = 'home.html';
    </script>");
} else {
    die("<script>alert('Error registering user'); window.history.back();</script>");
}

