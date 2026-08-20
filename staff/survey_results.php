<?php
require_once "../includes/session.php";

if(!isAdmin()){
    redirectToLogin();
}

$survey_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$surveys_list = [];
$survey = null;
$questions = [];
$total_respondents = 0;
$surveys_table_exists = tableExists('surveys');

if($surveys_table_exists){
    // Fetch all surveys for dropdown
    $sql_all = "SELECT id, title FROM surveys ORDER BY title ASC";
    if($stmt_all = mysqli_prepare($link, $sql_all)){
        mysqli_stmt_execute($stmt_all);
        $result_all = mysqli_stmt_get_result($stmt_all);
        while($row_all = mysqli_fetch_assoc($result_all)){
            $surveys_list[] = $row_all;
        }
        mysqli_stmt_close($stmt_all);
    }
}

// Handle CSV export
if(isset($_GET['export']) && $_GET['export'] === 'csv' && $survey_id > 0 && $surveys_table_exists){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=survey_results_' . $survey_id . '.csv');
    $output = fopen('php://output', 'w');

    // Get survey info
    $sql_exp = "SELECT * FROM surveys WHERE id = ?";
    if($stmt_exp = mysqli_prepare($link, $sql_exp)){
        mysqli_stmt_bind_param($stmt_exp, "i", $survey_id);
        mysqli_stmt_execute($stmt_exp);
        $res_exp = mysqli_stmt_get_result($stmt_exp);
        $survey_exp = mysqli_fetch_assoc($res_exp);
        mysqli_stmt_close($stmt_exp);
    }

    fputcsv($output, ['Survey: ' . ($survey_exp['title'] ?? '')]);
    fputcsv($output, ['']);

    // Get questions
    $sql_eq = "SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY sort_order ASC";
    if($stmt_eq = mysqli_prepare($link, $sql_eq)){
        mysqli_stmt_bind_param($stmt_eq, "i", $survey_id);
        mysqli_stmt_execute($stmt_eq);
        $res_eq = mysqli_stmt_get_result($stmt_eq);
        while($q = mysqli_fetch_assoc($res_eq)){
            fputcsv($output, ['Question: ' . $q['question_text'] . ' (' . $q['question_type'] . ')']);

            if($q['question_type'] === 'multiple_choice'){
                $sql_cc = "SELECT sc.choice_text, COUNT(sr.id) as cnt FROM survey_choices sc LEFT JOIN survey_responses sr ON sr.question_id = sc.question_id AND sr.response_text = sc.choice_text WHERE sc.question_id = ? GROUP BY sc.id, sc.choice_text ORDER BY sc.sort_order";
                if($stmt_cc = mysqli_prepare($link, $sql_cc)){
                    mysqli_stmt_bind_param($stmt_cc, "i", $q['id']);
                    mysqli_stmt_execute($stmt_cc);
                    $res_cc = mysqli_stmt_get_result($stmt_cc);
                    while($c = mysqli_fetch_assoc($res_cc)){
                        fputcsv($output, [$c['choice_text'], $c['cnt'] . ' responses']);
                    }
                    mysqli_stmt_close($stmt_cc);
                }
            } elseif($q['question_type'] === 'yes_no'){
                $sql_yn = "SELECT response_text, COUNT(*) as cnt FROM survey_responses WHERE question_id = ? GROUP BY response_text";
                if($stmt_yn = mysqli_prepare($link, $sql_yn)){
                    mysqli_stmt_bind_param($stmt_yn, "i", $q['id']);
                    mysqli_stmt_execute($stmt_yn);
                    $res_yn = mysqli_stmt_get_result($stmt_yn);
                    while($yn = mysqli_fetch_assoc($res_yn)){
                        fputcsv($output, [$yn['response_text'], $yn['cnt'] . ' responses']);
                    }
                    mysqli_stmt_close($stmt_yn);
                }
            } elseif($q['question_type'] === 'rating_scale'){
                $sql_rs = "SELECT AVG(CAST(response_text AS DECIMAL(10,2))) as avg_val, COUNT(*) as cnt FROM survey_responses WHERE question_id = ?";
                if($stmt_rs = mysqli_prepare($link, $sql_rs)){
                    mysqli_stmt_bind_param($stmt_rs, "i", $q['id']);
                    mysqli_stmt_execute($stmt_rs);
                    $res_rs = mysqli_stmt_get_result($stmt_rs);
                    $rs = mysqli_fetch_assoc($res_rs);
                    fputcsv($output, ['Average Rating: ' . number_format($rs['avg_val'] ?? 0, 2), $rs['cnt'] . ' responses']);
                    mysqli_stmt_close($stmt_rs);
                }
            } elseif($q['question_type'] === 'short_answer'){
                $sql_sa = "SELECT response_text FROM survey_responses WHERE question_id = ?";
                if($stmt_sa = mysqli_prepare($link, $sql_sa)){
                    mysqli_stmt_bind_param($stmt_sa, "i", $q['id']);
                    mysqli_stmt_execute($stmt_sa);
                    $res_sa = mysqli_stmt_get_result($stmt_sa);
                    while($sa = mysqli_fetch_assoc($res_sa)){
                        fputcsv($output, [$sa['response_text']]);
                    }
                    mysqli_stmt_close($stmt_sa);
                }
            }
            fputcsv($output, ['']);
        }
        mysqli_stmt_close($stmt_eq);
    }

    fclose($output);
    exit;
}

