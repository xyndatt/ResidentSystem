<?php
require_once "../includes/session.php";

if(!isAdmin()){
    redirectToLogin();
}

$resident_id = $_GET['id'] ?? 0;

// Fetch resident data
$resident = null;
$sql = "SELECT * FROM residents WHERE id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $resident = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if(!$resident){
    header("location: residents.php");
    exit;
}

// Fetch spouse data
$spouse = null;
$sql = "SELECT * FROM spouse WHERE resident_id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $spouse = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Fetch children data
$children = [];
$sql = "SELECT * FROM children WHERE resident_id = ? ORDER BY id ASC";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        $children[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Calculate age
$age = 0;
if(!empty($resident['birthday'])){
    $birthDate = new DateTime($resident['birthday']);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
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
    <title>View Resident - Resident Information System</title>
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
                <div class="navbar-brand">View Resident</div>
                <div class="navbar-menu">
                    <a href="residents.php">Back to Residents</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </nav>
<?php } ?>

        <!-- Breadcrumbs -->
        <div class="breadcrumbs">
            <a href="dashboard.php">Dashboard</a>
            <span class="separator">&gt;</span>
            <a href="residents.php">Residents</a>
            <span class="separator">&gt;</span>
            <span class="active">View Resident &gt; <?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']); ?></span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1><?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']); ?></h1>
            <p>Resident Number: <?php echo htmlspecialchars($resident['resident_number']); ?></p>
        </div>

        <!-- Action Buttons -->
        <div style="margin-bottom: 2rem;">
            <a href="edit_resident.php?id=<?php echo $resident_id; ?>" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="residents.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        <!-- Profile Grid: Two Column Layout -->
        <div class="profile-grid">
            <!-- Left Column: Personal & Contact -->
            <div>
                <!-- Personal Information -->
                <div class="card">
                    <div class="card-header">
                        <h3>Personal Information</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Resident Number</label>
                                <p><?php echo htmlspecialchars($resident['resident_number']); ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Full Name</label>
                                <p><?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name']); ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Birthday</label>
                                <p><?php echo !empty($resident['birthday']) ? date('F d, Y', strtotime($resident['birthday'])) : 'Not provided'; ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Age</label>
                                <p><?php echo $age; ?> years old</p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Gender</label>
                                <p><?php echo htmlspecialchars($resident['gender'] ?? 'Not specified'); ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Civil Status</label>
                                <p><?php echo htmlspecialchars($resident['civil_status'] ?? 'Not specified'); ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Blood Type</label>
                                <p><?php echo htmlspecialchars($resident['blood_type'] ?? 'Not specified'); ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Nationality</label>
                                <p><?php echo htmlspecialchars($resident['nationality'] ?? 'Not provided'); ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Religion</label>
                                <p><?php echo htmlspecialchars($resident['religion'] ?? 'Not provided'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Information -->
                <div class="card">
                    <div class="card-header">
                        <h3>Employment Information</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Occupation</label>
                                <p><?php echo htmlspecialchars($resident['occupation'] ?? 'Not provided'); ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Employer</label>
                                <p><?php echo htmlspecialchars($resident['employer'] ?? 'Not provided'); ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Employer Address</label>
                                <p><?php echo htmlspecialchars($resident['employer_address'] ?? 'Not provided'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Contact, Family, Photo -->
            <div>
                <!-- Contact Information -->
                <div class="card">
                    <div class="card-header">
                        <h3>Contact Information</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Address</label>
                                <p><?php echo htmlspecialchars($resident['address'] ?? 'Not provided'); ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Contact Number</label>
                                <p><?php echo htmlspecialchars($resident['contact_number'] ?? 'Not provided'); ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Email</label>
                                <p><?php echo htmlspecialchars($resident['email'] ?? 'Not provided'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Family Information -->
                <?php if($spouse || count($children) > 0): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>Family Information</h3>
                        </div>
                        <div class="card-body">
                            <?php if($spouse): ?>
                                <div style="margin-bottom: 1.5rem;">
                                    <h4 style="color: #5AA9E6; margin-bottom: 1rem;">Spouse</h4>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                        <div>
                                            <label style="font-weight: 600; color: #5AA9E6;">Name</label>
                                            <p><?php echo htmlspecialchars($spouse['spouse_name']); ?></p>
                                        </div>
                                        <div>
                                            <label style="font-weight: 600; color: #5AA9E6;">Occupation</label>
                                            <p><?php echo htmlspecialchars($spouse['occupation'] ?? 'Not provided'); ?></p>
                                        </div>
                                        <div>
                                            <label style="font-weight: 600; color: #5AA9E6;">Employer</label>
                                            <p><?php echo htmlspecialchars($spouse['employer'] ?? 'Not provided'); ?></p>
                                        </div>
                                        <div>
                                            <label style="font-weight: 600; color: #5AA9E6;">Contact Number</label>
                                            <p><?php echo htmlspecialchars($spouse['contact_number'] ?? 'Not provided'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if(count($children) > 0): ?>
                                <div>
                                    <h4 style="color: #5AA9E6; margin-bottom: 1rem;">Children</h4>
                                    <div style="display: grid; gap: 1rem;">
                                        <?php foreach($children as $child): ?>
                                            <div style="background: var(--primary-lightest); padding: 1rem; border-radius: 12px;">
                                                <p style="margin: 0; font-weight: 600;"><?php echo htmlspecialchars($child['child_name']); ?></p>
                                                <p style="margin: 0.5rem 0 0 0; color: #95A5A6; font-size: 0.9rem;">
                                                    <?php
                                                    if($child['birthday']){
                                                        echo date('F d, Y', strtotime($child['birthday'])) . ' • ' . $child['gender'];
                                                    }
                                                    ?>
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Photo Display -->
                <?php if(!empty($resident['photo'])): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>Resident Photo</h3>
                        </div>
                        <div class="card-body" style="text-align: center;">
                            <img src="../assets/uploads/<?php echo htmlspecialchars($resident['photo']); ?>"
                                 alt="Resident Photo"
                                 style="max-width: 200px; height: auto; border-radius: 25px; box-shadow: 10px 10px 20px rgba(0,0,0,.08), -10px -10px 20px white;">
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Metadata -->
                <div class="card">
                    <div class="card-header">
                        <h3>Record Information</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Created</label>
                                <p><?php echo date('F d, Y g:i A', strtotime($resident['created_at'])); ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #5AA9E6;">Last Updated</label>
                                <p><?php echo date('F d, Y g:i A', strtotime($resident['updated_at'])); ?></p>
                            </div>
                        </div>
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
