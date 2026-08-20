<?php
require_once "../includes/session.php";

if(!isAdmin()){
    redirectToLogin();
}

$search_results = [];
$search_term = "";

// Handle search
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $search_term = trim($_POST["search_term"] ?? "");

    if(!empty($search_term)){
        $search_term_like = "%" . $search_term . "%";
        $sql = "SELECT id, resident_number, first_name, last_name, contact_number, email FROM residents
                WHERE resident_number LIKE ? OR first_name LIKE ? OR last_name LIKE ?
                ORDER BY last_name ASC";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "sss", $search_term_like, $search_term_like, $search_term_like);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                $search_results[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// AJAX autocomplete endpoint
if(isset($_GET['ajax_search'])){
    header('Content-Type: application/json');
    $q = trim($_GET['q'] ?? "");
    $suggestions = [];
    if(strlen($q) >= 2){
        $q_like = "%" . $q . "%";
        $sql = "SELECT id, resident_number, first_name, last_name FROM residents
                WHERE resident_number LIKE ? OR first_name LIKE ? OR last_name LIKE ?
                ORDER BY last_name ASC LIMIT 10";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "sss", $q_like, $q_like, $q_like);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                $suggestions[] = [
                    'label' => $row['first_name'] . ' ' . $row['last_name'] . ' (' . $row['resident_number'] . ')',
                    'name' => $row['first_name'] . ' ' . $row['last_name'],
                    'id' => $row['id']
                ];
            }
            mysqli_stmt_close($stmt);
        }
    }
    echo json_encode($suggestions);
    exit;
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
    <title>Search - Resident Information System</title>
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
            <li><a href="search.php" class="active"><i class="bi bi-search"></i> Search</a></li>
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
                <div class="navbar-brand">Search Residents</div>
                <div class="navbar-menu">
                    <a href="residents.php">View All</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </nav>
        <?php } ?>

        <!-- Breadcrumbs -->
        <div class="breadcrumbs">
            <a href="dashboard.php">Dashboard</a>
            <span class="separator">&gt;</span>
            <span class="active">Search</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Search Residents</h1>
            <p>Find residents by resident number, first name, or last name</p>
        </div>

        <!-- Search Form -->
        <div class="card">
            <div class="card-header">
                <h3>Search</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Search Term</label>
                        <div class="autocomplete-wrapper">
                            <input type="text" name="search_term" id="searchInput" class="form-control" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Enter resident number, first name, or last name..." required>
                            <i class="bi bi-search autocomplete-icon"></i>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Search
                    </button>
                </form>
            </div>
        </div>

        <!-- Search Results -->
        <?php if(!empty($search_term)): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Search Results (<?php echo count($search_results); ?> found)</h3>
                </div>
                <div class="card-body">
                    <?php if(count($search_results) > 0): ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Resident Number</th>
                                        <th>Name</th>
                                        <th>Contact Number</th>
                                        <th>Email</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($search_results as $result): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($result['resident_number']); ?></td>
                                            <td><?php echo htmlspecialchars($result['first_name'] . ' ' . $result['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($result['contact_number'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($result['email'] ?? 'N/A'); ?></td>
                                            <td>
                                                <a href="view_resident.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <a href="edit_resident.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon"><i class="bi bi-search"></i></div>
                            <h3>No Results Found</h3>
                            <p>No residents match your search criteria. Try a different search term.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <script>document.addEventListener('DOMContentLoaded',function(){setupSearchAutocomplete('searchInput','search.php?ajax_search=1');});</script>

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
