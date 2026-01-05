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

// Get family background
$family_query = "SELECT * FROM family_background WHERE user_id = ?";
$family_stmt = $conn->prepare($family_query);
$family_stmt->bind_param("i", $user_id);
$family_stmt->execute();
$family = $family_stmt->get_result()->fetch_assoc();

// Get children
$children_query = "SELECT * FROM children WHERE user_id = ? ORDER BY date_of_birth";
$children_stmt = $conn->prepare($children_query);
$children_stmt->bind_param("i", $user_id);
$children_stmt->execute();
$children = $children_stmt->get_result();

// Get educational background
$education_query = "SELECT * FROM educational_background WHERE user_id = ? ORDER BY FIELD(level, 'Elementary', 'Secondary', 'Vocational', 'College', 'Graduate Studies')";
$education_stmt = $conn->prepare($education_query);
$education_stmt->bind_param("i", $user_id);
$education_stmt->execute();
$education = $education_stmt->get_result();

// Get work experience
$work_query = "SELECT * FROM work_experience WHERE user_id = ? ORDER BY date_from DESC";
$work_stmt = $conn->prepare($work_query);
$work_stmt->bind_param("i", $user_id);
$work_stmt->execute();
$work_experience = $work_stmt->get_result();

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
    <title>201 File / PDS | HRMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        
        .container { display: flex; min-height: 100vh; }
        
        .main { flex: 1; padding: 30px; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .header h1 { color: #0a4f0d; }
        
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h3 { color: #0a4f0d; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #3aa23f; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px 30px; }
        .info-row { padding: 10px 0; border-bottom: 1px solid #eee; }
        .info-label { font-weight: bold; color: #666; font-size: 13px; margin-bottom: 3px; }
        .info-value { color: #333; font-size: 14px; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; font-weight: bold; }
        .btn-primary { background: #0a4f0d; color: white; }
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #f8f9fa; color: #0a4f0d; font-weight: bold; }
        
        .notif-badge { background: red; color: white; padding: 3px 8px; border-radius: 10px; font-size: 12px; }
        
        .empty-state { text-align: center; padding: 40px; color: #999; }
        
        .section-actions { text-align: right; margin-top: 15px; }
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
        <a href="employee_201_file.php" class="active"><i class="fa-solid fa-file-lines"></i><span>201 File / PDS</span></a>
        <a href="employee_attendance.php"><i class="fa-solid fa-calendar-check"></i><span>Attendance</span></a>
        <a href="employee_leave.php"><i class="fa-solid fa-umbrella-beach"></i><span>Leave</span></a>
        <a href="employee_payroll.php"><i class="fa-solid fa-money-bill-wave"></i><span>Payroll</span></a>
        <a href="employee_notifications.php"><i class="fa-solid fa-bell"></i><span>Notifications</span> <?php if($unread_count > 0) echo "<span class='notif-badge'>$unread_count</span>"; ?></a>
        <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i><span>Log Out</span></a>
    </div>

    <div class="main">
        <div class="header">
            <h1>📋 201 File / Personal Data Sheet (PDS)</h1>
            <p>Your complete government-required employee records</p>
        </div>

        <?php
        if (isset($_SESSION['success'])) {
            echo "<div class='alert alert-success'>" . $_SESSION['success'] . "</div>";
            unset($_SESSION['success']);
        }
        ?>

        <div class="alert alert-info">
            <strong>📌 About Your 201 File:</strong> This is your official government employment record (CSC Form No. 212). Keep all information accurate and updated. Contact HR for assistance.
        </div>

        <!-- I. Personal Information -->
        <div class="card">
            <h3>I. PERSONAL INFORMATION</h3>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['middle_name'] ?? '') . ' ' . $user['last_name']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Employee ID</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['employee_id']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value"><?php echo $user['date_of_birth'] ? date('F j, Y', strtotime($user['date_of_birth'])) : 'Not set'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Place of Birth</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['place_of_birth'] ?? 'Not set'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Sex</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['sex'] ?? 'Not set'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Civil Status</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['civil_status'] ?? 'Not set'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Height</div>
                    <div class="info-value"><?php echo $user['height_cm'] ? $user['height_cm'] . ' cm' : 'Not set'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Weight</div>
                    <div class="info-value"><?php echo $user['weight_kg'] ? $user['weight_kg'] . ' kg' : 'Not set'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Blood Type</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['blood_type'] ?? 'Not set'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">GSIS ID No.</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['gsis_no'] ?? 'Not set'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Pag-IBIG ID No.</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['pagibig_no'] ?? 'Not set'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">PhilHealth No.</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['philhealth_no'] ?? 'Not set'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">SSS No.</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['sss_no'] ?? 'Not set'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">TIN</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['tin_no'] ?? 'Not set'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Contact Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['contact_number']); ?></div>
                </div>
            </div>
            <div class="section-actions">
                <a href="edit_personal_info.php" class="btn btn-primary">✏️ Edit Personal Information</a>
            </div>
        </div>

        <!-- II. Family Background -->
        <div class="card">
            <h3>II. FAMILY BACKGROUND</h3>
            
            <?php if ($family): ?>
                <h4 style="color: #666; margin-top: 20px; margin-bottom: 10px;">Spouse</h4>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Name</div>
                        <div class="info-value"><?php echo htmlspecialchars(($family['spouse_surname'] ?? '') . ', ' . ($family['spouse_firstname'] ?? '')); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Occupation</div>
                        <div class="info-value"><?php echo htmlspecialchars($family['spouse_occupation'] ?? 'Not specified'); ?></div>
                    </div>
                </div>
                
                <h4 style="color: #666; margin-top: 20px; margin-bottom: 10px;">Father</h4>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Name</div>
                        <div class="info-value"><?php echo htmlspecialchars(($family['father_surname'] ?? '') . ', ' . ($family['father_firstname'] ?? '')); ?></div>
                    </div>
                </div>
                
                <h4 style="color: #666; margin-top: 20px; margin-bottom: 10px;">Mother</h4>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Name</div>
                        <div class="info-value"><?php echo htmlspecialchars(($family['mother_surname'] ?? '') . ', ' . ($family['mother_firstname'] ?? '')); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Maiden Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($family['mother_maiden_name'] ?? 'Not specified'); ?></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">No family background information yet</div>
            <?php endif; ?>
            
            <h4 style="color: #666; margin-top: 20px; margin-bottom: 10px;">Children</h4>
            <?php if ($children->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Date of Birth</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($child = $children->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($child['full_name']); ?></td>
                                <td><?php echo date('F j, Y', strtotime($child['date_of_birth'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">No children recorded</div>
            <?php endif; ?>
            
            <div class="section-actions">
                <a href="edit_family_background.php" class="btn btn-primary">✏️ Edit Family Background</a>
            </div>
        </div>

        <!-- III. Educational Background -->
        <div class="card">
            <h3>III. EDUCATIONAL BACKGROUND</h3>
            
            <?php if ($education->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Level</th>
                            <th>School Name</th>
                            <th>Degree/Course</th>
                            <th>Period</th>
                            <th>Year Graduated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($ed = $education->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ed['level']); ?></td>
                                <td><?php echo htmlspecialchars($ed['school_name']); ?></td>
                                <td><?php echo htmlspecialchars($ed['degree_course'] ?? '-'); ?></td>
                                <td><?php echo $ed['year_from'] . ' - ' . ($ed['year_to'] ?? 'Present'); ?></td>
                                <td><?php echo $ed['year_graduated'] ?? '-'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">No educational background recorded</div>
            <?php endif; ?>
            
            <div class="section-actions">
                <a href="edit_educational_background.php" class="btn btn-primary">✏️ Edit Educational Background</a>
            </div>
        </div>

        <!-- V. Work Experience -->
        <div class="card">
            <h3>V. WORK EXPERIENCE</h3>
            
            <?php if ($work_experience->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Position</th>
                            <th>Company/Office</th>
                            <th>Period</th>
                            <th>Monthly Salary</th>
                            <th>Government Service</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($work = $work_experience->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($work['position_title']); ?></td>
                                <td><?php echo htmlspecialchars($work['company_name']); ?></td>
                                <td><?php echo date('M Y', strtotime($work['date_from'])) . ' - ' . ($work['is_present'] ? 'Present' : date('M Y', strtotime($work['date_to']))); ?></td>
                                <td>₱<?php echo number_format($work['monthly_salary'], 2); ?></td>
                                <td><?php echo $work['is_government_service'] ? 'Yes' : 'No'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">No work experience recorded</div>
            <?php endif; ?>
            
            <div class="section-actions">
                <a href="edit_work_experience.php" class="btn btn-primary">✏️ Edit Work Experience</a>
            </div>
        </div>

        <div class="alert alert-info">
            <strong>📝 Note:</strong> This is a simplified view of your 201 File. For complete PDS form or to update other sections (Civil Service Eligibility, Training & Seminars), please contact HR Department.
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
</script>
</body>
</html>