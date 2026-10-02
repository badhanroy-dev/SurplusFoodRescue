<?php
session_start();

if (!isset($_SESSION['admin_user_id'])) {
    header("Location: login.php");
    exit();
}
?>