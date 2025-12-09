<?php
session_start();
include("../includes/db_connect.php");

$employee_id     = $_POST['employee_id'];
$first_name      = $_POST['first_name'];
$last_name       = $_POST['last_name'];
$email           = $_POST['email'];
$contact_number  = $_POST['contact_number'];
$department      = $_POST['department'];
$password        = $_POST['password'];
$confirm_password= $_POST['confirm_password'];

// Check password match
if ($password !== $confirm_password) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: signup.php");
    exit();
}

// Check if email already exists
$check = $conn->prepare("SELECT * FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Email already exists.";
    header("Location: signup.php");
    exit();
}

// Check if employee_id already exists
$check2 = $conn->prepare("SELECT * FROM users WHERE employee_id = ?");
$check2->bind_param("s", $employee_id);
$check2->execute();
$result2 = $check2->get_result();

if ($result2->num_rows > 0) {
    $_SESSION['error'] = "Employee ID already exists.";
    header("Location: signup.php");
    exit();
}

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert into database
$query = $conn->prepare("INSERT INTO users 
    (employee_id, first_name, last_name, email, contact_number, password, department, role)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'employee')");

$query->bind_param("sssssss", 
    $employee_id,
    $first_name,
    $last_name,
    $email,
    $contact_number,
    $hashed_password,
    $department
);

if ($query->execute()) {
    $_SESSION['success'] = "Account created successfully. You can now log in.";
    header("Location: signup.php");
    exit();
} else {
    $_SESSION['error'] = "Something went wrong. Try again.";
    header("Location: signup.php");
    exit();
}

?>
