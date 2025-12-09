<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$announcement_id = $_GET['id'];

$query = $conn->prepare("DELETE FROM announcements WHERE id = ?");
$query->bind_param("i", $announcement_id);

if ($query->execute()) {
    $_SESSION['success'] = "Announcement deleted successfully.";
} else {
    $_SESSION['error'] = "Failed to delete announcement.";
}

header("Location: admin_announcements.php");
exit();
?>