<?php
require_once "../includes/session.php";

if(!isAdmin()){
    redirectToLogin();
}

$success_msg = $error_msg = "";

// Handle delete action via POST
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete']) && isset($_POST['id'])){
    $resident_id = $_POST['id'];

    if(!isset($_POST[CSRF_TOKEN_NAME]) || !validateCSRFToken($_POST[CSRF_TOKEN_NAME])){
        $error_msg = "Invalid security token. Please try again.";
    } else {
        $sql = "DELETE FROM residents WHERE id = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "i", $resident_id);
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Resident deleted successfully!";
                logActivity($_SESSION["id"], 'Delete Resident', 'Resident ' . $resident_id . ' deleted.');
            } else {
                $error_msg = "Error deleting resident.";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get all residents
$residents = [];
$sql = "SELECT id, resident_number, first_name, last_name, contact_number, email, updated_at FROM residents ORDER BY last_name ASC";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $residents[] = $row;
    }
    mysqli_stmt_close($stmt);
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
    <title>Residents - Resident Information System</title>
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
            <li><a href="residents.php" class="active"><i class="bi bi-people"></i> Residents</a></li>
            <li><a href="add_resident.php"><i class="bi bi-person-plus"></i> Add Resident</a></li>
            <li><a href="manage_staff.php"><i class="bi bi-person-badge"></i> Manage Staff</a></li>
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
        <!-- Top Navigation -->
        <nav class="navbar">
            <div class="navbar-container">
                <div class="navbar-brand">Residents Management</div>
                <div class="navbar-menu">
                    <a href="add_resident.php">Add New</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </nav>
<?php } ?>

        <!-- Breadcrumbs -->
        <div class="breadcrumbs">
            <a href="dashboard.php">Dashboard</a>
            <span class="separator">&gt;</span>
            <span class="active">Residents</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Residents</h1>
            <p>Manage all resident records</p>
        </div>

        <!-- Messages -->
        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Residents List -->
        <div class="card">
            <div class="card-header">
                <h3>All Residents (<?php echo count($residents); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if(count($residents) > 0): ?>
                    <div style="margin-bottom: 1rem;">
                        <input type="text" id="residentSearch" class="form-control" placeholder="Search by resident number, first name, or last name...">
                    </div>
                    <div class="table-responsive">
                        <table id="residentsTable">
                            <thead>
                                <tr>
                                    <th>Resident Number</th>
                                    <th>Name</th>
                                    <th>Contact Number</th>
                                    <th>Email</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($residents as $resident): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($resident['resident_number']); ?></td>
                                        <td><?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($resident['contact_number'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($resident['email'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($resident['updated_at'])); ?></td>
                                        <td>
                                            <a href="view_resident.php?id=<?php echo $resident['id']; ?>" class="btn btn-sm btn-primary" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="edit_resident.php?id=<?php echo $resident['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="residents.php" style="display:inline;" data-no-ajax onsubmit="event.preventDefault(); var f=this; showConfirmModal('Delete Resident', 'Are you sure you want to delete this resident? This action cannot be undone.', {type: 'destructive', confirmText: 'Delete'}, function(ok){ if(ok) f.submit(); });">
                                                <input type="hidden" name="delete" value="1">
                                                <input type="hidden" name="id" value="<?php echo $resident['id']; ?>">
                                                <?php outputCSRFHiddenField(); ?>
                                                <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="bi bi-people"></i></div>
                        <h3>No Residents Found</h3>
                        <p>No resident records are currently in the system.</p>
                        <a href="add_resident.php" class="empty-action">Add one now</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <script>document.addEventListener('DOMContentLoaded',function(){filterTable('residentSearch','residentsTable');});</script>
    </div>
<?php if(!$is_partial){ ?>

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
