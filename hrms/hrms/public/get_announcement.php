<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit();
}

$announcement_id = $_GET['id'];

$query = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
$query->bind_param("i", $announcement_id);
$query->execute();
$announcement = $query->get_result()->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($announcement);
?>