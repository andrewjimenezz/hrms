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

// Get filter parameter
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get leave requests
$leave_query = "SELECT lr.*, u.employee_id, u.first_name, u.last_name, u.department 
                FROM leave_requests lr 
                JOIN users u ON lr.user_id = u.id";

if ($filter_status !== 'all') {
    $leave_query .= " WHERE lr.status = '$filter_status'";
}

$leave_query .= " ORDER BY lr.created_at DESC";

$leave_requests = $conn->query($leave_query);

// Get statistics
$pending_count = $conn->query("SELECT COUNT(*) as count FROM leave_requests WHERE status = 'pending'")->fetch_assoc()['count'];
$approved_count = $conn->query("SELECT COUNT(*) as count FROM leave_requests WHERE status = 'approved'")->fetch_assoc()['count'];
$rejected_count = $conn->query("SELECT COUNT(*) as count FROM leave_requests WHERE status = 'rejected'")->fetch_assoc()['count'];
$total_count = $pending_count + $approved_count + $rejected_count;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leave | HRMS</title>
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
        .stat-number { font-size: 32px; font-weight: bold; margin: 10px 0; }
        .stat-label { color: #7f8c8d; font-size: 13px; }
        .stat-pending { color: #f39c12; }
        .stat-approved { color: #27ae60; }
        .stat-rejected { color: #e74c3c; }
        .stat-total { color: #3498db; }
        
        .filters { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .filters select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .filters button { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card h3 { color: #1a1a2e; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f39c12; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #34495e; color: white; font-weight: bold; }
        tr:hover { background: #f8f9fa; }
        
        .status-badge { padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-block; font-weight: bold; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-info { background: #3498db; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; }
        .modal-content { background: white; width: 600px; margin: 50px auto; padding: 30px; border-radius: 8px; max-height: 90vh; overflow-y: auto; }
        .modal-header { margin-bottom: 20px; border-bottom: 2px solid #f39c12; padding-bottom: 10px; }
        .close { float: right; font-size: 28px; cursor: pointer; color: #999; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #2c3e50; }
        .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        
        .leave-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px; }
        .leave-details p { margin-bottom: 8px; }
        .leave-details strong { color: #2c3e50; }
        
        .action-buttons { display: flex; gap: 5px; }
    </style>
</head>
<body>

<div class="container">
    <div class="sidebar">
        <h2>⚡ ADMIN PANEL</h2>
        <a href="admin_dashboard.php">📊 Dashboard</a>
        <a href="admin_users.php">👥 Manage Users</a>
        <a href="admin_attendance.php">📅 Manage Attendance</a>
        <a href="admin_leave.php" class="active">🏖️ Manage Leave</a>
        <a href="admin_payroll.php">💰 Manage Payroll</a>
        <a href="admin_announcements.php">📢 Announcements</a>
        <a href="admin_departments.php">🏢 Departments</a>
        <a href="admin_profile.php">👤 My Profile</a>
        <a href="logout.php" class="logout">🚪 Log Out</a>
    </div>

    <div class="main">
        <div class="header">
            <div>
                <h1>Manage Leave Requests</h1>
                <p>Approve or reject employee leave requests</p>
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">PENDING</div>
                <div class="stat-number stat-pending"><?php echo $pending_count; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">APPROVED</div>
                <div class="stat-number stat-approved"><?php echo $approved_count; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">REJECTED</div>
                <div class="stat-number stat-rejected"><?php echo $rejected_count; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">TOTAL REQUESTS</div>
                <div class="stat-number stat-total"><?php echo $total_count; ?></div>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="filters">
            <label>Filter by Status:</label>
            <select name="status">
                <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Requests</option>
                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $filter_status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
            </select>
            <button type="submit">🔍 Filter</button>
        </form>

        <!-- Leave Requests Table -->
        <div class="card">
            <h3>🏖️ Leave Requests</h3>
            
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Duration</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Applied On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($leave_requests->num_rows > 0): ?>
                        <?php while($leave = $leave_requests->fetch_assoc()): 
                            $start = new DateTime($leave['start_date']);
                            $end = new DateTime($leave['end_date']);
                            $days = $start->diff($end)->days + 1;
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($leave['employee_id']); ?></small>
                                </td>
                                <td><?php echo ucfirst($leave['leave_type']); ?></td>
                                <td>
                                    <?php echo date('M j', strtotime($leave['start_date'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($leave['end_date'])); ?>
                                </td>
                                <td><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></td>
                                <td><?php echo htmlspecialchars(substr($leave['reason'], 0, 50)) . (strlen($leave['reason']) > 50 ? '...' : ''); ?></td>
                                <td><span class="status-badge status-<?php echo $leave['status']; ?>"><?php echo ucfirst($leave['status']); ?></span></td>
                                <td><?php echo date('M j, Y', strtotime($leave['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-info btn-sm" onclick="viewLeave(<?php echo $leave['id']; ?>)">👁️ View</button>
                                        <?php if($leave['status'] == 'pending'): ?>
                                            <button class="btn btn-success btn-sm" onclick="approveLeave(<?php echo $leave['id']; ?>)">✅</button>
                                            <button class="btn btn-danger btn-sm" onclick="rejectLeave(<?php echo $leave['id']; ?>)">❌</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align: center; color: #999;">No leave requests found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- View Leave Modal -->
<div id="viewLeaveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="document.getElementById('viewLeaveModal').style.display='none'">&times;</span>
            <h2>📄 Leave Request Details</h2>
        </div>
        
        <div id="leaveDetailsContent"></div>
    </div>
</div>

<!-- Approve Leave Modal -->
<div id="approveLeaveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="document.getElementById('approveLeaveModal').style.display='none'">&times;</span>
            <h2>✅ Approve Leave Request</h2>
        </div>
        
        <form action="process_approve_leave.php" method="POST">
            <input type="hidden" name="leave_id" id="approve_leave_id">
            
            <p style="margin-bottom: 15px;">Are you sure you want to approve this leave request?</p>
            
            <div class="form-group">
                <label>Admin Remarks (Optional)</label>
                <textarea name="admin_remarks" rows="3" placeholder="Add any comments..."></textarea>
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">✅ Approve</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('approveLeaveModal').style.display='none'">❌ Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Leave Modal -->
<div id="rejectLeaveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="document.getElementById('rejectLeaveModal').style.display='none'">&times;</span>
            <h2>❌ Reject Leave Request</h2>
        </div>
        
        <form action="process_reject_leave.php" method="POST">
            <input type="hidden" name="leave_id" id="reject_leave_id">
            
            <p style="margin-bottom: 15px; color: #e74c3c;">Are you sure you want to reject this leave request?</p>
            
            <div class="form-group">
                <label>Reason for Rejection *</label>
                <textarea name="admin_remarks" rows="3" required placeholder="Please provide a reason for rejection..."></textarea>
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-danger">❌ Reject</button>
                <button type="button" class="btn btn-info" onclick="document.getElementById('rejectLeaveModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function viewLeave(leaveId) {
    fetch('get_leave_details.php?id=' + leaveId)
        .then(response => response.json())
        .then(data => {
            const content = `
                <div class="leave-details">
                    <p><strong>Employee:</strong> ${data.employee_name} (${data.employee_id})</p>
                    <p><strong>Department:</strong> ${data.department}</p>
                    <p><strong>Leave Type:</strong> ${data.leave_type}</p>
                    <p><strong>Start Date:</strong> ${data.start_date}</p>
                    <p><strong>End Date:</strong> ${data.end_date}</p>
                    <p><strong>Number of Days:</strong> ${data.days}</p>
                    <p><strong>Status:</strong> <span class="status-badge status-${data.status}">${data.status}</span></p>
                    <p><strong>Applied On:</strong> ${data.created_at}</p>
                </div>
                
                <div class="form-group">
                    <label>Reason for Leave:</label>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
                        ${data.reason}
                    </div>
                </div>
                
                ${data.admin_remarks ? `
                    <div class="form-group">
                        <label>Admin Remarks:</label>
                        <div style="background: #fff3cd; padding: 15px; border-radius: 5px;">
                            ${data.admin_remarks}
                        </div>
                    </div>
                ` : ''}
                
                ${data.status === 'pending' ? `
                    <div style="margin-top: 20px; display: flex; gap: 10px;">
                        <button class="btn btn-success" onclick="approveLeave(${leaveId})">✅ Approve</button>
                        <button class="btn btn-danger" onclick="rejectLeave(${leaveId})">❌ Reject</button>
                    </div>
                ` : ''}
            `;
            
            document.getElementById('leaveDetailsContent').innerHTML = content;
            document.getElementById('viewLeaveModal').style.display = 'block';
        });
}

function approveLeave(leaveId) {
    document.getElementById('approve_leave_id').value = leaveId;
    document.getElementById('viewLeaveModal').style.display = 'none';
    document.getElementById('approveLeaveModal').style.display = 'block';
}

function rejectLeave(leaveId) {
    document.getElementById('reject_leave_id').value = leaveId;
    document.getElementById('viewLeaveModal').style.display = 'none';
    document.getElementById('rejectLeaveModal').style.display = 'block';
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