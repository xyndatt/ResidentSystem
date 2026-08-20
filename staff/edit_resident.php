<?php
require_once "../includes/session.php";

if(!isAdmin()){
    redirectToLogin();
}

$resident_id = $_GET['id'] ?? 0;
$success_msg = $error_msg = "";

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

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!isset($_POST[CSRF_TOKEN_NAME]) || !validateCSRFToken($_POST[CSRF_TOKEN_NAME])){
        $error_msg = "Invalid security token. Please try again.";
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
                    $success_msg = "Resident information updated successfully!";
                    logActivity($_SESSION["id"], 'Edit Resident', 'Resident ' . $resident_id . ' updated.');

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

displayToasts();
$is_partial = isset($_GET['partial']) && $_GET['partial'] == '1';
?>

<?php if(!$is_partial){ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resident - Resident Information System</title>
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
                <div class="navbar-brand">Edit Resident</div>
                <div class="navbar-menu">
                    <a href="view_resident.php?id=<?php echo $resident_id; ?>">View</a>
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
            <span class="active">Edit Resident &gt; <?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']); ?></span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Edit Resident</h1>
            <p>Update resident information for <?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']); ?></p>
        </div>

        <!-- Messages -->
        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Edit Form -->
        <div class="card">
            <div class="card-header">
                <h3>Personal Information</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $resident_id; ?>" onsubmit="preventDuplicateSubmit(this.querySelector('button[type=submit]'))">
                    <?php outputCSRFHiddenField(); ?>

                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-person"></i> Personal Information</div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label for="last_name">Last Name <span style="color: red;">*</span></label>
                                <input type="text" id="last_name" name="last_name" class="form-control" autocomplete="family-name" value="<?php echo htmlspecialchars($resident['last_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="first_name">First Name <span style="color: red;">*</span></label>
                                <input type="text" id="first_name" name="first_name" class="form-control" autocomplete="given-name" value="<?php echo htmlspecialchars($resident['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" id="middle_name" name="middle_name" class="form-control" autocomplete="additional-name" value="<?php echo htmlspecialchars($resident['middle_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="extension_name">Extension Name</label>
                                <input type="text" id="extension_name" name="extension_name" class="form-control" autocomplete="honorific-suffix" value="<?php echo htmlspecialchars($resident['extension_name'] ?? ''); ?>">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label for="birthday">Birthday</label>
                                <input type="date" id="birthday" name="birthday" class="form-control" autocomplete="bday" value="<?php echo htmlspecialchars($resident['birthday'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender" class="form-control" autocomplete="sex">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo ($resident['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($resident['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($resident['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="civil_status">Civil Status</label>
                                <select id="civil_status" name="civil_status" class="form-control" autocomplete="off">
                                    <option value="">Select Civil Status</option>
                                    <option value="Single" <?php echo ($resident['civil_status'] === 'Single') ? 'selected' : ''; ?>>Single</option>
                                    <option value="Married" <?php echo ($resident['civil_status'] === 'Married') ? 'selected' : ''; ?>>Married</option>
                                    <option value="Divorced" <?php echo ($resident['civil_status'] === 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                                    <option value="Widowed" <?php echo ($resident['civil_status'] === 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="blood_type">Blood Type</label>
                                <select id="blood_type" name="blood_type" class="form-control" autocomplete="off">
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
                                <label for="contact_number">Contact Number</label>
                                <input type="tel" id="contact_number" name="contact_number" class="form-control" autocomplete="tel" value="<?php echo htmlspecialchars($resident['contact_number'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" autocomplete="email" value="<?php echo htmlspecialchars($resident['email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="nationality">Nationality</label>
                                <input type="text" id="nationality" name="nationality" class="form-control" autocomplete="nationality" value="<?php echo htmlspecialchars($resident['nationality'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="religion">Religion</label>
                                <input type="text" id="religion" name="religion" class="form-control" autocomplete="off" value="<?php echo htmlspecialchars($resident['religion'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" class="form-control" autocomplete="street-address" rows="3"><?php echo htmlspecialchars($resident['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Employment Information Section -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-briefcase"></i> Employment Information</div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label for="occupation">Occupation</label>
                                <input type="text" id="occupation" name="occupation" class="form-control" autocomplete="organization-title" value="<?php echo htmlspecialchars($resident['occupation'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="employer">Employer</label>
                                <input type="text" id="employer" name="employer" class="form-control" autocomplete="organization" value="<?php echo htmlspecialchars($resident['employer'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="employer_address">Employer Address</label>
                            <textarea id="employer_address" name="employer_address" class="form-control" autocomplete="off" rows="3"><?php echo htmlspecialchars($resident['employer_address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                        <a href="view_resident.php?id=<?php echo $resident_id; ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
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
</body>
</html>
<?php } ?>
