<?php
session_start();
include("../includes/db_connect.php"); // your DB connection

$email = $_POST['email'];
$password = $_POST['password'];

$query = "SELECT * FROM users WHERE email = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {

        // save user session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === "admin") {
            header("Location: admin_dashboard.php");
            exit();
        } else {
            header("Location: employee_dashboard.php");
            exit();
        }

    } else {
        $_SESSION['error'] = "Incorrect password.";
        header("Location: login.php");
        exit();
    }

} else {
    $_SESSION['error'] = "Account not found.";
    header("Location: login.php");
    exit();
}
?>
