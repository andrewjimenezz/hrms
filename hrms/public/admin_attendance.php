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

// Get filter parameters
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get attendance records for selected date
$attendance_query = "SELECT a.*, u.employee_id, u.first_name, u.last_name, u.department 
                     FROM attendance a 
                     JOIN users u ON a.user_id = u.id 
                     WHERE a.date = ?";

if ($filter_status !== 'all') {
    $attendance_query .= " AND a.status = '$filter_status'";
}

$attendance_query .= " ORDER BY u.last_name ASC";

$att_stmt = $conn->prepare($attendance_query);
$att_stmt->bind_param("s", $filter_date);
$att_stmt->execute();
$attendance_records = $att_stmt->get_result();

// Get all employees who haven't clocked in (only show for today, not future or past dates)
$today = date('Y-m-d');
$show_unmarked = ($filter_date === $today); // Only show for TODAY

if ($show_unmarked) {
    $absent_query = "SELECT u.id, u.employee_id, u.first_name, u.last_name, u.department 
                     FROM users u 
                     WHERE u.role = 'employee' 
                     AND u.id NOT IN (SELECT user_id FROM attendance WHERE date = ?)
                     ORDER BY u.last_name ASC";
    $absent_stmt = $conn->prepare($absent_query);
    $absent_stmt->bind_param("s", $filter_date);
    $absent_stmt->execute();
    $absent_employees = $absent_stmt->get_result();
} else {
    // For past or future dates, don't show unmarked employees
    $absent_employees = null;
}

// Get attendance statistics for the date
$stats_query = "SELECT 
    COUNT(CASE WHEN status = 'present' THEN 1 END) as present_count,
    COUNT(CASE WHEN status = 'late' THEN 1 END) as late_count,
    COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_count,
    COUNT(CASE WHEN status = 'half-day' THEN 1 END) as half_day_count
    FROM attendance WHERE date = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("s", $filter_date);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

