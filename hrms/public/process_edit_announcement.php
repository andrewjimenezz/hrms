<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$announcement_id = $_POST['announcement_id'];
$title = trim($_POST['title']);
$content = trim($_POST['content']);
$priority = $_POST['priority'];

if (empty($title) || empty($content)) {
    $_SESSION['error'] = "Title and content are required.";
    header("Location: admin_announcements.php");
    exit();
}

// Update announcement
$query = $conn->prepare("UPDATE announcements SET title = ?, content = ?, priority = ? WHERE id = ?");
$query->bind_param("sssi", $title, $content, $priority, $announcement_id);

if ($query->execute()) {
    $_SESSION['success'] = "Announcement updated successfully!";
} else {
    $_SESSION['error'] = "Failed to update announcement.";
}

header("Location: admin_announcements.php");
exit();
?>