<?php
require_once "../includes/session.php";

if(!isResident()){
    redirectToLogin();
}

$resident_id = $_SESSION["resident_id"];

// Get resident information for welcome message
$resident = null;
$sql = "SELECT * FROM residents WHERE id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $resident = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

$surveys_tables_exist = tableExists('surveys') && tableExists('survey_questions') && tableExists('survey_responses');

// Fetch active surveys that are currently open
$surveys = [];
if($surveys_tables_exist){
    $sql = "SELECT s.*, 
            (SELECT COUNT(*) FROM survey_questions sq WHERE sq.survey_id = s.id) as question_count
            FROM surveys s 
            WHERE s.is_active = 1 
            AND (s.open_date IS NULL OR s.open_date <= CURDATE()) 
            AND (s.close_date IS NULL OR s.close_date >= CURDATE())
            ORDER BY s.created_at DESC";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $surveys[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

// Check which surveys the resident has already completed
$completed_survey_ids = [];
if($surveys_tables_exist && count($surveys) > 0){
    $survey_ids = array_column($surveys, 'id');
    $placeholders = implode(',', array_fill(0, count($survey_ids), '?'));
    $types = str_repeat('i', count($survey_ids));
    
    $sql = "SELECT DISTINCT survey_id FROM survey_responses WHERE resident_id = ? AND survey_id IN ($placeholders)";
    $params = array_merge([$resident_id], $survey_ids);
    
    if($stmt = mysqli_prepare($link, $sql)){
        $bind_names[] = $types;
        for($i = 0; $i < count($params); $i++){
            $bind_names[] = &$params[$i];
        }
        call_user_func_array(array($stmt, 'bind_param'), $bind_names);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $completed_survey_ids[] = $row['survey_id'];
        }
        mysqli_stmt_close($stmt);
    }
}

// Also fetch completed surveys that are no longer active (to show completion history)
$all_completed = [];
if($surveys_tables_exist){
    if(count($completed_survey_ids) > 0 || count($surveys) === 0){
        $sql_check = "SELECT s.*, 
            (SELECT COUNT(*) FROM survey_questions sq WHERE sq.survey_id = s.id) as question_count
            FROM surveys s 
            INNER JOIN survey_responses sr ON sr.survey_id = s.id
            WHERE sr.resident_id = ?
            GROUP BY s.id
            ORDER BY s.created_at DESC";
        if($stmt = mysqli_prepare($link, $sql_check)){
            mysqli_stmt_bind_param($stmt, "i", $resident_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                if(!in_array($row['id'], $survey_ids ?? [])){
                    $all_completed[] = $row;
                }
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
    <title>Health Surveys - Resident Information System</title>
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

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="navbar">
            <div class="navbar-container">
                <div class="navbar-brand">Health Surveys</div>
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
            <span class="active">Health Surveys</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Health Surveys</h1>
            <p>Participate in health surveys to help improve community health services</p>
        </div>

        <?php displayToasts(); ?>

        <?php if(!$surveys_tables_exist): ?>
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 4rem 2rem;">
                    <div style="font-size: 4rem; color: #D5E8F7; margin-bottom: 1rem;">
                        <i class="bi bi-clipboard-x"></i>
                    </div>
                    <h3 style="color: #95A5A6; margin-bottom: 0.5rem;">Survey system is not yet configured.</h3>
                    <p style="color: #95A5A6;">Please contact the administrator to set up the survey system.</p>
                    <a href="dashboard.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="bi bi-speedometer2"></i> Return to Dashboard
                    </a>
                </div>
            </div>
        <?php else: ?>

        <!-- Available Surveys -->
        <?php if(count($surveys) > 0): ?>
            <h3 style="margin-bottom: 1rem;"><i class="bi bi-clipboard-check"></i> Available Surveys</h3>
            <div style="display: grid; gap: 1.5rem; margin-bottom: 2rem;">
                <?php foreach($surveys as $survey): ?>
                    <?php $is_completed = in_array($survey['id'], $completed_survey_ids); ?>
                    <div class="card" style="<?php echo $is_completed ? 'opacity: 0.7;' : ''; ?>">
                        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 style="margin-bottom: 0;"><?php echo htmlspecialchars($survey['title']); ?></h3>
                            <?php if($is_completed): ?>
                                <span class="btn btn-success btn-sm" style="cursor: default;">
                                    <i class="bi bi-check-circle"></i> Completed
                                </span>
                            <?php else: ?>
                                <span class="btn btn-primary btn-sm" style="cursor: default; background-color: var(--success); border-color: var(--success);">
                                    <i class="bi bi-clock"></i> Available
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if(!empty($survey['description'])): ?>
                                <p style="margin-bottom: 1rem;"><?php echo nl2br(htmlspecialchars($survey['description'])); ?></p>
                            <?php endif; ?>
                            
                            <div style="display: flex; gap: 2rem; flex-wrap: wrap; margin-bottom: 1rem; font-size: 0.9rem; color: #95A5A6;">
                                <span><i class="bi bi-question-circle"></i> <?php echo $survey['question_count']; ?> question(s)</span>
                                <?php if(!empty($survey['open_date'])): ?>
                                    <span><i class="bi bi-calendar-event"></i> Opens: <?php echo date('M d, Y', strtotime($survey['open_date'])); ?></span>
                                <?php endif; ?>
                                <?php if(!empty($survey['close_date'])): ?>
                                    <span><i class="bi bi-calendar-x"></i> Closes: <?php echo date('M d, Y', strtotime($survey['close_date'])); ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if($is_completed): ?>
                                <p style="color: #28a745; font-weight: 600; margin-bottom: 0;">
                                    <i class="bi bi-check-circle-fill"></i> You have already completed this survey.
                                </p>
                            <?php else: ?>
                                <a href="take_survey.php?id=<?php echo $survey['id']; ?>" class="btn btn-primary">
                                    <i class="bi bi-pencil-square"></i> Take Survey
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Previously Completed Surveys (not currently active) -->
        <?php if(count($all_completed) > 0): ?>
            <h3 style="margin-bottom: 1rem;"><i class="bi bi-check2-circle"></i> Previously Completed</h3>
            <div style="display: grid; gap: 1rem; margin-bottom: 2rem;">
                <?php foreach($all_completed as $survey): ?>
                    <div class="card" style="opacity: 0.7;">
                        <div class="card-body" style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h4 style="margin-bottom: 0.25rem;"><?php echo htmlspecialchars($survey['title']); ?></h4>
                                <span style="color: #95A5A6; font-size: 0.85rem;">
                                    <i class="bi bi-check-circle-fill" style="color: #28a745;"></i> Completed
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Empty State -->
        <?php if(count($surveys) === 0 && count($all_completed) === 0): ?>
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 4rem 2rem;">
                    <div style="font-size: 4rem; color: #D5E8F7; margin-bottom: 1rem;">
                        <i class="bi bi-clipboard-x"></i>
                    </div>
                    <h3 style="color: #95A5A6; margin-bottom: 0.5rem;">No surveys available at this time.</h3>
                    <p style="color: #95A5A6;">Check back later for new health surveys.</p>
                    <a href="dashboard.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="bi bi-speedometer2"></i> Return to Dashboard
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php endif; ?>
<?php if(!$is_partial){ ?>
    </div><!-- end main-content -->

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