$total_employees = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'employee'")->fetch_assoc()['count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attendance | HRMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        
        .container { display: flex; min-height: 100vh; }
        
        .main { flex: 1; margin-left: 250px; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header h1 { color: #1a1a2e; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-number { font-size: 32px; font-weight: bold; margin: 10px 0; }
        .stat-label { color: #7f8c8d; font-size: 13px; }
        .stat-present { color: #27ae60; }
        .stat-late { color: #f39c12; }
        .stat-absent { color: #e74c3c; }
        .stat-half { color: #3498db; }
        
        .filters { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .filters input, .filters select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .filters button { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .filters button:hover { background: #2980b9; }
        
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h3 { color: #1a1a2e; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f39c12; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #34495e; color: white; font-weight: bold; }
        tr:hover { background: #f8f9fa; }
        
        .status-badge { padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .status-present { background: #d4edda; color: #155724; }
        .status-late { background: #fff3cd; color: #856404; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .status-half-day { background: #d1ecf1; color: #0c5460; }
        
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; font-weight: bold; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; }
        .modal-content { background: white; width: 500px; margin: 80px auto; padding: 30px; border-radius: 8px; }
        .modal-header { margin-bottom: 20px; border-bottom: 2px solid #f39c12; padding-bottom: 10px; }
        .close { float: right; font-size: 28px; cursor: pointer; color: #999; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #2c3e50; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        
        .action-buttons { display: flex; gap: 5px; }
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
        <a href="admin_attendance.php" class="active"><i class="fa-solid fa-calendar-check"></i><span>Manage Attendance</span></a>
        <a href="admin_leave.php"><i class="fa-solid fa-umbrella-beach"></i><span>Manage Leave</span></a>
        <a href="admin_payroll.php"><i class="fa-solid fa-money-bill-wave"></i><span>Manage Payroll</span></a>
        <a href="admin_salary_grades.php"><i class="fa-solid fa-chart-bar"></i><span>Salary Grades</span></a>
        <a href="admin_announcements.php"><i class="fa-solid fa-bullhorn"></i><span>Announcements</span></a>
        <a href="admin_departments.php"><i class="fa-solid fa-building"></i><span>Departments</span></a>
        <a href="admin_reports.php"><i class="fa-solid fa-file-lines"></i><span>Reports & Analytics</span></a>
        <a href="admin_profile.php"><i class="fa-solid fa-user-circle"></i><span>My Profile</span></a>
        <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Log Out</span></a>
    </div>

    <div class="main">
        <div class="header">
            <div>
                <h1>Manage Attendance</h1>
                <p>View and manage employee attendance records</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-warning" onclick="if(confirm('Mark all unmarked employees as absent for yesterday?')) window.location.href='auto_mark_absent.php'">
                    ⚠️ Auto-Mark Absent
                </button>
                <button class="btn btn-success" onclick="document.getElementById('markAttendanceModal').style.display='block'">
                    ➕ Mark Attendance
                </button>
            </div>
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
        if (isset($_SESSION['info'])) {
            echo "<div class='alert alert-info'>" . $_SESSION['info'] . "</div>";
            unset($_SESSION['info']);
        }
        ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">PRESENT</div>
                <div class="stat-number stat-present"><?php echo $stats['present_count']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">LATE</div>
                <div class="stat-number stat-late"><?php echo $stats['late_count']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">ABSENT</div>
                <div class="stat-number stat-absent"><?php echo $stats['absent_count'] + ($show_unmarked && $absent_employees ? $absent_employees->num_rows : 0); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">HALF DAY</div>
                <div class="stat-number stat-half"><?php echo $stats['half_day_count']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">TOTAL EMPLOYEES</div>
                <div class="stat-number"><?php echo $total_employees; ?></div>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="filters">
            <label>Date:</label>
            <input type="date" name="date" value="<?php echo $filter_date; ?>" required>
            
            <label>Status:</label>
            <select name="status">
                <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="present" <?php echo $filter_status == 'present' ? 'selected' : ''; ?>>Present</option>
                <option value="late" <?php echo $filter_status == 'late' ? 'selected' : ''; ?>>Late</option>
                <option value="absent" <?php echo $filter_status == 'absent' ? 'selected' : ''; ?>>Absent</option>
                <option value="half-day" <?php echo $filter_status == 'half-day' ? 'selected' : ''; ?>>Half Day</option>
            </select>
            
            <button type="submit">🔍 Filter</button>
            <a href="admin_attendance.php" class="btn btn-warning">Reset</a>
        </form>

        <!-- Attendance Records -->
        <div class="card">
            <h3>📋 Attendance Records - <?php echo date('F j, Y', strtotime($filter_date)); ?></h3>
            
            <table>
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($attendance_records->num_rows > 0): ?>
                        <?php while($record = $attendance_records->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['employee_id']); ?></td>
                                <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['department']); ?></td>
                                <td><?php echo $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '-'; ?></td>
                                <td><?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '-'; ?></td>
                                <td><span class="status-badge status-<?php echo $record['status']; ?>"><?php echo ucfirst($record['status']); ?></span></td>
                                <td><?php echo htmlspecialchars($record['remarks']) ?: '-'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-warning btn-sm" onclick="editAttendance(<?php echo $record['id']; ?>)">✏️ Edit</button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteAttendance(<?php echo $record['id']; ?>)">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align: center; color: #999;">No attendance records found for this date</td></tr>
                    <?php endif; ?>
                    
                    <!-- Show unmarked employees (only for today or future dates) -->
                    <?php if($show_unmarked && $absent_employees && $absent_employees->num_rows > 0): ?>
                        <?php while($emp = $absent_employees->fetch_assoc()): ?>
                            <tr style="background: #fff3cd;">
                                <td><?php echo htmlspecialchars($emp['employee_id']); ?></td>
                                <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['department']); ?></td>
                                <td>-</td>
                                <td>-</td>
                                <td><span class="status-badge status-absent">Not Marked</span></td>
                                <td>No attendance recorded</td>
                                <td>
                                    <button class="btn btn-success btn-sm" onclick="markAttendanceFor(<?php echo $emp['id']; ?>, '<?php echo $emp['first_name'] . ' ' . $emp['last_name']; ?>')">
                                        ✅ Mark
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Mark Attendance Modal -->
<div id="markAttendanceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="document.getElementById('markAttendanceModal').style.display='none'">&times;</span>
            <h2>➕ Mark Attendance</h2>
        </div>
        
        <form action="process_mark_attendance.php" method="POST">
            <div class="form-group">
                <label>Employee *</label>
                <select name="user_id" required>
                    <option value="">Select Employee</option>
                    <?php
                    $all_employees = $conn->query("SELECT id, employee_id, first_name, last_name FROM users WHERE role = 'employee' ORDER BY first_name");
                    while($emp = $all_employees->fetch_assoc()) {
                        echo "<option value='" . $emp['id'] . "'>" . $emp['employee_id'] . " - " . $emp['first_name'] . " " . $emp['last_name'] . "</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Date *</label>
                <input type="date" name="date" value="<?php echo $filter_date; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Time In</label>
                <input type="time" name="time_in">
            </div>
            
            <div class="form-group">
                <label>Time Out</label>
                <input type="time" name="time_out">
            </div>
            
            <div class="form-group">
                <label>Status *</label>
                <select name="status" required>
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="absent">Absent</option>
                    <option value="half-day">Half Day</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Remarks</label>
                <textarea name="remarks" rows="3" placeholder="Optional notes..."></textarea>
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">✅ Mark Attendance</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('markAttendanceModal').style.display='none'">❌ Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Attendance Modal -->
<div id="editAttendanceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="document.getElementById('editAttendanceModal').style.display='none'">&times;</span>
            <h2>✏️ Edit Attendance</h2>
        </div>
        
        <form action="process_edit_attendance.php" method="POST">
            <input type="hidden" name="attendance_id" id="edit_attendance_id">
            
            <div class="form-group">
                <label>Employee</label>
                <input type="text" id="edit_employee_name" disabled>
            </div>
            
            <div class="form-group">
                <label>Date</label>
                <input type="date" id="edit_date" disabled>
            </div>
            
            <div class="form-group">
                <label>Time In</label>
                <input type="time" name="time_in" id="edit_time_in">
            </div>
            
            <div class="form-group">
                <label>Time Out</label>
                <input type="time" name="time_out" id="edit_time_out">
            </div>
            
            <div class="form-group">
                <label>Status *</label>
                <select name="status" id="edit_status" required>
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="absent">Absent</option>
                    <option value="half-day">Half Day</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Remarks</label>
                <textarea name="remarks" id="edit_remarks" rows="3"></textarea>
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">💾 Save Changes</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('editAttendanceModal').style.display='none'">❌ Cancel</button>
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


function markAttendanceFor(userId, userName) {
    const select = document.querySelector('#markAttendanceModal select[name="user_id"]');
    select.value = userId;
    document.getElementById('markAttendanceModal').style.display = 'block';
}

function editAttendance(attendanceId) {
    fetch('get_attendance.php?id=' + attendanceId)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_attendance_id').value = data.id;
            document.getElementById('edit_employee_name').value = data.employee_name;
            document.getElementById('edit_date').value = data.date;
            document.getElementById('edit_time_in').value = data.time_in || '';
            document.getElementById('edit_time_out').value = data.time_out || '';
            document.getElementById('edit_status').value = data.status;
            document.getElementById('edit_remarks').value = data.remarks || '';
            
            document.getElementById('editAttendanceModal').style.display = 'block';
        });
}

function deleteAttendance(id) {
    if (confirm('Are you sure you want to delete this attendance record?')) {
        window.location.href = 'process_delete_attendance.php?id=' + id;
    }
}

window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
}
</script>

</body>
</html>