<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_POST['user_id'];
$date = $_POST['date'];
$time_in = !empty($_POST['time_in']) ? $_POST['time_in'] : NULL;
$time_out = !empty($_POST['time_out']) ? $_POST['time_out'] : NULL;
$status = $_POST['status'];
$remarks = trim($_POST['remarks']);

// Check if attendance already exists
$check = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND date = ?");
$check->bind_param("is", $user_id, $date);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    $_SESSION['error'] = "Attendance already exists for this employee on this date.";
    header("Location: admin_attendance.php?date=$date");
    exit();
}

// Insert attendance
if ($time_in && $time_out) {
    $query = $conn->prepare("INSERT INTO attendance (user_id, date, time_in, time_out, status, remarks) VALUES (?, ?, ?, ?, ?, ?)");
    $query->bind_param("isssss", $user_id, $date, $time_in, $time_out, $status, $remarks);
} elseif ($time_in) {
    $query = $conn->prepare("INSERT INTO attendance (user_id, date, time_in, status, remarks) VALUES (?, ?, ?, ?, ?)");
    $query->bind_param("issss", $user_id, $date, $time_in, $status, $remarks);
} else {
    $query = $conn->prepare("INSERT INTO attendance (user_id, date, status, remarks) VALUES (?, ?, ?, ?)");
    $query->bind_param("isss", $user_id, $date, $status, $remarks);
}

if ($query->execute()) {
    // Notify employee
    $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $title = "Attendance Marked";
    $message = "Your attendance for " . date('M j, Y', strtotime($date)) . " has been marked as " . ucfirst($status) . " by admin.";
    $type = "info";
    $notif->bind_param("isss", $user_id, $title, $message, $type);
    $notif->execute();
    
    $_SESSION['success'] = "Attendance marked successfully!";
} else {
    $_SESSION['error'] = "Failed to mark attendance: " . $query->error;
}

header("Location: admin_attendance.php?date=$date");
exit();
?>