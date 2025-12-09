<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$leave_id = $_POST['leave_id'];
$admin_remarks = trim($_POST['admin_remarks']);

if (empty($admin_remarks)) {
    $_SESSION['error'] = "Please provide a reason for rejection.";
    header("Location: admin_leave.php");
    exit();
}

// Get leave details
$leave_query = $conn->prepare("SELECT lr.*, u.first_name, u.last_name 
                               FROM leave_requests lr 
                               JOIN users u ON lr.user_id = u.id 
                               WHERE lr.id = ?");
$leave_query->bind_param("i", $leave_id);
$leave_query->execute();
$leave = $leave_query->get_result()->fetch_assoc();

if (!$leave) {
    $_SESSION['error'] = "Leave request not found.";
    header("Location: admin_leave.php");
    exit();
}

// Update leave status
$query = $conn->prepare("UPDATE leave_requests SET status = 'rejected', admin_remarks = ? WHERE id = ?");
$query->bind_param("si", $admin_remarks, $leave_id);

if ($query->execute()) {
    // Notify employee
    $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $title = "Leave Request Rejected";
    $message = "Your " . ucfirst($leave['leave_type']) . " leave request from " . date('M j', strtotime($leave['start_date'])) . " to " . date('M j', strtotime($leave['end_date'])) . " has been rejected. Reason: " . $admin_remarks;
    $type = "error";
    $notif->bind_param("isss", $leave['user_id'], $title, $message, $type);
    $notif->execute();
    
    $_SESSION['success'] = "Leave request rejected.";
} else {
    $_SESSION['error'] = "Failed to reject leave request.";
}

header("Location: admin_leave.php");
exit();
?>