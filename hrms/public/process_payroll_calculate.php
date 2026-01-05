<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$payroll_id = $_GET['id'];

// Get payroll info
$info_query = $conn->prepare("SELECT p.*, u.id as user_id FROM payroll p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$info_query->bind_param("i", $payroll_id);
$info_query->execute();
$payroll = $info_query->get_result()->fetch_assoc();

if (!$payroll) {
    $_SESSION['error'] = "Payroll not found.";
    header("Location: admin_payroll.php");
    exit();
}

// Call stored procedure to calculate deductions
$stmt = $conn->prepare("CALL sp_calculate_government_deductions(?, @gsis, @pag_ibig, @philhealth, @tax)");
$stmt->bind_param("d", $payroll['basic_salary']);
$stmt->execute();
$stmt->close();

// Get the output variables
$result = $conn->query("SELECT @gsis AS gsis, @pag_ibig AS pag_ibig, @philhealth AS philhealth, @tax AS tax");
$deductions = $result->fetch_assoc();

$gsis = $deductions['gsis'];
$pag_ibig = $deductions['pag_ibig'];
$philhealth = $deductions['philhealth'];
$tax = $deductions['tax'];

$total_deductions = $gsis + $pag_ibig + $philhealth + $tax;
$net_salary = $payroll['basic_salary'] + $payroll['allowances'] - $total_deductions;

// Update payroll with calculated deductions
$update = $conn->prepare("UPDATE payroll SET gsis = ?, pag_ibig = ?, philhealth = ?, withholding_tax = ?, deductions = ?, net_salary = ?, status = 'processed' WHERE id = ?");
$update->bind_param("ddddddi", $gsis, $pag_ibig, $philhealth, $tax, $total_deductions, $net_salary, $payroll_id);

if ($update->execute()) {
    // Notify employee
    $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $title = "Payroll Processed";
    $message = "Your payroll for " . $payroll['month'] . " " . $payroll['year'] . " has been processed. Net salary: ₱" . number_format($net_salary, 2) . " (after government deductions).";
    $type = "success";
    $notif->bind_param("isss", $payroll['user_id'], $title, $message, $type);
    $notif->execute();
    
    $_SESSION['success'] = "Payroll processed! Government deductions calculated: GSIS (₱" . number_format($gsis, 2) . "), Pag-IBIG (₱" . number_format($pag_ibig, 2) . "), PhilHealth (₱" . number_format($philhealth, 2) . "), Tax (₱" . number_format($tax, 2) . ")";
} else {
    $_SESSION['error'] = "Failed to process payroll.";
}

header("Location: admin_payroll.php?month=" . $payroll['month'] . "&year=" . $payroll['year']);
exit();
?>