<?php
session_start();
require_once __DIR__ . '/../db.php';

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("<script>window.location.href = 'settings.html';</script>");
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("<script>alert('Please log in first to access settings.'); window.location.href = 'login.html';</script>");
}

$user_id = $_SESSION['user_id'];

// Retrieve input fields
$full_name        = trim($_POST['full_name'] ?? '');
$phone            = trim($_POST['phone'] ?? '');
$address          = trim($_POST['address'] ?? '');
$current_password = $_POST['current_password'] ?? '';
$new_password     = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation for required fields
if (!$full_name || !$phone || !$address || !$current_password || !$new_password || !$confirm_password) {
    die("<script>alert('Please fill in all required fields.'); window.history.back();</script>");
}

// Fetch current user from database
$result = mysqli_query($conn, "SELECT * FROM users WHERE user_id = '$user_id'");
$user   = mysqli_fetch_assoc($result);

if (!$user) {
    die("<script>alert('User account not found.'); window.location.href = 'login.html';</script>");
}

// Verify current password
if (!password_verify($current_password, $user['password_hash'])) {
    die("<script>alert('Current password is incorrect. Please try again.'); window.history.back();</script>");
}

// Validate new password formatting
if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/", $new_password)) {
    die("<script>alert('New password must be at least 8 characters and contain an uppercase letter, lowercase letter, and number.'); window.history.back();</script>");
}

// Check if new password matches confirm password
if ($new_password !== $confirm_password) {
    die("<script>alert('New password and confirm password do not match. Please try again.'); window.history.back();</script>");
}

// Securely hash new password
$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Update user details in database
$update_sql = "UPDATE users SET 
                full_name = '$full_name', 
                phone = '$phone', 
                address = '$address', 
                password_hash = '$new_password_hash' 
               WHERE user_id = '$user_id'";

if (mysqli_query($conn, $update_sql)) {
    // Update session variables
    $_SESSION['full_name'] = $full_name;

    // Redirect directly based on role location (e.g. ../charity/dashboard.html)
    $role         = strtolower($user['role']);
    $redirect_url = "../{$role}/dashboard.html";

    echo "<script>
        alert('Settings updated successfully!');
        window.location.href = '{$redirect_url}';
    </script>";
    exit();
} else {
    die("<script>alert('Error updating settings. Please try again.'); window.history.back();</script>");
}
?>
