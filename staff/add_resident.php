<?php
require_once "../includes/session.php";

if(!isAdmin()){
    redirectToLogin();
}

$success_msg = $error_msg = "";

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!isset($_POST[CSRF_TOKEN_NAME]) || !validateCSRFToken($_POST[CSRF_TOKEN_NAME])){
        $error_msg = "Invalid security token. Please try again.";
    } else {
        $resident_number = trim($_POST["resident_number"]);
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
        if(empty($resident_number) || empty($first_name) || empty($last_name)){
            $error_msg = "Resident Number, First Name, and Last Name are required.";
        } else {
            // Check if resident number already exists
            $sql = "SELECT id FROM residents WHERE resident_number = ?";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "s", $resident_number);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if(mysqli_num_rows($result) > 0){
                    $error_msg = "Resident Number already exists.";
                } else {
                    // Insert new resident
                    $sql = "INSERT INTO residents (resident_number, last_name, first_name, middle_name, extension_name,
                            civil_status, birthday, gender, address, contact_number, occupation, employer, employer_address,
                            email, nationality, religion, blood_type)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    if($stmt2 = mysqli_prepare($link, $sql)){
                        mysqli_stmt_bind_param($stmt2, "sssssssssssssssss",
                            $resident_number, $last_name, $first_name, $middle_name, $extension_name,
                            $civil_status, $birthday, $gender, $address, $contact_number,
                            $occupation, $employer, $employer_address, $email, $nationality, $religion, $blood_type);

                        if(mysqli_stmt_execute($stmt2)){
                            $resident_id = mysqli_insert_id($link);

                            // Create user account
                            $username = $resident_number;
                            $password = password_hash($resident_number, PASSWORD_DEFAULT);
                            $role = "resident";

                            $sql_user = "INSERT INTO users (username, password, role, resident_id, is_first_login) VALUES (?, ?, ?, ?, 1)";
                            if($stmt3 = mysqli_prepare($link, $sql_user)){
                                mysqli_stmt_bind_param($stmt3, "sssi", $username, $password, $role, $resident_id);

                                if(mysqli_stmt_execute($stmt3)){
                                    $success_msg = "Resident added successfully! Default password is the Resident Number.";
                                    logActivity($_SESSION["id"], 'Add Resident', 'New resident ' . $resident_number . ' added.');
                                } else {
                                    $error_msg = "Error creating user account.";
                                }
                                mysqli_stmt_close($stmt3);
                            }
                        } else {
                            $error_msg = "Error adding resident.";
                        }
                        mysqli_stmt_close($stmt2);
                    }
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
    <title>Add Resident - Resident Information System</title>
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
            <li><a href="add_resident.php" class="active"><i class="bi bi-person-plus"></i> Add Resident</a></li>
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
                <div class="navbar-brand">Add New Resident</div>
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
            <a href="residents.php">Residents</a>
            <span class="separator">&gt;</span>
            <span class="active">Add Resident</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Add New Resident</h1>
            <p>Register a new resident in the system</p>
        </div>

        <!-- Messages -->
        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Add Resident Form -->
        <div class="card">
            <div class="card-header">
                <h3>Resident Information</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="preventDuplicateSubmit(this.querySelector('button[type=submit]'))">
                    <?php outputCSRFHiddenField(); ?>

                    <!-- Personal Information Section -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-person"></i> Personal Information</div>

                        <div class="form-group">
                            <label>Resident Number <span style="color: red;">*</span></label>
                            <input type="text" name="resident_number" class="form-control" required>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label>Last Name <span style="color: red;">*</span></label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>First Name <span style="color: red;">*</span></label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Extension Name</label>
                                <input type="text" name="extension_name" class="form-control">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label>Birthday</label>
                                <input type="date" name="birthday" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender" class="form-control">
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Civil Status</label>
                                <select name="civil_status" class="form-control">
                                    <option value="">Select Civil Status</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Widowed">Widowed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Blood Type</label>
                                <select name="blood_type" class="form-control">
                                    <option value="">Select Blood Type</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
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
                                <input type="tel" name="contact_number" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Nationality</label>
                                <input type="text" name="nationality" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Religion</label>
                                <input type="text" name="religion" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="3"></textarea>
                        </div>
                    </div>

                    <!-- Employment Information Section -->
                    <div class="form-section">
                        <div class="form-section-title"><i class="bi bi-briefcase"></i> Employment Information</div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label>Occupation</label>
                                <input type="text" name="occupation" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Employer</label>
                                <input type="text" name="employer" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Employer Address</label>
                            <textarea name="employer_address" class="form-control" rows="3"></textarea>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Add Resident
                        </button>
                        <a href="residents.php" class="btn btn-secondary">
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
