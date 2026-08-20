<?php
require_once "../includes/session.php";

if(!isResident()){
    redirectToLogin();
}

$user_id = $_SESSION["id"];
$success_msg = $error_msg = "";
$is_first_login = $_SESSION["is_first_login"] ?? false;

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])){
        $error_msg = "Invalid CSRF token. Please try again.";
    } else {
        $current_password = trim($_POST["current_password"]);
        $new_password = trim($_POST["new_password"]);
        $confirm_password = trim($_POST["confirm_password"]);

        // Validate inputs
        if(empty($current_password) || empty($new_password) || empty($confirm_password)){
            $error_msg = "All fields are required.";
        } elseif($new_password !== $confirm_password){
            $error_msg = "New passwords do not match.";
        } elseif(strlen($new_password) < 6){
            $error_msg = "New password must be at least 6 characters long.";
        } else {
            // Verify current password
            $sql = "SELECT password FROM users WHERE id = ?";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                if($user && password_verify($current_password, $user['password'])){
                    // Hash new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update password
                    $sql = "UPDATE users SET password = ?, is_first_login = 0 WHERE id = ?";
                    if($stmt = mysqli_prepare($link, $sql)){
                        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
                        
                        if(mysqli_stmt_execute($stmt)){
                            $success_msg = "Password changed successfully!";
                            $_SESSION["is_first_login"] = 0;
                            logActivity($user_id, 'Change Password', 'User changed their password.');
                            
                            // Redirect to dashboard after 2 seconds if first login
                            if($is_first_login){
                                echo "<script>setTimeout(function(){ window.location.href = 'dashboard.php'; }, 2000);</script>";
                            }
                        } else {
                            $error_msg = "Error updating password. Please try again.";
                        }
                        mysqli_stmt_close($stmt);
                    }
                } else {
                    $error_msg = "Current password is incorrect.";
                }
            }
        }
    }
}
?><?php $is_partial = isset($_GET['partial']) && $_GET['partial'] == '1'; ?>
<?php if(!$is_partial){ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Resident Information System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3><i class="bi bi-hospital"></i> Resident Information System</h3>
        </div>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a href="surveys.php"><i class="bi bi-clipboard-check"></i> Health Surveys</a></li>
            <li><a href="personal_info.php"><i class="bi bi-person"></i> Personal Info</a></li>
            <li><a href="family_info.php"><i class="bi bi-people"></i> Family Info</a></li>
            <li><a href="references.php"><i class="bi bi-card-text"></i> References</a></li>
            <li><a href="photo_upload.php"><i class="bi bi-image"></i> Photo Upload</a></li>
            <li><a href="print_profile.php"><i class="bi bi-printer"></i> Print Profile</a></li>
            <li><a href="change_password.php" class="active"><i class="bi bi-key"></i> Change Password</a></li>
            <li><a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="navbar">
            <div class="navbar-container">
                <div class="navbar-brand">Change Password</div>
                <div class="navbar-menu">
                    <a href="dashboard.php">Back to Dashboard</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </nav>
<?php } ?>

        <!-- Breadcrumbs -->
        <div class="breadcrumbs">
            <a href="dashboard.php">Dashboard</a>
            <span class="separator">&gt;</span>
            <span class="active">Change Password</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Change Password</h1>
            <p><?php echo $is_first_login ? 'Please change your password to proceed.' : 'Update your account password'; ?></p>
        </div>

        <?php displayToasts(); ?>

        <!-- Messages -->
        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Change Password Card -->
        <div class="card" style="max-width: 500px;">
            <div class="card-header">
                <h3>Update Password</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="preventDuplicateSubmit(this.querySelector('button[type=submit]'))">
                    <?php outputCSRFHiddenField(); ?>

                    <?php if(!$is_first_login): ?>
                        <div class="form-group">
                            <label for="current_password">Current Password <span style="color: red;">*</span></label>
                            <input type="password" name="current_password" id="current_password" class="form-control" autocomplete="current-password" required>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> This is your first login. Your default password is your Resident Number.
                        </div>
                        <div class="form-group">
                            <label for="current_password_first">Current Password (Resident Number) <span style="color: red;">*</span></label>
                            <input type="password" name="current_password" id="current_password_first" class="form-control" autocomplete="current-password" required>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="new_password">New Password <span style="color: red;">*</span></label>
                        <input type="password" name="new_password" class="form-control" required minlength="6" data-strength="true" id="new_password" autocomplete="new-password">
                        <small style="color: #95A5A6; display: block; margin-top: 0.5rem;">Minimum 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password <span style="color: red;">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6" id="confirm_password" autocomplete="new-password">
                    </div>

                    <!-- Password Requirements Checklist -->
                    <div style="background: var(--primary-lightest); padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem;">
                        <h5 style="color: #5AA9E6; margin-bottom: 0.5rem;">Password Requirements</h5>
                        <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem; color: #2C3E50; list-style: none; padding-left: 0;">
                            <li id="req-length" style="margin-bottom: 0.35rem;"><i class="bi bi-x-circle" style="color: #95A5A6; margin-right: 0.5rem;"></i> Minimum 6 characters</li>
                            <li id="req-uppercase" style="margin-bottom: 0.35rem;"><i class="bi bi-x-circle" style="color: #95A5A6; margin-right: 0.5rem;"></i> Contains uppercase letter</li>
                            <li id="req-lowercase" style="margin-bottom: 0.35rem;"><i class="bi bi-x-circle" style="color: #95A5A6; margin-right: 0.5rem;"></i> Contains lowercase letter</li>
                            <li id="req-number" style="margin-bottom: 0.35rem;"><i class="bi bi-x-circle" style="color: #95A5A6; margin-right: 0.5rem;"></i> Contains a number</li>
                        </ul>
                    </div>

                    <!-- Submit Button -->
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Change Password
                        </button>
                        <?php if(!$is_first_login): ?>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
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
    <script>
        // Real-time password requirements checking
        var newPassInput = document.getElementById('new_password');
        if (newPassInput) {
            newPassInput.addEventListener('keyup', function() {
                var val = this.value;

                // Check length
                var reqLength = document.getElementById('req-length');
                if (val.length >= 6) {
                    reqLength.innerHTML = '<i class="bi bi-check-circle-fill" style="color: #28a745; margin-right: 0.5rem;"></i> Minimum 6 characters';
                } else {
                    reqLength.innerHTML = '<i class="bi bi-x-circle" style="color: #95A5A6; margin-right: 0.5rem;"></i> Minimum 6 characters';
                }

                // Check uppercase
                var reqUppercase = document.getElementById('req-uppercase');
                if (/[A-Z]/.test(val)) {
                    reqUppercase.innerHTML = '<i class="bi bi-check-circle-fill" style="color: #28a745; margin-right: 0.5rem;"></i> Contains uppercase letter';
                } else {
                    reqUppercase.innerHTML = '<i class="bi bi-x-circle" style="color: #95A5A6; margin-right: 0.5rem;"></i> Contains uppercase letter';
                }

                // Check lowercase
                var reqLowercase = document.getElementById('req-lowercase');
                if (/[a-z]/.test(val)) {
                    reqLowercase.innerHTML = '<i class="bi bi-check-circle-fill" style="color: #28a745; margin-right: 0.5rem;"></i> Contains lowercase letter';
                } else {
                    reqLowercase.innerHTML = '<i class="bi bi-x-circle" style="color: #95A5A6; margin-right: 0.5rem;"></i> Contains lowercase letter';
                }

                // Check number
                var reqNumber = document.getElementById('req-number');
                if (/\d/.test(val)) {
                    reqNumber.innerHTML = '<i class="bi bi-check-circle-fill" style="color: #28a745; margin-right: 0.5rem;"></i> Contains a number';
                } else {
                    reqNumber.innerHTML = '<i class="bi bi-x-circle" style="color: #95A5A6; margin-right: 0.5rem;"></i> Contains a number';
                }
            });
        }
    </script>
</body>
</html>
<?php } ?>
