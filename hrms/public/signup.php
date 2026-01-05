<?php
session_start();
?>

<?php include("../includes/db_connect.php"); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Sign Up | HRMS</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial;
            background: #f4f4f4;
        }

        .container {
            width: 450px;
            margin: 70px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 10px;
            color: #0a4f0d;
        }

        input, select {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 25px;
            font-size: 15px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #0a4f0d;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 25px;
            margin-top: 20px;
            cursor: pointer;
        }

        .btn:hover {
            background: #06600e;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }

        .success {
            color: green;
            text-align: center;
            margin-bottom: 10px;
        }

        .link {
            margin-top: 15px;
            text-align: center;
            font-size: 14px;
        }

        .link a {
            color: #0a4f0d;
            text-decoration: none;
        }

    </style>
</head>
<body>

<div class="container">

    <h2>Employee Sign Up</h2>
    <p style="text-align:center;">Create your HRMS account</p>

    <?php
    if (isset($_SESSION['error'])) {
        echo "<p class='error'>".$_SESSION['error']."</p>";
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo "<p class='success'>".$_SESSION['success']."</p>";
        unset($_SESSION['success']);
    }
    ?>

    <form action="process_signup.php" method="POST">

        <input type="text" name="employee_id" placeholder="Employee ID" required>

        <input type="text" name="first_name" placeholder="First Name" required>
        <input type="text" name="last_name" placeholder="Last Name" required>

        <input type="email" name="email" placeholder="Email" required>

        <input type="text" name="contact_number" placeholder="Contact Number" required>

        <select name="department" required>
            <option value="">Select Department</option>
            <option value="HR">HR</option>
            <option value="Finance">Finance</option>
            <option value="Registrar">Registrar</option>
            <option value="CCSE - College of Computer Studies and Engineering">CCSE - College of Computer Studies and Engineering</option>
            <option value="CTHM - College of Tourism and Hospitality Management">CTHM - College of Tourism and Hospitality Management</option>
            <option value="CBAM - College of Business Administration and Management">CBAM - College of Business Administration and Management</option>
            <option value="CTED - College of Teacher Education">CTED - College of Teacher Education</option>
            <option value="CNAHS - College of Nursing and Allied Health Sciences">CNAHS - College of Nursing and Allied Health Sciences</option>
            <option value="COA - College of Agriculture">COA - College of Agriculture</option>
            <option value="CAS - College of Arts and Sciences">CAS - College of Arts and Sciences</option>
        </select>

        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>

        <button class="btn" type="submit">Create Account</button>
    </form>

    <div class="link">
        Already have an account? <a href="login.php">Login here</a>
    </div>

</div>

</body>
</html>
