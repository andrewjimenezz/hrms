<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$month = $_GET['month'];
$year = $_GET['year'];
$payment_date = date('Y-m-d');

// Get all processed payroll for this month/year
$query = $conn->prepare("SELECT * FROM payroll WHERE month = ? AND year = ? AND status = 'processed'");
$query->bind_param("si", $month, $year);
$query->execute();
$payrolls = $query->get_result();

$count = 0;

while ($payroll = $payrolls->fetch_assoc()) {
    // Update to paid
    $update = $conn->prepare("UPDATE payroll SET status = 'paid', payment_date = ? WHERE id = ?");
    $update->bind_param("si", $payment_date, $payroll['id']);
    
    if ($update->execute()) {
        $count++;
        
        // Notify employee
        $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $title = "Salary Paid";
        $message = "Your salary for $month $year (₱" . number_format($payroll['net_salary'], 2) . ") has been paid!";
        $type = "success";
        $notif->bind_param("isss", $payroll['user_id'], $title, $message, $type);
        $notif->execute();
    }
}

if ($count > 0) {
    $_SESSION['success'] = "Successfully marked $count payroll record(s) as paid for $month $year!";
} else {
    $_SESSION['info'] = "No processed payroll records to mark as paid.";
}

header("Location: admin_payroll.php?month=$month&year=$year");
exit();
?>