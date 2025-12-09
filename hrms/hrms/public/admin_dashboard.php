<?php
session_start();
include("../includes/db_connect.php");

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get admin information
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Get total counts
$total_employees = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'employee'")->fetch_assoc()['count'];
$total_departments = $conn->query("SELECT COUNT(DISTINCT department) as count FROM users WHERE department IS NOT NULL AND department != ''")->fetch_assoc()['count'];
$pending_leaves = $conn->query("SELECT COUNT(*) as count FROM leave_requests WHERE status = 'pending'")->fetch_assoc()['count'];
$total_announcements = $conn->query("SELECT COUNT(*) as count FROM announcements")->fetch_assoc()['count'];

// Today's attendance stats
$today = date('Y-m-d');
$present_today = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$today' AND status IN ('present', 'late')")->fetch_assoc()['count'];
$absent_today = $total_employees - $present_today;

// Recent leave requests
$recent_leaves_query = "SELECT lr.*, u.first_name, u.last_name, u.employee_id 
                        FROM leave_requests lr 
                        JOIN users u ON lr.user_id = u.id 
                        ORDER BY lr.created_at DESC LIMIT 5";
$recent_leaves = $conn->query($recent_leaves_query);

// Recent employee registrations
$recent_users_query = "SELECT * FROM users WHERE role = 'employee' ORDER BY created_at DESC LIMIT 5";
$recent_users = $conn->query($recent_users_query);

// Attendance overview (last 7 days)
$attendance_stats = [];
for($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $present = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE date = '$date' AND status IN ('present', 'late')")->fetch_assoc()['count'];
    $attendance_stats[] = [
        'date' => date('M j', strtotime($date)),
        'present' => $present,
        'absent' => $total_employees - $present
    ];
}

