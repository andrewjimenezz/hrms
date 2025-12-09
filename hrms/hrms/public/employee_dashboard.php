<?php
session_start();
include("../includes/db_connect.php");

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user information
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

// Get recent notifications (last 3)
$recent_notif_query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 3";
$recent_notif_stmt = $conn->prepare($recent_notif_query);
$recent_notif_stmt->bind_param("i", $user_id);
$recent_notif_stmt->execute();
$recent_notifications = $recent_notif_stmt->get_result();

// Get today's attendance
$today = date('Y-m-d');
$att_query = "SELECT * FROM attendance WHERE user_id = ? AND date = ?";
$att_stmt = $conn->prepare($att_query);
$att_stmt->bind_param("is", $user_id, $today);
$att_stmt->execute();
$today_attendance = $att_stmt->get_result()->fetch_assoc();

// Get attendance streak (consecutive present days)
$streak_query = "SELECT date, status FROM attendance WHERE user_id = ? AND status IN ('present', 'late') ORDER BY date DESC LIMIT 30";
$streak_stmt = $conn->prepare($streak_query);
$streak_stmt->bind_param("i", $user_id);
$streak_stmt->execute();
$streak_result = $streak_stmt->get_result();

$streak = 0;
$yesterday = date('Y-m-d', strtotime('-1 day'));
$check_date = $yesterday;

while($att_row = $streak_result->fetch_assoc()) {
    if($att_row['date'] == $check_date) {
        $streak++;
        $check_date = date('Y-m-d', strtotime($check_date . ' -1 day'));
    } else {
        break;
    }
}

// Get recent announcements
$ann_query = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3";
$announcements = $conn->query($ann_query);

// Get pending leave requests count
$leave_query = "SELECT COUNT(*) as pending FROM leave_requests WHERE user_id = ? AND status = 'pending'";
$leave_stmt = $conn->prepare($leave_query);
$leave_stmt->bind_param("i", $user_id);
$leave_stmt->execute();
$pending_leaves = $leave_stmt->get_result()->fetch_assoc()['pending'];

// Get this month's payroll
$current_month = date('F');
$current_year = date('Y');
$payroll_query = "SELECT * FROM payroll WHERE user_id = ? AND month = ? AND year = ?";
$payroll_stmt = $conn->prepare($payroll_query);
$payroll_stmt->bind_param("isi", $user_id, $current_month, $current_year);
$payroll_stmt->execute();
$current_payroll = $payroll_stmt->get_result()->fetch_assoc();

// Upcoming holidays (hardcoded for now - admin can manage this later)
$holidays = [
    ['date' => '2025-12-25', 'name' => 'Christmas Day'],
    ['date' => '2025-12-30', 'name' => 'Rizal Day'],
    ['date' => '2025-12-31', 'name' => 'New Year\'s Eve (Special Non-Working)'],
    ['date' => '2026-01-01', 'name' => 'New Year\'s Day'],
];

// Filter upcoming holidays only
$upcoming_holidays = [];
foreach($holidays as $holiday) {
    if(strtotime($holiday['date']) >= strtotime($today)) {
        $upcoming_holidays[] = $holiday;
    }
}
$upcoming_holidays = array_slice($upcoming_holidays, 0, 3);

// Get recent activities
$activities = [];

// Recent attendance logs
$recent_att_query = "SELECT date, time_in, time_out, status FROM attendance WHERE user_id = ? ORDER BY date DESC LIMIT 3";
$recent_att_stmt = $conn->prepare($recent_att_query);
$recent_att_stmt->bind_param("i", $user_id);
$recent_att_stmt->execute();
$recent_att_result = $recent_att_stmt->get_result();

while($att = $recent_att_result->fetch_assoc()) {
    $activities[] = [
        'icon' => '📅',
        'action' => 'Attendance recorded',
        'details' => date('M j, Y', strtotime($att['date'])) . ' - ' . ucfirst($att['status']),
        'time' => $att['date']
    ];
}

// Recent leave requests
$recent_leave_query = "SELECT leave_type, start_date, status, created_at FROM leave_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 2";
$recent_leave_stmt = $conn->prepare($recent_leave_query);
$recent_leave_stmt->bind_param("i", $user_id);
$recent_leave_stmt->execute();
$recent_leave_result = $recent_leave_stmt->get_result();

while($leave = $recent_leave_result->fetch_assoc()) {
    $activities[] = [
        'icon' => '🏖️',
        'action' => ucfirst($leave['leave_type']) . ' leave request',
        'details' => 'Status: ' . ucfirst($leave['status']),
        'time' => $leave['created_at']
    ];
}

