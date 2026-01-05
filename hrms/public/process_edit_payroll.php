<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$payroll_id = $_POST['payroll_id'];
$basic_salary = $_POST['basic_salary'];
$allowances = $_POST['allowances'];
$deductions = $_POST['deductions'];
$net_salary = $_POST['net_salary'];

// Get current payroll info for redirect
$info_query = $conn->prepare("SELECT month, year, user_id FROM payroll WHERE id = ?");
$info_query->bind_param("i", $payroll_id);
$info_query->execute();
$info = $info_query->get_result()->fetch_assoc();

// Update payroll
$query = $conn->prepare("UPDATE payroll SET basic_salary = ?, allowances = ?, deductions = ?, net_salary = ?, status = 'processed' WHERE id = ?");
$query->bind_param("ddddi", $basic_salary, $allowances, $deductions, $net_salary, $payroll_id);

if ($query->execute()) {
    // Notify employee
    $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $title = "Payroll Updated";
    $message = "Your payroll for " . $info['month'] . " " . $info['year'] . " has been updated. New net salary: ₱" . number_format($net_salary, 2);
    $type = "info";
    $notif->bind_param("isss", $info['user_id'], $title, $message, $type);
    $notif->execute();
    
    $_SESSION['success'] = "Payroll updated successfully!";
} else {
    $_SESSION['error'] = "Failed to update payroll.";
}

header("Location: admin_payroll.php?month=" . $info['month'] . "&year=" . $info['year']);
exit();
?>