<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$leave_type = $_POST['leave_type'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$reason = $_POST['reason'];

// Validate dates
if (strtotime($start_date) > strtotime($end_date)) {
    $_SESSION['error'] = "End date must be after start date.";
    header("Location: employee_leave.php");
    exit();
}

// Check if dates are in the past
if (strtotime($start_date) < strtotime(date('Y-m-d'))) {
    $_SESSION['error'] = "Cannot apply for leave on past dates.";
    header("Location: employee_leave.php");
    exit();
}

// Insert leave request
$query = $conn->prepare("INSERT INTO leave_requests (user_id, leave_type, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, 'pending')");
$query->bind_param("issss", $user_id, $leave_type, $start_date, $end_date, $reason);

if ($query->execute()) {
    $_SESSION['success'] = "Leave request submitted successfully. Waiting for approval.";
    
    // Create notification for employee
    $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $title = "Leave Request Submitted";
    $message = "Your " . ucfirst($leave_type) . " leave request from " . date('M j', strtotime($start_date)) . " to " . date('M j', strtotime($end_date)) . " has been submitted.";
    $type = "info";
    $notif->bind_param("isss", $user_id, $title, $message, $type);
    $notif->execute();
    
    // Notify all admins
    $admin_query = "SELECT id FROM users WHERE role = 'admin'";
    $admins = $conn->query($admin_query);
    
    while($admin = $admins->fetch_assoc()) {
        $admin_notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        
        // Get employee name
        $emp_query = "SELECT first_name, last_name FROM users WHERE id = ?";
        $emp_stmt = $conn->prepare($emp_query);
        $emp_stmt->bind_param("i", $user_id);
        $emp_stmt->execute();
        $emp = $emp_stmt->get_result()->fetch_assoc();
        
        $admin_title = "New Leave Request";
        $admin_message = $emp['first_name'] . " " . $emp['last_name'] . " has requested " . ucfirst($leave_type) . " leave.";
        $admin_type = "warning";
        $admin_notif->bind_param("isss", $admin['id'], $admin_title, $admin_message, $admin_type);
        $admin_notif->execute();
    }
    
} else {
    $_SESSION['error'] = "Failed to submit leave request. Please try again.";
}

header("Location: employee_leave.php");
exit();
?>