// Sort activities by time
usort($activities, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
$activities = array_slice($activities, 0, 5);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard | HRMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        
        .container { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar { width: 250px; background: linear-gradient(to bottom, #0a4f0d, #3aa23f); color: white; padding: 20px; }
        .sidebar h2 { margin-bottom: 30px; text-align: center; }
        .sidebar a { display: block; padding: 12px 15px; color: white; text-decoration: none; margin-bottom: 5px; border-radius: 5px; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }
        .sidebar .logout { margin-top: auto; background: rgba(255,0,0,0.3); }
        
        /* Main Content */
        .main { flex: 1; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 8px; }
        .header h1 { color: #0a4f0d; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .notif-badge { background: red; color: white; padding: 3px 8px; border-radius: 10px; font-size: 12px; }
        
        /* Dashboard Grid */
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card h3 { margin-bottom: 15px; color: #0a4f0d; }
        .card .stat { font-size: 32px; font-weight: bold; color: #3aa23f; }
        
        /* Attendance Card */
        .attendance-btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin-right: 10px; margin-top: 10px; }
        .btn-in { background: #4CAF50; color: white; }
        .btn-out { background: #f44336; color: white; }
        .btn-disabled { background: #ccc; cursor: not-allowed; }
        
        /* Streak Card */
        .streak-number { font-size: 48px; font-weight: bold; color: #ff6b6b; text-align: center; margin: 20px 0; }
        .streak-text { text-align: center; color: #666; font-size: 14px; }
        .fire-emoji { font-size: 36px; text-align: center; margin-bottom: 10px; }
        
        /* Announcements */
        .announcement { padding: 15px; border-left: 4px solid #3aa23f; margin-bottom: 10px; background: #f9f9f9; }
        .announcement.priority-high { border-left-color: #f44336; }
        .announcement h4 { margin-bottom: 5px; }
        .announcement small { color: #666; }
        
        /* Holidays */
        .holiday-item { display: flex; justify-content: space-between; padding: 12px; border-bottom: 1px solid #eee; }
        .holiday-item:last-child { border-bottom: none; }
        .holiday-date { font-weight: bold; color: #0a4f0d; }
        .holiday-name { color: #666; }
        
        /* Notifications Widget */
        .notif-item { padding: 12px; border-left: 3px solid #17a2b8; margin-bottom: 10px; background: #f8f9fa; border-radius: 3px; }
        .notif-item.unread { background: #e3f2fd; }
        .notif-item.type-warning { border-left-color: #ffc107; }
        .notif-item.type-success { border-left-color: #28a745; }
        .notif-item.type-error { border-left-color: #dc3545; }
        .notif-title { font-weight: bold; font-size: 13px; margin-bottom: 3px; }
        .notif-message { font-size: 12px; color: #666; }
        .view-all { text-align: center; margin-top: 10px; }
        .view-all a { color: #0a4f0d; text-decoration: none; font-size: 13px; }
        
        /* Activities */
        .activity-item { display: flex; gap: 15px; padding: 12px; border-bottom: 1px solid #eee; }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon { font-size: 24px; }
        .activity-content { flex: 1; }
        .activity-action { font-weight: bold; font-size: 14px; color: #333; }
        .activity-details { font-size: 12px; color: #666; margin-top: 3px; }
        .activity-time { font-size: 11px; color: #999; }
        
        /* Current Time Display */
        .current-time { font-size: 24px; font-weight: bold; color: #0a4f0d; text-align: center; margin: 10px 0; }
        .current-date { text-align: center; color: #666; font-size: 14px; }
    </style>
</head>
<body>

<div class="container">
    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <h2>HRMS</h2>
        <a href="employee_dashboard.php" class="active">📊 Dashboard</a>
        <a href="employee_profile.php">👤 My Profile</a>
        <a href="employee_attendance.php">📅 Attendance</a>
        <a href="employee_leave.php">🏖️ Leave</a>
        <a href="employee_payroll.php">💰 Payroll</a>
        <a href="employee_notifications.php">🔔 Notifications <?php if($unread_count > 0) echo "<span class='notif-badge'>$unread_count</span>"; ?></a>
        <a href="logout.php" class="logout">🚪 Log Out</a>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="header">
            <div>
                <h1>Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                <p>Employee ID: <?php echo htmlspecialchars($user['employee_id']); ?></p>
            </div>
            <div class="user-info">
                <span><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
        </div>

        <!-- Dashboard Cards Row 1 -->
        <div class="dashboard-grid">
            <!-- Today's Attendance -->
            <div class="card">
                <h3>Today's Attendance</h3>
                <div class="current-time" id="currentTime">--:--:--</div>
                <div class="current-date"><?php echo date('l, F j, Y'); ?></div>
                
                <?php if($today_attendance): ?>
                    <p style="margin-top: 15px;"><strong>Time In:</strong> <?php echo $today_attendance['time_in'] ? date('h:i A', strtotime($today_attendance['time_in'])) : 'Not yet clocked in'; ?></p>
                    <p><strong>Time Out:</strong> <?php echo $today_attendance['time_out'] ? date('h:i A', strtotime($today_attendance['time_out'])) : 'Not yet clocked out'; ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst($today_attendance['status']); ?></p>
                <?php else: ?>
                    <p style="margin-top: 15px;">No attendance recorded today</p>
                <?php endif; ?>
                
                <form action="process_attendance.php" method="POST" style="margin-top: 15px;">
                    <?php if(!$today_attendance || !$today_attendance['time_in']): ?>
                        <button type="submit" name="action" value="time_in" class="attendance-btn btn-in">Clock In</button>
                    <?php elseif(!$today_attendance['time_out']): ?>
                        <button type="submit" name="action" value="time_out" class="attendance-btn btn-out">Clock Out</button>
                    <?php else: ?>
                        <button class="attendance-btn btn-disabled" disabled>Attendance Complete</button>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Attendance Streak -->
            <div class="card">
                <h3>Attendance Streak</h3>
                <div class="fire-emoji">🔥</div>
                <div class="streak-number"><?php echo $streak; ?></div>
                <div class="streak-text">Consecutive Days Present</div>
                <p style="text-align: center; margin-top: 15px; font-size: 12px; color: #999;">
                    <?php echo $streak > 0 ? "Keep it up! Great work!" : "Start your streak by clocking in today!"; ?>
                </p>
            </div>

            <!-- Leave Status -->
            <div class="card">
                <h3>Leave Requests</h3>
                <div class="stat"><?php echo $pending_leaves; ?></div>
                <p>Pending Requests</p>
                <a href="employee_leave.php" style="color: #0a4f0d; text-decoration: none; margin-top: 10px; display: inline-block;">View All →</a>
            </div>
        </div>

        <!-- Dashboard Cards Row 2 -->
        <div class="dashboard-grid">
            <!-- Payroll Info -->
            <div class="card">
                <h3><?php echo $current_month . ' ' . $current_year; ?> Payroll</h3>
                <?php if($current_payroll): ?>
                    <div class="stat">₱<?php echo number_format($current_payroll['net_salary'], 2); ?></div>
                    <p>Status: <?php echo ucfirst($current_payroll['status']); ?></p>
                    <?php if($current_payroll['status'] == 'paid'): ?>
                        <a href="download_payslip.php?id=<?php echo $current_payroll['id']; ?>" style="color: #0a4f0d; text-decoration: none; margin-top: 10px; display: inline-block;">📄 Download Payslip</a>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No payroll data for this month</p>
                <?php endif; ?>
                <a href="employee_payroll.php" style="color: #0a4f0d; text-decoration: none; margin-top: 10px; display: inline-block;">View Details →</a>
            </div>

            <!-- Recent Notifications -->
            <div class="card">
                <h3>Recent Notifications</h3>
                <?php if($recent_notifications->num_rows > 0): ?>
                    <?php while($notif = $recent_notifications->fetch_assoc()): ?>
                        <div class="notif-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?> type-<?php echo $notif['type']; ?>">
                            <div class="notif-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                            <div class="notif-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                        </div>
                    <?php endwhile; ?>
                    <div class="view-all">
                        <a href="employee_notifications.php">View All Notifications →</a>
                    </div>
                <?php else: ?>
                    <p>No notifications</p>
                <?php endif; ?>
            </div>

            <!-- Upcoming Holidays -->
            <div class="card">
                <h3>Upcoming Holidays</h3>
                <?php if(count($upcoming_holidays) > 0): ?>
                    <?php foreach($upcoming_holidays as $holiday): ?>
                        <div class="holiday-item">
                            <span class="holiday-date"><?php echo date('M j', strtotime($holiday['date'])); ?></span>
                            <span class="holiday-name"><?php echo $holiday['name']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No upcoming holidays</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Announcements -->
        <div class="card">
            <h3>Recent Announcements</h3>
            <?php if($announcements->num_rows > 0): ?>
                <?php while($ann = $announcements->fetch_assoc()): ?>
                    <div class="announcement <?php echo $ann['priority'] == 'high' ? 'priority-high' : ''; ?>">
                        <h4><?php echo htmlspecialchars($ann['title']); ?></h4>
                        <p><?php echo htmlspecialchars($ann['content']); ?></p>
                        <small><?php echo date('F j, Y', strtotime($ann['created_at'])); ?></small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No announcements available</p>
            <?php endif; ?>
        </div>

        <!-- Recent Activities -->
        <div class="card">
            <h3>Recent Activities</h3>
            <?php if(count($activities) > 0): ?>
                <?php foreach($activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon"><?php echo $activity['icon']; ?></div>
                        <div class="activity-content">
                            <div class="activity-action"><?php echo $activity['action']; ?></div>
                            <div class="activity-details"><?php echo $activity['details']; ?></div>
                            <div class="activity-time">
                                <?php 
                                $time_diff = time() - strtotime($activity['time']);
                                if($time_diff < 3600) {
                                    echo floor($time_diff / 60) . " min ago";
                                } elseif($time_diff < 86400) {
                                    echo floor($time_diff / 3600) . " hour" . (floor($time_diff / 3600) > 1 ? 's' : '') . " ago";
                                } else {
                                    echo date('M j, Y', strtotime($activity['time']));
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No recent activities</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Update current time every second
function updateTime() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('currentTime').textContent = hours + ':' + minutes + ':' + seconds;
}

updateTime();
setInterval(updateTime, 1000);
</script>

</body>
</html>