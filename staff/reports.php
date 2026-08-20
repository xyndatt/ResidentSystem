<?php
require_once "../includes/session.php";

if(!isAdmin()){
    redirectToLogin();
}

// Summary Statistics
$total_residents = 0;
$male_count = 0;
$female_count = 0;
$children_count = 0;
$adults_count = 0;
$senior_count = 0;
$active_surveys = 0;
$total_responses = 0;

// Total residents
$sql = "SELECT COUNT(*) as count FROM residents";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $total_residents = $row['count'];
    mysqli_stmt_close($stmt);
}

// Male residents
$sql = "SELECT COUNT(*) as count FROM residents WHERE gender = 'Male'";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $male_count = $row['count'];
    mysqli_stmt_close($stmt);
}

// Female residents
$sql = "SELECT COUNT(*) as count FROM residents WHERE gender = 'Female'";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $female_count = $row['count'];
    mysqli_stmt_close($stmt);
}

// Children (age < 18)
$sql = "SELECT COUNT(*) as count FROM residents WHERE YEAR(CURDATE()) - YEAR(birthday) < 18";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $children_count = $row['count'];
    mysqli_stmt_close($stmt);
}

// Adults (age 18-59)
$sql = "SELECT COUNT(*) as count FROM residents WHERE YEAR(CURDATE()) - YEAR(birthday) >= 18 AND YEAR(CURDATE()) - YEAR(birthday) < 60";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $adults_count = $row['count'];
    mysqli_stmt_close($stmt);
}

// Senior citizens (age >= 60)
$sql = "SELECT COUNT(*) as count FROM residents WHERE YEAR(CURDATE()) - YEAR(birthday) >= 60";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $senior_count = $row['count'];
    mysqli_stmt_close($stmt);
}

// Active surveys
if(tableExists('surveys')){
    $sql = "SELECT COUNT(*) as count FROM surveys WHERE is_active = 1";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $active_surveys = $row['count'];
        mysqli_stmt_close($stmt);
    }
}

// Total survey responses
if(tableExists('survey_responses')){
    $sql = "SELECT COUNT(*) as count FROM survey_responses";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $total_responses = $row['count'];
        mysqli_stmt_close($stmt);
    }
}

// Calculate percentages for charts
$malePercent = $total_residents > 0 ? round(($male_count / $total_residents) * 100) : 0;
$femalePercent = $total_residents > 0 ? round(($female_count / $total_residents) * 100) : 0;
$childrenPercent = $total_residents > 0 ? round(($children_count / $total_residents) * 100) : 0;
$adultsPercent = $total_residents > 0 ? round(($adults_count / $total_residents) * 100) : 0;
$seniorPercent = $total_residents > 0 ? round(($senior_count / $total_residents) * 100) : 0;

