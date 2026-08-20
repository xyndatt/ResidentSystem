<?php
require_once "../includes/session.php";

if(!isResident()){
    redirectToLogin();
}

$resident_id = $_SESSION["resident_id"];
$success_msg = $error_msg = "";

// Fetch resident data
$sql = "SELECT * FROM residents WHERE id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $resident = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])){
        $error_msg = "Invalid CSRF token. Please try again.";
    } else {
        $last_name = trim($_POST["last_name"]);
        $first_name = trim($_POST["first_name"]);
        $middle_name = trim($_POST["middle_name"]);
        $extension_name = trim($_POST["extension_name"]);
        $civil_status = trim($_POST["civil_status"]);
        $birthday = trim($_POST["birthday"]);
        $gender = trim($_POST["gender"]);
        $address = trim($_POST["address"]);
        $contact_number = trim($_POST["contact_number"]);
        $occupation = trim($_POST["occupation"]);
        $employer = trim($_POST["employer"]);
        $employer_address = trim($_POST["employer_address"]);
        $email = trim($_POST["email"]);
        $nationality = trim($_POST["nationality"]);
        $religion = trim($_POST["religion"]);
        $blood_type = trim($_POST["blood_type"]);

        // Validate required fields
        if(empty($first_name) || empty($last_name)){
            $error_msg = "First name and last name are required.";
        } else {
            // Update resident information
            $sql = "UPDATE residents SET last_name = ?, first_name = ?, middle_name = ?, extension_name = ?, 
                    civil_status = ?, birthday = ?, gender = ?, address = ?, contact_number = ?, 
                    occupation = ?, employer = ?, employer_address = ?, email = ?, nationality = ?, 
                    religion = ?, blood_type = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "ssssssssssssssssi", 
                    $last_name, $first_name, $middle_name, $extension_name, $civil_status, $birthday, 
                    $gender, $address, $contact_number, $occupation, $employer, $employer_address, 
                    $email, $nationality, $religion, $blood_type, $resident_id);
                
                if(mysqli_stmt_execute($stmt)){
                    $success_msg = "Personal information updated successfully!";
                    logActivity($_SESSION["id"], 'Update Personal Info', 'Resident ' . $resident_id . ' updated personal information.');
                    
                    // Refresh resident data
                    $sql = "SELECT * FROM residents WHERE id = ?";
                    if($stmt2 = mysqli_prepare($link, $sql)){
                        mysqli_stmt_bind_param($stmt2, "i", $resident_id);
                        mysqli_stmt_execute($stmt2);
                        $result = mysqli_stmt_get_result($stmt2);
                        $resident = mysqli_fetch_assoc($result);
                        mysqli_stmt_close($stmt2);
                    }
                } else {
                    $error_msg = "Error updating information. Please try again.";
                }
                mysqli_stmt_close($stmt);
            }
        }
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
    <title>Personal Information - Resident Information System</title>
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
            <li><a href="personal_info.php" class="active"><i class="bi bi-person"></i> Personal Info</a></li>
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
                <div class="navbar-brand">Personal Information</div>
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
            <span class="active">Personal Info</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Update Personal Information</h1>
            <p>Edit your personal details</p>
        </div>

        <?php displayToasts(); ?>

        <!-- Messages -->
        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="card">
            <div class="card-header">
                <h3>Personal Information</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="preventDuplicateSubmit(this.querySelector('button[type=submit]'))">
                    <?php outputCSRFHiddenField(); ?>

                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-person"></i> Personal Information</div>

                        <!-- Resident Number (Read-only) -->
                        <div class="form-group">
                            <label>Resident Number</label>
                            <div style="position: relative;">
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($resident['resident_number']); ?>" readonly style="background-color: #f0f0f0; cursor: not-allowed; padding-left: 2.5rem;">
                                <i class="bi bi-lock" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #95A5A6;"></i>
                            </div>
                            <small style="color: #95A5A6; display: block; margin-top: 0.25rem;">Resident number cannot be changed.</small>
                        </div>

                        <!-- Name Fields -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label>Last Name <span style="color: red;">*</span></label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($resident['last_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>First Name <span style="color: red;">*</span></label>
                                <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($resident['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" class="form-control" value="<?php echo htmlspecialchars($resident['middle_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Extension Name</label>
                                <input type="text" name="extension_name" class="form-control" value="<?php echo htmlspecialchars($resident['extension_name'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Birthday, Gender, Civil Status, Blood Type -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label>Birthday</label>
                                <input type="date" name="birthday" class="form-control" value="<?php echo htmlspecialchars($resident['birthday'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender" class="form-control">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo ($resident['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($resident['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($resident['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Civil Status</label>
                                <select name="civil_status" class="form-control">
                                    <option value="">Select Civil Status</option>
                                    <option value="Single" <?php echo ($resident['civil_status'] === 'Single') ? 'selected' : ''; ?>>Single</option>
                                    <option value="Married" <?php echo ($resident['civil_status'] === 'Married') ? 'selected' : ''; ?>>Married</option>
                                    <option value="Divorced" <?php echo ($resident['civil_status'] === 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                                    <option value="Widowed" <?php echo ($resident['civil_status'] === 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Blood Type</label>
                                <select name="blood_type" class="form-control">
                                    <option value="">Select Blood Type</option>
                                    <option value="O+" <?php echo ($resident['blood_type'] === 'O+') ? 'selected' : ''; ?>>O+</option>
                                    <option value="O-" <?php echo ($resident['blood_type'] === 'O-') ? 'selected' : ''; ?>>O-</option>
                                    <option value="A+" <?php echo ($resident['blood_type'] === 'A+') ? 'selected' : ''; ?>>A+</option>
                                    <option value="A-" <?php echo ($resident['blood_type'] === 'A-') ? 'selected' : ''; ?>>A-</option>
                                    <option value="B+" <?php echo ($resident['blood_type'] === 'B+') ? 'selected' : ''; ?>>B+</option>
                                    <option value="B-" <?php echo ($resident['blood_type'] === 'B-') ? 'selected' : ''; ?>>B-</option>
                                    <option value="AB+" <?php echo ($resident['blood_type'] === 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                    <option value="AB-" <?php echo ($resident['blood_type'] === 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-envelope"></i> Contact Information</div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label>Contact Number</label>
                                <input type="tel" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($resident['contact_number'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($resident['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Nationality</label>
                                <input type="text" name="nationality" class="form-control" value="<?php echo htmlspecialchars($resident['nationality'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Religion</label>
                                <input type="text" name="religion" class="form-control" value="<?php echo htmlspecialchars($resident['religion'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($resident['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Employment Information Section -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-briefcase"></i> Employment Information</div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label>Occupation</label>
                                <input type="text" name="occupation" class="form-control" value="<?php echo htmlspecialchars($resident['occupation'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Employer</label>
                                <input type="text" name="employer" class="form-control" value="<?php echo htmlspecialchars($resident['employer'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Employer Address -->
                        <div class="form-group">
                            <label>Employer Address</label>
                            <textarea name="employer_address" class="form-control" rows="3"><?php echo htmlspecialchars($resident['employer_address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
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
