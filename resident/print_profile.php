<?php
require_once "../includes/session.php";

if(!isResident()){
    redirectToLogin();
}

$resident_id = $_SESSION["resident_id"];

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

// Calculate age
$age = 0;
if(!empty($resident['birthday'])){
    $birthDate = new DateTime($resident['birthday']);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
}
$is_partial = isset($_GET['partial']) && $_GET['partial'] == '1';
?>
<?php if(!$is_partial){ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Profile - Resident Information System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
            }
            .print-hide {
                display: none !important;
            }
            .print-area {
                box-shadow: none !important;
                border: none !important;
                padding: 1.5rem;
                page-break-after: avoid;
            }
            .print-section {
                page-break-inside: avoid;
            }
            .print-item {
                border-bottom: none !important;
            }
            .page-break {
                page-break-after: always;
            }
            .print-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
            }
        }

        .print-area {
            background: white;
            padding: 2rem;
            border-radius: 0;
            box-shadow: none;
            margin: 0;
        }

        .print-header {
            text-align: center;
            border-bottom: 3px solid #5AA9E6;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .print-header h1 {
            margin: 0;
            color: #5AA9E6;
            font-size: 1.8rem;
        }

        .print-header p {
            margin: 0.3rem 0 0 0;
            color: #95A5A6;
            font-size: 0.95rem;
        }

        .print-section {
            margin-bottom: 1.5rem;
            page-break-inside: avoid;
            padding-bottom: 1rem;
            border-bottom: 1px solid #EDF6F9;
        }

        .print-section:last-of-type {
            border-bottom: none;
        }

        .print-section-title {
            background: #EDF6F9;
            padding: 0.75rem 1rem;
            border-left: 4px solid #5AA9E6;
            font-weight: 600;
            color: #5AA9E6;
            margin-bottom: 1rem;
        }

        .print-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem 2rem;
        }

        .print-item {
            page-break-inside: avoid;
            padding: 0.4rem 0;
            border-bottom: 1px solid #f0f4f8;
        }

        .print-item:last-child,
        .print-item:nth-last-child(2):nth-child(odd) {
            border-bottom: none;
        }

        .print-label {
            font-weight: 600;
            color: #5AA9E6;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .print-value {
            color: #2C3E50;
            margin-top: 0.15rem;
            word-break: break-word;
            font-size: 0.95rem;
        }

        .photo-section {
            text-align: center;
            margin-bottom: 1.5rem;
            page-break-inside: avoid;
            padding: 1rem;
            border-bottom: 1px solid #EDF6F9;
        }

        .photo-section img {
            max-width: 180px;
            height: auto;
            border-radius: 8px;
            border: 2px solid #5AA9E6;
            box-shadow: 0 2px 8px rgba(90, 169, 230, 0.2);
        }

        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-top: 2.5rem;
            page-break-inside: avoid;
        }

        .signature-box {
            text-align: center;
            border-top: 1px solid #2C3E50;
            padding-top: 3rem;
            min-height: 120px;
        }

        .signature-label {
            font-size: 0.85rem;
            color: #2C3E50;
            font-weight: 500;
        }

        .print-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #D5E8F7;
            color: #95A5A6;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <aside class="sidebar print-hide">
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
            <li><a href="print_profile.php" class="active"><i class="bi bi-printer"></i> Print Profile</a></li>
            <li><a href="change_password.php"><i class="bi bi-key"></i> Change Password</a></li>
            <li><a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </aside>

    <div class="main-content print-hide">
        <!-- Top Navigation -->
        <nav class="navbar">
            <div class="navbar-container">
                <div class="navbar-brand">Print Profile</div>
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
            <span class="active">Print Profile</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Print Your Profile</h1>
            <p>Print your complete resident profile</p>
        </div>

        <!-- Print Button -->
        <div style="margin-bottom: 2rem;">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Print Profile
            </button>
        </div>
    </div>

    <!-- Printable Content -->
    <div class="print-area">
        <!-- Header -->
        <div class="print-header">
            <h1>Barangay Health Center</h1>
            <p>Resident Information Profile</p>
            <p style="font-size: 0.9rem; margin-top: 0.5rem;">Generated on <?php echo date('F d, Y'); ?></p>
        </div>

        <!-- Photo Section -->
        <?php if(!empty($resident['photo'])): ?>
            <div class="photo-section">
                <img src="../assets/uploads/<?php echo htmlspecialchars($resident['photo']); ?>" alt="Resident Photo">
            </div>
        <?php endif; ?>

        <!-- Personal Information -->
        <div class="print-section">
            <div class="print-section-title">Personal Information</div>
            <div class="print-grid">
                <div class="print-item">
                    <div class="print-label">Resident Number</div>
                    <div class="print-value"><?php echo htmlspecialchars($resident['resident_number']); ?></div>
                </div>
                <div class="print-item">
                    <div class="print-label">Full Name</div>
                    <div class="print-value"><?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['middle_name'] . ' ' . $resident['last_name']); ?></div>
                </div>
                <div class="print-item">
                    <div class="print-label">Birthday</div>
                    <div class="print-value"><?php echo !empty($resident['birthday']) ? date('F d, Y', strtotime($resident['birthday'])) : 'Not provided'; ?></div>
                </div>
                <div class="print-item">
                    <div class="print-label">Age</div>
                    <div class="print-value"><?php echo $age; ?> years old</div>
                </div>
                <div class="print-item">
                    <div class="print-label">Gender</div>
                    <div class="print-value"><?php echo htmlspecialchars($resident['gender'] ?? 'Not specified'); ?></div>
                </div>
                <div class="print-item">
                    <div class="print-label">Civil Status</div>
                    <div class="print-value"><?php echo htmlspecialchars($resident['civil_status'] ?? 'Not specified'); ?></div>
                </div>
                <div class="print-item">
                    <div class="print-label">Blood Type</div>
                    <div class="print-value"><?php echo htmlspecialchars($resident['blood_type'] ?? 'Not specified'); ?></div>
                </div>
                <div class="print-item">
                    <div class="print-label">Nationality</div>
                    <div class="print-value"><?php echo htmlspecialchars($resident['nationality'] ?? 'Not provided'); ?></div>
                </div>
                <div class="print-item">
                    <div class="print-label">Religion</div>
                    <div class="print-value"><?php echo htmlspecialchars($resident['religion'] ?? 'Not provided'); ?></div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="print-section">
            <div class="print-section-title">Contact Information</div>
            <div class="print-grid">
                <div class="print-item">
                    <div class="print-label">Address</div>
                    <div class="print-value"><?php echo htmlspecialchars($resident['address'] ?? 'Not provided'); ?></div>
                </div>
                <div class="print-item">
                    <div class="print-label">Contact Number</div>
                    <div class="print-value"><?php echo htmlspecialchars($resident['contact_number'] ?? 'Not provided'); ?></div>
                </div>
                <div class="print-item">
                    <div class="print-label">Email</div>
                    <div class="print-value"><?php echo htmlspecialchars($resident['email'] ?? 'Not provided'); ?></div>
                </div>
            </div>
        </div>

        <!-- Employment Information -->
        <div class="print-section">
            <div class="print-section-title">Employment Information</div>
            <div class="print-grid">
                <div class="print-item">
                    <div class="print-label">Occupation</div>
                    <div class="print-value"><?php echo htmlspecialchars($resident['occupation'] ?? 'Not provided'); ?></div>
                </div>
                <div class="print-item">
                    <div class="print-label">Employer</div>
                    <div class="print-value"><?php echo htmlspecialchars($resident['employer'] ?? 'Not provided'); ?></div>
                </div>
                <div class="print-item">
                    <div class="print-label">Employer Address</div>
                    <div class="print-value"><?php echo htmlspecialchars($resident['employer_address'] ?? 'Not provided'); ?></div>
                </div>
            </div>
        </div>

        <!-- Family Information -->
        <?php if($spouse || count($children) > 0 || $parents): ?>
            <div class="print-section">
                <div class="print-section-title">Family Information</div>
                
                <?php if($spouse): ?>
                    <div style="margin-bottom: 1rem;">
                        <strong style="color: #5AA9E6;">Spouse:</strong>
                        <div class="print-grid">
                            <div class="print-item">
                                <div class="print-label">Name</div>
                                <div class="print-value"><?php echo htmlspecialchars($spouse['spouse_name']); ?></div>
                            </div>
                            <div class="print-item">
                                <div class="print-label">Occupation</div>
                                <div class="print-value"><?php echo htmlspecialchars($spouse['occupation'] ?? 'Not provided'); ?></div>
                            </div>
                            <div class="print-item">
                                <div class="print-label">Employer</div>
                                <div class="print-value"><?php echo htmlspecialchars($spouse['employer'] ?? 'Not provided'); ?></div>
                            </div>
                            <div class="print-item">
                                <div class="print-label">Contact Number</div>
                                <div class="print-value"><?php echo htmlspecialchars($spouse['contact_number'] ?? 'Not provided'); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if(count($children) > 0): ?>
                    <div style="margin-bottom: 1rem;">
                        <strong style="color: #5AA9E6;">Children:</strong>
                        <div style="margin-top: 0.5rem;">
                            <?php foreach($children as $child): ?>
                                <div style="margin-bottom: 0.5rem; padding-left: 1rem;">
                                    <div class="print-value">
                                        <?php echo htmlspecialchars($child['child_name']); ?> 
                                        <?php if($child['birthday']): ?>
                                            (<?php echo date('F d, Y', strtotime($child['birthday'])); ?>)
                                        <?php endif; ?>
                                        - <?php echo htmlspecialchars($child['gender'] ?? 'Not specified'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if($parents): ?>
                    <div>
                        <strong style="color: #5AA9E6;">Parents:</strong>
                        <div class="print-grid" style="margin-top: 0.5rem;">
                            <div class="print-item">
                                <div class="print-label">Father</div>
                                <div class="print-value"><?php echo htmlspecialchars($parents['father_name'] ?? 'Not provided'); ?></div>
                            </div>
                            <div class="print-item">
                                <div class="print-label">Mother</div>
                                <div class="print-value"><?php echo htmlspecialchars($parents['mother_name'] ?? 'Not provided'); ?></div>
                            </div>
                            <div class="print-item">
                                <div class="print-label">Occupation</div>
                                <div class="print-value"><?php echo htmlspecialchars($parents['occupation'] ?? 'Not provided'); ?></div>
                            </div>
                            <div class="print-item">
                                <div class="print-label">Contact Number</div>
                                <div class="print-value"><?php echo htmlspecialchars($parents['contact_number'] ?? 'Not provided'); ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-label">Resident Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-label">Date</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="print-footer">
            <p>This document is an official record from the Barangay Health Center Resident Information System.</p>
            <p>Printed on <?php echo date('F d, Y \a\t g:i A'); ?></p>
        </div>
    </div>

<?php if(!$is_partial){ ?>
    <!-- Global Footer (print-hide) -->
    <div class="print-hide">
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
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
<?php } ?>
