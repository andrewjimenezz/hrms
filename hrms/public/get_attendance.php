<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit();
}

$attendance_id = $_GET['id'];

$query = $conn->prepare("SELECT a.*, u.first_name, u.last_name, u.employee_id 
                         FROM attendance a 
                         JOIN users u ON a.user_id = u.id 
                         WHERE a.id = ?");
$query->bind_param("i", $attendance_id);
$query->execute();
$attendance = $query->get_result()->fetch_assoc();

if ($attendance) {
    $attendance['employee_name'] = $attendance['first_name'] . ' ' . $attendance['last_name'] . ' (' . $attendance['employee_id'] . ')';
}

header('Content-Type: application/json');
echo json_encode($attendance);
?>