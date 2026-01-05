<?php
session_start();
include("../includes/db_connect.php");
include '../includes/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark all as read if requested
if (isset($_GET['mark_all_read'])) {
    $update = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $update->bind_param("i", $user_id);
    $update->execute();
    header("Location: employee_notifications.php");
    exit();
}

// Mark single notification as read
if (isset($_GET['mark_read'])) {
    $notif_id = $_GET['mark_read'];
    $update = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $update->bind_param("ii", $notif_id, $user_id);
    $update->execute();
    header("Location: employee_notifications.php");
    exit();
}

// Get all notifications
$notif_query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result();

// Get unread count
$unread_query = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_query);
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_count = $unread_stmt->get_result()->fetch_assoc()['unread'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | HRMS</title>
    <style>
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

/* MAIN CONTENT — respects sidebar width */
.main {
    margin-left: 280px;
    padding: 30px;
    min-height: 100vh;
    transition: margin-left 0.3s ease;
}

/* Sidebar collapsed */
.sidebar.collapsed ~ .main {
    margin-left: 80px;
}

/* Header */
.header {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header h1 {
    color: #0a4f0d;
}

/* Buttons */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #0a4f0d;
    color: white;
}

.btn-primary:hover {
    background: #06600e;
}

/* Badge */
.notif-badge {
    background: red;
    color: white;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 12px;
}

/* Notification cards */
.notification-item {
    background: white;
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 8px;
    border-left: 4px solid #0a4f0d;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.notification-item.unread {
    background: #f0f8ff;
    border-left-color: #3aa23f;
}

.notification-item.type-info { border-left-color: #17a2b8; }
.notification-item.type-warning { border-left-color: #ffc107; }
.notification-item.type-success { border-left-color: #28a745; }
.notification-item.type-error { border-left-color: #dc3545; }

/* Notification content */
.notif-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.notif-title {
    font-weight: bold;
    font-size: 16px;
    color: #333;
}

.notif-time {
    font-size: 12px;
    color: #888;
}

.notif-message {
    color: #666;
    line-height: 1.5;
}

/* Mark read */
.mark-read-btn {
    background: none;
    border: none;
    color: #0a4f0d;
    cursor: pointer;
    font-size: 12px;
    text-decoration: underline;
}

.mark-read-btn:hover {
    color: #06600e;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 50px;
    background: white;
    border-radius: 8px;
}

/* Mobile */
@media (max-width: 768px) {
    .main {
        margin-left: 0;
        padding: 20px;
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
        <a href="employee_profile.php"><i class="fa-solid fa-user"></i><span>My Profile</span></a>
        <a href="employee_201_file.php"><i class="fa-solid fa-file-lines"></i><span>201 File / PDS</span></a>
        <a href="employee_attendance.php"><i class="fa-solid fa-calendar-check"></i><span>Attendance</span></a>
        <a href="employee_leave.php"><i class="fa-solid fa-umbrella-beach"></i><span>Leave</span></a>
        <a href="employee_payroll.php"><i class="fa-solid fa-money-bill-wave"></i><span>Payroll</span></a>
        <a href="employee_notifications.php" class="active"><i class="fa-solid fa-bell"></i><span>Notifications</span> <?php if($unread_count > 0) echo "<span class='notif-badge'>$unread_count</span>"; ?></a>
        <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Log Out</span></a>
    </div>

    <div class="main">
        <div class="header">
            <div>
                <h1>Notifications</h1>
                <p><?php echo $unread_count; ?> unread notification<?php echo $unread_count != 1 ? 's' : ''; ?></p>
            </div>
            <?php if($unread_count > 0): ?>
                <a href="?mark_all_read=1" class="btn btn-primary">Mark All as Read</a>
            <?php endif; ?>
        </div>

        <?php if($notifications->num_rows > 0): ?>
            <?php while($notif = $notifications->fetch_assoc()): ?>
                <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?> type-<?php echo $notif['type']; ?>">
                    <div class="notif-header">
                        <div class="notif-title">
                            <?php echo htmlspecialchars($notif['title']); ?>
                            <?php if(!$notif['is_read']): ?>
                                <span style="color: #3aa23f; font-size: 12px; margin-left: 10px;">● NEW</span>
                            <?php endif; ?>
                        </div>
                        <div class="notif-time">
                            <?php 
                            $time_diff = time() - strtotime($notif['created_at']);
                            if($time_diff < 60) {
                                echo "Just now";
                            } elseif($time_diff < 3600) {
                                echo floor($time_diff / 60) . " min ago";
                            } elseif($time_diff < 86400) {
                                echo floor($time_diff / 3600) . " hour" . (floor($time_diff / 3600) > 1 ? 's' : '') . " ago";
                            } else {
                                echo date('M j, Y h:i A', strtotime($notif['created_at']));
                            }
                            ?>
                        </div>
                    </div>
                    <div class="notif-message">
                        <?php echo htmlspecialchars($notif['message']); ?>
                    </div>
                    <?php if(!$notif['is_read']): ?>
                        <div style="margin-top: 10px;">
                            <a href="?mark_read=<?php echo $notif['id']; ?>" class="mark-read-btn">Mark as read</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <h3>No notifications yet</h3>
                <p>You're all caught up! Check back later for updates.</p>
            </div>
        <?php endif; ?>
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