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
?>
