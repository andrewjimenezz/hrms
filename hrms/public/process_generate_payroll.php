<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_POST['user_id'];
$month = $_POST['month'];
$year = $_POST['year'];
$basic_salary = $_POST['basic_salary'];
$allowances = $_POST['allowances'];
$deductions = $_POST['deductions'];
$net_salary = $_POST['net_salary'];

// Check if payroll already exists for this user/month/year
$check = $conn->prepare("SELECT id FROM payroll WHERE user_id = ? AND month = ? AND year = ?");
$check->bind_param("isi", $user_id, $month, $year);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    $_SESSION['error'] = "Payroll already exists for this employee in $month $year.";
    header("Location: admin_payroll.php?month=$month&year=$year");
    exit();
}

// Insert payroll
$query = $conn->prepare("INSERT INTO payroll (user_id, month, year, basic_salary, allowances, deductions, net_salary, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'draft')");
$query->bind_param("isidddd", $user_id, $month, $year, $basic_salary, $allowances, $deductions, $net_salary);

if ($query->execute()) {
    // Notify employee
    $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $title = "Payroll Generated";
    $message = "Your payroll for $month $year has been generated. Net salary: ₱" . number_format($net_salary, 2);
    $type = "info";
    $notif->bind_param("isss", $user_id, $title, $message, $type);
    $notif->execute();
    
    $_SESSION['success'] = "Payroll generated successfully!";
} else {
    $_SESSION['error'] = "Failed to generate payroll.";
}

header("Location: admin_payroll.php?month=$month&year=$year");
exit();
?>