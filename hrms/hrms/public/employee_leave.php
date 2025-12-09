<?php
session_start();
include("../includes/db_connect.php");

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

// Get leave requests
$leave_query = "SELECT * FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC";
$leave_stmt = $conn->prepare($leave_query);
$leave_stmt->bind_param("i", $user_id);
$leave_stmt->execute();
$leave_requests = $leave_stmt->get_result();

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
    <title>Leave Requests | HRMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        
        .container { display: flex; min-height: 100vh; }
        
        .sidebar { width: 250px; background: linear-gradient(to bottom, #0a4f0d, #3aa23f); color: white; padding: 20px; }
        .sidebar h2 { margin-bottom: 30px; text-align: center; }
        .sidebar a { display: block; padding: 12px 15px; color: white; text-decoration: none; margin-bottom: 5px; border-radius: 5px; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }
        .sidebar .logout { margin-top: auto; background: rgba(255,0,0,0.3); }
        
        .main { flex: 1; padding: 30px; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .header h1 { color: #0a4f0d; }
        
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #0a4f0d; color: white; }
        .btn-primary:hover { background: #06600e; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0a4f0d; color: white; }
        
        .status-badge { padding: 5px 10px; border-radius: 3px; font-size: 12px; display: inline-block; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: white; width: 500px; margin: 50px auto; padding: 30px; border-radius: 8px; }
        .modal-header { margin-bottom: 20px; }
        .close { float: right; font-size: 28px; cursor: pointer; }
        
        .notif-badge { background: red; color: white; padding: 3px 8px; border-radius: 10px; font-size: 12px; }
    </style>
</head>
<body>

<div class="container">
    <div class="sidebar">
        <h2>HRMS</h2>
        <a href="employee_dashboard.php">📊 Dashboard</a>
        <a href="employee_profile.php">👤 My Profile</a>
        <a href="employee_attendance.php">📅 Attendance</a>
        <a href="employee_leave.php" class="active">🏖️ Leave</a>
        <a href="employee_payroll.php">💰 Payroll</a>
        <a href="employee_notifications.php">🔔 Notifications <?php if($unread_count > 0) echo "<span class='notif-badge'>$unread_count</span>"; ?></a>
        <a href="logout.php" class="logout">🚪 Log Out</a>
    </div>

    <div class="main">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Leave Requests</h1>
                    <p>Apply for leave and track your requests</p>
                </div>
                <button class="btn btn-primary" onclick="document.getElementById('leaveModal').style.display='block'">+ Apply for Leave</button>
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
        ?>

        <!-- Leave Requests Table -->
        <div class="card">
            <h2>My Leave Requests</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Admin Remarks</th>
                        <th>Applied On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($leave_requests->num_rows > 0): ?>
                        <?php while($leave = $leave_requests->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo ucfirst($leave['leave_type']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($leave['start_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($leave['end_date'])); ?></td>
                                <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $leave['status']; ?>">
                                        <?php echo ucfirst($leave['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $leave['admin_remarks'] ?: '-'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($leave['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No leave requests found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Leave Application Modal -->
<div id="leaveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="document.getElementById('leaveModal').style.display='none'">&times;</span>
            <h2>Apply for Leave</h2>
        </div>
        
        <form action="process_leave.php" method="POST">
            <div class="form-group">
                <label>Leave Type</label>
                <select name="leave_type" required>
                    <option value="">Select Type</option>
                    <option value="sick">Sick Leave</option>
                    <option value="vacation">Vacation Leave</option>
                    <option value="emergency">Emergency Leave</option>
                    <option value="maternity">Maternity Leave</option>
                    <option value="paternity">Paternity Leave</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Start Date</label>
                <input type="date" name="start_date" required>
            </div>
            
            <div class="form-group">
                <label>End Date</label>
                <input type="date" name="end_date" required>
            </div>
            
            <div class="form-group">
                <label>Reason</label>
                <textarea name="reason" rows="4" required placeholder="Please provide a reason for your leave..."></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Submit Request</button>
        </form>
    </div>
</div>

</body>
</html>