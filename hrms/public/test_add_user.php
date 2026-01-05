<?php
session_start();
include("../includes/db_connect.php");

echo "<h2>Testing Add User Form</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p style='color: green;'>✅ Form submitted successfully!</p>";
    
    // Test database connection
    if ($conn) {
        echo "<p style='color: green;'>✅ Database connected!</p>";
    }
    
    // Test insert
    $employee_id = $_POST['employee_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];
    $department = $_POST['department'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $query = $conn->prepare("INSERT INTO users (employee_id, first_name, last_name, email, contact_number, department, role, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $query->bind_param("ssssssss", $employee_id, $first_name, $last_name, $email, $contact_number, $department, $role, $password);
    
    if ($query->execute()) {
        echo "<p style='color: green;'>✅ User inserted successfully! ID: " . $conn->insert_id . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $query->error . "</p>";
    }
}
?>

<form method="POST">
    <input type="text" name="employee_id" placeholder="Employee ID" required><br>
    <input type="text" name="first_name" placeholder="First Name" required><br>
    <input type="text" name="last_name" placeholder="Last Name" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="text" name="contact_number" placeholder="Contact" required><br>
    <select name="department" required>
        <option value="HR">HR</option>
    </select><br>
    <select name="role" required>
        <option value="employee">Employee</option>
    </select><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <input type="password" name="confirm_password" placeholder="Confirm" required><br>
    <button type="submit">Test Add User</button>
</form>