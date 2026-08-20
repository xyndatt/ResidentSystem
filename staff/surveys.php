<?php
require_once "../includes/session.php";

if(!isAdmin()){
    redirectToLogin();
}

$success_msg = $error_msg = "";

if(tableExists('surveys')){
    if(isset($_GET['action']) && isset($_GET['id'])){
        $action = $_GET['action'];
        $survey_id = intval($_GET['id']);

        if($action === 'toggle'){
            $sql = "UPDATE surveys SET is_active = NOT is_active WHERE id = ?";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "i", $survey_id);
                if(mysqli_stmt_execute($stmt)){
                    showToast("Survey status updated successfully.");
                } else {
                    showToast("Error updating survey status.", "error");
                }
                mysqli_stmt_close($stmt);
            }
            header("Location: surveys.php");
            exit;
        }

        if($action === 'delete'){
            $sql = "DELETE FROM surveys WHERE id = ?";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "i", $survey_id);
                if(mysqli_stmt_execute($stmt)){
                    logActivity($_SESSION["id"], 'Delete Survey', 'Survey ID: ' . $survey_id . ' deleted.');
                    showToast("Survey deleted successfully.");
                } else {
                    showToast("Error deleting survey.", "error");
                }
                mysqli_stmt_close($stmt);
            }
            header("Location: surveys.php");
            exit;
        }
    }

    $surveys = [];
    $sql = "SELECT s.*, 
            (SELECT COUNT(DISTINCT sr.resident_id) FROM survey_responses sr WHERE sr.survey_id = s.id) as response_count
            FROM surveys s ORDER BY s.created_at DESC";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $surveys[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $surveys = [];
    $error_msg = "Survey system is not configured. Please run the database migration.";
}

$is_partial = isset($_GET['partial']) && $_GET['partial'] == '1';
?>
<?php if(!$is_partial){ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Management - Resident Information System</title>
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
            <li><a href="manage_staff.php"><i class="bi bi-person-badge"></i> Manage Staff</a></li>
            <li><a href="search.php"><i class="bi bi-search"></i> Search</a></li>
            <li><a href="reports.php"><i class="bi bi-file-earmark-pdf"></i> Reports</a></li>
            <li><a href="activity_logs.php"><i class="bi bi-clock-history"></i> Activity Logs</a></li>
            <li class="sidebar-divider"><span>Surveys</span></li>
            <li><a href="surveys.php" class="active"><i class="bi bi-clipboard-check"></i> Manage Surveys</a></li>
            <li><a href="survey_results.php"><i class="bi bi-bar-chart"></i> Survey Results</a></li>
            <li><a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="navbar">
            <div class="navbar-container">
                <div class="navbar-brand">Survey Management</div>
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
            <span class="active">Surveys</span>
        </div>

        <!-- Page Header -->
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1>Survey Management</h1>
                <p>Create and manage community surveys</p>
            </div>
            <a href="create_survey.php" class="btn btn-primary" style="width: auto;">
                <i class="bi bi-plus-circle"></i> Create New Survey
            </a>
        </div>

        <?php displayToasts(); ?>

        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-info"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Survey List -->
        <div class="card">
            <div class="card-header">
                <h3>All Surveys (<?php echo count($surveys); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if(count($surveys) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Open Date</th>
                                    <th>Close Date</th>
                                    <th>Responses</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($surveys as $survey): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($survey['title']); ?></strong>
                                            <?php if(!empty($survey['description'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($survey['description'], 0, 80)) . (strlen($survey['description']) > 80 ? '...' : ''); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($survey['is_active']): ?>
                                                <span style="background: rgba(46, 204, 113, 0.1); color: var(--success); padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                                    <i class="bi bi-check-circle"></i> Active
                                                </span>
                                            <?php else: ?>
                                                <span style="background: rgba(149, 165, 166, 0.1); color: var(--gray); padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                                    <i class="bi bi-pause-circle"></i> Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $survey['open_date'] ? date('M d, Y', strtotime($survey['open_date'])) : '<span class="text-muted">Not set</span>'; ?></td>
                                        <td><?php echo $survey['close_date'] ? date('M d, Y', strtotime($survey['close_date'])) : '<span class="text-muted">Not set</span>'; ?></td>
                                        <td>
                                            <span style="font-weight: 600; color: var(--primary);"><?php echo $survey['response_count']; ?></span>
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="survey_results.php?id=<?php echo $survey['id']; ?>" class="btn btn-sm btn-success" title="View Results">
                                                    <i class="bi bi-bar-chart"></i>
                                                </a>
                                                <a href="edit_survey.php?id=<?php echo $survey['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="surveys.php?action=toggle&id=<?php echo $survey['id']; ?>" 
                                                   class="btn btn-sm btn-secondary" 
                                                   title="<?php echo $survey['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                                                   onclick="event.preventDefault(); event.stopPropagation(); showConfirmModal('<?php echo $survey['is_active'] ? 'Deactivate' : 'Activate'; ?> Survey', 'Are you sure you want to <?php echo $survey['is_active'] ? 'deactivate' : 'activate'; ?> this survey?', {type: 'primary', confirmText: '<?php echo $survey['is_active'] ? 'Deactivate' : 'Activate'; ?>'}).then(function(ok){ if(ok) window.location.href='surveys.php?action=toggle&id=<?php echo $survey['id']; ?>'; });">
                                                    <i class="bi bi-<?php echo $survey['is_active'] ? 'pause' : 'play'; ?>-circle"></i>
                                                </a>
                                                <a href="surveys.php?action=delete&id=<?php echo $survey['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   title="Delete"
                                                   onclick="event.preventDefault(); event.stopPropagation(); showConfirmModal('Delete Survey', 'Are you sure you want to delete this survey? This action cannot be undone.', {type: 'destructive', confirmText: 'Delete'}).then(function(ok){ if(ok) window.location.href='surveys.php?action=delete&id=<?php echo $survey['id']; ?>'; });">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <span class="empty-icon"><i class="bi bi-clipboard-data"></i></span>
                        <h3>No surveys created yet</h3>
                        <p>Create your first survey to start gathering feedback from residents.</p>
                        <a href="create_survey.php" class="empty-action">
                            <i class="bi bi-plus-circle"></i> Create your first survey
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
