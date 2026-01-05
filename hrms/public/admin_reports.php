<?php
session_start();
include("../includes/db_connect.php");
include '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get admin info
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Handle report generation
$report_data = null;
$report_type = null;

if (isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'];
    
    if ($report_type == 'attendance') {
        $month = $_POST['month'];
        $year = $_POST['year'];
        
        $stmt = $conn->prepare("CALL sp_monthly_attendance_report(?, ?)");
        $stmt->bind_param("si", $month, $year);
        $stmt->execute();
        $report_data = $stmt->get_result();
        $stmt->close();
        
    } elseif ($report_type == 'leave_balance') {
        $emp_id = $_POST['employee_id'];
        $year = $_POST['year'];
        
        $stmt = $conn->prepare("CALL sp_calculate_leave_balance(?, ?)");
        $stmt->bind_param("ii", $emp_id, $year);
        $stmt->execute();
        $report_data = $stmt->get_result();
        $stmt->close();
    }
}

// Handle bulk payroll generation
if (isset($_POST['bulk_generate'])) {
    $month = $_POST['bulk_month'];
    $year = $_POST['bulk_year'];
    $default_salary = $_POST['default_salary'];
    
    $stmt = $conn->prepare("CALL sp_process_monthly_payroll(?, ?, ?)");
    $stmt->bind_param("sid", $month, $year, $default_salary);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Bulk payroll generated for $month $year!";
    } else {
        $_SESSION['error'] = "Failed to generate bulk payroll.";
    }
    $stmt->close();
    
    header("Location: admin_reports.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics | HRMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        
        .container { display: flex; min-height: 100vh; }
        
        .main { flex: 1; margin-left: 250px; padding: 30px; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header h1 { color: #1a1a2e; }
        
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h3 { color: #1a1a2e; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f39c12; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .report-form { background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #3498db; }
        .report-form h4 { margin-bottom: 15px; color: #2c3e50; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #2c3e50; font-size: 14px; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: bold; }
        .btn-primary { background: #3498db; color: white; width: 100%; }
        .btn-success { background: #27ae60; color: white; width: 100%; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; font-size: 13px; }
        th { background: #34495e; color: white; font-weight: bold; }
        tr:hover { background: #f8f9fa; }
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .info-box { background: #d1ecf1; padding: 15px; border-radius: 5px; border-left: 4px solid #17a2b8; margin-bottom: 20px; }
        .info-box h4 { color: #0c5460; margin-bottom: 10px; }
        .info-box p { color: #0c5460; font-size: 14px; line-height: 1.6; }
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
        <h2 class="sidebar-title">HRMS Admin</h2>
    </div>
        <a href="admin_dashboard.php"><i class="fa-solid fa-chart-line"></i><span>Dashboard</span></a>
        <a href="admin_users.php"><i class="fa-solid fa-user"></i><span>Manage Users</span></a>
        <a href="admin_attendance.php"><i class="fa-solid fa-calendar-check"></i><span>Manage Attendance</span></a>
        <a href="admin_leave.php"><i class="fa-solid fa-umbrella-beach"></i><span>Manage Leave</span></a>
        <a href="admin_payroll.php"><i class="fa-solid fa-money-bill-wave"></i><span>Manage Payroll</span></a>
        <a href="admin_salary_grades.php"><i class="fa-solid fa-chart-bar"></i><span>Salary Grades</span></a>
        <a href="admin_announcements.php"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
        <a href="admin_departments.php"><i class="fa-solid fa-building"></i><span>Departments</span></a>
        <a href="admin_reports.php" class="active"><i class="fa-solid fa-file-lines"></i><span>Reports & Analytics</span></a>
        <a href="admin_profile.php"><i class="fa-solid fa-user-circle"></i><span>My Profile</span></a>
        <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Log Out</span></a>
    </div>

    <div class="main">
        <div class="header">
            <h1>Reports & Analytics</h1>
            <p>Generate reports using stored procedures and view analytics</p>
        </div>

        <?php
        if (isset($_SESSION['success'])) {
            echo "<div class='alert alert-success'>" . $_SESSION['success'] . "</div>";
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo "<div class='alert alert-error'>" . $_SESSION['error'] . "</div>";
            unset($_SESSION['error']);
        }
        ?>

        <div class="info-box">
            <h4>📊 About This Feature</h4>
            <p>This page demonstrates the use of <strong>Stored Procedures</strong> and <strong>Views</strong> - advanced SQL features required for the project evaluation. All reports are generated using database procedures for better performance and security.</p>
        </div>

        <!-- Report Generation Forms -->
        <div class="card">
            <h3>📋 Generate Reports</h3>
            
            <div class="form-grid">
                <!-- Attendance Report -->
                <div class="report-form">
                    <h4>📅 Monthly Attendance Report</h4>
                    <form method="POST">
                        <input type="hidden" name="report_type" value="attendance">
                        
                        <div class="form-group">
                            <label>Month</label>
                            <select name="month" required>
                                <?php
                                $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                                foreach($months as $m) {
                                    $selected = ($m == date('F')) ? 'selected' : '';
                                    echo "<option value='$m' $selected>$m</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Year</label>
                            <select name="year" required>
                                <?php
                                $current_year = date('Y');
                                for($y = $current_year - 2; $y <= $current_year; $y++) {
                                    echo "<option value='$y'>$y</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <button type="submit" name="generate_report" class="btn btn-primary">📊 Generate</button>
                    </form>
                </div>

                <!-- Leave Balance Report -->
                <div class="report-form">
                    <h4>🏖️ Leave Balance Report</h4>
                    <form method="POST">
                        <input type="hidden" name="report_type" value="leave_balance">
                        
                        <div class="form-group">
                            <label>Employee</label>
                            <select name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php
                                $employees = $conn->query("SELECT id, employee_id, first_name, last_name FROM users WHERE role = 'employee' ORDER BY first_name");
                                while($emp = $employees->fetch_assoc()) {
                                    echo "<option value='" . $emp['id'] . "'>" . $emp['employee_id'] . " - " . $emp['first_name'] . " " . $emp['last_name'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Year</label>
                            <select name="year" required>
                                <?php
                                for($y = $current_year - 1; $y <= $current_year + 1; $y++) {
                                    echo "<option value='$y'>$y</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <button type="submit" name="generate_report" class="btn btn-primary">📊 Generate</button>
                    </form>
                </div>

                <!-- Bulk Payroll Generation -->
                <div class="report-form" style="border-left-color: #27ae60;">
                    <h4>💰 Bulk Payroll Generation</h4>
                    <form method="POST">
                        <div class="form-group">
                            <label>Month</label>
                            <select name="bulk_month" required>
                                <?php foreach($months as $m) echo "<option value='$m'>$m</option>"; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Year</label>
                            <select name="bulk_year" required>
                                <?php
                                for($y = $current_year; $y <= $current_year + 1; $y++) {
                                    echo "<option value='$y'>$y</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Default Salary</label>
                            <input type="number" name="default_salary" step="0.01" value="25000" required>
                        </div>
                        
                        <button type="submit" name="bulk_generate" class="btn btn-success">⚡ Generate for All</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Report Results -->
        <?php if ($report_data && $report_data->num_rows > 0): ?>
            <div class="card">
                <h3>📊 Report Results</h3>
                
                <?php if ($report_type == 'attendance'): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Total Days</th>
                                <th>Present</th>
                                <th>Late</th>
                                <th>Absent</th>
                                <th>Half Day</th>
                                <th>Attendance Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $report_data->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['employee_id']; ?></td>
                                    <td><?php echo $row['employee_name']; ?></td>
                                    <td><?php echo $row['department']; ?></td>
                                    <td><?php echo $row['total_days_recorded']; ?></td>
                                    <td><?php echo $row['present_days']; ?></td>
                                    <td><?php echo $row['late_days']; ?></td>
                                    <td><?php echo $row['absent_days']; ?></td>
                                    <td><?php echo $row['half_days']; ?></td>
                                    <td><strong><?php echo $row['attendance_rate']; ?>%</strong></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                
                <?php elseif ($report_type == 'leave_balance'): ?>
                    <?php $row = $report_data->fetch_assoc(); ?>
                    <div style="max-width: 600px; margin: 20px auto;">
                        <div style="background: #f8f9fa; padding: 30px; border-radius: 8px; text-align: center;">
                            <h2 style="color: #2c3e50; margin-bottom: 20px;">Leave Balance Report</h2>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 30px;">
                                <div>
                                    <div style="font-size: 36px; font-weight: bold; color: #3498db;"><?php echo $row['total_allowance']; ?></div>
                                    <div style="color: #7f8c8d; margin-top: 5px;">Total Allowance</div>
                                </div>
                                <div>
                                    <div style="font-size: 36px; font-weight: bold; color: #e74c3c;"><?php echo $row['used_days']; ?></div>
                                    <div style="color: #7f8c8d; margin-top: 5px;">Used Days</div>
                                </div>
                                <div>
                                    <div style="font-size: 36px; font-weight: bold; color: #27ae60;"><?php echo $row['remaining_balance']; ?></div>
                                    <div style="color: #7f8c8d; margin-top: 5px;">Remaining</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Quick Views -->
        <div class="card">
            <h3>👁️ Quick Views (Using Database Views)</h3>
            
            <div style="margin-top: 20px;">
                <h4 style="color: #2c3e50; margin-bottom: 15px;">Recent Leave Requests</h4>
                <?php
                $view_data = $conn->query("SELECT * FROM vw_leave_requests LIMIT 10");
                if ($view_data->num_rows > 0):
                ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Duration</th>
                                <th>Days</th>
                                <th>Status</th>
                                <th>Date Applied</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $view_data->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['employee_name']; ?><br><small><?php echo $row['employee_id']; ?></small></td>
                                    <td><?php echo ucfirst($row['leave_type']); ?></td>
                                    <td><?php echo date('M j', strtotime($row['start_date'])) . ' - ' . date('M j, Y', strtotime($row['end_date'])); ?></td>
                                    <td><?php echo $row['total_days']; ?></td>
                                    <td><?php echo ucfirst($row['status']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 20px;">No leave requests found</p>
                <?php endif; ?>
            </div>
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
</script>
</body>
</html>