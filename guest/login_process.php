<?php
// Start session for logged-in user
session_start();

// Include procedural database connection
require_once __DIR__ . '/../db.php';

// Check if form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve and sanitize input fields
    $login_input = isset($_POST['login_input']) ? trim($_POST['login_input']) : '';
    $password    = isset($_POST['password']) ? $_POST['password'] : '';

    // 1. Validate required fields
    if (empty($login_input) || empty($password)) {
        die("<script>alert('Please enter your email/phone and password.');window.history.back();</script>");
    }

    // 2. Prepared SQL query to find user by email OR phone
    $sql = "SELECT user_id, full_name, email, phone, password_hash, role, account_status FROM users WHERE email = ? OR phone = ?"; // prevents sql injection
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die("<script>alert('Database error during login query.'); window.location.href = 'login.html';</script>");
    }

    // Bind parameters for email or phone
    mysqli_stmt_bind_param($stmt, "ss", $login_input, $login_input);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // 3. Check if user exists in database
    if ($user = mysqli_fetch_assoc($result)) {
        
        // 4. Verify password against stored hash in database
        if (password_verify($password, $user['password_hash'])) {

            // Store user details in PHP Session
            $_SESSION['user_id']        = $user['user_id'];
            $_SESSION['full_name']      = $user['full_name'];
            $_SESSION['email']          = $user['email'];
            $_SESSION['role']           = $user['role'];
            $_SESSION['account_status'] = $user['account_status'];

            mysqli_stmt_close($stmt);
            mysqli_close($conn);

            // Role-based dashboard redirection (Donor/Restaurant, Charity, Volunteer, Admin)
            $redirect_url = '../charity/dashboard.html'; // Default fallback

            if ($user['role'] === 'Charity') {
                $redirect_url = '../charity/dashboard.html';
            } elseif ($user['role'] === 'Donor') {
                // Food Donor / Restaurant
                $redirect_url = file_exists(__DIR__ . '/../donor/dashboard.html') ? '../donor/dashboard.html' : '../charity/dashboard.html';
            } elseif ($user['role'] === 'Volunteer') {
                // Volunteer Rider
                $redirect_url = file_exists(__DIR__ . '/../volunteer/dashboard.html') ? '../volunteer/dashboard.html' : '../charity/dashboard.html';
            } elseif ($user['role'] === 'Admin') {
                // System Admin
                $redirect_url = file_exists(__DIR__ . '/../admin/dashboard.html') ? '../admin/dashboard.html' : '../charity/dashboard.html';
            }

            // Redirect user to their role-specific dashboard
            echo "<script>
                alert('Login Successful! Welcome back, " . addslashes($user['full_name']) . "!');
                window.location.href = '" . $redirect_url . "';
            </script>";
            exit();

        } else {
            // Password mismatch
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            die("<script>alert('Invalid password. Please try again.'); window.location.href = 'login.html';</script>");
        }

    } else {
        // User not found
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        die("<script>alert('No account found with that email or phone number.'); window.location.href = 'login.html';</script>");
    }

} else {
    // If not POST request, redirect back to login page
    header("Location: login.html");
    exit();
}
?>
