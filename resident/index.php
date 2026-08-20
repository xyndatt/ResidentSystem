<?php
require_once "../includes/session.php";

// Check if user is resident
if(!isResident()){
    redirectToLogin();
}

// Get resident name for welcome message
$resident_id = $_SESSION["resident_id"];
$resident_name = '';
$sql = "SELECT first_name FROM residents WHERE id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($row = mysqli_fetch_assoc($result)){
        $resident_name = $row['first_name'];
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Information System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .layout-shell {
            margin-left: 220px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 0;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .main-content > .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            flex-shrink: 0;
        }

        #spa-content {
            flex: 1;
            padding: 2rem;
        }

        .navbar-menu { gap: 1rem; }
        .navbar-menu a {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Fixed sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3><i class="bi bi-hospital"></i> Resident Information System</h3>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="active" data-spa><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a href="surveys.php" data-spa><i class="bi bi-clipboard-check"></i> Health Surveys</a></li>
            <li><a href="personal_info.php" data-spa><i class="bi bi-person"></i> Personal Info</a></li>
            <li><a href="family_info.php" data-spa><i class="bi bi-people"></i> Family Info</a></li>
            <li><a href="references.php" data-spa><i class="bi bi-card-text"></i> References</a></li>
            <li><a href="photo_upload.php" data-spa><i class="bi bi-image"></i> Photo Upload</a></li>
            <li><a href="print_profile.php" data-spa><i class="bi bi-printer"></i> Print Profile</a></li>
            <li><a href="change_password.php" data-spa><i class="bi bi-key"></i> Change Password</a></li>
            <li><a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Offset wrapper (sidebar width) -->
    <div class="layout-shell">
        <div class="main-content">
            <nav class="navbar">
                <div class="navbar-container">
                    <div class="navbar-brand">Welcome, <?php echo htmlspecialchars($resident_name); ?></div>
                    <div class="navbar-menu">
                        <a href="personal_info.php" data-spa>Edit Profile</a>
                        <a href="../auth/logout.php">Logout</a>
                    </div>
                </div>
            </nav>

            <div id="spa-content">
                <div class="spa-loading">
                    <div class="spinner"></div>
                    <p>Loading...</p>
                </div>
            </div>
        </div>

    <script src="../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            initSPA('spa-content');
        });
    </script>
</body>
</html>
