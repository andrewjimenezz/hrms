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

// Get all users
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users = $conn->query($users_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | HRMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        
        .container { display: flex; min-height: 100vh; }
        
        .main { flex: 1; margin-left: 250px; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header h1 { color: #1a1a2e; }
        
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; font-weight: bold; }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #34495e; color: white; font-weight: bold; }
        tr:hover { background: #f8f9fa; }
        
        .role-badge { padding: 5px 10px; border-radius: 3px; font-size: 12px; font-weight: bold; }
        .role-admin { background: #e74c3c; color: white; }
        .role-employee { background: #3498db; color: white; }
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; }
        .modal-content { background: white; width: 600px; margin: 50px auto; padding: 30px; border-radius: 8px; max-height: 90vh; overflow-y: auto; }
        .modal-header { margin-bottom: 20px; border-bottom: 2px solid #f39c12; padding-bottom: 10px; }
        .modal-header h2 { color: #1a1a2e; }
        .close { float: right; font-size: 28px; cursor: pointer; color: #999; }
        .close:hover { color: #333; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #2c3e50; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .search-box { padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 300px; font-size: 14px; }
        
        .action-buttons { display: flex; gap: 5px; }
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
        <a href="admin_users.php" class="active"><i class="fa-solid fa-user"></i><span>Manage Users</span></a>
        <a href="admin_attendance.php"><i class="fa-solid fa-calendar-check"></i><span>Manage Attendance</span></a>
        <a href="admin_leave.php"><i class="fa-solid fa-umbrella-beach"></i><span>Manage Leave</span></a>
        <a href="admin_payroll.php"><i class="fa-solid fa-money-bill-wave"></i><span>Manage Payroll</span></a>
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
                <h1>Manage Users</h1>
                <p>View, add, edit, and manage all system users</p>
            </div>
            <button class="btn btn-primary" onclick="document.getElementById('addUserModal').style.display='block'">
                ➕ Add New User
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

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>All Users (<?php echo $users->num_rows; ?>)</h3>
                <input type="text" id="searchInput" class="search-box" placeholder="🔍 Search by name, email, or ID..." onkeyup="searchTable()">
            </div>
            
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Contact</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($users->num_rows > 0): ?>
                        <?php while($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['employee_id']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['department']); ?></td>
                                <td><?php echo htmlspecialchars($user['contact_number']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo strtoupper($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-warning btn-sm" onclick="editUser(<?php echo $user['id']; ?>)">✏️ Edit</button>
                                        <?php if($user['id'] != $user_id): ?>
                                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')">🗑️ Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; color: #999;">No users found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="document.getElementById('addUserModal').style.display='none'">&times;</span>
            <h2>➕ Add New User</h2>
        </div>
        
        <form action="process_add_user.php" method="POST">
            <div class="form-group">
                <label>Employee ID *</label>
                <input type="text" name="employee_id" required placeholder="e.g., EMP001">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Contact Number *</label>
                <input type="text" name="contact_number" required>
            </div>
            
            <div class="form-group">
                <label>Department *</label>
                <select name="department" required>
                    <option value="">Select Department</option>
                    <option value="HR">HR</option>
                    <option value="Finance">Finance</option>
                    <option value="Registrar">Registrar</option>
                    <option value="CCSE - College of Computer Studies and Engineering">CCSE</option>
                    <option value="CTHM - College of Tourism and Hospitality Management">CTHM</option>
                    <option value="CBAM - College of Business Administration and Management">CBAM</option>
                    <option value="CTED - College of Teacher Education">CTED</option>
                    <option value="CNAHS - College of Nursing and Allied Health Sciences">CNAHS</option>
                    <option value="COA - College of Agriculture">COA</option>
                    <option value="CAS - College of Arts and Sciences">CAS</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Role *</label>
                <select name="role" required>
                    <option value="employee">Employee</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">✅ Create User</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('addUserModal').style.display='none'">❌ Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="document.getElementById('editUserModal').style.display='none'">&times;</span>
            <h2>✏️ Edit User</h2>
        </div>
        
        <form action="process_edit_user.php" method="POST" id="editUserForm">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="form-group">
                <label>Employee ID</label>
                <input type="text" id="edit_employee_id" disabled>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" id="edit_first_name" required>
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" id="edit_last_name" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" id="edit_email" required>
            </div>
            
            <div class="form-group">
                <label>Contact Number *</label>
                <input type="text" name="contact_number" id="edit_contact_number" required>
            </div>
            
            <div class="form-group">
                <label>Department *</label>
                <select name="department" id="edit_department" required>
                    <option value="">Select Department</option>
                    <option value="HR">HR</option>
                    <option value="Finance">Finance</option>
                    <option value="Registrar">Registrar</option>
                    <option value="CCSE - College of Computer Studies and Engineering">CCSE</option>
                    <option value="CTHM - College of Tourism and Hospitality Management">CTHM</option>
                    <option value="CBAM - College of Business Administration and Management">CBAM</option>
                    <option value="CTED - College of Teacher Education">CTED</option>
                    <option value="CNAHS - College of Nursing and Allied Health Sciences">CNAHS</option>
                    <option value="COA - College of Agriculture">COA</option>
                    <option value="CAS - College of Arts and Sciences">CAS</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Role *</label>
                <select name="role" id="edit_role" required>
                    <option value="employee">Employee</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">💾 Save Changes</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('editUserModal').style.display='none'">❌ Cancel</button>
            </div>
        </form>
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


// Search function
function searchTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('usersTable');
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? '' : 'none';
    }
}

// Edit user function
function editUser(userId) {
    fetch('get_user.php?id=' + userId)
        .then(response => response.json())
        .then(user => {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_employee_id').value = user.employee_id;
            document.getElementById('edit_first_name').value = user.first_name;
            document.getElementById('edit_last_name').value = user.last_name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_contact_number').value = user.contact_number;
            document.getElementById('edit_department').value = user.department;
            document.getElementById('edit_role').value = user.role;
            
            document.getElementById('editUserModal').style.display = 'block';
        });
}

// Delete confirmation
function confirmDelete(userId, userName) {
    if (confirm('Are you sure you want to delete ' + userName + '? This action cannot be undone.')) {
        window.location.href = 'process_delete_user.php?id=' + userId;
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addUserModal');
    const editModal = document.getElementById('editUserModal');
    if (event.target == addModal) {
        addModal.style.display = 'none';
    }
    if (event.target == editModal) {
        editModal.style.display = 'none';
    }
}
</script>

</body>
</html>