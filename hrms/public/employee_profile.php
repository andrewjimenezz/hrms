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

// Get unread notifications count
$notif_query = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$unread_count = $notif_stmt->get_result()->fetch_assoc()['unread'];

// Set default profile picture if none exists
// Add timestamp to prevent caching issues
$timestamp = time();
$profile_pic = $user['profile_picture'] ? 'uploads/profiles/' . $user['profile_picture'] . '?t=' . $timestamp : 'https://via.placeholder.com/150/0a4f0d/FFFFFF?text=' . strtoupper(substr($user['first_name'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | HRMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
}

.container {
    min-height: 100vh;
}

/* Main Content — SAME LOGIC AS DASHBOARD */
.main {
    margin-left: 280px;        /* space for sidebar */
    padding: 30px;
    min-height: 100vh;
    transition: margin-left 0.3s ease;
}

/* Sidebar collapsed */
.sidebar.collapsed ~ .main {
    margin-left: 80px;
}

.header {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.header h1 {
    color: #0a4f0d;
}

.profile-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 20px;
}

.card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* Profile Picture */
.profile-pic-section {
    text-align: center;
}

.profile-pic-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 20px;
}

.profile-pic {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #0a4f0d;
}

.upload-overlay {
    position: absolute;
    bottom: 0;
    right: 0;
    background: #0a4f0d;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: 3px solid white;
}

.upload-overlay:hover {
    background: #06600e;
}

/* Forms */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #333;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #0a4f0d;
}

.form-group input:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.btn-group {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn {
    padding: 12px 30px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
}

.btn-primary {
    background: #0a4f0d;
    color: white;
}

.btn-primary:hover {
    background: #06600e;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
}

.notif-badge {
    background: red;
    color: white;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 12px;
}

#imageInput {
    display: none;
}

/* Mobile */
@media (max-width: 768px) {
    .main {
        margin-left: 0;
        padding: 20px;
    }

    .profile-grid {
        grid-template-columns: 1fr;
    }

    .sidebar.collapsed ~ .main {
        margin-left: 0;
    }
}

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
        <a href="employee_profile.php" class="active"><i class="fa-solid fa-user"></i><span>My Profile</span></a>
        <a href="employee_201_file.php"><i class="fa-solid fa-file-lines"></i><span>201 File / PDS</span></a>
        <a href="employee_attendance.php"><i class="fa-solid fa-calendar-check"></i><span>Attendance</span></a>
        <a href="employee_leave.php"><i class="fa-solid fa-umbrella-beach"></i><span>Leave</span></a>
        <a href="employee_payroll.php"><i class="fa-solid fa-money-bill-wave"></i><span>Payroll</span></a>
        <a href="employee_notifications.php"><i class="fa-solid fa-bell"></i><span>Notifications</span> <?php if($unread_count > 0) echo "<span class='notif-badge'>$unread_count</span>"; ?></a>
        <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Log Out</span></a>
    </div>

    <div class="main">
        <div class="header">
            <h1>My Profile</h1>
            <p>View and update your personal information</p>
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
                    <form action="process_profile_picture.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                        <label for="imageInput" class="upload-overlay" title="Change Profile Picture">
                            📷
                        </label>
                        <input type="file" name="profile_picture" id="imageInput" accept="image/*" onchange="previewAndUpload(this)">
                    </form>
                </div>
                <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                <p style="color: #666; margin-top: 5px;"><?php echo htmlspecialchars($user['employee_id']); ?></p>
                <p style="color: #0a4f0d; margin-top: 5px; font-weight: bold;"><?php echo ucfirst($user['role']); ?></p>
                
                <div style="margin-top: 30px; text-align: left;">
                    <h4 style="margin-bottom: 15px; color: #0a4f0d;">Quick Info</h4>
                    <div class="info-row">
                        <span class="info-label">Department:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['department']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Contact:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user['contact_number']); ?></span>
                    </div>
                    <div class="info-row" style="border-bottom: none;">
                        <span class="info-label">Joined:</span>
                        <span class="info-value"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="card">
                <h2 style="margin-bottom: 25px; color: #0a4f0d;">Edit Profile Information</h2>
                
                <form action="process_update_profile.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Employee ID</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['employee_id']); ?>" disabled>
                        <small style="color: #666;">Employee ID cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Contact Number *</label>
                        <input type="text" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['department']); ?>" disabled>
                        <small style="color: #666;">Contact HR to change department</small>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.reload()">Cancel</button>
                    </div>
                </form>
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
    // Add timestamp to image to force reload
    const img = document.getElementById('profileImage');
    if(img) {
        img.src = img.src.split('?')[0] + '?t=' + new Date().getTime();
    }
<?php endif; ?>
</script>

</body>
</html>