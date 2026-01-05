<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Debug: Check if form data is received
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: admin_users.php");
    exit();
}

$employee_id = trim($_POST['employee_id']);
$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$email = trim($_POST['email']);
$contact_number = trim($_POST['contact_number']);
$department = $_POST['department'];
$role = $_POST['role'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// Validate all fields are filled
if (empty($employee_id) || empty($first_name) || empty($last_name) || empty($email) || 
    empty($contact_number) || empty($department) || empty($role) || empty($password)) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: admin_users.php");
    exit();
}

// Validate passwords match
if ($password !== $confirm_password) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: admin_users.php");
    exit();
}

// Validate password length
if (strlen($password) < 6) {
    $_SESSION['error'] = "Password must be at least 6 characters.";
    header("Location: admin_users.php");
    exit();
}

// Check if employee_id already exists
$check = $conn->prepare("SELECT id FROM users WHERE employee_id = ?");
$check->bind_param("s", $employee_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $_SESSION['error'] = "Employee ID already exists.";
    header("Location: admin_users.php");
    exit();
}

// Check if email already exists
$check2 = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check2->bind_param("s", $email);
$check2->execute();
if ($check2->get_result()->num_rows > 0) {
    $_SESSION['error'] = "Email already exists.";
    header("Location: admin_users.php");
    exit();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$query = $conn->prepare("INSERT INTO users (employee_id, first_name, last_name, email, contact_number, department, role, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

if (!$query) {
    $_SESSION['error'] = "Database error: " . $conn->error;
    header("Location: admin_users.php");
    exit();
}

$query->bind_param("ssssssss", $employee_id, $first_name, $last_name, $email, $contact_number, $department, $role, $hashed_password);

if ($query->execute()) {
    $new_user_id = $conn->insert_id;
    
    // Send welcome notification to new user (only if notifications table exists)
    $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    if ($notif) {
        $title = "Welcome to HRMS";
        $message = "Your account has been created successfully. You can now log in with your credentials.";
        $type = "success";
        $notif->bind_param("isss", $new_user_id, $title, $message, $type);
        $notif->execute();
    }
    
    $_SESSION['success'] = "User created successfully! Employee ID: $employee_id";
} else {
    $_SESSION['error'] = "Failed to create user: " . $query->error;
}

header("Location: admin_users.php");
exit();
?>