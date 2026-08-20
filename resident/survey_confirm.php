<?php
require_once "../includes/session.php";

if(!isResident()){
    redirectToLogin();
}

$survey_title = isset($_GET['title']) ? htmlspecialchars($_GET['title']) : 'Survey';

$is_partial = isset($_GET['partial']) && $_GET['partial'] == '1';
?>
<?php if(!$is_partial){ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Submitted - Resident Information System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3><i class="bi bi-hospital"></i> Resident Information System</h3>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a href="surveys.php" class="active"><i class="bi bi-clipboard-check"></i> Health Surveys</a></li>
            <li><a href="personal_info.php"><i class="bi bi-person"></i> Personal Info</a></li>
            <li><a href="family_info.php"><i class="bi bi-people"></i> Family Info</a></li>
            <li><a href="references.php"><i class="bi bi-card-text"></i> References</a></li>
            <li><a href="photo_upload.php"><i class="bi bi-image"></i> Photo Upload</a></li>
            <li><a href="print_profile.php"><i class="bi bi-printer"></i> Print Profile</a></li>
            <li><a href="change_password.php"><i class="bi bi-key"></i> Change Password</a></li>
            <li><a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </aside>

    <div class="main-content">
        <nav class="navbar">
            <div class="navbar-container">
                <div class="navbar-brand">Survey Submitted</div>
                <div class="navbar-menu">
                    <a href="dashboard.php">Back to Dashboard</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </nav>
<?php } ?>

        <div class="breadcrumbs">
            <a href="dashboard.php">Dashboard</a>
            <span class="separator">&gt;</span>
            <a href="surveys.php">Health Surveys</a>
            <span class="separator">&gt;</span>
            <span class="active">Submitted</span>
        </div>

        <div class="card" style="max-width: 600px; margin: 2rem auto;">
            <div class="card-body" style="text-align: center; padding: 3rem 2rem;">
                <div style="font-size: 5rem; color: #28a745; margin-bottom: 1.5rem;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h2 style="margin-bottom: 0.5rem;">Survey Submitted Successfully!</h2>
                <p style="margin-bottom: 2rem; color: #95A5A6;">Thank you for completing the survey. Your responses have been recorded.</p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="surveys.php" class="btn btn-primary">
                        <i class="bi bi-clipboard-check"></i> Back to Available Surveys
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-speedometer2"></i> Return to Dashboard
                    </a>
                </div>
            </div>
        </div>
<?php if(!$is_partial){ ?>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
</body>
</html>
<?php } ?>
