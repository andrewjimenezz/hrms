<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$delete_user_id = $_GET['id'];
$admin_id = $_SESSION['user_id'];

// Prevent admin from deleting themselves
if ($delete_user_id == $admin_id) {
    $_SESSION['error'] = "You cannot delete your own account!";
    header("Location: admin_users.php");
    exit();
}

// Get user info before deleting
$user_query = $conn->prepare("SELECT first_name, last_name, employee_id FROM users WHERE id = ?");
$user_query->bind_param("i", $delete_user_id);
$user_query->execute();
$user_info = $user_query->get_result()->fetch_assoc();

if (!$user_info) {
    $_SESSION['error'] = "User not found.";
    header("Location: admin_users.php");
    exit();
}

// Delete user (CASCADE will delete related records)
$query = $conn->prepare("DELETE FROM users WHERE id = ?");
$query->bind_param("i", $delete_user_id);

if ($query->execute()) {
    $_SESSION['success'] = "User " . $user_info['first_name'] . " " . $user_info['last_name'] . " (ID: " . $user_info['employee_id'] . ") has been deleted successfully.";
} else {
    $_SESSION['error'] = "Failed to delete user. Please try again.";
}

header("Location: admin_users.php");
exit();
?>