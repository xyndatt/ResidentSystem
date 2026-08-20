<?php
require_once "../includes/session.php";

// Check if user is resident
if(!isResident()){
    redirectToLogin();
}

// Get resident information
$resident_id = $_SESSION["resident_id"];
$sql = "SELECT * FROM residents WHERE id = ?";

if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $resident = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Get spouse information
$spouse = null;
$sql = "SELECT * FROM spouse WHERE resident_id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $spouse = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Get children count
$children_count = 0;
$sql = "SELECT COUNT(*) as count FROM children WHERE resident_id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $children_count = $row['count'];
    mysqli_stmt_close($stmt);
}

// Calculate profile completion percentage
$profile_fields = [
    'first_name', 'last_name', 'birthday', 'gender', 'address', 
    'contact_number', 'email', 'occupation', 'photo'
];
$completed_fields = 0;
foreach($profile_fields as $field){
    if(!empty($resident[$field])){
        $completed_fields++;
    }
}
$completion_percentage = round(($completed_fields / count($profile_fields)) * 100);

// Calculate age
$age = 0;
if(!empty($resident['birthday'])){
    $birthDate = new DateTime($resident['birthday']);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
}

// Get available surveys count (active, open, not yet completed by resident)
$available_surveys = 0;
if(tableExists('surveys') && tableExists('survey_responses')){
    $sql = "SELECT COUNT(*) as count FROM surveys s 
            WHERE s.is_active = 1 
            AND (s.open_date IS NULL OR s.open_date <= CURDATE()) 
            AND (s.close_date IS NULL OR s.close_date >= CURDATE())
            AND s.id NOT IN (
                SELECT DISTINCT sr.survey_id FROM survey_responses sr WHERE sr.resident_id = ?
            )";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $resident_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $available_surveys = $row['count'];
        mysqli_stmt_close($stmt);
    }
}

$is_partial = isset($_GET['partial']) && $_GET['partial'] == '1';
?>

<?php if(!$is_partial){ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Dashboard - Resident Information System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3><i class="bi bi-hospital"></i> Resident Information System</h3>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a href="surveys.php"><i class="bi bi-clipboard-check"></i> Health Surveys</a></li>
            <li><a href="personal_info.php"><i class="bi bi-person"></i> Personal Info</a></li>
            <li><a href="family_info.php"><i class="bi bi-people"></i> Family Info</a></li>
            <li><a href="references.php"><i class="bi bi-card-text"></i> References</a></li>
            <li><a href="photo_upload.php"><i class="bi bi-image"></i> Photo Upload</a></li>
            <li><a href="print_profile.php"><i class="bi bi-printer"></i> Print Profile</a></li>
            <li><a href="change_password.php"><i class="bi bi-key"></i> Change Password</a></li>
            <li><a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="navbar">
            <div class="navbar-container">
                <div class="navbar-brand">Welcome, <?php echo htmlspecialchars($resident['first_name']); ?></div>
                <div class="navbar-menu">
                    <a href="personal_info.php">Edit Profile</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </nav>
<?php } ?>

        <div class="content-body">
        <!-- Breadcrumbs -->
        <div class="breadcrumbs">
            <a href="dashboard.php">Dashboard</a>
            <span class="separator">&gt;</span>
            <span class="active">Overview</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Welcome to your Resident Information System dashboard</p>
        </div>

        <?php displayToasts(); ?>

        <!-- Dashboard Cards -->
        <div class="dashboard-grid">
    <!-- Welcome Card -->
    <div class="stat-card stat-card-accent-blue">
                <div class="stat-card-icon">
                    <i class="bi bi-hand-thumbs-up"></i>
                </div>
                <div class="stat-card-value">Hello!</div>
                <div class="stat-card-label">Welcome to Resident Information System</div>
            </div>

    <!-- Profile Completion Card -->
    <div class="stat-card stat-card-accent-green">
                <div class="stat-card-icon">
                    <i class="bi bi-percent"></i>
                </div>
                <div class="stat-card-value"><?php echo $completion_percentage; ?>%</div>
                <div class="stat-card-label">Profile Complete</div>
            </div>

    <!-- Photo Status Card -->
    <div class="stat-card stat-card-accent-orange">
                <div class="stat-card-icon">
                    <i class="bi bi-image"></i>
                </div>
                <div class="stat-card-value"><?php echo !empty($resident['photo']) ? 'Yes' : 'No'; ?></div>
                <div class="stat-card-label">Photo Uploaded</div>
            </div>

    <!-- Available Surveys Card -->
    <div class="stat-card stat-card-accent-purple">
                <div class="stat-card-icon">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div class="stat-card-value"><?php echo $available_surveys; ?></div>
                <div class="stat-card-label">Available Surveys</div>
            </div>

    <!-- Last Update Card -->
    <div class="stat-card stat-card-accent-blue">
                <div class="stat-card-icon">
                    <i class="bi bi-calendar-event"></i>
                </div>
                <div class="stat-card-value"><?php echo date('M d, Y', strtotime($resident['updated_at'])); ?></div>
                <div class="stat-card-label">Last Updated</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                    <a href="surveys.php" class="btn btn-primary">
                        <i class="bi bi-clipboard-check"></i> Health Surveys
                    </a>
                    <a href="personal_info.php" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Edit Personal Info
                    </a>
                    <a href="family_info.php" class="btn btn-primary">
                        <i class="bi bi-people"></i> Manage Family
                    </a>
                    <a href="photo_upload.php" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Upload Photo
                    </a>
                    <a href="print_profile.php" class="btn btn-primary">
                        <i class="bi bi-printer"></i> Print Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Personal Information Summary -->
        <div class="card">
            <div class="card-header">
                <h3>Personal Information</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <div>
                        <label style="font-weight: 600; color: #5AA9E6;">Resident Number</label>
                        <p><?php echo htmlspecialchars($resident['resident_number']); ?></p>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #5AA9E6;">Full Name</label>
                        <p><?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name']); ?></p>
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

        <!-- Family Information Summary -->
        <div class="card">
            <div class="card-header">
                <h3>Family Information</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                    <div>
                        <label style="font-weight: 600; color: #5AA9E6;">Civil Status</label>
                        <p><?php echo htmlspecialchars($resident['civil_status'] ?? 'Not specified'); ?></p>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #5AA9E6;">Spouse</label>
                        <p><?php echo $spouse ? htmlspecialchars($spouse['spouse_name']) : 'Not provided'; ?></p>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #5AA9E6;">Children</label>
                        <p><?php echo $children_count; ?> child(ren)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Photo Display -->
        <?php if(!empty($resident['photo'])): ?>
        <div class="card">
            <div class="card-header">
                <h3>Your Photo</h3>
            </div>
            <div class="card-body" style="text-align: center;">
                <img src="../assets/uploads/<?php echo htmlspecialchars($resident['photo']); ?>" 
                     alt="Resident Photo" 
                     style="max-width: 200px; height: auto; border-radius: 25px; box-shadow: 10px 10px 20px rgba(0,0,0,.08), -10px -10px 20px white;">
            </div>
        </div>
        <?php endif; ?>
        </div>
<?php if(!$is_partial){ ?>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
</body>
</html>
<?php } ?>
