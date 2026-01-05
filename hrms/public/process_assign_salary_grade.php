<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$employee_id = $_POST['employee_id'];
$salary_grade = $_POST['salary_grade'];
$step_increment = $_POST['step_increment'];

// Update employee salary grade
$query = $conn->prepare("UPDATE users SET salary_grade = ?, step_increment = ? WHERE id = ?");
$query->bind_param("iii", $salary_grade, $step_increment, $employee_id);

if ($query->execute()) {
    // Get salary amount
    $salary_query = $conn->prepare("SELECT monthly_salary FROM salary_grades WHERE salary_grade = ? AND step_increment = ?");
    $salary_query->bind_param("ii", $salary_grade, $step_increment);
    $salary_query->execute();
    $salary_result = $salary_query->get_result()->fetch_assoc();
    
    // Notify employee
    $notif = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $title = "Salary Grade Assigned";
    $message = "You have been assigned Salary Grade " . $salary_grade . ", Step " . $step_increment . ". Monthly salary: ₱" . number_format($salary_result['monthly_salary'], 2);
    $type = "success";
    $notif->bind_param("isss", $employee_id, $title, $message, $type);
    $notif->execute();
    
    $_SESSION['success'] = "Salary grade assigned successfully! SG $salary_grade, Step $step_increment (₱" . number_format($salary_result['monthly_salary'], 2) . ")";
} else {
    $_SESSION['error'] = "Failed to assign salary grade.";
}

header("Location: admin_salary_grades.php");
exit();
?>