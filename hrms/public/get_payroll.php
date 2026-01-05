<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit();
}

$payroll_id = $_GET['id'];

$query = $conn->prepare("SELECT p.*, u.employee_id, u.first_name, u.last_name, u.department 
                         FROM payroll p 
                         JOIN users u ON p.user_id = u.id 
                         WHERE p.id = ?");
$query->bind_param("i", $payroll_id);
$query->execute();
$payroll = $query->get_result()->fetch_assoc();

if ($payroll) {
    $payroll['employee_name'] = $payroll['first_name'] . ' ' . $payroll['last_name'];
    if ($payroll['payment_date']) {
        $payroll['payment_date'] = date('F j, Y', strtotime($payroll['payment_date']));
    }
}

header('Content-Type: application/json');
echo json_encode($payroll);
?>