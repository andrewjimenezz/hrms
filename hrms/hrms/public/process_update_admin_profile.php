<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$email = trim($_POST['email']);
$contact_number = trim($_POST['contact_number']);

// Get current user data to check if anything actually changed
$current_query = "SELECT first_name, last_name, email, contact_number FROM users WHERE id = ?";
$current_stmt = $conn->prepare($current_query);
$current_stmt->bind_param("i", $user_id);
$current_stmt->execute();
$current_data = $current_stmt->get_result()->fetch_assoc();

// Check if any data actually changed
$data_changed = (
    $current_data['first_name'] !== $first_name ||
    $current_data['last_name'] !== $last_name ||
    $current_data['email'] !== $email ||
    $current_data['contact_number'] !== $contact_number
);

if (!$data_changed) {
    $_SESSION['info'] = "No changes were made to your profile.";
    header("Location: admin_profile.php");
    exit();
}

// Validate inputs
if (empty($first_name) || empty($last_name) || empty($email) || empty($contact_number)) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: admin_profile.php");
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format.";
    header("Location: admin_profile.php");
    exit();
}

// Check if email is already used by another user
$check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$check->bind_param("si", $email, $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Email is already in use by another account.";
    header("Location: admin_profile.php");
    exit();
}

// Update user information
$update = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, contact_number = ? WHERE id = ?");
$update->bind_param("ssssi", $first_name, $last_name, $email, $contact_number, $user_id);

if ($update->execute()) {
    $_SESSION['success'] = "Profile updated successfully!";
} else {
    $_SESSION['error'] = "Failed to update profile. Please try again.";
}

header("Location: admin_profile.php");
exit();
?>