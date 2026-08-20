<?php
require_once "../includes/session.php";

// Check if user is admin
if(!isAdmin()){
    redirectToLogin();
}

// Get statistics
// Total residents
$total_residents = 0;
$sql = "SELECT COUNT(*) as count FROM residents";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $total_residents = $row['count'];
    mysqli_stmt_close($stmt);
}

// Male residents
$male_count = 0;
$sql = "SELECT COUNT(*) as count FROM residents WHERE gender = 'Male'";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $male_count = $row['count'];
    mysqli_stmt_close($stmt);
}

// Female residents
$female_count = 0;
$sql = "SELECT COUNT(*) as count FROM residents WHERE gender = 'Female'";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $female_count = $row['count'];
    mysqli_stmt_close($stmt);
}

// Senior citizens (age >= 60)
$senior_count = 0;
$sql = "SELECT COUNT(*) as count FROM residents WHERE YEAR(CURDATE()) - YEAR(birthday) >= 60";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $senior_count = $row['count'];
    mysqli_stmt_close($stmt);
}

// Children (age < 18)
$children_count = 0;
$sql = "SELECT COUNT(*) as count FROM residents WHERE YEAR(CURDATE()) - YEAR(birthday) < 18";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $children_count = $row['count'];
    mysqli_stmt_close($stmt);
}

// Recently updated (last 7 days)
$recent_updates = 0;
$sql = "SELECT COUNT(*) as count FROM residents WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $recent_updates = $row['count'];
    mysqli_stmt_close($stmt);
}

// Today's updates
$today_updates = 0;
$sql = "SELECT COUNT(*) as count FROM residents WHERE DATE(updated_at) = CURDATE()";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $today_updates = $row['count'];
    mysqli_stmt_close($stmt);
}

// Latest updates (last 7 days)
$latest_updates = [];
$sql = "SELECT id, first_name, last_name, updated_at FROM residents WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY updated_at DESC LIMIT 5";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $latest_updates[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Active surveys
$active_surveys = 0;
if(tableExists('surveys')){
    $sql = "SELECT COUNT(*) as count FROM surveys WHERE is_active = 1";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $active_surveys = $row['count'];
        mysqli_stmt_close($stmt);
    }
}

// Total survey responses
$total_responses = 0;
if(tableExists('survey_responses')){
    $sql = "SELECT COUNT(*) as count FROM survey_responses";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $total_responses = $row['count'];
        mysqli_stmt_close($stmt);
    }
}

