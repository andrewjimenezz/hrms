<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_POST['user_id'];
$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$email = trim($_POST['email']);
$contact_number = trim($_POST['contact_number']);
$department = $_POST['department'];
$role = $_POST['role'];

// Check if email is already used by another user
$check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$check->bind_param("si", $email, $user_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    $_SESSION['error'] = "Email is already in use by another user.";
    header("Location: admin_users.php");
    exit();
}

// Update user
$query = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, contact_number = ?, department = ?, role = ? WHERE id = ?");
$query->bind_param("ssssssi", $first_name, $last_name, $email, $contact_number, $department, $role, $user_id);

if ($query->execute()) {
    // Send notification to user
    $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $title = "Profile Updated by Admin";
    $message = "Your account information has been updated by an administrator.";
    $type = "info";
    $notif->bind_param("isss", $user_id, $title, $message, $type);
    $notif->execute();
    
    $_SESSION['success'] = "User updated successfully!";
} else {
    $_SESSION['error'] = "Failed to update user. Please try again.";
}

header("Location: admin_users.php");
exit();
?>