// Fetch surveys with response counts for survey overview
$surveys_overview = [];
if(tableExists('surveys') && tableExists('survey_responses')){
    $sql = "SELECT s.id, s.title, s.is_active, s.created_at,
            (SELECT COUNT(DISTINCT sr.resident_id) FROM survey_responses sr WHERE sr.survey_id = s.id) as response_count
            FROM surveys s ORDER BY s.created_at DESC";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)){
            $surveys_overview[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

$report_data = [];
$report_type = "";
$survey_error = "";

// Handle report generation
if(isset($_GET['type'])){
    $report_type = $_GET['type'];

    if($report_type === "all_residents"){
        $sql = "SELECT id, resident_number, first_name, last_name, gender, contact_number, email FROM residents ORDER BY last_name ASC";
    } elseif($report_type === "senior_citizens"){
        $sql = "SELECT id, resident_number, first_name, last_name, gender, birthday, contact_number FROM residents 
                WHERE YEAR(CURDATE()) - YEAR(birthday) >= 60 ORDER BY last_name ASC";
    } elseif($report_type === "male_residents"){
        $sql = "SELECT id, resident_number, first_name, last_name, contact_number, email FROM residents 
                WHERE gender = 'Male' ORDER BY last_name ASC";
    } elseif($report_type === "female_residents"){
        $sql = "SELECT id, resident_number, first_name, last_name, contact_number, email FROM residents 
                WHERE gender = 'Female' ORDER BY last_name ASC";
    } elseif($report_type === "updated_records"){
        $sql = "SELECT id, resident_number, first_name, last_name, updated_at FROM residents 
                WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY updated_at DESC";
    } elseif($report_type === "survey_overview"){
        if(tableExists('surveys')){
            $report_type = "survey_overview";
        } else {
            $survey_error = "Survey system is not configured. Please run the database migration.";
            $report_type = "";
        }
    } elseif($report_type === "survey_detail" && isset($_GET['survey_id'])){
        if(!tableExists('surveys') || !tableExists('survey_responses') || !tableExists('survey_questions') || !tableExists('survey_choices')){
            $survey_error = "Survey system is not configured. Please run the database migration.";
            $report_type = "";
        } else {
        $survey_detail_id = intval($_GET['survey_id']);
        $survey_detail = null;
        $survey_questions = [];
        $survey_total_respondents = 0;

        $sql_sd = "SELECT * FROM surveys WHERE id = ?";
        if($stmt_sd = mysqli_prepare($link, $sql_sd)){
            mysqli_stmt_bind_param($stmt_sd, "i", $survey_detail_id);
            mysqli_stmt_execute($stmt_sd);
            $result_sd = mysqli_stmt_get_result($stmt_sd);
            $survey_detail = mysqli_fetch_assoc($result_sd);
            mysqli_stmt_close($stmt_sd);
        }

        if($survey_detail){
            $sql_tr = "SELECT COUNT(DISTINCT resident_id) as cnt FROM survey_responses WHERE survey_id = ?";
            if($stmt_tr = mysqli_prepare($link, $sql_tr)){
                mysqli_stmt_bind_param($stmt_tr, "i", $survey_detail_id);
                mysqli_stmt_execute($stmt_tr);
                $res_tr = mysqli_stmt_get_result($stmt_tr);
                $row_tr = mysqli_fetch_assoc($res_tr);
                $survey_total_respondents = $row_tr['cnt'];
                mysqli_stmt_close($stmt_tr);
            }

            $sql_q = "SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY sort_order ASC";
            if($stmt_q = mysqli_prepare($link, $sql_q)){
                mysqli_stmt_bind_param($stmt_q, "i", $survey_detail_id);
                mysqli_stmt_execute($stmt_q);
                $result_q = mysqli_stmt_get_result($stmt_q);
                while($q = mysqli_fetch_assoc($result_q)){
                    $q['data'] = [];

                    if($q['question_type'] === 'multiple_choice'){
                        $sql_mc = "SELECT sc.choice_text, COUNT(sr.id) as cnt 
                                   FROM survey_choices sc 
                                   LEFT JOIN survey_responses sr ON sr.question_id = sc.question_id AND sr.survey_id = ? AND sr.response_text = sc.choice_text 
                                   WHERE sc.question_id = ? 
                                   GROUP BY sc.id, sc.choice_text 
                                   ORDER BY sc.sort_order";
                        if($stmt_mc = mysqli_prepare($link, $sql_mc)){
                            mysqli_stmt_bind_param($stmt_mc, "ii", $survey_detail_id, $q['id']);
                            mysqli_stmt_execute($stmt_mc);
                            $res_mc = mysqli_stmt_get_result($stmt_mc);
                            $total_answers = 0;
                            while($mc = mysqli_fetch_assoc($res_mc)){
                                $total_answers += $mc['cnt'];
                                $q['data'][] = $mc;
                            }
                            $q['total_answers'] = $total_answers;
                            foreach($q['data'] as &$mc){
                                $mc['percentage'] = $total_answers > 0 ? round(($mc['cnt'] / $total_answers) * 100, 1) : 0;
                            }
                            unset($mc);
                            mysqli_stmt_close($stmt_mc);
                        }
                    } elseif($q['question_type'] === 'yes_no'){
                        $sql_yn = "SELECT response_text, COUNT(*) as cnt FROM survey_responses WHERE survey_id = ? AND question_id = ? GROUP BY response_text";
                        if($stmt_yn = mysqli_prepare($link, $sql_yn)){
                            mysqli_stmt_bind_param($stmt_yn, "ii", $survey_detail_id, $q['id']);
                            mysqli_stmt_execute($stmt_yn);
                            $res_yn = mysqli_stmt_get_result($stmt_yn);
                            $yn_data = ['Yes' => 0, 'No' => 0];
                            while($yn = mysqli_fetch_assoc($res_yn)){
                                $yn_data[$yn['response_text']] = $yn['cnt'];
                            }
                            $total_yn = $yn_data['Yes'] + $yn_data['No'];
                            $q['data'] = [
                                ['response_text' => 'Yes', 'cnt' => $yn_data['Yes'], 'percentage' => $total_yn > 0 ? round(($yn_data['Yes'] / $total_yn) * 100, 1) : 0],
                                ['response_text' => 'No', 'cnt' => $yn_data['No'], 'percentage' => $total_yn > 0 ? round(($yn_data['No'] / $total_yn) * 100, 1) : 0]
                            ];
                            $q['total_answers'] = $total_yn;
                            mysqli_stmt_close($stmt_yn);
                        }
                    } elseif($q['question_type'] === 'rating_scale'){
                        $sql_rs = "SELECT CAST(response_text AS DECIMAL(10,2)) as rating_val FROM survey_responses WHERE survey_id = ? AND question_id = ?";
                        if($stmt_rs = mysqli_prepare($link, $sql_rs)){
                            mysqli_stmt_bind_param($stmt_rs, "ii", $survey_detail_id, $q['id']);
                            mysqli_stmt_execute($stmt_rs);
                            $res_rs = mysqli_stmt_get_result($stmt_rs);
                            $ratings = [];
                            while($rs = mysqli_fetch_assoc($res_rs)){
                                $ratings[] = $rs['rating_val'];
                            }
                            $q['avg_rating'] = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 2) : 0;
                            $q['total_answers'] = count($ratings);

                            $distribution = [];
                            foreach($ratings as $r){
                                $r_int = intval($r);
                                $distribution[$r_int] = ($distribution[$r_int] ?? 0) + 1;
                            }
                            ksort($distribution);
                            $max_dist = max(array_values($distribution) ?: [1]);
                            $q['distribution'] = [];
                            for($i = 1; $i <= 10; $i++){
                                $cnt = $distribution[$i] ?? 0;
                                $q['distribution'][] = [
                                    'value' => $i,
                                    'count' => $cnt,
                                    'percentage' => count($ratings) > 0 ? round(($cnt / count($ratings)) * 100, 1) : 0,
                                    'bar_width' => $max_dist > 0 ? round(($cnt / $max_dist) * 100) : 0
                                ];
                            }
                            mysqli_stmt_close($stmt_rs);
                        }
                    } elseif($q['question_type'] === 'short_answer'){
                        $sql_sa = "SELECT sr.response_text, r.first_name, r.last_name 
                                   FROM survey_responses sr 
                                   LEFT JOIN residents r ON sr.resident_id = r.id 
                                   WHERE sr.survey_id = ? AND sr.question_id = ? 
                                   ORDER BY sr.created_at ASC";
                        if($stmt_sa = mysqli_prepare($link, $sql_sa)){
                            mysqli_stmt_bind_param($stmt_sa, "ii", $survey_detail_id, $q['id']);
                            mysqli_stmt_execute($stmt_sa);
                            $res_sa = mysqli_stmt_get_result($stmt_sa);
                            while($sa = mysqli_fetch_assoc($res_sa)){
                                $q['data'][] = $sa;
                            }
                            $q['total_answers'] = count($q['data']);
                            mysqli_stmt_close($stmt_sa);
                        }
                    }

                    $survey_questions[] = $q;
                }
                mysqli_stmt_close($stmt_q);
            }
        }
        }
    }

    if($report_type !== "survey_overview" && $report_type !== "survey_detail" && isset($sql)){
        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)){
                $report_data[] = $row;
            }
            mysqli_stmt_close($stmt);
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
    <title>Reports - Resident Information System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .chart-bar-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        .chart-bar-label {
            width: 120px;
            font-size: 0.9rem;
            color: #2C3E50;
        }
        .chart-bar-track {
            flex: 1;
            background: #EDF6F9;
            border-radius: 8px;
            overflow: hidden;
            height: 24px;
        }
        .chart-bar-fill {
            height: 100%;
            border-radius: 8px;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            padding-left: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #fff;
            min-width: fit-content;
        }
        .chart-bar-value {
            font-weight: 600;
            min-width: 40px;
            text-align: right;
            font-size: 0.9rem;
            color: #2C3E50;
        }
        .stat-card-mini {
            background: #fff;
            border-radius: 10px;
            padding: 1.25rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            text-align: center;
        }
        .stat-card-mini .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .stat-card-mini .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2C3E50;
        }
        .stat-card-mini .stat-label {
            font-size: 0.8rem;
            color: #7F8C8D;
            margin-top: 0.25rem;
        }
        .survey-result-bar {
            background: #EDF6F9;
            border-radius: 6px;
            height: 28px;
            overflow: hidden;
            position: relative;
        }
        .survey-result-bar-fill {
            height: 100%;
            border-radius: 6px;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            padding: 0 10px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #fff;
            min-width: fit-content;
        }
        .survey-result-bar-fill.mc { background: linear-gradient(135deg, #5AA9E6, #3a8fd4); }
        .survey-result-bar-fill.yes { background: #2ECC71; }
        .survey-result-bar-fill.no { background: #E74C3C; }
        .survey-result-bar-fill.rating { background: linear-gradient(135deg, #9B59B6, #8E44AD); }
        .result-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.35rem;
            font-size: 0.9rem;
        }
        .result-label-text { color: #2C3E50; font-weight: 500; }
        .result-label-count { color: #7F8C8D; font-size: 0.85rem; }
        .response-item {
            background: #f0f7ff;
            border-radius: 6px;
            padding: 1rem 1.25rem;
            margin-bottom: 0.75rem;
            border-left: 3px solid #5AA9E6;
        }
        .response-text { color: #2C3E50; font-size: 0.95rem; margin-bottom: 0.25rem; }
        .response-author { color: #7F8C8D; font-size: 0.8rem; }
    </style>
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
            <li><a href="add_resident.php"><i class="bi bi-person-plus"></i> Add Resident</a></li>
            <li><a href="manage_staff.php"><i class="bi bi-person-badge"></i> Manage Staff</a></li>
            <li><a href="search.php"><i class="bi bi-search"></i> Search</a></li>
            <li><a href="reports.php" class="active"><i class="bi bi-file-earmark-pdf"></i> Reports</a></li>
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
                <div class="navbar-brand">Reports</div>
                <div class="navbar-menu">
                    <a href="dashboard.php">Dashboard</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </nav>
        <?php } ?>

        <!-- Breadcrumbs -->
        <div class="breadcrumbs">
            <a href="dashboard.php">Dashboard</a>
            <span class="separator">&gt;</span>
            <span class="active">Reports</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Generate Reports</h1>
            <p>Create and view various resident and survey reports</p>
        </div>

        <?php if(!empty($survey_error)): ?>
            <div style="background:#FEE;border:1px solid #E74C3C;color:#C0392B;padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($survey_error); ?>
            </div>
        <?php endif; ?>

        <!-- Summary Statistics Section -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="stat-card-mini">
                <div class="stat-icon" style="color: #5AA9E6;"><i class="bi bi-people-fill"></i></div>
                <div class="stat-value" style="color: #5AA9E6;"><?php echo $total_residents; ?></div>
                <div class="stat-label">Total Residents</div>
            </div>
            <div class="stat-card-mini">
                <div class="stat-icon" style="color: #3498DB;"><i class="bi bi-gender-male"></i></div>
                <div class="stat-value" style="color: #3498DB;"><?php echo $male_count; ?></div>
                <div class="stat-label">Male</div>
            </div>
            <div class="stat-card-mini">
                <div class="stat-icon" style="color: #9B59B6;"><i class="bi bi-gender-female"></i></div>
                <div class="stat-value" style="color: #9B59B6;"><?php echo $female_count; ?></div>
                <div class="stat-label">Female</div>
            </div>
            <div class="stat-card-mini">
                <div class="stat-icon" style="color: #F39C12;"><i class="bi bi-emoji-smile"></i></div>
                <div class="stat-value" style="color: #F39C12;"><?php echo $children_count; ?></div>
                <div class="stat-label">Children (&lt;18)</div>
            </div>
            <div class="stat-card-mini">
                <div class="stat-icon" style="color: #2ECC71;"><i class="bi bi-person"></i></div>
                <div class="stat-value" style="color: #2ECC71;"><?php echo $adults_count; ?></div>
                <div class="stat-label">Adults (18-59)</div>
            </div>
            <div class="stat-card-mini">
                <div class="stat-icon" style="color: #E67E22;"><i class="bi bi-person-hearts"></i></div>
                <div class="stat-value" style="color: #E67E22;"><?php echo $senior_count; ?></div>
                <div class="stat-label">Seniors (60+)</div>
            </div>
            <div class="stat-card-mini">
                <div class="stat-icon" style="color: #1ABC9C;"><i class="bi bi-clipboard-check"></i></div>
                <div class="stat-value" style="color: #1ABC9C;"><?php echo $active_surveys; ?></div>
                <div class="stat-label">Active Surveys</div>
            </div>
            <div class="stat-card-mini">
                <div class="stat-icon" style="color: #E74C3C;"><i class="bi bi-chat-dots"></i></div>
                <div class="stat-value" style="color: #E74C3C;"><?php echo $total_responses; ?></div>
                <div class="stat-label">Total Responses</div>
            </div>
        </div>

        <!-- Visual Charts -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
            <!-- Gender Distribution Chart -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-gender-male"></i> Gender Distribution</h3>
                </div>
                <div class="card-body">
                    <div class="chart-bar-row">
                        <span class="chart-bar-label">Male</span>
                        <div class="chart-bar-track">
                            <div class="chart-bar-fill" style="width:<?php echo $malePercent; ?>%;background:#3498DB;">
                                <?php if($malePercent >= 10): ?><?php echo $malePercent; ?>%<?php endif; ?>
                            </div>
                        </div>
                        <span class="chart-bar-value"><?php echo $male_count; ?></span>
                    </div>
                    <div class="chart-bar-row">
                        <span class="chart-bar-label">Female</span>
                        <div class="chart-bar-track">
                            <div class="chart-bar-fill" style="width:<?php echo $femalePercent; ?>%;background:#9B59B6;">
                                <?php if($femalePercent >= 10): ?><?php echo $femalePercent; ?>%<?php endif; ?>
                            </div>
                        </div>
                        <span class="chart-bar-value"><?php echo $female_count; ?></span>
                    </div>
                </div>
            </div>

            <!-- Age Distribution Chart -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-bar-chart"></i> Age Distribution</h3>
                </div>
                <div class="card-body">
                    <div class="chart-bar-row">
                        <span class="chart-bar-label">Children</span>
                        <div class="chart-bar-track">
                            <div class="chart-bar-fill" style="width:<?php echo $childrenPercent; ?>%;background:#F39C12;">
                                <?php if($childrenPercent >= 10): ?><?php echo $childrenPercent; ?>%<?php endif; ?>
                            </div>
                        </div>
                        <span class="chart-bar-value"><?php echo $children_count; ?></span>
                    </div>
                    <div class="chart-bar-row">
                        <span class="chart-bar-label">Adults</span>
                        <div class="chart-bar-track">
                            <div class="chart-bar-fill" style="width:<?php echo $adultsPercent; ?>%;background:#2ECC71;">
                                <?php if($adultsPercent >= 10): ?><?php echo $adultsPercent; ?>%<?php endif; ?>
                            </div>
                        </div>
                        <span class="chart-bar-value"><?php echo $adults_count; ?></span>
                    </div>
                    <div class="chart-bar-row">
                        <span class="chart-bar-label">Seniors</span>
                        <div class="chart-bar-track">
                            <div class="chart-bar-fill" style="width:<?php echo $seniorPercent; ?>%;background:#E67E22;">
                                <?php if($seniorPercent >= 10): ?><?php echo $seniorPercent; ?>%<?php endif; ?>
                            </div>
                        </div>
                        <span class="chart-bar-value"><?php echo $senior_count; ?></span>
                    </div>
                </div>
            </div>

            <!-- Survey Response Rates Chart -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-clipboard-data"></i> Survey Response Rates</h3>
                </div>
                <div class="card-body">
                    <?php if(count($surveys_overview) > 0): ?>
                        <?php
                        $max_responses = 1;
                        foreach($surveys_overview as $sv){
                            if($sv['response_count'] > $max_responses) $max_responses = $sv['response_count'];
                        }
                        ?>
                        <?php foreach($surveys_overview as $sv): ?>
                            <?php
                            $sv_bar_width = $max_responses > 0 ? round(($sv['response_count'] / $max_responses) * 100) : 0;
                            ?>
                            <div class="chart-bar-row">
                                <span class="chart-bar-label" style="width:160px;font-size:0.8rem;" title="<?php echo htmlspecialchars($sv['title']); ?>"><?php echo htmlspecialchars(mb_strimwidth($sv['title'], 0, 20, '...')); ?></span>
                                <div class="chart-bar-track">
                                    <div class="chart-bar-fill" style="width:<?php echo max($sv_bar_width, 3); ?>%;background:<?php echo $sv['is_active'] ? '#2ECC71' : '#95A5A6'; ?>;">
                                        <?php if($sv_bar_width >= 15): ?><?php echo $sv['response_count']; ?><?php endif; ?>
                                    </div>
                                </div>
                                <span class="chart-bar-value"><?php echo $sv['response_count']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #7F8C8D; text-align: center; padding: 1rem;">No surveys available yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Report Options -->
        <div class="card">
            <div class="card-header">
                <h3>Available Reports</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="reports.php?type=all_residents" class="btn btn-primary" style="display:flex;align-items:center;gap:0.5rem;justify-content:center;">
                        <i class="bi bi-people-fill"></i> All Residents
                    </a>
                    <a href="reports.php?type=senior_citizens" class="btn btn-primary" style="display:flex;align-items:center;gap:0.5rem;justify-content:center;">
                        <i class="bi bi-person-hearts"></i> Senior Citizens
                    </a>
                    <a href="reports.php?type=male_residents" class="btn btn-primary" style="display:flex;align-items:center;gap:0.5rem;justify-content:center;">
                        <i class="bi bi-gender-male"></i> Male Residents
                    </a>
                    <a href="reports.php?type=female_residents" class="btn btn-primary" style="display:flex;align-items:center;gap:0.5rem;justify-content:center;">
                        <i class="bi bi-gender-female"></i> Female Residents
                    </a>
                    <a href="reports.php?type=updated_records" class="btn btn-primary" style="display:flex;align-items:center;gap:0.5rem;justify-content:center;">
                        <i class="bi bi-arrow-repeat"></i> Recently Updated
                    </a>
                    <a href="reports.php?type=survey_overview" class="btn btn-success" style="display:flex;align-items:center;gap:0.5rem;justify-content:center;">
                        <i class="bi bi-clipboard-data"></i> Survey Results Overview
                    </a>
                    <?php if(count($surveys_overview) > 0): ?>
                        <div style="grid-column: 1 / -1;">
                            <label style="font-weight: 600; color: #5AA9E6; font-size: 0.9rem; margin-bottom: 0.5rem; display: block;">
                                <i class="bi bi-search"></i> Survey Detail Report
                            </label>
                            <div style="display:flex;gap:0.75rem;align-items:center;">
                                <select id="surveyDetailSelect" class="form-control" style="flex:1;">
                                    <option value="">-- Select a Survey --</option>
                                    <?php foreach($surveys_overview as $sv): ?>
                                        <option value="<?php echo $sv['id']; ?>"><?php echo htmlspecialchars($sv['title']); ?> (<?php echo $sv['response_count']; ?> responses)</option>
                                    <?php endforeach; ?>
                                </select>
                                <button onclick="var sel=document.getElementById('surveyDetailSelect');if(sel.value){window.location='reports.php?type=survey_detail&survey_id='+sel.value;}else{alert('Please select a survey.');}" class="btn btn-primary">
                                    <i class="bi bi-bar-chart-line"></i> View Detail
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Report Results (Original 5 types) -->
        <?php if(!empty($report_type) && $report_type !== "survey_overview" && $report_type !== "survey_detail" && count($report_data) > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Report Results (<?php echo count($report_data); ?> records)</h3>
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 1rem;">
                        <button onclick="exportTableToCSV('reportTable', 'report_<?php echo $report_type; ?>.csv')" class="btn btn-success">
                            <i class="bi bi-download"></i> Export to CSV
                        </button>
                        <button onclick="window.print()" class="btn btn-primary">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table id="reportTable">
                            <thead>
                                <tr>
                                    <th>Resident Number</th>
                                    <th>Name</th>
                                    <?php if($report_type === "senior_citizens"): ?>
                                        <th>Birthday</th>
                                    <?php elseif($report_type === "updated_records"): ?>
                                        <th>Last Updated</th>
                                    <?php else: ?>
                                        <th>Gender</th>
                                    <?php endif; ?>
                                    <th>Contact Number</th>
                                    <?php if($report_type !== "senior_citizens" && $report_type !== "updated_records"): ?>
                                        <th>Email</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($report_data as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['resident_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <?php if($report_type === "senior_citizens"): ?>
                                            <td><?php echo date('M d, Y', strtotime($row['birthday'])); ?></td>
                                        <?php elseif($report_type === "updated_records"): ?>
                                            <td><?php echo date('M d, Y g:i A', strtotime($row['updated_at'])); ?></td>
                                        <?php else: ?>
                                            <td><?php echo htmlspecialchars($row['gender'] ?? 'N/A'); ?></td>
                                        <?php endif; ?>
                                        <td><?php echo htmlspecialchars($row['contact_number'] ?? 'N/A'); ?></td>
                                        <?php if($report_type !== "senior_citizens" && $report_type !== "updated_records"): ?>
                                            <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php elseif(!empty($report_type) && $report_type !== "survey_overview" && $report_type !== "survey_detail" && count($report_data) === 0): ?>
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 2rem;">
                    <p style="color: #95A5A6;">No data found for this report.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Survey Overview Report -->
        <?php if($report_type === "survey_overview"): ?>
            <div class="card">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                    <h3><i class="bi bi-clipboard-data"></i> Survey Results Overview (<?php echo count($surveys_overview); ?> surveys)</h3>
                    <div>
                        <button onclick="exportTableToCSV('surveyOverviewTable', 'survey_overview.csv')" class="btn btn-success btn-sm">
                            <i class="bi bi-download"></i> Export CSV
                        </button>
                        <button onclick="window.print()" class="btn btn-primary btn-sm">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(count($surveys_overview) > 0): ?>
                        <div class="table-responsive">
                            <table id="surveyOverviewTable">
                                <thead>
                                    <tr>
                                        <th>Survey Title</th>
                                        <th>Status</th>
                                        <th>Responses</th>
                                        <th>Date Created</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($surveys_overview as $sv): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($sv['title']); ?></td>
                                            <td>
                                                <?php if($sv['is_active']): ?>
                                                    <span style="background:#2ECC71;color:#fff;padding:3px 10px;border-radius:12px;font-size:0.8rem;font-weight:600;">Active</span>
                                                <?php else: ?>
                                                    <span style="background:#95A5A6;color:#fff;padding:3px 10px;border-radius:12px;font-size:0.8rem;font-weight:600;">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?php echo $sv['response_count']; ?></strong></td>
                                            <td><?php echo date('M d, Y', strtotime($sv['created_at'])); ?></td>
                                            <td>
                                                <a href="reports.php?type=survey_detail&survey_id=<?php echo $sv['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-bar-chart-line"></i> View Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center;padding:2rem;">
                            <p style="color:#95A5A6;">No surveys have been created yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Survey Detail Report -->
        <?php if($report_type === "survey_detail" && isset($survey_detail) && $survey_detail): ?>
            <div class="card">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                    <h3><i class="bi bi-bar-chart-line"></i> <?php echo htmlspecialchars($survey_detail['title']); ?></h3>
                    <div>
                        <a href="reports.php?type=survey_overview" class="btn btn-sm btn-primary">
                            <i class="bi bi-arrow-left"></i> Back to Overview
                        </a>
                        <a href="survey_results.php?id=<?php echo $survey_detail_id; ?>&export=csv" class="btn btn-success btn-sm">
                            <i class="bi bi-download"></i> Export CSV
                        </a>
                        <button onclick="window.print()" class="btn btn-primary btn-sm">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(!empty($survey_detail['description'])): ?>
                        <p style="color:#7F8C8D;margin-bottom:1rem;"><?php echo htmlspecialchars($survey_detail['description']); ?></p>
                    <?php endif; ?>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem;">
                        <div class="stat-card-mini" style="background:#f0f7ff;">
                            <div class="stat-value" style="color:#5AA9E6;"><?php echo $survey_total_respondents; ?></div>
                            <div class="stat-label">Total Respondents</div>
                        </div>
                        <div class="stat-card-mini" style="background:#f0fff4;">
                            <div class="stat-value" style="color:#2ECC71;"><?php echo count($survey_questions); ?></div>
                            <div class="stat-label">Questions</div>
                        </div>
                        <div class="stat-card-mini" style="background:#faf5ff;">
                            <div class="stat-value" style="color:#9B59B6;"><?php echo $survey_detail['is_active'] ? 'Active' : 'Inactive'; ?></div>
                            <div class="stat-label">Status</div>
                        </div>
                        <div class="stat-card-mini" style="background:#fff9f0;">
                            <div class="stat-label" style="margin-bottom:0;">
                                <?php echo $survey_detail['open_date'] ? 'Opens: ' . date('M d, Y', strtotime($survey_detail['open_date'])) : 'No open date'; ?>
                                <br>
                                <?php echo $survey_detail['close_date'] ? 'Closes: ' . date('M d, Y', strtotime($survey_detail['close_date'])) : 'No close date'; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Questions Results -->
                    <?php if(count($survey_questions) > 0): ?>
                        <?php foreach($survey_questions as $q_idx => $q): ?>
                            <div style="border-bottom:1px solid #EDF6F9;padding-bottom:1.5rem;margin-bottom:1.5rem;">
                                <h4 style="font-size:1.05rem;color:#2C3E50;margin-bottom:1rem;">
                                    <span style="background:#5AA9E6;color:#fff;border-radius:50%;width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;font-size:0.8rem;margin-right:0.5rem;"><?php echo $q_idx + 1; ?></span>
                                    <?php echo htmlspecialchars($q['question_text']); ?>
                                    <?php if($q['is_required']): ?>
                                        <span style="color:#E74C3C;">*</span>
                                    <?php endif; ?>
                                </h4>

                                <?php if($q['question_type'] === 'multiple_choice'): ?>
                                    <p style="font-size:0.85rem;color:#7F8C8D;margin-bottom:1rem;">Multiple Choice &mdash; <?php echo $q['total_answers']; ?> responses</p>
                                    <?php if(count($q['data']) > 0): ?>
                                        <?php foreach($q['data'] as $choice): ?>
                                            <div class="result-label">
                                                <span class="result-label-text"><?php echo htmlspecialchars($choice['choice_text']); ?></span>
                                                <span class="result-label-count"><?php echo $choice['cnt']; ?> (<?php echo $choice['percentage']; ?>%)</span>
                                            </div>
                                            <div class="survey-result-bar" style="margin-bottom:0.75rem;">
                                                <div class="survey-result-bar-fill mc" style="width:<?php echo max($choice['percentage'], 2); ?>%;">
                                                    <?php echo $choice['percentage']; ?>%
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p style="color:#95A5A6;">No responses yet.</p>
                                    <?php endif; ?>

                                <?php elseif($q['question_type'] === 'yes_no'): ?>
                                    <p style="font-size:0.85rem;color:#7F8C8D;margin-bottom:1rem;">Yes/No &mdash; <?php echo $q['total_answers']; ?> responses</p>
                                    <?php foreach($q['data'] as $yn): ?>
                                        <div class="result-label">
                                            <span class="result-label-text"><?php echo htmlspecialchars($yn['response_text']); ?></span>
                                            <span class="result-label-count"><?php echo $yn['cnt']; ?> (<?php echo $yn['percentage']; ?>%)</span>
                                        </div>
                                        <div class="survey-result-bar" style="margin-bottom:0.75rem;">
                                            <div class="survey-result-bar-fill <?php echo strtolower($yn['response_text']) === 'yes' ? 'yes' : 'no'; ?>" style="width:<?php echo max($yn['percentage'], 2); ?>%;">
                                                <?php echo $yn['percentage']; ?>%
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                <?php elseif($q['question_type'] === 'rating_scale'): ?>
                                    <p style="font-size:0.85rem;color:#7F8C8D;margin-bottom:0.5rem;">Rating Scale &mdash; <?php echo $q['total_answers']; ?> responses</p>
                                    <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;">
                                        <div>
                                            <span style="font-size:2rem;font-weight:700;color:#9B59B6;"><?php echo $q['avg_rating']; ?></span>
                                            <span style="color:#7F8C8D;font-size:0.9rem;"> / 10 average</span>
                                        </div>
                                    </div>
                                    <p style="font-size:0.85rem;font-weight:600;color:#2C3E50;margin-bottom:0.75rem;">Rating Distribution</p>
                                    <?php foreach($q['distribution'] as $dist): ?>
                                        <?php if($dist['count'] > 0 || $dist['value'] <= 5): ?>
                                            <div class="result-label">
                                                <span class="result-label-text"><?php echo $dist['value']; ?></span>
                                                <span class="result-label-count"><?php echo $dist['count']; ?> (<?php echo $dist['percentage']; ?>%)</span>
                                            </div>
                                            <div class="survey-result-bar" style="margin-bottom:0.5rem;">
                                                <div class="survey-result-bar-fill rating" style="width:<?php echo max($dist['bar_width'], 2); ?>%;">
                                                    <?php if($dist['percentage'] >= 5): ?><?php echo $dist['percentage']; ?>%<?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                <?php elseif($q['question_type'] === 'short_answer'): ?>
                                    <p style="font-size:0.85rem;color:#7F8C8D;margin-bottom:1rem;">Short Answer &mdash; <?php echo count($q['data']); ?> responses</p>
                                    <?php if(count($q['data']) > 0): ?>
                                        <?php foreach($q['data'] as $answer): ?>
                                            <div class="response-item">
                                                <div class="response-text"><?php echo nl2br(htmlspecialchars($answer['response_text'])); ?></div>
                                                <?php if(!empty($answer['first_name'])): ?>
                                                    <div class="response-author">&mdash; <?php echo htmlspecialchars($answer['first_name'] . ' ' . $answer['last_name']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p style="color:#95A5A6;">No responses yet.</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif($report_type === "survey_detail" && (!isset($survey_detail) || !$survey_detail)): ?>
            <div class="card">
                <div class="card-body" style="text-align:center;padding:2rem;">
                    <p style="color:#95A5A6;">Survey not found.</p>
                </div>
            </div>
        <?php endif; ?>

<?php if(!$is_partial){ ?>
    </div>

    <!-- Global Footer -->
    <footer class="global-footer">
        <div class="footer-grid">
            <div class="footer-brand">
                <h3><i class="bi bi-hospital"></i> Resident Information System</h3>
                <p>Streamlining community management and enhancing resident services.</p>
            </div>
            <div class="footer-column">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="residents.php">Residents</a></li>
                    <li><a href="surveys.php">Surveys</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h4>Support</h4>
                <ul>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Contact Admin</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h4>Contact</h4>
                <p><i class="bi bi-envelope"></i> admin@ris.com</p>
                <p><i class="bi bi-geo-alt"></i> Community Office</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Resident Information System. All rights reserved.</p>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
</body>
</html>
<?php } ?>