<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit();
}

$leave_id = $_GET['id'];

$query = $conn->prepare("SELECT lr.*, u.employee_id, u.first_name, u.last_name, u.department 
                         FROM leave_requests lr 
                         JOIN users u ON lr.user_id = u.id 
                         WHERE lr.id = ?");
$query->bind_param("i", $leave_id);
$query->execute();
$leave = $query->get_result()->fetch_assoc();

if ($leave) {
    $leave['employee_name'] = $leave['first_name'] . ' ' . $leave['last_name'];
    $leave['leave_type'] = ucfirst($leave['leave_type']);
    $leave['status'] = ucfirst($leave['status']);
    $leave['start_date'] = date('F j, Y', strtotime($leave['start_date']));
    $leave['end_date'] = date('F j, Y', strtotime($leave['end_date']));
    $leave['created_at'] = date('F j, Y h:i A', strtotime($leave['created_at']));
    
    // Calculate days
    $start = new DateTime($leave['start_date']);
    $end = new DateTime($leave['end_date']);
    $leave['days'] = $start->diff($end)->days + 1;
}

header('Content-Type: application/json');
echo json_encode($leave);
?>