// Load survey data if ID provided
if($survey_id > 0 && $surveys_table_exists){
    // Get survey info
    $sql = "SELECT * FROM surveys WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $survey_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $survey = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }

    if($survey){
        // Total respondents
        $sql_tr = "SELECT COUNT(DISTINCT resident_id) as cnt FROM survey_responses WHERE survey_id = ?";
        if($stmt_tr = mysqli_prepare($link, $sql_tr)){
            mysqli_stmt_bind_param($stmt_tr, "i", $survey_id);
            mysqli_stmt_execute($stmt_tr);
            $res_tr = mysqli_stmt_get_result($stmt_tr);
            $row_tr = mysqli_fetch_assoc($res_tr);
            $total_respondents = $row_tr['cnt'];
            mysqli_stmt_close($stmt_tr);
        }

        // Get questions with results
        $sql_q = "SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY sort_order ASC";
        if($stmt_q = mysqli_prepare($link, $sql_q)){
            mysqli_stmt_bind_param($stmt_q, "i", $survey_id);
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
                        mysqli_stmt_bind_param($stmt_mc, "ii", $survey_id, $q['id']);
                        mysqli_stmt_execute($stmt_mc);
                        $res_mc = mysqli_stmt_get_result($stmt_mc);
                        $total_answers = 0;
                        while($mc = mysqli_fetch_assoc($res_mc)){
                            $total_answers += $mc['cnt'];
                            $q['data'][] = $mc;
                        }
                        $q['total_answers'] = $total_answers;
                        // Calculate percentages
                        foreach($q['data'] as &$mc){
                            $mc['percentage'] = $total_answers > 0 ? round(($mc['cnt'] / $total_answers) * 100, 1) : 0;
                        }
                        unset($mc);
                        mysqli_stmt_close($stmt_mc);
                    }
                } elseif($q['question_type'] === 'yes_no'){
                    $sql_yn = "SELECT response_text, COUNT(*) as cnt FROM survey_responses WHERE survey_id = ? AND question_id = ? GROUP BY response_text";
                    if($stmt_yn = mysqli_prepare($link, $sql_yn)){
                        mysqli_stmt_bind_param($stmt_yn, "ii", $survey_id, $q['id']);
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
                        mysqli_stmt_bind_param($stmt_rs, "ii", $survey_id, $q['id']);
                        mysqli_stmt_execute($stmt_rs);
                        $res_rs = mysqli_stmt_get_result($stmt_rs);
                        $ratings = [];
                        while($rs = mysqli_fetch_assoc($res_rs)){
                            $ratings[] = $rs['rating_val'];
                        }
                        $q['avg_rating'] = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 2) : 0;
                        $q['total_answers'] = count($ratings);

                        // Distribution
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
                        mysqli_stmt_bind_param($stmt_sa, "ii", $survey_id, $q['id']);
                        mysqli_stmt_execute($stmt_sa);
                        $res_sa = mysqli_stmt_get_result($stmt_sa);
                        while($sa = mysqli_fetch_assoc($res_sa)){
                            $q['data'][] = $sa;
                        }
                        $q['total_answers'] = count($q['data']);
                        mysqli_stmt_close($stmt_sa);
                    }
                }

                $questions[] = $q;
            }
            mysqli_stmt_close($stmt_q);
        }

        // Average rating across all rating questions
        $avg_rating_all = 0;
        $rating_count = 0;
        foreach($questions as $q){
            if($q['question_type'] === 'rating_scale' && isset($q['avg_rating'])){
                $avg_rating_all += $q['avg_rating'];
                $rating_count++;
            }
        }
        $avg_rating_all = $rating_count > 0 ? round($avg_rating_all / $rating_count, 2) : 0;
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
    <title>Survey Results - Resident Information System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<?php } ?>
    <style>
        .result-bar {
            background: var(--light-gray);
            border-radius: 6px;
            height: 28px;
            overflow: hidden;
            position: relative;
        }
        .result-bar-fill {
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
        .result-bar-fill.mc { background: linear-gradient(135deg, var(--primary), var(--primary-light)); }
        .result-bar-fill.yes { background: var(--success); }
        .result-bar-fill.no { background: var(--danger); }
        .result-bar-fill.rating { background: linear-gradient(135deg, #9B59B6, #8E44AD); }
        .result-label {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.35rem;
            font-size: 0.9rem;
        }
        .result-label-text { color: var(--dark); font-weight: 500; }
        .result-label-count { color: var(--gray); font-size: 0.85rem; }
        .response-item {
            background: var(--primary-lightest);
            border-radius: var(--radius-sm);
            padding: 1rem 1.25rem;
            margin-bottom: 0.75rem;
            border-left: 3px solid var(--primary);
        }
        .response-text { color: var(--dark); font-size: 0.95rem; margin-bottom: 0.25rem; }
        .response-author { color: var(--gray); font-size: 0.8rem; }
        .stat-mini {
            text-align: center;
            padding: 1rem;
        }
        .stat-mini-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
        }
        .stat-mini-label {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }
        .question-result-card {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .question-result-card:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }
    </style>
<?php if(!$is_partial){ ?>
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
            <li><a href="reports.php"><i class="bi bi-file-earmark-pdf"></i> Reports</a></li>
            <li><a href="surveys.php" class="active"><i class="bi bi-clipboard-data"></i> Surveys</a></li>
            <li><a href="activity_logs.php"><i class="bi bi-clock-history"></i> Activity Logs</a></li>
            <li><a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="navbar">
            <div class="navbar-container">
                <div class="navbar-brand">Survey Results</div>
                <div class="navbar-menu">
                    <a href="surveys.php">All Surveys</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </nav>
<?php } ?>

        <!-- Breadcrumbs -->
        <div class="breadcrumbs">
            <a href="dashboard.php">Dashboard</a>
            <span class="separator">&gt;</span>
            <a href="surveys.php">Surveys</a>
            <span class="separator">&gt;</span>
            <span class="active">Results</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Survey Results</h1>
            <p>View and analyze survey responses</p>
        </div>

        <?php displayToasts(); ?>

        <?php if(!$surveys_table_exists): ?>
            <div class="card">
                <div class="card-body">
                    <div class="empty-state" style="padding: 2rem;">
                        <span class="empty-icon"><i class="bi bi-exclamation-triangle"></i></span>
                        <h3>Survey system not available</h3>
                        <p>The survey tables have not been created yet. Please run the database migration first.</p>
                        <a href="surveys.php" class="empty-action">Back to Surveys</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Survey Selector -->
            <?php if($survey_id <= 0 || !$survey): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>Select a Survey</h3>
                    </div>
                    <div class="card-body">
                        <?php if(count($surveys_list) > 0): ?>
                            <div class="form-group">
                                <label>Choose a survey to view results</label>
                                <select id="surveySelect" class="form-control" onchange="if(this.value) window.location='survey_results.php?id='+this.value">
                                    <option value="">-- Select Survey --</option>
                                    <?php foreach($surveys_list as $sl): ?>
                                        <option value="<?php echo $sl['id']; ?>" <?php echo $survey_id == $sl['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($sl['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <div class="empty-state" style="padding: 2rem;">
                                <span class="empty-icon"><i class="bi bi-clipboard-data"></i></span>
                                <h3>No surveys available</h3>
                                <p>Create a survey first to view results.</p>
                                <a href="create_survey.php" class="empty-action">Create a survey</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Survey Info & Actions -->
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3><?php echo htmlspecialchars($survey['title']); ?></h3>
                        <div style="display: flex; gap: 0.75rem;" class="print-hide">
                            <a href="survey_results.php?id=<?php echo $survey_id; ?>&export=csv" class="btn btn-success btn-sm" style="width: auto;">
                                <i class="bi bi-download"></i> Export CSV
                            </a>
                            <button onclick="window.print()" class="btn btn-primary btn-sm" style="width: auto;">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if(!empty($survey['description'])): ?>
                            <p style="color: var(--gray); margin-bottom: 1rem;"><?php echo htmlspecialchars($survey['description']); ?></p>
                        <?php endif; ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 0.5rem;">
                            <div class="stat-mini" style="background: var(--primary-lightest); border-radius: var(--radius-sm);">
                                <div class="stat-mini-value" style="color: var(--primary);"><?php echo $total_respondents; ?></div>
                                <div class="stat-mini-label">Total Respondents</div>
                            </div>
                            <div class="stat-mini" style="background: rgba(46, 204, 113, 0.08); border-radius: var(--radius-sm);">
                                <div class="stat-mini-value" style="color: var(--success);"><?php echo count($questions); ?></div>
                                <div class="stat-mini-label">Questions</div>
                            </div>
                            <?php if($avg_rating_all > 0): ?>
                            <div class="stat-mini" style="background: rgba(155, 89, 182, 0.08); border-radius: var(--radius-sm);">
                                <div class="stat-mini-value" style="color: #9B59B6;"><?php echo $avg_rating_all; ?></div>
                                <div class="stat-mini-label">Avg Rating</div>
                            </div>
                            <?php endif; ?>
                            <div class="stat-mini" style="background: rgba(255, 193, 7, 0.08); border-radius: var(--radius-sm);">
                                <div class="stat-mini-label" style="margin-bottom: 0;">
                                    <?php echo $survey['open_date'] ? 'Opens: ' . date('M d, Y', strtotime($survey['open_date'])) : 'No open date'; ?>
                                    <br>
                                    <?php echo $survey['close_date'] ? 'Closes: ' . date('M d, Y', strtotime($survey['close_date'])) : 'No close date'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Questions Results -->
                <?php if(count($questions) > 0): ?>
                    <?php foreach($questions as $q_idx => $q): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3>
                                    <span class="survey-question-number"><?php echo $q_idx + 1; ?></span>
                                    <?php echo htmlspecialchars($q['question_text']); ?>
                                    <?php if($q['is_required']): ?>
                                        <span class="survey-required">*</span>
                                    <?php endif; ?>
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="question-result-card">
                                    <?php if($q['question_type'] === 'multiple_choice'): ?>
                                        <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 1rem;">Multiple Choice &mdash; <?php echo $q['total_answers']; ?> responses</p>
                                        <?php if(count($q['data']) > 0): ?>
                                            <?php foreach($q['data'] as $choice): ?>
                                                <div class="result-label">
                                                    <span class="result-label-text"><?php echo htmlspecialchars($choice['choice_text']); ?></span>
                                                    <span class="result-label-count"><?php echo $choice['cnt']; ?> (<?php echo $choice['percentage']; ?>%)</span>
                                                </div>
                                                <div class="result-bar" style="margin-bottom: 0.75rem;">
                                                    <div class="result-bar-fill mc" style="width: <?php echo max($choice['percentage'], 2); ?>%;">
                                                        <?php echo $choice['percentage']; ?>%
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted">No responses yet.</p>
                                        <?php endif; ?>

                                    <?php elseif($q['question_type'] === 'yes_no'): ?>
                                        <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 1rem;">Yes/No &mdash; <?php echo $q['total_answers']; ?> responses</p>
                                        <?php foreach($q['data'] as $yn): ?>
                                            <div class="result-label">
                                                <span class="result-label-text"><?php echo htmlspecialchars($yn['response_text']); ?></span>
                                                <span class="result-label-count"><?php echo $yn['cnt']; ?> (<?php echo $yn['percentage']; ?>%)</span>
                                            </div>
                                            <div class="result-bar" style="margin-bottom: 0.75rem;">
                                                <div class="result-bar-fill <?php echo strtolower($yn['response_text']) === 'yes' ? 'yes' : 'no'; ?>" style="width: <?php echo max($yn['percentage'], 2); ?>%;">
                                                    <?php echo $yn['percentage']; ?>%
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                    <?php elseif($q['question_type'] === 'rating_scale'): ?>
                                        <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 0.5rem;">Rating Scale &mdash; <?php echo $q['total_answers']; ?> responses</p>
                                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem;">
                                            <div>
                                                <span style="font-size: 2rem; font-weight: 700; color: #9B59B6;"><?php echo $q['avg_rating']; ?></span>
                                                <span style="color: var(--gray); font-size: 0.9rem;"> / 10 average</span>
                                            </div>
                                        </div>
                                        <p style="font-size: 0.85rem; font-weight: 600; color: var(--dark); margin-bottom: 0.75rem;">Rating Distribution</p>
                                        <?php foreach($q['distribution'] as $dist): ?>
                                            <?php if($dist['count'] > 0 || $dist['value'] <= 5): ?>
                                                <div class="result-label">
                                                    <span class="result-label-text"><?php echo $dist['value']; ?></span>
                                                    <span class="result-label-count"><?php echo $dist['count']; ?> (<?php echo $dist['percentage']; ?>%)</span>
                                                </div>
                                                <div class="result-bar" style="margin-bottom: 0.5rem;">
                                                    <div class="result-bar-fill rating" style="width: <?php echo max($dist['bar_width'], 2); ?>%;">
                                                        <?php if($dist['percentage'] >= 5): ?><?php echo $dist['percentage']; ?>%<?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>

                                    <?php elseif($q['question_type'] === 'short_answer'): ?>
                                        <p style="font-size: 0.85rem; color: var(--gray); margin-bottom: 1rem;">Short Answer &mdash; <?php echo count($q['data']); ?> responses</p>
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
                                            <p class="text-muted">No responses yet.</p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
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
</body>
</html>
<?php } ?>
