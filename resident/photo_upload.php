<?php
require_once "../includes/session.php";

if(!isResident()){
    redirectToLogin();
}

$resident_id = $_SESSION["resident_id"];
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

// Handle file upload
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["photo"])){
    $file = $_FILES["photo"];
    
    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if($file["error"] != 0){
        $error_msg = "Error uploading file. Please try again.";
    } elseif(!in_array($file["type"], $allowed_types)){
        $error_msg = "Only JPG, JPEG, and PNG files are allowed.";
    } elseif($file["size"] > $max_size){
        $error_msg = "File size must not exceed 5MB.";
    } else {
        // Create upload directory if it doesn't exist
        $upload_dir = "../assets/uploads/";
        if(!is_dir($upload_dir)){
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $file_ext = pathinfo($file["name"], PATHINFO_EXTENSION);
        $new_filename = "photo_" . $resident_id . "_" . time() . "." . $file_ext;
        $upload_path = $upload_dir . $new_filename;

        // Move uploaded file
        if(move_uploaded_file($file["tmp_name"], $upload_path)){
            // Delete old photo if exists
            if(!empty($resident['photo']) && file_exists($upload_dir . $resident['photo'])){
                unlink($upload_dir . $resident['photo']);
            }

            // Update database
            $sql = "UPDATE residents SET photo = ? WHERE id = ?";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "si", $new_filename, $resident_id);
                
                if(mysqli_stmt_execute($stmt)){
                    $success_msg = "Photo uploaded successfully!";
                    logActivity($_SESSION["id"], 'Upload Photo', 'Resident ' . $resident_id . ' uploaded photo.');
                    
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
                    $error_msg = "Error saving photo information. Please try again.";
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $error_msg = "Error uploading file. Please try again.";
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
    <title>Photo Upload - Resident Information System</title>
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
            <li><a href="photo_upload.php" class="active"><i class="bi bi-image"></i> Photo Upload</a></li>
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
                <div class="navbar-brand">Photo Upload</div>
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
            <span class="active">Photo Upload</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Upload Passport-Size Photo</h1>
            <p>Upload your passport-size photo for your resident profile</p>
        </div>

        <?php displayToasts(); ?>

        <!-- Messages -->
        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Photo Upload Card -->
        <div class="card">
            <div class="card-header">
                <h3>Upload Photo</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data" onsubmit="preventDuplicateSubmit(this)">
                    <!-- Upload Area -->
                    <div style="border: 2px dashed #5AA9E6; border-radius: 25px; padding: 3rem; text-align: center; background: rgba(90, 169, 230, 0.05); margin-bottom: 2rem;">
                        <div style="font-size: 3rem; color: #5AA9E6; margin-bottom: 1rem;">
                            <i class="bi bi-cloud-upload"></i>
                        </div>
                        <h4 style="color: #2C3E50; margin-bottom: 0.5rem;">Drag and drop your photo here</h4>
                        <p style="color: #95A5A6; margin-bottom: 1.5rem;">or click to select a file</p>
                        
                        <input type="file" name="photo" id="photoInput" class="form-control" accept="image/jpeg,image/png,image/jpg" required style="display: none;">
                        <button type="button" class="btn btn-primary" onclick="document.getElementById('photoInput').click();">
                            <i class="bi bi-folder-open"></i> Select Photo
                        </button>
                    </div>

                    <!-- File Requirements -->
                    <div style="background: var(--primary-lightest); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
                        <h5 style="color: #5AA9E6; margin-bottom: 1rem;">File Requirements</h5>
                        <ul style="margin: 0; padding-left: 1.5rem; color: #2C3E50;">
                            <li>Format: JPG, JPEG, or PNG</li>
                            <li>Maximum size: 5MB</li>
                            <li>Passport-size photo (recommended: 2x2 inches or 4x6 cm)</li>
                            <li>Clear, recent photo with good lighting</li>
                        </ul>
                    </div>

                    <!-- Preview -->
                    <div id="previewContainer" style="display: none; margin-bottom: 2rem;">
                        <label style="font-weight: 600; color: #5AA9E6; display: block; margin-bottom: 1rem;">Preview</label>
                        <img id="photoPreview" src="" alt="Photo Preview" style="max-width: 300px; height: auto; border-radius: 25px; box-shadow: 10px 10px 20px rgba(0,0,0,.08), -10px -10px 20px white;">
                    </div>

                    <!-- Submit Button -->
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Upload Photo
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Current Photo Display -->
        <?php if(!empty($resident['photo'])): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Current Photo</h3>
                </div>
                <div class="card-body" style="text-align: center;">
                    <img src="../assets/uploads/<?php echo htmlspecialchars($resident['photo']); ?>" 
                         alt="Current Photo" 
                         style="max-width: 300px; height: auto; border-radius: 25px; box-shadow: 10px 10px 20px rgba(0,0,0,.08), -10px -10px 20px white;">
                    <p style="margin-top: 1rem; color: #95A5A6;">
                        Uploaded on: <?php echo date('F d, Y', strtotime($resident['updated_at'])); ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
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
        // Handle file input change
        document.getElementById('photoInput').addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('photoPreview').src = event.target.result;
                    document.getElementById('previewContainer').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Handle drag and drop
        const uploadArea = document.querySelector('div[style*="border: 2px dashed"]');
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.background = 'rgba(90, 169, 230, 0.15)';
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.background = 'rgba(90, 169, 230, 0.05)';
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.background = 'rgba(90, 169, 230, 0.05)';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                document.getElementById('photoInput').files = files;
                document.getElementById('photoInput').dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>
<?php } ?>
