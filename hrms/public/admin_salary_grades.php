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

// Get all salary grades
$salary_grades = $conn->query("SELECT * FROM salary_grades ORDER BY salary_grade, step_increment");

// Get employees with their salary grades
$employees = $conn->query("SELECT u.id, u.employee_id, u.first_name, u.last_name, u.department, u.salary_grade, u.step_increment, sg.monthly_salary 
                           FROM users u 
                           LEFT JOIN salary_grades sg ON u.salary_grade = sg.salary_grade AND u.step_increment = sg.step_increment 
                           WHERE u.role = 'employee' 
                           ORDER BY u.last_name");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Grades | HRMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        
        .container { display: flex; min-height: 100vh; }
        
        .main { flex: 1; margin-left: 250px; padding: 30px; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header h1 { color: #1a1a2e; }
        
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h3 { color: #1a1a2e; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f39c12; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; font-size: 13px; }
        th { background: #34495e; color: white; font-weight: bold; }
        tr:hover { background: #f8f9fa; }
        
        .info-box { background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #f39c12; margin-bottom: 20px; }
        .info-box h4 { color: #856404; margin-bottom: 10px; }
        
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; font-weight: bold; }
        .btn-primary { background: #3498db; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 11px; }
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .sg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-top: 20px; }
        .sg-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 8px; text-align: center; }
        .sg-card .grade { font-size: 24px; font-weight: bold; }
        .sg-card .salary { font-size: 14px; margin-top: 5px; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; }
        .modal-content { background: white; width: 500px; margin: 80px auto; padding: 30px; border-radius: 8px; }
        .modal-header { margin-bottom: 20px; border-bottom: 2px solid #f39c12; padding-bottom: 10px; }
        .close { float: right; font-size: 28px; cursor: pointer; color: #999; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #2c3e50; }
        .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
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
        <a href="admin_salary_grades.php" class="active"><i class="fa-solid fa-chart-bar"></i><span>Salary Grades</span></a>
        <a href="admin_announcements.php"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
        <a href="admin_departments.php"><i class="fa-solid fa-building"></i><span>Departments</span></a>
        <a href="admin_reports.php"><i class="fa-solid fa-file-lines"></i><span>Reports & Analytics</span></a>
        <a href="admin_profile.php"><i class="fa-solid fa-user-circle"></i><span>My Profile</span></a>
        <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Log Out</span></a>
    </div>

    <div class="main">
        <div class="header">
            <h1>Salary Grade System</h1>
            <p>Manage employee salary grades based on Philippine Government standards</p>
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
            <h4>📋 About Salary Grade System</h4>
            <p>The Salary Standardization Law (SSL) defines salary grades (SG) from 1 to 33, each with 8 step increments. Employee salaries are automatically calculated based on their assigned SG and step, plus government deductions (GSIS, Pag-IBIG, PhilHealth, Withholding Tax).</p>
        </div>

        <!-- Salary Grade Matrix (Sample View) -->
        <div class="card">
            <h3>💵 Salary Grade Matrix (Sample)</h3>
            <div class="sg-grid">
                <?php
                $sample_grades = $conn->query("SELECT * FROM salary_grades WHERE step_increment = 1 ORDER BY salary_grade LIMIT 12");
                while($sg = $sample_grades->fetch_assoc()):
                ?>
                    <div class="sg-card">
                        <div class="grade">SG <?php echo $sg['salary_grade']; ?></div>
                        <div class="salary">₱<?php echo number_format($sg['monthly_salary'], 0); ?></div>
                        <small style="opacity: 0.8;">Step 1</small>
                    </div>
                <?php endwhile; ?>
            </div>
            <p style="margin-top: 20px; text-align: center; color: #7f8c8d; font-size: 13px;">
                * Showing Step 1 salaries only. Each grade has 8 steps with incremental increases.
            </p>
        </div>

        <!-- Employees with Salary Grades -->
        <div class="card">
            <h3>👥 Employee Salary Assignments</h3>
            
            <table>
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Salary Grade</th>
                        <th>Step</th>
                        <th>Monthly Salary</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($employees->num_rows > 0): ?>
                        <?php while($emp = $employees->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                                <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['department']); ?></td>
                                <td><?php echo $emp['salary_grade'] ? 'SG ' . $emp['salary_grade'] : '<span style="color: #e74c3c;">Not Set</span>'; ?></td>
                                <td><?php echo $emp['step_increment'] ?? '-'; ?></td>
                                <td><?php echo $emp['monthly_salary'] ? '₱' . number_format($emp['monthly_salary'], 2) : '-'; ?></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="assignSalaryGrade(<?php echo $emp['id']; ?>, '<?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>')">
                                        <?php echo $emp['salary_grade'] ? '✏️ Update' : '➕ Assign'; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; color: #999;">No employees found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Assign Salary Grade Modal -->
<div id="assignSGModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="document.getElementById('assignSGModal').style.display='none'">&times;</span>
            <h2>💵 Assign Salary Grade</h2>
        </div>
        
        <form action="process_assign_salary_grade.php" method="POST">
            <input type="hidden" name="employee_id" id="assign_employee_id">
            
            <div class="form-group">
                <label>Employee</label>
                <input type="text" id="assign_employee_name" disabled style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f5f5f5;">
            </div>
            
            <div class="form-group">
                <label>Salary Grade *</label>
                <select name="salary_grade" id="salary_grade" required onchange="updateSteps()">
                    <option value="">Select Salary Grade</option>
                    <?php
                    $grades = $conn->query("SELECT DISTINCT salary_grade FROM salary_grades ORDER BY salary_grade");
                    while($g = $grades->fetch_assoc()) {
                        echo "<option value='" . $g['salary_grade'] . "'>SG " . $g['salary_grade'] . "</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Step Increment *</label>
                <select name="step_increment" id="step_increment" required onchange="updateSalaryPreview()">
                    <option value="">Select Step</option>
                    <?php for($i=1; $i<=8; $i++) echo "<option value='$i'>Step $i</option>"; ?>
                </select>
            </div>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                <strong>Monthly Salary Preview:</strong>
                <div id="salary_preview" style="font-size: 24px; color: #27ae60; font-weight: bold; margin-top: 10px;">-</div>
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary" style="flex: 1; padding: 12px;">💾 Save</button>
                <button type="button" class="btn" style="flex: 1; padding: 12px; background: #95a5a6; color: white;" onclick="document.getElementById('assignSGModal').style.display='none'">❌ Cancel</button>
            </div>
        </form>
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


function assignSalaryGrade(empId, empName) {
    document.getElementById('assign_employee_id').value = empId;
    document.getElementById('assign_employee_name').value = empName;
    document.getElementById('assignSGModal').style.display = 'block';
}

function updateSalaryPreview() {
    const grade = document.getElementById('salary_grade').value;
    const step = document.getElementById('step_increment').value;
    
    if (grade && step) {
        fetch('get_salary_amount.php?grade=' + grade + '&step=' + step)
            .then(response => response.json())
            .then(data => {
                document.getElementById('salary_preview').textContent = '₱' + parseFloat(data.salary).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            });
    }
}

function updateSteps() {
    updateSalaryPreview();
}

window.onclick = function(event) {
    const modal = document.getElementById('assignSGModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

</body>
</html>