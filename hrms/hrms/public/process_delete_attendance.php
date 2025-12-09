<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$attendance_id = $_GET['id'];

// Get date for redirect
$date_query = $conn->prepare("SELECT date FROM attendance WHERE id = ?");
$date_query->bind_param("i", $attendance_id);
$date_query->execute();
$result = $date_query->get_result();

if ($result->num_rows > 0) {
    $date = $result->fetch_assoc()['date'];
    
    // Delete attendance
    $query = $conn->prepare("DELETE FROM attendance WHERE id = ?");
    $query->bind_param("i", $attendance_id);
    
    if ($query->execute()) {
        $_SESSION['success'] = "Attendance record deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete attendance record.";
    }
} else {
    $_SESSION['error'] = "Attendance record not found.";
    $date = date('Y-m-d');
}

header("Location: admin_attendance.php?date=$date");
exit();
?>