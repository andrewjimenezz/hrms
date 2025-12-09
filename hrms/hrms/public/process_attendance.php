<?php
session_start();
include("../includes/db_connect.php");

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'];
$today = date('Y-m-d');
$current_time = date('H:i:s');

if ($action === 'time_in') {
    // Check if already clocked in today
    $check = $conn->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ?");
    $check->bind_param("is", $user_id, $today);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "You have already clocked in today.";
    } else {
        // Determine status based on time
        $time_in_hour = (int)date('H');
        $status = 'present';
        
        // If clock in after 9 AM, mark as late
        if ($time_in_hour >= 9) {
            $status = 'late';
        }
        
        // Insert new attendance record
        $insert = $conn->prepare("INSERT INTO attendance (user_id, date, time_in, status) VALUES (?, ?, ?, ?)");
        $insert->bind_param("isss", $user_id, $today, $current_time, $status);
        
        if ($insert->execute()) {
            $_SESSION['success'] = "Successfully clocked in at " . date('h:i A');
            
            // Create notification
            $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $title = "Attendance Recorded";
            $message = "You clocked in at " . date('h:i A') . " - Status: " . ucfirst($status);
            $type = "success";
            $notif->bind_param("isss", $user_id, $title, $message, $type);
            $notif->execute();
        } else {
            $_SESSION['error'] = "Failed to record attendance.";
        }
    }
}

if ($action === 'time_out') {
    // Check if attendance exists and time_out is not set
    $check = $conn->prepare("SELECT * FROM attendance WHERE user_id = ? AND date = ? AND time_out IS NULL");
    $check->bind_param("is", $user_id, $today);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "No clock-in record found or you have already clocked out.";
    } else {
        // Update attendance with time_out
        $update = $conn->prepare("UPDATE attendance SET time_out = ? WHERE user_id = ? AND date = ?");
        $update->bind_param("sis", $current_time, $user_id, $today);
        
        if ($update->execute()) {
            $_SESSION['success'] = "Successfully clocked out at " . date('h:i A');
            
            // Create notification
            $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
            $title = "Clock Out Recorded";
            $message = "You clocked out at " . date('h:i A');
            $type = "info";
            $notif->bind_param("isss", $user_id, $title, $message, $type);
            $notif->execute();
        } else {
            $_SESSION['error'] = "Failed to record clock out.";
        }
    }
}

header("Location: employee_dashboard.php");
exit();
?>