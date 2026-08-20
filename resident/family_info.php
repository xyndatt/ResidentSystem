<?php
require_once "../includes/session.php";

if(!isResident()){
    redirectToLogin();
}

$resident_id = $_SESSION["resident_id"];
$success_msg = $error_msg = "";

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

// Fetch parents data
$parents = null;
$sql = "SELECT * FROM parents WHERE resident_id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $parents = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $action = $_POST["action"] ?? "";

    if($action === "update_spouse"){
        $spouse_name = trim($_POST["spouse_name"]);
        $spouse_occupation = trim($_POST["spouse_occupation"]);
        $spouse_employer = trim($_POST["spouse_employer"]);
        $spouse_contact = trim($_POST["spouse_contact"]);

        if($spouse){
            // Update existing spouse
            $sql = "UPDATE spouse SET spouse_name = ?, occupation = ?, employer = ?, contact_number = ? WHERE resident_id = ?";
        } else {
            // Insert new spouse
            $sql = "INSERT INTO spouse (resident_id, spouse_name, occupation, employer, contact_number) VALUES (?, ?, ?, ?, ?)";
        }

        if($stmt = mysqli_prepare($link, $sql)){
            if($spouse){
                mysqli_stmt_bind_param($stmt, "ssssi", $spouse_name, $spouse_occupation, $spouse_employer, $spouse_contact, $resident_id);
            } else {
                mysqli_stmt_bind_param($stmt, "issss", $resident_id, $spouse_name, $spouse_occupation, $spouse_employer, $spouse_contact);
            }
            
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Spouse information updated successfully!";
                logActivity($_SESSION["id"], 'Update Spouse Info', 'Resident ' . $resident_id . ' updated spouse information.');
                
                // Refresh spouse data
                $sql = "SELECT * FROM spouse WHERE resident_id = ?";
                if($stmt2 = mysqli_prepare($link, $sql)){
                    mysqli_stmt_bind_param($stmt2, "i", $resident_id);
                    mysqli_stmt_execute($stmt2);
                    $result = mysqli_stmt_get_result($stmt2);
                    $spouse = mysqli_fetch_assoc($result);
                    mysqli_stmt_close($stmt2);
                }
            } else {
                $error_msg = "Error updating spouse information.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    elseif($action === "add_child"){
        $child_name = trim($_POST["child_name"]);
        $child_birthday = trim($_POST["child_birthday"]);
        $child_gender = trim($_POST["child_gender"]);

        if(empty($child_name)){
            $error_msg = "Child name is required.";
        } else {
            $sql = "INSERT INTO children (resident_id, child_name, birthday, gender) VALUES (?, ?, ?, ?)";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "isss", $resident_id, $child_name, $child_birthday, $child_gender);
                if(mysqli_stmt_execute($stmt)){
                    $success_msg = "Child added successfully!";
                    logActivity($_SESSION["id"], 'Add Child', 'Resident ' . $resident_id . ' added child: ' . $child_name);
                    
                    // Refresh children data
                    $children = [];
                    $sql = "SELECT * FROM children WHERE resident_id = ? ORDER BY id ASC";
                    if($stmt2 = mysqli_prepare($link, $sql)){
                        mysqli_stmt_bind_param($stmt2, "i", $resident_id);
                        mysqli_stmt_execute($stmt2);
                        $result = mysqli_stmt_get_result($stmt2);
                        while($row = mysqli_fetch_assoc($result)){
                            $children[] = $row;
                        }
                        mysqli_stmt_close($stmt2);
                    }
                } else {
                    $error_msg = "Error adding child.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }

    elseif($action === "delete_child"){
        $child_id = $_POST["child_id"];
        $sql = "DELETE FROM children WHERE id = ? AND resident_id = ?";
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "ii", $child_id, $resident_id);
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Child removed successfully!";
                logActivity($_SESSION["id"], 'Delete Child', 'Resident ' . $resident_id . ' deleted child.');
                
                // Refresh children data
                $children = [];
                $sql = "SELECT * FROM children WHERE resident_id = ? ORDER BY id ASC";
                if($stmt2 = mysqli_prepare($link, $sql)){
                    mysqli_stmt_bind_param($stmt2, "i", $resident_id);
                    mysqli_stmt_execute($stmt2);
                    $result = mysqli_stmt_get_result($stmt2);
                    while($row = mysqli_fetch_assoc($result)){
                        $children[] = $row;
                    }
                    mysqli_stmt_close($stmt2);
                }
            } else {
                $error_msg = "Error removing child.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    elseif($action === "update_parents"){
        $father_name = trim($_POST["father_name"]);
        $mother_name = trim($_POST["mother_name"]);
        $parents_occupation = trim($_POST["parents_occupation"]);
        $parents_contact = trim($_POST["parents_contact"]);

        if($parents){
            $sql = "UPDATE parents SET father_name = ?, mother_name = ?, occupation = ?, contact_number = ? WHERE resident_id = ?";
        } else {
            $sql = "INSERT INTO parents (resident_id, father_name, mother_name, occupation, contact_number) VALUES (?, ?, ?, ?, ?)";
        }

        if($stmt = mysqli_prepare($link, $sql)){
            if($parents){
                mysqli_stmt_bind_param($stmt, "ssssi", $father_name, $mother_name, $parents_occupation, $parents_contact, $resident_id);
            } else {
                mysqli_stmt_bind_param($stmt, "issss", $resident_id, $father_name, $mother_name, $parents_occupation, $parents_contact);
            }
            
            if(mysqli_stmt_execute($stmt)){
                $success_msg = "Parents information updated successfully!";
                logActivity($_SESSION["id"], 'Update Parents Info', 'Resident ' . $resident_id . ' updated parents information.');
                
                // Refresh parents data
                $sql = "SELECT * FROM parents WHERE resident_id = ?";
                if($stmt2 = mysqli_prepare($link, $sql)){
                    mysqli_stmt_bind_param($stmt2, "i", $resident_id);
                    mysqli_stmt_execute($stmt2);
                    $result = mysqli_stmt_get_result($stmt2);
                    $parents = mysqli_fetch_assoc($result);
                    mysqli_stmt_close($stmt2);
                }
            } else {
                $error_msg = "Error updating parents information.";
            }
            mysqli_stmt_close($stmt);
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
    <title>Family Information - Resident Information System</title>
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
            <li><a href="family_info.php" class="active"><i class="bi bi-people"></i> Family Info</a></li>
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
                <div class="navbar-brand">Family Information</div>
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
            <span class="active">Family Info</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Family Information</h1>
            <p>Manage your family details</p>
        </div>

        <?php displayToasts(); ?>

        <!-- Messages -->
        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Spouse Information -->
        <div class="card">
            <div class="card-header">
                <h3>Spouse Information</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="action" value="update_spouse">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div class="form-group">
                            <label>Spouse Name</label>
                            <input type="text" name="spouse_name" class="form-control" value="<?php echo htmlspecialchars($spouse['spouse_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Occupation</label>
                            <input type="text" name="spouse_occupation" class="form-control" value="<?php echo htmlspecialchars($spouse['occupation'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Employer</label>
                            <input type="text" name="spouse_employer" class="form-control" value="<?php echo htmlspecialchars($spouse['employer'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="tel" name="spouse_contact" class="form-control" value="<?php echo htmlspecialchars($spouse['contact_number'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save Spouse Information
                    </button>
                </form>
            </div>
        </div>

        <!-- Children Information -->
        <div class="card">
            <div class="card-header">
                <h3>Children Information</h3>
            </div>
            <div class="card-body">
                <!-- Add Child Form -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid #D5E8F7;">
                    <input type="hidden" name="action" value="add_child">
                    <h4 style="margin-bottom: 1rem;">Add New Child</h4>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div class="form-group">
                            <label>Child Name <span style="color: red;">*</span></label>
                            <input type="text" name="child_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Birthday</label>
                            <input type="date" name="child_birthday" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="child_gender" class="form-control">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Add Child
                    </button>
                </form>

                <!-- Children List -->
                <?php if(count($children) > 0): ?>
                    <h4 style="margin-bottom: 1rem;">Your Children</h4>
                    <div style="display: grid; gap: 1rem;">
                        <?php foreach($children as $child): ?>
                            <div style="background: var(--primary-lightest); padding: 1rem; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <p style="margin: 0; font-weight: 600;"><?php echo htmlspecialchars($child['child_name']); ?></p>
                                    <p style="margin: 0.5rem 0 0 0; color: #95A5A6; font-size: 0.9rem;">
                                        <?php 
                                        if($child['birthday']){
                                            $birthDate = new DateTime($child['birthday']);
                                            $today = new DateTime();
                                            $age = $today->diff($birthDate)->y;
                                            echo $age . ' years old &bull; ' . $child['gender'];
                                        }
                                        ?>
                                    </p>
                                </div>
                                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_child">
                                    <input type="hidden" name="child_id" value="<?php echo $child['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">
                                        <i class="bi bi-trash"></i> Remove
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #95A5A6; text-align: center; padding: 2rem;">No children added yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Parents Information -->
        <div class="card">
            <div class="card-header">
                <h3>Parents Information</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="action" value="update_parents">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div class="form-group">
                            <label>Father Name</label>
                            <input type="text" name="father_name" class="form-control" value="<?php echo htmlspecialchars($parents['father_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Mother Name</label>
                            <input type="text" name="mother_name" class="form-control" value="<?php echo htmlspecialchars($parents['mother_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Occupation</label>
                            <input type="text" name="parents_occupation" class="form-control" value="<?php echo htmlspecialchars($parents['occupation'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="tel" name="parents_contact" class="form-control" value="<?php echo htmlspecialchars($parents['contact_number'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save Parents Information
                    </button>
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
