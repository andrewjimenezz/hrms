<?php
/**
 * Auto Mark Absent System
 * 
 * This script automatically marks employees as absent if they haven't clocked in.
 */

session_start();
include("../includes/db_connect.php");

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: admin_attendance.php");
    exit();
}

// Get yesterday's date (mark absent for previous day)
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Get all employees who haven't marked attendance for yesterday
$query = "SELECT u.id, u.employee_id, u.first_name, u.last_name 
          FROM users u 
          WHERE u.role = 'employee' 
          AND u.id NOT IN (SELECT user_id FROM attendance WHERE date = ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $yesterday);
$stmt->execute();
$absent_employees = $stmt->get_result();

$marked_count = 0;
$employee_names = [];

while ($employee = $absent_employees->fetch_assoc()) {
    // Mark as absent
    $insert = $conn->prepare("INSERT INTO attendance (user_id, date, status, remarks) VALUES (?, ?, 'absent', 'Auto-marked absent by system')");
    $insert->bind_param("is", $employee['id'], $yesterday);
    
    if ($insert->execute()) {
        $marked_count++;
        $employee_names[] = $employee['first_name'] . ' ' . $employee['last_name'];
        
        // Send notification to employee
        $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $title = "Marked Absent";
        $message = "You were marked absent for " . date('F j, Y', strtotime($yesterday)) . " as no attendance was recorded.";
        $type = "warning";
        $notif->bind_param("isss", $employee['id'], $title, $message, $type);
        $notif->execute();
    }
}

if ($marked_count > 0) {
    $_SESSION['success'] = "Successfully marked $marked_count employee(s) as absent for " . date('F j, Y', strtotime($yesterday)) . ".";
} else {
    $_SESSION['info'] = "No employees to mark as absent for " . date('F j, Y', strtotime($yesterday)) . ". All attendance is up to date!";
}

header("Location: admin_attendance.php?date=$yesterday");
exit();
?>