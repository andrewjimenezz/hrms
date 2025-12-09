<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMS Landing Page</title>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #0a4f0d, #3aa23f);
            color: white;
        }

        .container {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            padding: 40px;
        }

        .left-section {
            width: 45%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .left-section img {
            width: 320px;
        }

        .right-section {
            width: 55%;
            padding: 20px;
        }

        .right-section p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        h1 {
            margin: 20px 0 5px;
            font-size: 24px;
            font-weight: bold;
        }

        .btn-login {
            padding: 12px 28px;
            border: none;
            border-radius: 25px;
            background: white;
            color: #0a4f0d;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-login:hover {
            background: #dfffe0;
        }

        .footer {
            position: absolute;
            bottom: 10px;
            right: 20px;
            font-size: 12px;
            opacity: 0.8;
        }
    </style>

</head>
<body>

    <div class="container">
        <!-- LEFT SIDE -->
        <div class="left-section">
            <img src="images/PLSP_LOGO 1.png" alt="School Logo">
        </div>

        <!-- RIGHT SIDE -->
        <div class="right-section">
            <p>
                Our HRMS is a centralized platform designed to simplify and automate essential HR tasks.
                It provides users with easy access to dashboards, attendance tracking, payroll information,
                leave management, and department or user details. The system helps both administrators and
                employees stay organized, improve efficiency, and manage daily HR processes in one secure
                and user-friendly space.
            </p>

            <h1>WELCOME!</h1>
            <p>HUMAN RESOURCE MANAGEMENT SYSTEM</p>

            <a href="login.php">
                <button class="btn-login">LOGIN TO CONTINUE</button>
            </a>
        </div>
    </div>

    <div class="footer">
        Developed by BSIS – 3B, HRMS PROJECT 2025
    </div>

</body>
</html>
