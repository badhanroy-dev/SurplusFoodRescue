<?php
session_start();
require_once __DIR__ . '/../db.php';

// Ensure POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("<script>window.location.href = 'login.html';</script>");
}



$login_input = trim($_POST['login_input'] ?? '');
$password    = $_POST['password'] ?? '';

if (empty($login_input) || empty($password)) {
    die("<script>alert('Please enter your email/phone and password.'); window.history.back();</script>");
}

// Find user by email or phone
$result = mysqli_query($conn, "SELECT * FROM users WHERE email = '$login_input' OR phone = '$login_input'");
$user   = mysqli_fetch_assoc($result);

if (!$user) {
    die("<script>alert('No account found with that email or phone number.'); window.location.href = 'login.html';</script>");
}

if (!password_verify($password, $user['password_hash'])) {
    die("<script>alert('Invalid password. Please try again.'); window.location.href = 'login.html';</script>");
}

// Check if account is approved by admin
if ($user['account_status'] === 'Pending') {
    die("<script>alert('Your account is pending admin approval. Please wait for approval before logging in.'); window.location.href = 'login.html';</script>");
}

if ($user['account_status'] === 'Disabled' || $user['account_status'] === 'Rejected') {
    die("<script>alert('Your account is disabled or rejected. Please contact support.'); window.location.href = 'login.html';</script>");
}


// Store user details in PHP Session
$_SESSION['user_id']        = $user['user_id'];
$_SESSION['full_name']      = $user['full_name'];
$_SESSION['email']          = $user['email'];
$_SESSION['role']           = $user['role'];
$_SESSION['account_status'] = $user['account_status'];

// Redirect directly based on role location (e.g. ../charity/dashboard.html)
$role         = strtolower($user['role']);
if($role === 'admin')
{
    $_SESSION['admin_user_id'] = $user['user_id'];
    $redirect_url = "../../admin/index.php";
    
}
else if($role === 'charity')
{
    $redirect_url = "../charity/dashboard.html";

}
else if($role === 'donor')
{
     $redirect_url = "../../donor/dashboard.php";

}
else if($role === 'volunteer')
{
     $redirect_url = "../../volunteer/dashboard.php";

}


$user_name =$user['full_name'];
echo "<script>
    alert('Login Successful! Welcome back, {$user_name}!');
    window.location.href = '{$redirect_url}';
</script>";
exit();

