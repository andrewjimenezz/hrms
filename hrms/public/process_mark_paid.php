<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$payroll_id = $_GET['id'];
$payment_date = date('Y-m-d');

// Get payroll info
$info_query = $conn->prepare("SELECT p.*, u.first_name, u.last_name FROM payroll p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$info_query->bind_param("i", $payroll_id);
$info_query->execute();
$payroll = $info_query->get_result()->fetch_assoc();

if (!$payroll) {
    $_SESSION['error'] = "Payroll not found.";
    header("Location: admin_payroll.php");
    exit();
}

// Update to paid
$query = $conn->prepare("UPDATE payroll SET status = 'paid', payment_date = ? WHERE id = ?");
$query->bind_param("si", $payment_date, $payroll_id);

if ($query->execute()) {
    // Notify employee
    $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $title = "Salary Paid";
    $message = "Your salary for " . $payroll['month'] . " " . $payroll['year'] . " (₱" . number_format($payroll['net_salary'], 2) . ") has been paid on " . date('F j, Y', strtotime($payment_date)) . ".";
    $type = "success";
    $notif->bind_param("isss", $payroll['user_id'], $title, $message, $type);
    $notif->execute();
    
    $_SESSION['success'] = "Payroll marked as paid for " . $payroll['first_name'] . " " . $payroll['last_name'] . "!";
} else {
    $_SESSION['error'] = "Failed to mark payroll as paid.";
}

header("Location: admin_payroll.php?month=" . $payroll['month'] . "&year=" . $payroll['year']);
exit();
?>