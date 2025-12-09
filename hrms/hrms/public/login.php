<?php
session_start();
?>

<?php include("../includes/db_connect.php"); ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | HRMS</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #f7f7f7;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        /* LEFT SIDE */
        .left-pane {
            width: 50%;
            background: linear-gradient(to bottom right, #0a4f0d, #3aa23f);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }

        .left-pane img {
            width: 250px;
            margin-bottom: 20px;
        }

        .left-pane h1 {
            margin-top: 20px;
            font-size: 24px;
        }

        /* RIGHT SIDE */
        .right-pane {
            width: 50%;
            background: white;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        h2 {
            margin-bottom: 10px;
            color: #333;
        }

        .input-box {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 25px;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: #0a4f0d;
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn-login:hover {
            background: #06600e;
        }

        .links {
            margin-top: 10px;
            font-size: 14px;
        }

        .links a {
            color: #0a4f0d;
            text-decoration: none;
        }

        .error-message {
            color: red;
            margin-bottom: 10px;
            font-size: 14px;
        }

    </style>
</head>
<body>

<div class="container">

    <!-- LEFT SIDE -->
    <div class="left-pane">
        <img src="images/PLSP_LOGO 1.png" alt="Logo">
        <h1>WELCOME!</h1>
        <p>HUMAN RESOURCE MANAGEMENT SYSTEM</p>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right-pane">
        <h2>Login to HRMS</h2>
        <p>Enter your details below</p>

        <?php
        if (isset($_SESSION['error'])) {
            echo "<div class='error-message'>" . $_SESSION['error'] . "</div>";
            unset($_SESSION['error']);
        }
        ?>

        <form action="process_login.php" method="POST">
            <input type="email" name="email" class="input-box" placeholder="Email" required>
            <input type="password" name="password" class="input-box" placeholder="Password" required>

            <button type="submit" class="btn-login">LOG IN</button>
        </form>

        <div class="links">
            <a href="#">Forgot your password?</a><br>
            Don't have an account? <a href="signup.php">Sign Up</a>
        </div>
    </div>

</div>

</body>
</html>
