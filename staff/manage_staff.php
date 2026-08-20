<?php
require_once "../includes/session.php";

if(!isAdmin()){
    redirectToLogin();
}

$success_msg = $error_msg = "";

// Handle delete action via POST
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_staff']) && isset($_POST['id'])){
    $user_id = $_POST['id'];

    if(!isset($_POST[CSRF_TOKEN_NAME]) || !validateCSRFToken($_POST[CSRF_TOKEN_NAME])){
        $error_msg = "Invalid security token. Please try again.";
    } elseif($user_id == $_SESSION['id']){
        $error_msg = "You cannot delete your own account.";
    } else {
        $sql = "DELETE FROM users WHERE id = ? AND role = 'admin'";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Staff account deleted successfully!";
                logActivity($_SESSION["id"], 'Delete Staff', 'Staff user ID ' . $user_id . ' deleted.');
            } else {
                $error_msg = "Error deleting staff account.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Handle add staff
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_staff'])){
    if(!isset($_POST[CSRF_TOKEN_NAME]) || !validateCSRFToken($_POST[CSRF_TOKEN_NAME])){
        $error_msg = "Invalid security token. Please try again.";
    } else {
        $full_name = trim($_POST["full_name"]);
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);
        $confirm_password = trim($_POST["confirm_password"]);

        if(empty($full_name) || empty($username) || empty($password) || empty($confirm_password)){
            $error_msg = "All fields are required.";
        } elseif($password !== $confirm_password){
            $error_msg = "Passwords do not match.";
        } else {
            // Check if username exists
            $sql = "SELECT id FROM users WHERE username = ?";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "s", $username);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);

                if(mysqli_stmt_num_rows($stmt) > 0){
                    $error_msg = "Username already exists.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $role = 'admin';

                    $sql_insert = "INSERT INTO users (username, password, role, full_name, is_first_login) VALUES (?, ?, ?, ?, 0)";
                    if($stmt_insert = @mysqli_prepare($link, $sql_insert)){
                        mysqli_stmt_bind_param($stmt_insert, "ssss", $username, $hashed_password, $role, $full_name);
                    } else {
                        $sql_insert = "INSERT INTO users (username, password, role, is_first_login) VALUES (?, ?, ?, 0)";
                        $stmt_insert = mysqli_prepare($link, $sql_insert);
                        mysqli_stmt_bind_param($stmt_insert, "sss", $username, $hashed_password, $role);
                    }
                    if($stmt_insert){
                        if(mysqli_stmt_execute($stmt_insert)){
                            $success_msg = "New staff account created successfully!";
                            logActivity($_SESSION["id"], 'Add Staff', 'New staff user ' . $username . ' created.');
                        } else {
                            $error_msg = "Error creating staff account.";
                        }
                        mysqli_stmt_close($stmt_insert);
                    }
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Get all staff members with their full name from residents table via JOIN
$staff_members = [];
$sql = "SELECT u.id, u.username, u.full_name, u.role, u.created_at,
                r.first_name, r.last_name
        FROM users u
        LEFT JOIN residents r ON u.resident_id = r.id
        WHERE u.role = 'admin'
        ORDER BY u.username ASC";
if($stmt = @mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $staff_members[] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    $sql = "SELECT u.id, u.username, u.role, u.created_at,
                    r.first_name, r.last_name
            FROM users u
            LEFT JOIN residents r ON u.resident_id = r.id
            WHERE u.role = 'admin'
            ORDER BY u.username ASC";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $staff_members[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

displayToasts();
$is_partial = isset($_GET['partial']) && $_GET['partial'] == '1';
?>

<?php if(!$is_partial){ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - Resident Information System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3><i class="bi bi-hospital"></i> RIS Admin</h3>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a href="residents.php"><i class="bi bi-people"></i> Residents</a></li>
            <li><a href="add_resident.php"><i class="bi bi-person-plus"></i> Add Resident</a></li>
            <li><a href="manage_staff.php" class="active"><i class="bi bi-person-badge"></i> Manage Staff</a></li>
            <li><a href="search.php"><i class="bi bi-search"></i> Search</a></li>
            <li><a href="reports.php"><i class="bi bi-file-earmark-pdf"></i> Reports</a></li>
            <li><a href="activity_logs.php"><i class="bi bi-clock-history"></i> Activity Logs</a></li>
            <li class="sidebar-divider"><span>Surveys</span></li>
            <li><a href="surveys.php"><i class="bi bi-clipboard-check"></i> Manage Surveys</a></li>
            <li><a href="survey_results.php"><i class="bi bi-bar-chart"></i> Survey Results</a></li>
            <li><a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <nav class="navbar">
            <div class="navbar-container">
                <div class="navbar-brand">Staff Management</div>
                <div class="navbar-menu">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </nav>
        <?php } ?>

        <!-- Breadcrumbs -->
        <div class="breadcrumbs">
            <a href="dashboard.php">Dashboard</a>
            <span class="separator">&gt;</span>
            <span class="active">Manage Staff</span>
        </div>

        <div class="page-header">
            <h1>Manage Staff Accounts</h1>
            <p>Add or remove administrative staff members</p>
        </div>

        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
            <!-- Add Staff Form -->
            <div class="card">
                <div class="card-header">
                    <h3>Add New Staff</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="preventDuplicateSubmit(this.querySelector('button[type=submit]'))">
                        <?php outputCSRFHiddenField(); ?>
                        <input type="hidden" name="add_staff" value="1">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-plus"></i> Create Account
                        </button>
                    </form>
                </div>
            </div>

            <!-- Staff List -->
            <div class="card">
                <div class="card-header">
                    <h3>Current Staff Members</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Role</th>
                                    <th>Date Created</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($staff_members as $staff): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($staff['username']); ?></td>
                                        <td><?php echo htmlspecialchars($staff['full_name'] ?? (($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? ''))); ?></td>
                                        <td>
                                            <span style="background: var(--primary-lightest); color: var(--primary); padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">
                                                <?php echo ucfirst(htmlspecialchars($staff['role'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($staff['created_at'])); ?></td>
                                        <td>
                                            <?php if($staff['id'] != $_SESSION['id']): ?>
                                                <form method="POST" action="manage_staff.php" style="display:inline;" onsubmit="event.preventDefault(); var f=this; showConfirmModal('Delete Staff', 'Are you sure you want to delete this staff account? This action cannot be undone.', {type: 'destructive', confirmText: 'Delete'}).then(function(ok){ if(ok) f.submit(); });">
                                                    <input type="hidden" name="delete_staff" value="1">
                                                    <input type="hidden" name="id" value="<?php echo $staff['id']; ?>">
                                                    <?php outputCSRFHiddenField(); ?>
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">Current User</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

<?php if(!$is_partial){ ?>
    </div>
    <!-- Global Footer -->
    <footer class="global-footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3><i class="bi bi-hospital"></i> Barangay Health Center</h3>
                <p>Empowering our community through efficient health services and modern technology.</p>
            </div>
            <div class="footer-column">
                <h3>Contact</h3>
                <p><i class="bi bi-telephone"></i> (02) 8888-1234</p>
                <p><i class="bi bi-envelope"></i> health@barangay.gov</p>
            </div>
            <div class="footer-column">
                <h3>Address</h3>
                <p><i class="bi bi-geo-alt"></i> 123 Health Street, Barangay Center, Metro Manila</p>
            </div>
            <div class="footer-column">
                <h3>Office Hours</h3>
                <p><i class="bi bi-clock"></i> Mon-Fri: 8:00 AM - 5:00 PM</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Barangay Health Center. All rights reserved.</p>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
</body>
</html>
<?php } ?>