// Department distribution
$dept_query = "SELECT department, COUNT(*) as count FROM users WHERE role = 'employee' AND department IS NOT NULL GROUP BY department ORDER BY count DESC LIMIT 5";
$dept_stats = $conn->query($dept_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | HRMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        
        .container { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar { width: 250px; background: linear-gradient(to bottom, #1a1a2e, #16213e); color: white; padding: 20px; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar h2 { margin-bottom: 30px; text-align: center; color: #f39c12; }
        .sidebar a { display: block; padding: 12px 15px; color: white; text-decoration: none; margin-bottom: 5px; border-radius: 5px; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(243, 156, 18, 0.2); }
        .sidebar .logout { margin-top: 30px; background: rgba(231, 76, 60, 0.3); }
        
        /* Main Content */
        .main { flex: 1; margin-left: 250px; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header h1 { color: #1a1a2e; }
        .admin-info { display: flex; align-items: center; gap: 15px; }
        .admin-badge { background: #f39c12; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-left: 4px solid #3498db; }
        .stat-card.employees { border-left-color: #3498db; }
        .stat-card.departments { border-left-color: #9b59b6; }
        .stat-card.leaves { border-left-color: #e74c3c; }
        .stat-card.attendance { border-left-color: #27ae60; }
        .stat-number { font-size: 36px; font-weight: bold; color: #1a1a2e; margin: 10px 0; }
        .stat-label { color: #7f8c8d; font-size: 14px; text-transform: uppercase; }
        .stat-icon { font-size: 24px; }
        
        /* Content Grid */
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card h3 { color: #1a1a2e; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f39c12; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #f8f9fa; color: #1a1a2e; font-weight: bold; }
        tr:hover { background: #f8f9fa; }
        
        .status-badge { padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-present { background: #d4edda; color: #155724; }
        .status-absent { background: #f8d7da; color: #721c24; }
        
        /* Department List */
        .dept-item { display: flex; justify-content: space-between; padding: 12px; border-bottom: 1px solid #ecf0f1; }
        .dept-name { font-weight: bold; color: #34495e; }
        .dept-count { background: #3498db; color: white; padding: 3px 10px; border-radius: 15px; font-size: 12px; }
        
        /* Chart placeholder */
        .chart-container { height: 250px; display: flex; align-items: flex-end; justify-content: space-around; border-bottom: 2px solid #ecf0f1; padding: 20px 0; }
        .chart-bar { width: 60px; background: #3498db; border-radius: 5px 5px 0 0; position: relative; transition: 0.3s; }
        .chart-bar:hover { background: #2980b9; }
        .chart-label { text-align: center; margin-top: 10px; font-size: 12px; color: #7f8c8d; }
        .chart-value { position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px; font-weight: bold; color: #2c3e50; }
    </style>
</head>
<body>

<div class="container">
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <h2>⚡ ADMIN PANEL</h2>
        <a href="admin_dashboard.php" class="active">📊 Dashboard</a>
        <a href="admin_users.php">👥 Manage Users</a>
        <a href="admin_attendance.php">📅 Manage Attendance</a>
        <a href="admin_leave.php">🏖️ Manage Leave</a>
        <a href="admin_payroll.php">💰 Manage Payroll</a>
        <a href="admin_announcements.php">📢 Announcements</a>
        <a href="admin_departments.php">🏢 Departments</a>
        <a href="admin_profile.php">👤 My Profile</a>
        <a href="logout.php" class="logout">🚪 Log Out</a>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="header">
            <div>
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($admin['first_name']); ?>! Here's your overview.</p>
            </div>
            <div class="admin-info">
                <span class="admin-badge">ADMINISTRATOR</span>
                <span><?php echo htmlspecialchars($admin['email']); ?></span>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card employees">
                <div class="stat-icon">👥</div>
                <div class="stat-label">Total Employees</div>
                <div class="stat-number"><?php echo $total_employees; ?></div>
                <small style="color: #27ae60;">Active users in system</small>
            </div>
            
            <div class="stat-card departments">
                <div class="stat-icon">🏢</div>
                <div class="stat-label">Departments</div>
                <div class="stat-number"><?php echo $total_departments; ?></div>
                <small style="color: #9b59b6;">Active departments</small>
            </div>
            
            <div class="stat-card leaves">
                <div class="stat-icon">⏳</div>
                <div class="stat-label">Pending Leaves</div>
                <div class="stat-number"><?php echo $pending_leaves; ?></div>
                <small style="color: #e74c3c;">Awaiting approval</small>
            </div>
            
            <div class="stat-card attendance">
                <div class="stat-icon">✅</div>
                <div class="stat-label">Present Today</div>
                <div class="stat-number"><?php echo $present_today; ?> / <?php echo $total_employees; ?></div>
                <small style="color: #27ae60;"><?php echo $absent_today; ?> absent</small>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Attendance Chart (Last 7 Days) -->
            <div class="card">
                <h3>📊 Attendance Overview (Last 7 Days)</h3>
                <div class="chart-container">
                    <?php foreach($attendance_stats as $stat): ?>
                        <div style="text-align: center;">
                            <div class="chart-bar" style="height: <?php echo ($stat['present'] / $total_employees) * 200; ?>px;">
                                <div class="chart-value"><?php echo $stat['present']; ?></div>
                            </div>
                            <div class="chart-label"><?php echo $stat['date']; ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p style="margin-top: 20px; color: #7f8c8d; font-size: 13px;">
                    📈 Green bars show number of employees present each day
                </p>
            </div>

            <!-- Top Departments -->
            <div class="card">
                <h3>🏢 Top Departments</h3>
                <?php while($dept = $dept_stats->fetch_assoc()): ?>
                    <div class="dept-item">
                        <span class="dept-name"><?php echo htmlspecialchars($dept['department']); ?></span>
                        <span class="dept-count"><?php echo $dept['count']; ?> employees</span>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="content-grid">
            <!-- Recent Leave Requests -->
            <div class="card">
                <h3>🏖️ Recent Leave Requests</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_leaves->num_rows > 0): ?>
                            <?php while($leave = $recent_leaves->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></td>
                                    <td><?php echo ucfirst($leave['leave_type']); ?></td>
                                    <td><?php echo date('M j', strtotime($leave['start_date'])) . ' - ' . date('M j', strtotime($leave['end_date'])); ?></td>
                                    <td><span class="status-badge status-<?php echo $leave['status']; ?>"><?php echo ucfirst($leave['status']); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: #999;">No recent leave requests</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Employees -->
            <div class="card">
                <h3>👤 Recently Added Employees</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_users->num_rows > 0): ?>
                            <?php while($user = $recent_users->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['department']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align: center; color: #999;">No recent employees</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>