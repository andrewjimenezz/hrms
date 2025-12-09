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

// Set default profile picture
$timestamp = time();
$profile_pic = $admin['profile_picture'] ? 'uploads/profiles/' . $admin['profile_picture'] . '?t=' . $timestamp : 'https://via.placeholder.com/150/1a1a2e/f39c12?text=' . strtoupper(substr($admin['first_name'], 0, 1));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | HRMS Admin</title>
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
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header h1 { color: #1a1a2e; }
        
        .profile-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        .profile-pic-section { text-align: center; }
        .profile-pic-wrapper { position: relative; display: inline-block; margin-bottom: 20px; }
        .profile-pic { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #f39c12; }
        .upload-overlay { position: absolute; bottom: 0; right: 0; background: #f39c12; color: white; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 3px solid white; }
        .upload-overlay:hover { background: #e67e22; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #2c3e50; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #f39c12; }
        .form-group input:disabled { background: #f5f5f5; cursor: not-allowed; }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .btn { padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: bold; }
        .btn-primary { background: #f39c12; color: white; }
        .btn-primary:hover { background: #e67e22; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; }
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .info-row { display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #eee; }
        .info-label { font-weight: bold; color: #666; }
        .info-value { color: #333; }
        
        .admin-badge { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 20px; border-radius: 20px; font-size: 14px; font-weight: bold; display: inline-block; margin-top: 10px; }
        
        .btn-group { display: flex; gap: 10px; margin-top: 20px; }
        
        #imageInput { display: none; }
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
        <a href="admin_departments.php">🏢 Departments</a>
        <a href="admin_profile.php" class="active">👤 My Profile</a>
        <a href="logout.php" class="logout">🚪 Log Out</a>
    </div>

    <div class="main">
        <div class="header">
            <h1>My Profile</h1>
            <p>Manage your admin account settings</p>
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

        <div class="profile-grid">
            <!-- Profile Picture Section -->
            <div class="card profile-pic-section">
                <div class="profile-pic-wrapper">
                    <img src="<?php echo $profile_pic; ?>" alt="Profile Picture" class="profile-pic" id="profileImage">
                    <form action="process_admin_profile_picture.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                        <label for="imageInput" class="upload-overlay" title="Change Profile Picture">
                            📷
                        </label>
                        <input type="file" name="profile_picture" id="imageInput" accept="image/*" onchange="previewAndUpload(this)">
                    </form>
                </div>
                <h3><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h3>
                <p style="color: #666; margin-top: 5px;"><?php echo htmlspecialchars($admin['employee_id']); ?></p>
                <span class="admin-badge">🛡️ ADMINISTRATOR</span>
                
                <div style="margin-top: 30px; text-align: left;">
                    <h4 style="margin-bottom: 15px; color: #1a1a2e;">Quick Info</h4>
                    <div class="info-row">
                        <span class="info-label">Department:</span>
                        <span class="info-value"><?php echo htmlspecialchars($admin['department']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($admin['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Contact:</span>
                        <span class="info-value"><?php echo htmlspecialchars($admin['contact_number']); ?></span>
                    </div>
                    <div class="info-row" style="border-bottom: none;">
                        <span class="info-label">Member Since:</span>
                        <span class="info-value"><?php echo date('M j, Y', strtotime($admin['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="card">
                <h2 style="margin-bottom: 25px; color: #1a1a2e;">Edit Profile Information</h2>
                
                <form action="process_update_admin_profile.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($admin['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($admin['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Employee ID</label>
                        <input type="text" value="<?php echo htmlspecialchars($admin['employee_id']); ?>" disabled>
                        <small style="color: #666;">Employee ID cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Contact Number *</label>
                        <input type="text" name="contact_number" value="<?php echo htmlspecialchars($admin['contact_number']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" value="<?php echo htmlspecialchars($admin['department']); ?>" disabled>
                        <small style="color: #666;">Contact super admin to change department</small>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.reload()">❌ Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewAndUpload(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            document.getElementById('profileImage').src = e.target.result;
        }
        
        reader.readAsDataURL(input.files[0]);
        
        // Auto-submit the form
        document.getElementById('uploadForm').submit();
    }
}

// Force reload page after profile picture upload to clear cache
<?php if(isset($_SESSION['success']) && strpos($_SESSION['success'], 'Profile picture') !== false): ?>
    const img = document.getElementById('profileImage');
    if(img) {
        img.src = img.src.split('?')[0] + '?t=' + new Date().getTime();
    }
<?php endif; ?>
</script>

</body>
</html>