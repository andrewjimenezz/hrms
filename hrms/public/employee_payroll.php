<?php
session_start();
include("../includes/db_connect.php");
include '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user info
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get payroll history
$payroll_query = "SELECT * FROM payroll WHERE user_id = ? ORDER BY year DESC, FIELD(month, 'December', 'November', 'October', 'September', 'August', 'July', 'June', 'May', 'April', 'March', 'February', 'January') LIMIT 12";
$payroll_stmt = $conn->prepare($payroll_query);
$payroll_stmt->bind_param("i", $user_id);
$payroll_stmt->execute();
$payroll_records = $payroll_stmt->get_result();

// Get unread notifications count
$notif_query = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$unread_count = $notif_stmt->get_result()->fetch_assoc()['unread'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payroll | HRMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        
        .container { display: flex; min-height: 100vh; }
        
        .main { flex: 1; padding: 30px; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .header h1 { color: #0a4f0d; }
        
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0a4f0d; color: white; }
        
        .status-badge { padding: 5px 10px; border-radius: 3px; font-size: 12px; display: inline-block; }
        .status-draft { background: #e0e0e0; color: #666; }
        .status-processed { background: #fff3cd; color: #856404; }
        .status-paid { background: #d4edda; color: #155724; }
        
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; }
        .btn-view { background: #0a4f0d; color: white; }
        .btn-view:hover { background: #06600e; }
        
        .notif-badge { background: red; color: white; padding: 3px 8px; border-radius: 10px; font-size: 12px; }
        
        .payslip-details { display: none; background: #f9f9f9; padding: 20px; margin-top: 15px; border-radius: 5px; }
        .payslip-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #ddd; }
        .payslip-total { font-weight: bold; font-size: 18px; color: #0a4f0d; }
    </style>
</head>
<body>

<div class="container">
    <div class="sidebar" id="sidebar">
    <button class="toggle-btn" id="toggleBtn">
        <i class="fa-solid fa-bars"></i>
    </button>

    <div class="sidebar-header">
        <img src="../public/images/PLSP_LOGO 1.png" alt="PLSP Logo" class="sidebar-logo">
        <h2 class="sidebar-title">HRMS</h2>
    </div>
        <a href="employee_dashboard.php"><i class="fa-solid fa-chart-line"></i><span>Dashboard</span></a>
        <a href="employee_profile.php"><i class="fa-solid fa-user"></i><span>My Profile</span></a>
        <a href="employee_201_file.php"><i class="fa-solid fa-file-lines"></i><span>201 File / PDS</span></a>
        <a href="employee_attendance.php"><i class="fa-solid fa-calendar-check"></i><span>Attendance</span></a>
        <a href="employee_leave.php"><i class="fa-solid fa-umbrella-beach"></i><span>Leave</span></a>
        <a href="employee_payroll.php" class="active"><i class="fa-solid fa-money-bill-wave"></i><span>Payroll</span></a>
        <a href="employee_notifications.php"><i class="fa-solid fa-bell"></i><span>Notifications</span> <?php if($unread_count > 0) echo "<span class='notif-badge'>$unread_count</span>"; ?></a>
        <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Log Out</span></a>
    </div>

    <div class="main">
        <div class="header">
            <h1>My Payroll</h1>
            <p>View your salary and payslip history</p>
        </div>

        <!-- Payroll History -->
        <div class="card">
            <h2>Payroll History</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Basic Salary</th>
                        <th>Allowances</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th>Payment Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($payroll_records->num_rows > 0): ?>
                        <?php while($payroll = $payroll_records->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $payroll['month'] . ' ' . $payroll['year']; ?></td>
                                <td>₱<?php echo number_format($payroll['basic_salary'], 2); ?></td>
                                <td>₱<?php echo number_format($payroll['allowances'], 2); ?></td>
                                <td>₱<?php echo number_format($payroll['deductions'], 2); ?></td>
                                <td><strong>₱<?php echo number_format($payroll['net_salary'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $payroll['status']; ?>">
                                        <?php echo ucfirst($payroll['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $payroll['payment_date'] ? date('M j, Y', strtotime($payroll['payment_date'])) : '-'; ?></td>
                                <td>
                                    <button class="btn btn-view" onclick="togglePayslip(<?php echo $payroll['id']; ?>)">View Details</button>
                                </td>
                            </tr>
                            <tr id="payslip-<?php echo $payroll['id']; ?>" style="display: none;">
                                <td colspan="8">
                                    <div class="payslip-details">
                                        <h3>Payslip Details - <?php echo $payroll['month'] . ' ' . $payroll['year']; ?></h3>
                                        <hr style="margin: 15px 0;">
                                        
                                        <div class="payslip-row">
                                            <span>Employee Name:</span>
                                            <span><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></span>
                                        </div>
                                        <div class="payslip-row">
                                            <span>Employee ID:</span>
                                            <span><?php echo $user['employee_id']; ?></span>
                                        </div>
                                        <div class="payslip-row">
                                            <span>Department:</span>
                                            <span><?php echo $user['department']; ?></span>
                                        </div>
                                        
                                        <hr style="margin: 15px 0;">
                                        <h4>Earnings</h4>
                                        <div class="payslip-row">
                                            <span>Basic Salary:</span>
                                            <span>₱<?php echo number_format($payroll['basic_salary'], 2); ?></span>
                                        </div>
                                        <div class="payslip-row">
                                            <span>Allowances:</span>
                                            <span>₱<?php echo number_format($payroll['allowances'], 2); ?></span>
                                        </div>
                                        
                                        <hr style="margin: 15px 0;">
                                        <h4>Deductions</h4>
                                        <div class="payslip-row">
                                            <span>Total Deductions:</span>
                                            <span>₱<?php echo number_format($payroll['deductions'], 2); ?></span>
                                        </div>
                                        
                                        <hr style="margin: 15px 0;">
                                        <div class="payslip-row payslip-total">
                                            <span>Net Salary:</span>
                                            <span>₱<?php echo number_format($payroll['net_salary'], 2); ?></span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">No payroll records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>

      const sidebar = document.getElementById("sidebar");
  const toggleBtn = document.getElementById("toggleBtn");

  // Restore state on load
  if (localStorage.getItem("sidebarCollapsed") === "true") {
    sidebar.classList.add("collapsed");
  }

  toggleBtn.addEventListener("click", () => {
    if (window.innerWidth <= 768) {
      sidebar.classList.toggle("open");
    } else {
      sidebar.classList.toggle("collapsed");
      localStorage.setItem(
        "sidebarCollapsed",
        sidebar.classList.contains("collapsed")
      );
    }
  });

function togglePayslip(id) {
    const row = document.getElementById('payslip-' + id);
    if (row.style.display === 'none') {
        row.style.display = 'table-row';
    } else {
        row.style.display = 'none';
    }
}
</script>

</body>
</html>