<?php
// Database connection parameters
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "foodrescue_db";

// Create procedural mysqli connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
?>
