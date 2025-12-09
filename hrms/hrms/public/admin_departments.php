<?php
session_start();
include("../includes/db_connect.php");

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

// Get department statistics
$dept_query = "SELECT 
                department,
                COUNT(*) as employee_count,
                COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_count
                FROM users 
                WHERE department IS NOT NULL AND department != ''
                GROUP BY department 
                ORDER BY employee_count DESC";
$departments = $conn->query($dept_query);

$total_depts = $departments->num_rows;
$total_employees = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'employee'")->fetch_assoc()['count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments | HRMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        
        .container { display: flex; min-height: 100vh; }
        
        .sidebar { width: 250px; background: linear-gradient(to bottom, #1a1a2e, #16213e); color: white; padding: 20px; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar h2 { margin-bottom: 30px; text-align: center; color: #f39c12; }
        .sidebar a { display: block; padding: 12px 15px; color: white; text-decoration: none; margin-bottom: 5px; border-radius: 5px; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(243, 156, 18, 0.2); }
        .sidebar .logout { margin-top: 30px; background: rgba(231, 76, 60, 0.3); }
        
        .main { flex: 1; margin-left: 250px; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header h1 { color: #1a1a2e; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-number { font-size: 32px; font-weight: bold; margin: 10px 0; color: #3498db; }
        .stat-label { color: #7f8c8d; font-size: 13px; }
        
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h3 { color: #1a1a2e; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f39c12; }
        
        .dept-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .dept-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: 0.3s; cursor: pointer; }
        .dept-card:hover { transform: translateY(-5px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); }
        
        .dept-card:nth-child(2) { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .dept-card:nth-child(3) { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .dept-card:nth-child(4) { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .dept-card:nth-child(5) { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .dept-card:nth-child(6) { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
        .dept-card:nth-child(7) { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
        .dept-card:nth-child(8) { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); }
        .dept-card:nth-child(9) { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); }
        .dept-card:nth-child(10) { background: linear-gradient(135deg, #ff6e7f 0%, #bfe9ff 100%); }
        
        .dept-name { font-size: 18px; font-weight: bold; margin-bottom: 15px; }
        .dept-stats { display: flex; justify-content: space-between; margin-top: 20px; }
        .dept-stat { text-align: center; }
        .dept-stat-number { font-size: 28px; font-weight: bold; }
        .dept-stat-label { font-size: 12px; opacity: 0.9; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #34495e; color: white; font-weight: bold; }
        tr:hover { background: #f8f9fa; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; }
        .modal-content { background: white; width: 800px; margin: 50px auto; padding: 30px; border-radius: 8px; max-height: 90vh; overflow-y: auto; }
        .modal-header { margin-bottom: 20px; border-bottom: 2px solid #f39c12; padding-bottom: 10px; }
        .close { float: right; font-size: 28px; cursor: pointer; color: #999; }
        .close:hover { color: #333; }
        
        .role-badge { padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .role-admin { background: #e74c3c; color: white; }
        .role-employee { background: #3498db; color: white; }
    </style>
</head>
<body>

<div class="container">
    <div class="sidebar">
        <h2>⚡ ADMIN PANEL</h2>
        <a href="admin_dashboard.php">📊 Dashboard</a>
        <a href="admin_users.php">👥 Manage Users</a>
        <a href="admin_attendance.php">📅 Manage Attendance</a>
        <a href="admin_leave.php">🏖️ Manage Leave</a>
        <a href="admin_payroll.php">💰 Manage Payroll</a>
        <a href="admin_announcements.php">📢 Announcements</a>
        <a href="admin_departments.php" class="active">🏢 Departments</a>
        <a href="admin_profile.php">👤 My Profile</a>
        <a href="logout.php" class="logout">🚪 Log Out</a>
    </div>

    <div class="main">
        <div class="header">
            <div>
                <h1>Departments Overview</h1>
                <p>View department statistics and employee distribution</p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">TOTAL DEPARTMENTS</div>
                <div class="stat-number"><?php echo $total_depts; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">TOTAL EMPLOYEES</div>
                <div class="stat-number" style="color: #27ae60;"><?php echo $total_employees; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">AVG PER DEPARTMENT</div>
                <div class="stat-number" style="color: #f39c12;"><?php echo $total_depts > 0 ? round($total_employees / $total_depts) : 0; ?></div>
            </div>
        </div>

        <!-- Department Cards -->
        <div class="card">
            <h3>🏢 All Departments</h3>
            
            <div class="dept-grid">
                <?php 
                $departments->data_seek(0); // Reset pointer
                while($dept = $departments->fetch_assoc()): 
                ?>
                    <div class="dept-card" onclick="viewDepartmentEmployees('<?php echo htmlspecialchars($dept['department'], ENT_QUOTES); ?>')">
                        <div class="dept-name"><?php echo htmlspecialchars($dept['department']); ?></div>
                        <div class="dept-stats">
                            <div class="dept-stat">
                                <div class="dept-stat-number"><?php echo $dept['employee_count']; ?></div>
                                <div class="dept-stat-label">Total Staff</div>
                            </div>
                            <div class="dept-stat">
                                <div class="dept-stat-number"><?php echo $dept['admin_count']; ?></div>
                                <div class="dept-stat-label">Admins</div>
                            </div>
                            <div class="dept-stat">
                                <div class="dept-stat-number"><?php echo round(($dept['employee_count'] / $total_employees) * 100); ?>%</div>
                                <div class="dept-stat-label">Of Total</div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="card">
            <h3>📊 Detailed Breakdown</h3>
            
            <table>
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Total Employees</th>
                        <th>Admins</th>
                        <th>Regular Staff</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $departments->data_seek(0); // Reset pointer again
                    while($dept = $departments->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dept['department']); ?></strong></td>
                            <td><?php echo $dept['employee_count']; ?></td>
                            <td><?php echo $dept['admin_count']; ?></td>
                            <td><?php echo $dept['employee_count'] - $dept['admin_count']; ?></td>
                            <td><?php echo round(($dept['employee_count'] / $total_employees) * 100, 1); ?>%</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Department Employees Modal -->
<div id="departmentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="document.getElementById('departmentModal').style.display='none'">&times;</span>
            <h2 id="modalDepartmentName">Department Employees</h2>
        </div>
        
        <div id="employeeListContent">
            <p style="text-align: center; padding: 20px;">Loading...</p>
        </div>
    </div>
</div>

<script>
function viewDepartmentEmployees(departmentName) {
    document.getElementById('modalDepartmentName').textContent = departmentName + ' - Employees';
    document.getElementById('departmentModal').style.display = 'block';
    
    fetch('get_department_employees.php?department=' + encodeURIComponent(departmentName))
        .then(response => response.json())
        .then(data => {
            let html = '<table><thead><tr><th>Employee ID</th><th>Name</th><th>Email</th><th>Contact</th><th>Role</th></tr></thead><tbody>';
            
            if (data.length > 0) {
                data.forEach(emp => {
                    html += `<tr>
                        <td>${emp.employee_id}</td>
                        <td>${emp.first_name} ${emp.last_name}</td>
                        <td>${emp.email}</td>
                        <td>${emp.contact_number}</td>
                        <td><span class="role-badge role-${emp.role}">${emp.role.toUpperCase()}</span></td>
                    </tr>`;
                });
            } else {
                html += '<tr><td colspan="5" style="text-align: center; color: #999;">No employees found</td></tr>';
            }
            
            html += '</tbody></table>';
            document.getElementById('employeeListContent').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('employeeListContent').innerHTML = '<p style="text-align: center; color: red;">Error loading employees</p>';
        });
}

window.onclick = function(event) {
    const modal = document.getElementById('departmentModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

</body>
</html>