// Recent survey responses (last 7 days)
$recent_responses = 0;
if(tableExists('survey_responses')){
    $sql = "SELECT COUNT(*) as count FROM survey_responses WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $recent_responses = $row['count'];
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
    <title>Staff Dashboard - Resident Information System</title>
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
            <li><a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a href="residents.php"><i class="bi bi-people"></i> Residents</a></li>
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
                <div class="navbar-brand">Staff Dashboard</div>
                <div class="navbar-menu">
                    <a href="residents.php">View Residents</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </nav>
<?php } ?>

        <!-- Breadcrumbs -->
        <div class="breadcrumbs">
            <a href="dashboard.php">Dashboard</a>
            <span class="separator">&gt;</span>
            <span class="active">Overview</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Welcome to the Staff Administration Dashboard</p>
        </div>

        <!-- Statistics Cards -->
        <div class="dashboard-grid">
            <div class="stat-card stat-card-accent-blue">
                <div class="stat-card-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-card-value"><?php echo $total_residents; ?></div>
                <div class="stat-card-label">Total Residents</div>
            </div>

            <div class="stat-card stat-card-accent-blue">
                <div class="stat-card-icon">
                    <i class="bi bi-gender-male"></i>
                </div>
                <div class="stat-card-value"><?php echo $male_count; ?></div>
                <div class="stat-card-label">Male Residents</div>
            </div>

            <div class="stat-card stat-card-accent-purple">
                <div class="stat-card-icon">
                    <i class="bi bi-gender-female"></i>
                </div>
                <div class="stat-card-value"><?php echo $female_count; ?></div>
                <div class="stat-card-label">Female Residents</div>
            </div>

            <div class="stat-card stat-card-accent-green">
                <div class="stat-card-icon">
                    <i class="bi bi-person-hearts"></i>
                </div>
                <div class="stat-card-value"><?php echo $senior_count; ?></div>
                <div class="stat-card-label">Senior Citizens</div>
            </div>

            <div class="stat-card stat-card-accent-orange">
                <div class="stat-card-icon">
                    <i class="bi bi-emoji-smile"></i>
                </div>
                <div class="stat-card-value"><?php echo $children_count; ?></div>
                <div class="stat-card-label">Children</div>
            </div>

            <div class="stat-card stat-card-accent-green">
                <div class="stat-card-icon">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="stat-card-value"><?php echo $today_updates; ?></div>
                <div class="stat-card-label">Updates Today</div>
            </div>

            <div class="stat-card stat-card-accent-blue">
                <div class="stat-card-icon">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div class="stat-card-value"><?php echo $active_surveys; ?></div>
                <div class="stat-card-label">Active Surveys</div>
            </div>

            <div class="stat-card stat-card-accent-green">
                <div class="stat-card-icon">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <div class="stat-card-value"><?php echo $total_responses; ?></div>
                <div class="stat-card-label">Total Responses</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                    <a href="add_resident.php" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Add Resident
                    </a>
                    <a href="residents.php" class="btn btn-primary">
                        <i class="bi bi-people"></i> View Residents
                    </a>
                    <a href="search.php" class="btn btn-primary">
                        <i class="bi bi-search"></i> Search
                    </a>
                    <a href="reports.php" class="btn btn-primary">
                        <i class="bi bi-file-earmark-pdf"></i> Reports
                    </a>
                    <a href="surveys.php" class="btn btn-primary">
                        <i class="bi bi-clipboard-check"></i> Manage Surveys
                    </a>
                    <a href="survey_results.php" class="btn btn-primary">
                        <i class="bi bi-bar-chart"></i> Survey Results
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Updates -->
        <div class="card">
            <div class="card-header">
                <h3>Updates This Week</h3>
            </div>
            <div class="card-body">
                <?php if(count($latest_updates) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Resident Name</th>
                                    <th>Last Updated</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($latest_updates as $update): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($update['first_name'] . ' ' . $update['last_name']); ?></td>
                                        <td><?php echo date('M d, Y g:i A', strtotime($update['updated_at'])); ?></td>
                                        <td>
                                            <a href="view_resident.php?id=<?php echo $update['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="bi bi-inbox"></i></div>
                        <h3>No Updates This Week</h3>
                        <p>No resident records have been updated this week.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="card">
            <div class="card-header">
                <h3>Summary</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <div>
                        <label style="font-weight: 600; color: #5AA9E6;">Total Residents</label>
                        <p style="font-size: 1.8rem; font-weight: 700; color: #2C3E50; margin: 0.5rem 0 0 0;"><?php echo $total_residents; ?></p>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #5AA9E6;">Updates This Week</label>
                        <p style="font-size: 1.8rem; font-weight: 700; color: #2ECC71; margin: 0.5rem 0 0 0;"><?php echo $recent_updates; ?></p>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #5AA9E6;">Gender Distribution</label>
                        <p style="font-size: 0.95rem; color: #2C3E50; margin: 0.5rem 0 0 0;">
                            Male: <?php echo $male_count; ?> | Female: <?php echo $female_count; ?>
                        </p>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #5AA9E6;">Age Groups</label>
                        <p style="font-size: 0.95rem; color: #2C3E50; margin: 0.5rem 0 0 0;">
                            Children: <?php echo $children_count; ?> | Senior: <?php echo $senior_count; ?>
                        </p>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #5AA9E6;">Surveys</label>
                        <p style="font-size: 0.95rem; color: #2C3E50; margin: 0.5rem 0 0 0;">
                            Active: <?php echo $active_surveys; ?> | Responses: <?php echo $total_responses; ?>
                        </p>
                    </div>
                </div>
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
