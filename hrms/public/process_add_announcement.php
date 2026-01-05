<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$title = trim($_POST['title']);
$content = trim($_POST['content']);
$priority = $_POST['priority'];
$created_by = $_SESSION['user_id'];

if (empty($title) || empty($content)) {
    $_SESSION['error'] = "Title and content are required.";
    header("Location: admin_announcements.php");
    exit();
}

// Insert announcement
$query = $conn->prepare("INSERT INTO announcements (title, content, priority, created_by) VALUES (?, ?, ?, ?)");
$query->bind_param("sssi", $title, $content, $priority, $created_by);

if ($query->execute()) {
    // Notify all employees
    $employees = $conn->query("SELECT id FROM users WHERE role = 'employee'");
    
    $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $notif_title = "New Announcement: " . $title;
    $notif_message = substr($content, 0, 100) . (strlen($content) > 100 ? '...' : '');
    $notif_type = $priority == 'high' ? 'warning' : 'info';
    
    while($emp = $employees->fetch_assoc()) {
        $notif_stmt->bind_param("isss", $emp['id'], $notif_title, $notif_message, $notif_type);
        $notif_stmt->execute();
    }
    
    $_SESSION['success'] = "Announcement posted successfully! All employees have been notified.";
} else {
    $_SESSION['error'] = "Failed to post announcement.";
}

header("Location: admin_announcements.php");
exit();
?>