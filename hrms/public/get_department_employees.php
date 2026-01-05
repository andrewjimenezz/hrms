<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit();
}

$department = $_GET['department'];

$query = $conn->prepare("SELECT employee_id, first_name, last_name, email, contact_number, role 
                         FROM users 
                         WHERE department = ? 
                         ORDER BY role DESC, last_name ASC");
$query->bind_param("s", $department);
$query->execute();
$result = $query->get_result();

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}

header('Content-Type: application/json');
echo json_encode($employees);
?>