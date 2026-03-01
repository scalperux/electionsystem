<?php
// Start session on every page that includes this file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database settings for XAMPP
$host = "localhost";
$db   = "online_voting";
$user = "root";   // default XAMPP user
$pass = "";       // default XAMPP password is empty

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Optional: set default timezone (change to yours if needed)
date_default_timezone_set('Africa/Nairobi');