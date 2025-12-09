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

// Get all announcements
$announcements_query = "SELECT a.*, u.first_name, u.last_name 
                        FROM announcements a 
                        JOIN users u ON a.created_by = u.id 
                        ORDER BY a.created_at DESC";
$announcements = $conn->query($announcements_query);

// Get statistics
$total_announcements = $conn->query("SELECT COUNT(*) as count FROM announcements")->fetch_assoc()['count'];
$high_priority = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE priority = 'high'")->fetch_assoc()['count'];
$total_employees = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'employee'")->fetch_assoc()['count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements | HRMS</title>
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
        
        .announcement-item { background: #f8f9fa; padding: 20px; border-left: 4px solid #3498db; margin-bottom: 15px; border-radius: 5px; }
        .announcement-item.priority-high { border-left-color: #e74c3c; background: #fff5f5; }
        .announcement-item.priority-medium { border-left-color: #f39c12; }
        .announcement-item.priority-low { border-left-color: #95a5a6; }
        
        .announcement-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .announcement-title { font-size: 18px; font-weight: bold; color: #2c3e50; }
        .announcement-content { color: #34495e; line-height: 1.6; margin-bottom: 10px; }
        .announcement-meta { display: flex; gap: 15px; font-size: 12px; color: #7f8c8d; }
        
        .priority-badge { padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .priority-high { background: #e74c3c; color: white; }
        .priority-medium { background: #f39c12; color: white; }
        .priority-low { background: #95a5a6; color: white; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; font-weight: bold; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
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
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        
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
        <a href="admin_leave.php">🏖️ Manage Leave</a>
        <a href="admin_payroll.php">💰 Manage Payroll</a>
        <a href="admin_announcements.php" class="active">📢 Announcements</a>
        <a href="admin_departments.php">🏢 Departments</a>
        <a href="admin_profile.php">👤 My Profile</a>
        <a href="logout.php" class="logout">🚪 Log Out</a>
    </div>

    <div class="main">
        <div class="header">
            <div>
                <h1>Manage Announcements</h1>
                <p>Create and manage company-wide announcements</p>
            </div>
            <button class="btn btn-primary" onclick="document.getElementById('addAnnouncementModal').style.display='block'">
                ➕ New Announcement
            </button>
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
                <div class="stat-label">TOTAL ANNOUNCEMENTS</div>
                <div class="stat-number"><?php echo $total_announcements; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">HIGH PRIORITY</div>
                <div class="stat-number" style="color: #e74c3c;"><?php echo $high_priority; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">TOTAL EMPLOYEES</div>
                <div class="stat-number" style="color: #27ae60;"><?php echo $total_employees; ?></div>
                <small>Will receive announcements</small>
            </div>
        </div>

        <!-- Announcements List -->
        <div class="card">
            <h3 style="color: #1a1a2e; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f39c12;">
                📢 All Announcements
            </h3>
            
            <?php if($announcements->num_rows > 0): ?>
                <?php while($announcement = $announcements->fetch_assoc()): ?>
                    <div class="announcement-item priority-<?php echo $announcement['priority']; ?>">
                        <div class="announcement-header">
                            <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                            <span class="priority-badge priority-<?php echo $announcement['priority']; ?>">
                                <?php echo strtoupper($announcement['priority']); ?> PRIORITY
                            </span>
                        </div>
                        
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>
                        
                        <div class="announcement-meta">
                            <span>📝 Posted by: <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?></span>
                            <span>📅 <?php echo date('F j, Y h:i A', strtotime($announcement['created_at'])); ?></span>
                        </div>
                        
                        <div style="margin-top: 15px;">
                            <div class="action-buttons">
                                <button class="btn btn-warning btn-sm" onclick="editAnnouncement(<?php echo $announcement['id']; ?>)">✏️ Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>)">🗑️ Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">
                    No announcements yet. Create your first announcement!
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Announcement Modal -->
<div id="addAnnouncementModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="document.getElementById('addAnnouncementModal').style.display='none'">&times;</span>
            <h2>➕ Create New Announcement</h2>
        </div>
        
        <form action="process_add_announcement.php" method="POST">
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" required placeholder="Enter announcement title">
            </div>
            
            <div class="form-group">
                <label>Content *</label>
                <textarea name="content" rows="6" required placeholder="Enter announcement content..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Priority *</label>
                <select name="priority" required>
                    <option value="low">Low - General information</option>
                    <option value="medium" selected>Medium - Standard announcement</option>
                    <option value="high">High - Important/Urgent</option>
                </select>
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">📢 Post Announcement</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('addAnnouncementModal').style.display='none'">❌ Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div id="editAnnouncementModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="document.getElementById('editAnnouncementModal').style.display='none'">&times;</span>
            <h2>✏️ Edit Announcement</h2>
        </div>
        
        <form action="process_edit_announcement.php" method="POST">
            <input type="hidden" name="announcement_id" id="edit_announcement_id">
            
            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" id="edit_title" required>
            </div>
            
            <div class="form-group">
                <label>Content *</label>
                <textarea name="content" id="edit_content" rows="6" required></textarea>
            </div>
            
            <div class="form-group">
                <label>Priority *</label>
                <select name="priority" id="edit_priority" required>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">💾 Save Changes</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('editAnnouncementModal').style.display='none'">❌ Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function editAnnouncement(announcementId) {
    fetch('get_announcement.php?id=' + announcementId)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_announcement_id').value = data.id;
            document.getElementById('edit_title').value = data.title;
            document.getElementById('edit_content').value = data.content;
            document.getElementById('edit_priority').value = data.priority;
            
            document.getElementById('editAnnouncementModal').style.display = 'block';
        });
}

function deleteAnnouncement(id) {
    if (confirm('Are you sure you want to delete this announcement? All employees will no longer see it.')) {
        window.location.href = 'process_delete_announcement.php?id=' + id;
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