<?php
session_start();
include("../includes/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$payroll_id = $_GET['id'];

// Get payroll info before deleting
$info_query = $conn->prepare("SELECT month, year FROM payroll WHERE id = ?");
$info_query->bind_param("i", $payroll_id);
$info_query->execute();
$info = $info_query->get_result()->fetch_assoc();

if (!$info) {
    $_SESSION['error'] = "Payroll not found.";
    header("Location: admin_payroll.php");
    exit();
}

// Delete payroll (only if status is draft)
$query = $conn->prepare("DELETE FROM payroll WHERE id = ? AND status = 'draft'");
$query->bind_param("i", $payroll_id);

if ($query->execute()) {
    if ($query->affected_rows > 0) {
        $_SESSION['success'] = "Payroll deleted successfully.";
    } else {
        $_SESSION['error'] = "Cannot delete payroll. Only draft payroll can be deleted.";
    }
} else {
    $_SESSION['error'] = "Failed to delete payroll.";
}

header("Location: admin_payroll.php?month=" . $info['month'] . "&year=" . $info['year']);
exit();
?>