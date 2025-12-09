<?php
$servername = "localhost";     // XAMPP default
$username   = "root";          // XAMPP default
$password   = "";              // empty password
$database   = "hrms";       // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");
?>
