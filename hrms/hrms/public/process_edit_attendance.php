<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$attendance_id = $_POST['attendance_id'];
$time_in = !empty($_POST['time_in']) ? $_POST['time_in'] : NULL;
$time_out = !empty($_POST['time_out']) ? $_POST['time_out'] : NULL;
$status = $_POST['status'];
$remarks = trim($_POST['remarks']);

// Get date for redirect
$date_query = $conn->prepare("SELECT date FROM attendance WHERE id = ?");
$date_query->bind_param("i", $attendance_id);
$date_query->execute();
$date = $date_query->get_result()->fetch_assoc()['date'];

// Update attendance
$query = $conn->prepare("UPDATE attendance SET time_in = ?, time_out = ?, status = ?, remarks = ? WHERE id = ?");
$query->bind_param("ssssi", $time_in, $time_out, $status, $remarks, $attendance_id);

if ($query->execute()) {
    $_SESSION['success'] = "Attendance updated successfully!";
} else {
    $_SESSION['error'] = "Failed to update attendance: " . $query->error;
}

header("Location: admin_attendance.php?date=$date");
exit();
?>