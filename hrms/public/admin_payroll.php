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
$filter_month = isset($_GET['month']) ? $_GET['month'] : date('F');
$filter_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get payroll records for selected month/year
$payroll_query = "SELECT p.*, u.employee_id, u.first_name, u.last_name, u.department 
                  FROM payroll p 
                  JOIN users u ON p.user_id = u.id 
                  WHERE p.month = ? AND p.year = ?";

if ($filter_status !== 'all') {
    $payroll_query .= " AND p.status = '$filter_status'";
}

$payroll_query .= " ORDER BY u.last_name ASC";

$payroll_stmt = $conn->prepare($payroll_query);
$payroll_stmt->bind_param("si", $filter_month, $filter_year);
$payroll_stmt->execute();
$payroll_records = $payroll_stmt->get_result();

// Get statistics for current month/year
$stats_query = "SELECT 
                COUNT(*) as total_records,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) as processed_count,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                SUM(net_salary) as total_payroll
                FROM payroll WHERE month = ? AND year = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("si", $filter_month, $filter_year);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

$total_employees = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'employee'")->fetch_assoc()['count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payroll | HRMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        
        .container { display: flex; min-height: 100vh; }
                
        .main { flex: 1; margin-left: 250px; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header h1 { color: #1a1a2e; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-number { font-size: 28px; font-weight: bold; margin: 10px 0; }
        .stat-label { color: #7f8c8d; font-size: 13px; }
        .stat-draft { color: #95a5a6; }
        .stat-processed { color: #f39c12; }
        .stat-paid { color: #27ae60; }
        .stat-total { color: #3498db; }
        
        .filters { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .filters select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .filters button { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 14px; }
        .btn-filter { background: #3498db; color: white; }
        .btn-generate { background: #27ae60; color: white; }
        .btn-bulk { background: #f39c12; color: white; }
        
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .card h3 { color: #1a1a2e; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f39c12; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; font-size: 13px; }
        th { background: #34495e; color: white; font-weight: bold; }
        tr:hover { background: #f8f9fa; }
        
        .status-badge { padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .status-draft { background: #ecf0f1; color: #7f8c8d; }
        .status-processed { background: #fff3cd; color: #856404; }
        .status-paid { background: #d4edda; color: #155724; }
        
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; font-weight: bold; }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 11px; }
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; }
        .modal-content { background: white; width: 600px; margin: 50px auto; padding: 30px; border-radius: 8px; max-height: 90vh; overflow-y: auto; }
        .modal-header { margin-bottom: 20px; border-bottom: 2px solid #f39c12; padding-bottom: 10px; }
        .close { float: right; font-size: 28px; cursor: pointer; color: #999; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #2c3e50; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .action-buttons { display: flex; gap: 5px; }
        
        .payroll-summary { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 15px; }
        .payroll-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #dee2e6; }
        .payroll-total { font-size: 18px; font-weight: bold; color: #27ae60; margin-top: 10px; }
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
        <a href="admin_attendance.php"><i class="fa-solid fa-calendar-check"></i><span>Manage Attendance</span></a>
        <a href="admin_leave.php"><i class="fa-solid fa-umbrella-beach"></i><span>Manage Leave</span></a>
        <a href="admin_payroll.php" class="active"><i class="fa-solid fa-money-bill-wave"></i><span>Manage Payroll</span></a>
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
                <h1>Manage Payroll</h1>
                <p>Generate, process, and manage employee payroll</p>
            </div>
            <button class="btn btn-success" onclick="document.getElementById('generatePayrollModal').style.display='block'">
                ➕ Generate Payroll
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
        if (isset($_SESSION['info'])) {
            echo "<div class='alert alert-info'>" . $_SESSION['info'] . "</div>";
            unset($_SESSION['info']);
        }
        ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">DRAFT</div>
                <div class="stat-number stat-draft"><?php echo $stats['draft_count']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">PROCESSED</div>
                <div class="stat-number stat-processed"><?php echo $stats['processed_count']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">PAID</div>
                <div class="stat-number stat-paid"><?php echo $stats['paid_count']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">TOTAL PAYROLL</div>
                <div class="stat-number stat-total">₱<?php echo number_format($stats['total_payroll'], 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">EMPLOYEES</div>
                <div class="stat-number"><?php echo $total_employees; ?></div>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="filters">
            <label>Month:</label>
            <select name="month">
                <?php
                $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                foreach($months as $month) {
                    $selected = ($month == $filter_month) ? 'selected' : '';
                    echo "<option value='$month' $selected>$month</option>";
                }
                ?>
            </select>
            
            <label>Year:</label>
            <select name="year">
                <?php
                $current_year = date('Y');
                for($y = $current_year - 2; $y <= $current_year + 1; $y++) {
                    $selected = ($y == $filter_year) ? 'selected' : '';
                    echo "<option value='$y' $selected>$y</option>";
                }
                ?>
            </select>
            
            <label>Status:</label>
            <select name="status">
                <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="draft" <?php echo $filter_status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                <option value="processed" <?php echo $filter_status == 'processed' ? 'selected' : ''; ?>>Processed</option>
                <option value="paid" <?php echo $filter_status == 'paid' ? 'selected' : ''; ?>>Paid</option>
            </select>
            
            <button type="submit" class="btn-filter">🔍 Filter</button>
            
            <?php if($stats['processed_count'] > 0): ?>
                <button type="button" class="btn-bulk" onclick="bulkMarkPaid()">✅ Mark All as Paid</button>
            <?php endif; ?>
        </form>

        <!-- Payroll Records Table -->
        <div class="card">
            <h3>💰 Payroll Records - <?php echo $filter_month . ' ' . $filter_year; ?></h3>
            
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Basic Salary</th>
                        <th>Allowances</th>
                        <th>GSIS</th>
                        <th>Pag-IBIG</th>
                        <th>PhilHealth</th>
                        <th>Tax</th>
                        <th>Total Deductions</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th>Payment Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($payroll_records->num_rows > 0): ?>
                        <?php while($record = $payroll_records->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($record['employee_id']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($record['department']); ?></td>
                                <td>₱<?php echo number_format($record['basic_salary'], 2); ?></td>
                                <td>₱<?php echo number_format($record['allowances'], 2); ?></td>
                                <td>₱<?php echo number_format($record['gsis'] ?? 0, 2); ?></td>
                                <td>₱<?php echo number_format($record['pag_ibig'] ?? 0, 2); ?></td>
                                <td>₱<?php echo number_format($record['philhealth'] ?? 0, 2); ?></td>
                                <td>₱<?php echo number_format($record['withholding_tax'] ?? 0, 2); ?></td>
                                <td>₱<?php echo number_format($record['deductions'], 2); ?></td>
                                <td><strong>₱<?php echo number_format($record['net_salary'], 2); ?></strong></td>
                                <td><span class="status-badge status-<?php echo $record['status']; ?>"><?php echo ucfirst($record['status']); ?></span></td>
                                <td><?php echo $record['payment_date'] ? date('M j, Y', strtotime($record['payment_date'])) : '-'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-primary btn-sm" onclick="viewPayroll(<?php echo $record['id']; ?>)">👁️</button>
                                        <button class="btn btn-warning btn-sm" onclick="editPayroll(<?php echo $record['id']; ?>)">✏️</button>
                                        <?php if($record['status'] == 'draft'): ?>
                                            <button class="btn btn-success btn-sm" onclick="processPayroll(<?php echo $record['id']; ?>)">⚡ Process</button>
                                        <?php endif; ?>
                                        <?php if($record['status'] == 'processed'): ?>
                                            <button class="btn btn-success btn-sm" onclick="markPaid(<?php echo $record['id']; ?>)">✅ Paid</button>
                                        <?php endif; ?>
                                        <?php if($record['status'] == 'draft'): ?>
                                            <button class="btn btn-danger btn-sm" onclick="deletePayroll(<?php echo $record['id']; ?>)">🗑️</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align: center; color: #999; padding: 40px;">
                            No payroll records for this period. Click "Generate Payroll" to create.
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Generate Payroll Modal -->
<div id="generatePayrollModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="document.getElementById('generatePayrollModal').style.display='none'">&times;</span>
            <h2>➕ Generate Payroll</h2>
        </div>
        
        <form action="process_generate_payroll.php" method="POST">
            <div class="form-group">
                <label>Employee *</label>
                <select name="user_id" required>
                    <option value="">Select Employee</option>
                    <?php
                    $employees = $conn->query("SELECT id, employee_id, first_name, last_name FROM users WHERE role = 'employee' ORDER BY first_name");
                    while($emp = $employees->fetch_assoc()) {
                        echo "<option value='" . $emp['id'] . "'>" . $emp['employee_id'] . " - " . $emp['first_name'] . " " . $emp['last_name'] . "</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Month *</label>
                    <select name="month" required>
                        <?php foreach($months as $month) echo "<option value='$month'>$month</option>"; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Year *</label>
                    <select name="year" required>
                        <?php for($y = $current_year - 1; $y <= $current_year + 1; $y++) echo "<option value='$y'>$y</option>"; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Basic Salary *</label>
                    <input type="number" name="basic_salary" step="0.01" required placeholder="0.00" id="basic_salary" onchange="calculateNet()">
                </div>
                <div class="form-group">
                    <label>Allowances</label>
                    <input type="number" name="allowances" step="0.01" value="0" placeholder="0.00" id="allowances" onchange="calculateNet()">
                </div>
            </div>
            
            <div class="form-group">
                <label>Deductions</label>
                <input type="number" name="deductions" step="0.01" value="0" placeholder="0.00" id="deductions" onchange="calculateNet()">
            </div>
            
            <div class="payroll-summary">
                <div class="payroll-row">
                    <span>Basic Salary:</span>
                    <span id="display_basic">₱0.00</span>
                </div>
                <div class="payroll-row">
                    <span>Allowances:</span>
                    <span id="display_allowances">₱0.00</span>
                </div>
                <div class="payroll-row">
                    <span>Deductions:</span>
                    <span id="display_deductions">₱0.00</span>
                </div>
                <div class="payroll-row payroll-total">
                    <span>Net Salary:</span>
                    <span id="display_net">₱0.00</span>
                </div>
            </div>
            
            <input type="hidden" name="net_salary" id="net_salary" value="0">
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">💾 Generate Payroll</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('generatePayrollModal').style.display='none'">❌ Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Payroll Modal -->
<div id="editPayrollModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="document.getElementById('editPayrollModal').style.display='none'">&times;</span>
            <h2>✏️ Edit Payroll</h2>
        </div>
        
        <form action="process_edit_payroll.php" method="POST">
            <input type="hidden" name="payroll_id" id="edit_payroll_id">
            
            <div class="form-group">
                <label>Employee</label>
                <input type="text" id="edit_employee_name" disabled>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Basic Salary *</label>
                    <input type="number" name="basic_salary" id="edit_basic_salary" step="0.01" required onchange="calculateEditNet()">
                </div>
                <div class="form-group">
                    <label>Allowances</label>
                    <input type="number" name="allowances" id="edit_allowances" step="0.01" onchange="calculateEditNet()">
                </div>
            </div>
            
            <div class="form-group">
                <label>Deductions</label>
                <input type="number" name="deductions" id="edit_deductions" step="0.01" onchange="calculateEditNet()">
            </div>
            
            <div class="payroll-summary">
                <div class="payroll-row payroll-total">
                    <span>Net Salary:</span>
                    <span id="edit_display_net">₱0.00</span>
                </div>
            </div>
            
            <input type="hidden" name="net_salary" id="edit_net_salary">
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">💾 Save Changes</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('editPayrollModal').style.display='none'">❌ Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- View Payroll Modal -->
<div id="viewPayrollModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="document.getElementById('viewPayrollModal').style.display='none'">&times;</span>
            <h2>📄 Payslip Details</h2>
        </div>
        <div id="payrollDetailsContent"></div>
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

  
function calculateNet() {
    const basic = parseFloat(document.getElementById('basic_salary').value) || 0;
    const allowances = parseFloat(document.getElementById('allowances').value) || 0;
    const deductions = parseFloat(document.getElementById('deductions').value) || 0;
    const net = basic + allowances - deductions;
    
    document.getElementById('display_basic').textContent = '₱' + basic.toFixed(2);
    document.getElementById('display_allowances').textContent = '₱' + allowances.toFixed(2);
    document.getElementById('display_deductions').textContent = '₱' + deductions.toFixed(2);
    document.getElementById('display_net').textContent = '₱' + net.toFixed(2);
    document.getElementById('net_salary').value = net.toFixed(2);
}

function calculateEditNet() {
    const basic = parseFloat(document.getElementById('edit_basic_salary').value) || 0;
    const allowances = parseFloat(document.getElementById('edit_allowances').value) || 0;
    const deductions = parseFloat(document.getElementById('edit_deductions').value) || 0;
    const net = basic + allowances - deductions;
    
    document.getElementById('edit_display_net').textContent = '₱' + net.toFixed(2);
    document.getElementById('edit_net_salary').value = net.toFixed(2);
}

function editPayroll(payrollId) {
    fetch('get_payroll.php?id=' + payrollId)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_payroll_id').value = data.id;
            document.getElementById('edit_employee_name').value = data.employee_name;
            document.getElementById('edit_basic_salary').value = data.basic_salary;
            document.getElementById('edit_allowances').value = data.allowances;
            document.getElementById('edit_deductions').value = data.deductions;
            calculateEditNet();
            document.getElementById('editPayrollModal').style.display = 'block';
        });
}

function viewPayroll(payrollId) {
    fetch('get_payroll.php?id=' + payrollId)
        .then(response => response.json())
        .then(data => {
            const content = `
                <div class="payroll-summary">
                    <h3 style="text-align: center; margin-bottom: 20px;">Payslip - ${data.month} ${data.year}</h3>
                    <div class="payroll-row"><strong>Employee:</strong> <span>${data.employee_name}</span></div>
                    <div class="payroll-row"><strong>Employee ID:</strong> <span>${data.employee_id}</span></div>
                    <div class="payroll-row"><strong>Department:</strong> <span>${data.department}</span></div>
                    <hr style="margin: 15px 0;">
                    <div class="payroll-row"><strong>Basic Salary:</strong> <span>₱${parseFloat(data.basic_salary).toFixed(2)}</span></div>
                    <div class="payroll-row"><strong>Allowances:</strong> <span>₱${parseFloat(data.allowances).toFixed(2)}</span></div>
                    <div class="payroll-row"><strong>Deductions:</strong> <span>₱${parseFloat(data.deductions).toFixed(2)}</span></div>
                    <hr style="margin: 15px 0;">
                    <div class="payroll-row payroll-total"><strong>Net Salary:</strong> <span>₱${parseFloat(data.net_salary).toFixed(2)}</span></div>
                    <hr style="margin: 15px 0;">
                    <div class="payroll-row"><strong>Status:</strong> <span class="status-badge status-${data.status}">${data.status.toUpperCase()}</span></div>
                    ${data.payment_date ? `<div class="payroll-row"><strong>Payment Date:</strong> <span>${data.payment_date}</span></div>` : ''}
                </div>
            `;
            document.getElementById('payrollDetailsContent').innerHTML = content;
            document.getElementById('viewPayrollModal').style.display = 'block';
        });
}

function markPaid(payrollId) {
    if (confirm('Mark this payroll as PAID? This action confirms payment has been made.')) {
        window.location.href = 'process_mark_paid.php?id=' + payrollId;
    }
}

function processPayroll(payrollId) {
    if (confirm('Process this payroll? This will calculate government deductions (GSIS, Pag-IBIG, PhilHealth, Tax).')) {
        window.location.href = 'process_payroll_calculate.php?id=' + payrollId;
    }
}

function bulkMarkPaid() {
    if (confirm('Mark ALL processed payroll records as PAID for this period?')) {
        window.location.href = 'process_bulk_mark_paid.php?month=<?php echo $filter_month; ?>&year=<?php echo $filter_year; ?>';
    }
}

function deletePayroll(payrollId) {
    if (confirm('Delete this payroll record? This action cannot be undone.')) {
        window.location.href = 'process_delete_payroll.php?id=' + payrollId;
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