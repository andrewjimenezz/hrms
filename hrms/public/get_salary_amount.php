<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit();
}

$grade = $_GET['grade'];
$step = $_GET['step'];

$query = $conn->prepare("SELECT monthly_salary FROM salary_grades WHERE salary_grade = ? AND step_increment = ?");
$query->bind_param("ii", $grade, $step);
$query->execute();
$result = $query->get_result()->fetch_assoc();

header('Content-Type: application/json');
echo json_encode(['salary' => $result['monthly_salary'] ?? 0]);
?>