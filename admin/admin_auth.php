<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_user_id']) && isset($_SESSION['user_id']) && strtolower($_SESSION['role'] ?? '') === 'admin') {
    $_SESSION['admin_user_id'] = $_SESSION['user_id'];
}

if (!isset($_SESSION['admin_user_id'])) {
    // header("Location: login.php"); removed
    header("Location: ../charityGuest/guest/login.html");
    exit();
}
?>