<?php
require_once "../includes/session.php";

if(!isResident()){
    redirectToLogin();
}

$resident_id = $_SESSION["resident_id"];
$success_msg = $error_msg = "";

$references = null;
$sql = "SELECT * FROM references_table WHERE resident_id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $references = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}


if($_SERVER["REQUEST_METHOD"] == "POST"){
    $ref_one_name = trim($_POST["ref_one_name"]);
    $ref_one_address = trim($_POST["ref_one_address"]);
    $ref_one_contact = trim($_POST["ref_one_contact"]);
    $ref_two_name = trim($_POST["ref_two_name"]);
    $ref_two_address = trim($_POST["ref_two_address"]);
    $ref_two_contact = trim($_POST["ref_two_contact"]);

    $signature_photo = $references['signature_photo'] ?? null;
    if(isset($_FILES["signature_photo"]) && $_FILES["signature_photo"]["error"] == 0){
        $file = $_FILES["signature_photo"];
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024;

        if(!in_array($file["type"], $allowed_types)){
            $error_msg = "Only JPG, JPEG, and PNG files are allowed for signature.";
        } elseif($file["size"] > $max_size){
            $error_msg = "Signature file must not exceed 5MB.";
        } else {
            $upload_dir = "../assets/uploads/";
            if(!is_dir($upload_dir)){
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = pathinfo($file["name"], PATHINFO_EXTENSION);
            $new_filename = "signature_" . $resident_id . "_" . time() . "." . $file_ext;
            
            if(move_uploaded_file($file["tmp_name"], $upload_dir . $new_filename)){
                $signature_photo = $new_filename;
            } else {
                $error_msg = "Error uploading signature file.";
            }
        }
    }

    if(empty($error_msg)){
        if($references){
            
            $sql = "UPDATE references_table SET ref_one_name = ?, ref_one_address = ?, ref_one_contact = ?, 
                    ref_two_name = ?, ref_two_address = ?, ref_two_contact = ?, signature_photo = ? WHERE resident_id = ?";
            
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "sssssssi", $ref_one_name, $ref_one_address, $ref_one_contact, 
                    $ref_two_name, $ref_two_address, $ref_two_contact, $signature_photo, $resident_id);
                
                if(mysqli_stmt_execute($stmt)){
                    $success_msg = "References updated successfully!";
                    logActivity($_SESSION["id"], 'Update References', 'Resident ' . $resident_id . ' updated references.');
                    
                   
                    $sql = "SELECT * FROM references_table WHERE resident_id = ?";
                    if($stmt2 = mysqli_prepare($link, $sql)){
                        mysqli_stmt_bind_param($stmt2, "i", $resident_id);
                        mysqli_stmt_execute($stmt2);
                        $result = mysqli_stmt_get_result($stmt2);
                        $references = mysqli_fetch_assoc($result);
                        mysqli_stmt_close($stmt2);
                    }
                } else {
                    $error_msg = "Error updating references.";
                }
                mysqli_stmt_close($stmt);
            }
        } else {
           
            $sql = "INSERT INTO references_table (resident_id, ref_one_name, ref_one_address, ref_one_contact, 
                    ref_two_name, ref_two_address, ref_two_contact, signature_photo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "isssssss", $resident_id, $ref_one_name, $ref_one_address, $ref_one_contact, 
                    $ref_two_name, $ref_two_address, $ref_two_contact, $signature_photo);
                
                if(mysqli_stmt_execute($stmt)){
                    $success_msg = "References added successfully!";
                    logActivity($_SESSION["id"], 'Add References', 'Resident ' . $resident_id . ' added references.');
                 
                    $sql = "SELECT * FROM references_table WHERE resident_id = ?";
                    if($stmt2 = mysqli_prepare($link, $sql)){
                        mysqli_stmt_bind_param($stmt2, "i", $resident_id);
                        mysqli_stmt_execute($stmt2);
                        $result = mysqli_stmt_get_result($stmt2);
                        $references = mysqli_fetch_assoc($result);
                        mysqli_stmt_close($stmt2);
                    }
                } else {
                    $error_msg = "Error adding references.";
                }
                mysqli_stmt_close($stmt);
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
    <title>References - Resident Information System</title>
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
            <li><a href="references.php" class="active"><i class="bi bi-card-text"></i> References</a></li>
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
                <div class="navbar-brand">Character References</div>
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
            <span class="active">References</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Character References</h1>
            <p>Provide information about your character references</p>
        </div>

        <?php displayToasts(); ?>

        <!-- Messages -->
        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- References Form -->
        <div class="card">
            <div class="card-header">
                <h3>Reference Information</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                    <!-- Reference One -->
                    <h4 style="margin-bottom: 1rem; color: #5AA9E6;">Reference One</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="ref_one_name" class="form-control" value="<?php echo htmlspecialchars($references['ref_one_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="tel" name="ref_one_contact" class="form-control" value="<?php echo htmlspecialchars($references['ref_one_contact'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="ref_one_address" class="form-control" rows="2"><?php echo htmlspecialchars($references['ref_one_address'] ?? ''); ?></textarea>
                    </div>

                    <!-- Reference Two -->
                    <h4 style="margin-bottom: 1rem; margin-top: 2rem; color: #5AA9E6;">Reference Two</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="ref_two_name" class="form-control" value="<?php echo htmlspecialchars($references['ref_two_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Contact Number</label>
                            <input type="tel" name="ref_two_contact" class="form-control" value="<?php echo htmlspecialchars($references['ref_two_contact'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="ref_two_address" class="form-control" rows="2"><?php echo htmlspecialchars($references['ref_two_address'] ?? ''); ?></textarea>
                    </div>

                    <!-- Signature Upload -->
                    <h4 style="margin-bottom: 1rem; margin-top: 2rem; color: #5AA9E6;">Signature (Optional)</h4>
                    <div class="form-group">
                        <label>Upload Signature Photo</label>
                        <input type="file" name="signature_photo" class="form-control" accept="image/jpeg,image/png,image/jpg">
                        <small style="color: #95A5A6; display: block; margin-top: 0.5rem;">Accepted formats: JPG, JPEG, PNG. Maximum size: 5MB</small>
                    </div>

                    <?php if(!empty($references['signature_photo'])): ?>
                        <div style="margin-bottom: 1rem;">
                            <label style="font-weight: 600; color: #5AA9E6;">Current Signature</label>
                            <img src="../assets/uploads/<?php echo htmlspecialchars($references['signature_photo']); ?>" 
                                 alt="Signature" 
                                 style="max-width: 200px; height: auto; border-radius: 12px; box-shadow: 10px 10px 20px rgba(0,0,0,.08), -10px -10px 20px white;">
                        </div>
                    <?php endif; ?>

                    <!-- Submit Button -->
                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save References
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
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
