<?php
require_once "../includes/session.php";

if(!isAdmin()){
    redirectToLogin();
}

$survey_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($survey_id <= 0){
    showToast("Invalid survey ID.", "error");
    header("Location: surveys.php");
    exit;
}

$success_msg = $error_msg = "";
$survey = null;
$existing_questions = [];

if(tableExists('surveys')){
    // Load survey
    $sql = "SELECT * FROM surveys WHERE id = ?";
    if($stmt = mysqli_prepare($link, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $survey_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $survey = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }

    if(!$survey){
        showToast("Survey not found.", "error");
        header("Location: surveys.php");
        exit;
    }

    // Load existing questions with choices
    $sql_q = "SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY sort_order ASC";
    if($stmt_q = mysqli_prepare($link, $sql_q)){
        mysqli_stmt_bind_param($stmt_q, "i", $survey_id);
        mysqli_stmt_execute($stmt_q);
        $result_q = mysqli_stmt_get_result($stmt_q);
        while($q_row = mysqli_fetch_assoc($result_q)){
            $q_row['choices'] = [];
            $sql_c = "SELECT * FROM survey_choices WHERE question_id = ? ORDER BY sort_order ASC";
            if($stmt_c = mysqli_prepare($link, $sql_c)){
                mysqli_stmt_bind_param($stmt_c, "i", $q_row['id']);
                mysqli_stmt_execute($stmt_c);
                $result_c = mysqli_stmt_get_result($stmt_c);
                while($c_row = mysqli_fetch_assoc($result_c)){
                    $q_row['choices'][] = $c_row;
                }
                mysqli_stmt_close($stmt_c);
            }
            // Check if question has responses
            $sql_r = "SELECT COUNT(*) as cnt FROM survey_responses WHERE question_id = ?";
            if($stmt_r = mysqli_prepare($link, $sql_r)){
                mysqli_stmt_bind_param($stmt_r, "i", $q_row['id']);
                mysqli_stmt_execute($stmt_r);
                $res_r = mysqli_stmt_get_result($stmt_r);
                $row_r = mysqli_fetch_assoc($res_r);
                $q_row['has_responses'] = $row_r['cnt'] > 0;
                mysqli_stmt_close($stmt_r);
            }
            $existing_questions[] = $q_row;
        }
        mysqli_stmt_close($stmt_q);
    }
} else {
    $error_msg = "Survey system is not configured. Please run the database migration.";
}

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!tableExists('surveys')){
        $error_msg = "Survey system is not configured. Please run the database migration.";
    } else {
        $title = trim($_POST["title"]);
        $description = trim($_POST["description"]);
        $open_date = !empty($_POST["open_date"]) ? $_POST["open_date"] : null;
        $close_date = !empty($_POST["close_date"]) ? $_POST["close_date"] : null;
        $is_active = isset($_POST["is_active"]) ? 1 : 0;

        // Existing question data (from hidden fields)
        $existing_q_ids = $_POST["existing_question_id"] ?? [];
        $existing_q_texts = $_POST["existing_question_text"] ?? [];
        $existing_q_types = $_POST["existing_question_type"] ?? [];
        $existing_q_required = $_POST["existing_question_required"] ?? [];
        $existing_q_scales = $_POST["existing_question_scale"] ?? [];
        $existing_q_choices = $_POST["existing_question_choices"] ?? [];

        // New question data
        $new_q_texts = $_POST["new_question_text"] ?? [];
        $new_q_types = $_POST["new_question_type"] ?? [];
        $new_q_required = $_POST["new_question_required"] ?? [];
        $new_q_scales = $_POST["new_question_scale"] ?? [];
        $new_q_choices = $_POST["new_question_choices"] ?? [];

        // Questions to delete (ones with no responses that were removed)
        $keep_question_ids = $_POST["keep_question_id"] ?? [];

        if(empty($title)){
            $error_msg = "Survey title is required.";
        } else {
            // Update survey
            $sql = "UPDATE surveys SET title = ?, description = ?, open_date = ?, close_date = ?, is_active = ? WHERE id = ?";
            if($stmt = mysqli_prepare($link, $sql)){
                mysqli_stmt_bind_param($stmt, "ssssii", $title, $description, $open_date, $close_date, $is_active, $survey_id);
                if(mysqli_stmt_execute($stmt)){
                    mysqli_stmt_close($stmt);

                    // Update existing questions
                    foreach($existing_q_ids as $idx => $eq_id){
                        $eq_id = intval($eq_id);
                        $eq_text = $existing_q_texts[$idx] ?? '';
                        $eq_type = $existing_q_types[$idx] ?? 'multiple_choice';
                        $eq_req = isset($existing_q_required[$idx]) ? 1 : 0;
                        $eq_scale = $existing_q_scales[$idx] ?? 5;

                        if(empty($eq_text)) continue;

                        $sql_uq = "UPDATE survey_questions SET question_text = ?, question_type = ?, is_required = ? WHERE id = ? AND survey_id = ?";
                        if($stmt_uq = mysqli_prepare($link, $sql_uq)){
                            mysqli_stmt_bind_param($stmt_uq, "ssiii", $eq_text, $eq_type, $eq_req, $eq_id, $survey_id);
                            mysqli_stmt_execute($stmt_uq);
                            mysqli_stmt_close($stmt_uq);
                        }

                        // Update choices for multiple_choice
                        if($eq_type === 'multiple_choice' && isset($existing_q_choices[$idx])){
                            // Delete existing choices and re-insert
                            $sql_dc = "DELETE FROM survey_choices WHERE question_id = ?";
                            if($stmt_dc = mysqli_prepare($link, $sql_dc)){
                                mysqli_stmt_bind_param($stmt_dc, "i", $eq_id);
                                mysqli_stmt_execute($stmt_dc);
                                mysqli_stmt_close($stmt_dc);
                            }
                            foreach($existing_q_choices[$idx] as $c_idx => $choice_text){
                                if(empty($choice_text)) continue;
                                $sql_ic = "INSERT INTO survey_choices (question_id, choice_text, sort_order) VALUES (?, ?, ?)";
                                if($stmt_ic = mysqli_prepare($link, $sql_ic)){
                                    $c_sort = $c_idx + 1;
                                    mysqli_stmt_bind_param($stmt_ic, "isi", $eq_id, $choice_text, $c_sort);
                                    mysqli_stmt_execute($stmt_ic);
                                    mysqli_stmt_close($stmt_ic);
                                }
                            }
                        }
                    }

                    // Delete removed questions (that have no responses)
                    $existing_ids_to_keep = array_map('intval', $keep_question_ids);
                    foreach($existing_questions as $eq){
                        if(!in_array($eq['id'], $existing_ids_to_keep) && !$eq['has_responses']){
                            $sql_dq = "DELETE FROM survey_questions WHERE id = ? AND survey_id = ?";
                            if($stmt_dq = mysqli_prepare($link, $sql_dq)){
                                mysqli_stmt_bind_param($stmt_dq, "ii", $eq['id'], $survey_id);
                                mysqli_stmt_execute($stmt_dq);
                                mysqli_stmt_close($stmt_dq);
                            }
                        }
                    }

                    // Add new questions
                    foreach($new_q_texts as $idx => $nq_text){
                        if(empty($nq_text)) continue;
                        $nq_type = $new_q_types[$idx] ?? 'multiple_choice';
                        $nq_req = isset($new_q_required[$idx]) ? 1 : 0;
                        $nq_scale = $new_q_scales[$idx] ?? 5;

                        // Determine sort order
                        $max_sort = 0;
                        foreach($existing_questions as $eq){
                            if($eq['sort_order'] > $max_sort) $max_sort = $eq['sort_order'];
                        }
                        $max_sort++;
                        // Also check existing_q_ids count
                        $max_sort = max($max_sort, count($existing_q_ids) + $idx + 1);

                        $sql_nq = "INSERT INTO survey_questions (survey_id, question_text, question_type, is_required, sort_order) VALUES (?, ?, ?, ?, ?)";
                        if($stmt_nq = mysqli_prepare($link, $sql_nq)){
                            mysqli_stmt_bind_param($stmt_nq, "issii", $survey_id, $nq_text, $nq_type, $nq_req, $max_sort);
                            if(mysqli_stmt_execute($stmt_nq)){
                                $new_q_id = mysqli_insert_id($stmt_nq);
                                mysqli_stmt_close($stmt_nq);

                                if($nq_type === 'multiple_choice' && isset($new_q_choices[$idx])){
                                    foreach($new_q_choices[$idx] as $c_idx => $choice_text){
                                        if(empty($choice_text)) continue;
                                        $sql_nc = "INSERT INTO survey_choices (question_id, choice_text, sort_order) VALUES (?, ?, ?)";
                                        if($stmt_nc = mysqli_prepare($link, $sql_nc)){
                                            $c_sort = $c_idx + 1;
                                            mysqli_stmt_bind_param($stmt_nc, "isi", $new_q_id, $choice_text, $c_sort);
                                            mysqli_stmt_execute($stmt_nc);
                                            mysqli_stmt_close($stmt_nc);
                                        }
                                    }
                                }
                            } else {
                                mysqli_stmt_close($stmt_nq);
                            }
                        }
                    }

                    logActivity($_SESSION["id"], 'Update Survey', 'Survey "' . $title . '" updated.');
                    showToast("Survey updated successfully!");
                    header("Location: surveys.php");
                    exit;
                } else {
                    $error_msg = "Error updating survey.";
                }
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
    <title>Edit Survey - Resident Information System</title>
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
                <div class="navbar-brand">Edit Survey</div>
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
            <span class="separator">/</span>
            <a href="surveys.php">Surveys</a>
            <span class="separator">/</span>
            <span class="active">Edit Survey</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Edit Survey</h1>
            <p>Modify survey details and questions</p>
        </div>

        <?php displayToasts(); ?>

        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <?php if(!tableExists('surveys')): ?>
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
        <?php elseif($survey): ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . '?id=' . $survey_id); ?>" id="editSurveyForm">
                <!-- Survey Details -->
                <div class="card">
                    <div class="card-header">
                        <h3>Survey Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Survey Title <span style="color: red;">*</span></label>
                            <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($survey['title']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($survey['description'] ?? ''); ?></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label>Open Date</label>
                                <input type="date" name="open_date" class="form-control" value="<?php echo $survey['open_date'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Close Date</label>
                                <input type="date" name="close_date" class="form-control" value="<?php echo $survey['close_date'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" name="is_active" value="1" <?php echo $survey['is_active'] ? 'checked' : ''; ?>> Active
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Existing Questions -->
                <div class="card">
                    <div class="card-header">
                        <h3>Existing Questions</h3>
                    </div>
                    <div class="card-body">
                        <?php if(count($existing_questions) > 0): ?>
                            <?php foreach($existing_questions as $idx => $eq): ?>
                                <div class="survey-question-card" id="existing_question_<?php echo $eq['id']; ?>" style="background: var(--primary-lightest); border-radius: var(--radius-sm); padding: 1.5rem; margin-bottom: 1rem; border-left: 4px solid var(--primary); position: relative;">
                                    <input type="hidden" name="existing_question_id[]" value="<?php echo $eq['id']; ?>">
                                    <input type="hidden" name="keep_question_id[]" value="<?php echo $eq['id']; ?>">

                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                        <h4 style="margin: 0; color: var(--primary);">Question <?php echo $idx + 1; ?>
                                            <?php if($eq['has_responses']): ?>
                                                <small style="color: var(--gray); font-weight: 400; font-size: 0.8rem;">(has responses - cannot delete)</small>
                                            <?php endif; ?>
                                        </h4>
                                        <?php if(!$eq['has_responses']): ?>
                                            <button type="button" class="btn btn-danger btn-sm" style="width: auto;" onclick="removeExistingQuestion(<?php echo $eq['id']; ?>)">
                                                <i class="bi bi-trash"></i> Remove
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-group">
                                        <label>Question Text <span style="color: red;">*</span></label>
                                        <input type="text" name="existing_question_text[]" class="form-control" required value="<?php echo htmlspecialchars($eq['question_text']); ?>">
                                    </div>

                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                        <div class="form-group">
                                            <label>Question Type</label>
                                            <select name="existing_question_type[]" class="form-control" onchange="toggleExistingQuestionOptions(this, <?php echo $eq['id']; ?>)">
                                                <option value="multiple_choice" <?php echo $eq['question_type'] === 'multiple_choice' ? 'selected' : ''; ?>>Multiple Choice</option>
                                                <option value="yes_no" <?php echo $eq['question_type'] === 'yes_no' ? 'selected' : ''; ?>>Yes/No</option>
                                                <option value="rating_scale" <?php echo $eq['question_type'] === 'rating_scale' ? 'selected' : ''; ?>>Rating Scale</option>
                                                <option value="short_answer" <?php echo $eq['question_type'] === 'short_answer' ? 'selected' : ''; ?>>Short Answer</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                <input type="checkbox" name="existing_question_required[]" value="<?php echo $eq['id']; ?>" <?php echo $eq['is_required'] ? 'checked' : ''; ?>> Required
                                            </label>
                                        </div>
                                    </div>

                                    <div id="existing_options_<?php echo $eq['id']; ?>">
                                        <?php if($eq['question_type'] === 'multiple_choice'): ?>
                                            <div class="choices-container" id="existing_choices_<?php echo $eq['id']; ?>">
                                                <label style="font-weight: 600; color: var(--dark); font-size: 0.9rem; margin-bottom: 0.5rem; display: block;">Choices</label>
                                                <?php foreach($eq['choices'] as $c_idx => $choice): ?>
                                                    <div class="choice-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                                                        <input type="text" name="existing_question_choices[<?php echo $idx; ?>][]" class="form-control" value="<?php echo htmlspecialchars($choice['choice_text']); ?>">
                                                        <?php if(!$eq['has_responses']): ?>
                                                            <button type="button" class="btn btn-danger btn-sm" style="width: auto; flex-shrink: 0;" onclick="removeChoice(this)"><i class="bi bi-x"></i></button>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php if(!$eq['has_responses']): ?>
                                                <button type="button" class="btn btn-secondary btn-sm" style="width: auto; margin-top: 0.5rem;" onclick="addExistingChoice(<?php echo $eq['id']; ?>, <?php echo $idx; ?>)">
                                                    <i class="bi bi-plus"></i> Add Choice
                                                </button>
                                            <?php endif; ?>
                                        <?php elseif($eq['question_type'] === 'rating_scale'): ?>
                                            <div class="form-group" style="margin-top: 0.5rem;">
                                                <label>Maximum Scale Value</label>
                                                <input type="number" name="existing_question_scale[<?php echo $idx; ?>]" class="form-control" min="2" max="10" value="5" style="max-width: 150px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted" style="text-align: center; padding: 1rem;">No existing questions.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- New Questions -->
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3>Add New Questions</h3>
                        <button type="button" class="btn btn-primary" style="width: auto;" onclick="addNewQuestion()">
                            <i class="bi bi-plus-circle"></i> Add Question
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="newQuestionsContainer"></div>
                        <div id="noNewQuestionsMsg" class="empty-state" style="padding: 2rem;">
                            <p class="text-muted" style="margin-bottom: 0;">Click "Add Question" to add new questions to this survey.</p>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div style="display: flex; gap: 1rem; margin-top: 1rem; margin-bottom: 2rem;">
                    <button type="submit" class="btn btn-primary" style="width: auto;" onclick="return validateSurveyForm()">
                        <i class="bi bi-check-circle"></i> Update Survey
                    </button>
                    <a href="surveys.php" class="btn btn-secondary" style="width: auto;">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
<?php if(!$is_partial){ ?>

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
<?php } ?>
    <?php if(tableExists('surveys')): ?>
    <script>
    var newQuestionCounter = 0;

    function removeExistingQuestion(qId){
        if(!confirm('This question will be removed. Continue?')) return;
        var el = document.getElementById('existing_question_' + qId);
        if(el){
            el.style.display = 'none';
            var keepInput = el.querySelector('input[name="keep_question_id[]"]');
            if(keepInput) keepInput.remove();
        }
    }

    function toggleExistingQuestionOptions(select, qId){
        var optionsDiv = document.getElementById('existing_options_' + qId);
        if(!optionsDiv) return;
        var type = select.value;

        if(type === 'multiple_choice'){
            var qIdx = select.closest('.survey-question-card').querySelector('input[name="existing_question_id[]"]').value;
            optionsDiv.innerHTML = '<div class="choices-container" id="existing_choices_' + qId + '">' +
                '<label style="font-weight: 600; color: var(--dark); font-size: 0.9rem; margin-bottom: 0.5rem; display: block;">Choices</label>' +
                '<div class="choice-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">' +
                    '<input type="text" name="existing_question_choices[' + qIdx + '][]" class="form-control" placeholder="Choice 1">' +
                '</div>' +
                '<div class="choice-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">' +
                    '<input type="text" name="existing_question_choices[' + qIdx + '][]" class="form-control" placeholder="Choice 2">' +
                '</div>' +
            '</div>' +
            '<button type="button" class="btn btn-secondary btn-sm" style="width: auto; margin-top: 0.5rem;" onclick="addExistingChoice(' + qId + ', ' + qIdx + ')">' +
                '<i class="bi bi-plus"></i> Add Choice' +
            '</button>';
        } else if(type === 'rating_scale'){
            var qIdx2 = select.closest('.survey-question-card').querySelector('input[name="existing_question_id[]"]').value;
            optionsDiv.innerHTML = '<div class="form-group" style="margin-top: 0.5rem;">' +
                '<label>Maximum Scale Value</label>' +
                '<input type="number" name="existing_question_scale[' + qIdx2 + ']" class="form-control" min="2" max="10" value="5" style="max-width: 150px;">' +
            '</div>';
        } else {
            optionsDiv.innerHTML = '';
        }
    }

    function addExistingChoice(qId, qIdx){
        var container = document.getElementById('existing_choices_' + qId);
        if(!container) return;
        var count = container.querySelectorAll('.choice-row').length + 1;
        var html = '<div class="choice-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">' +
            '<input type="text" name="existing_question_choices[' + qIdx + '][]" class="form-control" placeholder="Choice ' + count + '">' +
            '<button type="button" class="btn btn-danger btn-sm" style="width: auto; flex-shrink: 0;" onclick="removeChoice(this)"><i class="bi bi-x"></i></button>' +
        '</div>';
        container.insertAdjacentHTML('beforeend', html);
    }

    function addNewQuestion(){
        newQuestionCounter++;
        var nNum = newQuestionCounter;
        var container = document.getElementById('newQuestionsContainer');
        var noMsg = document.getElementById('noNewQuestionsMsg');
        if(noMsg) noMsg.style.display = 'none';

        var html = '<div class="survey-question-card" id="new_question_' + nNum + '" style="background: rgba(46, 204, 113, 0.05); border-radius: var(--radius-sm); padding: 1.5rem; margin-bottom: 1rem; border-left: 4px solid var(--success); position: relative;">' +
            '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">' +
                '<h4 style="margin: 0; color: var(--success);">New Question</h4>' +
                '<button type="button" class="btn btn-danger btn-sm" style="width: auto;" onclick="removeNewQuestion(' + nNum + ')">' +
                    '<i class="bi bi-trash"></i> Remove' +
                '</button>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Question Text <span style="color: red;">*</span></label>' +
                '<input type="text" name="new_question_text[]" class="form-control" required placeholder="Enter your question">' +
            '</div>' +
            '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">' +
                '<div class="form-group">' +
                    '<label>Question Type</label>' +
                    '<select name="new_question_type[]" class="form-control" onchange="toggleNewQuestionOptions(this, ' + nNum + ')">' +
                        '<option value="multiple_choice">Multiple Choice</option>' +
                        '<option value="yes_no">Yes/No</option>' +
                        '<option value="rating_scale">Rating Scale</option>' +
                        '<option value="short_answer">Short Answer</option>' +
                    '</select>' +
                '</div>' +
                '<div class="form-group">' +
                    '<label>&nbsp;</label>' +
                    '<label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">' +
                        '<input type="checkbox" name="new_question_required[]" value="' + nNum + '"> Required' +
                    '</label>' +
                '</div>' +
            '</div>' +
            '<div id="new_options_' + nNum + '">' +
                '<div class="choices-container" id="new_choices_' + nNum + '">' +
                    '<label style="font-weight: 600; color: var(--dark); font-size: 0.9rem; margin-bottom: 0.5rem; display: block;">Choices</label>' +
                    '<div class="choice-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">' +
                        '<input type="text" name="new_question_choices[' + nNum + '][]" class="form-control" placeholder="Choice 1">' +
                    '</div>' +
                    '<div class="choice-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">' +
                        '<input type="text" name="new_question_choices[' + nNum + '][]" class="form-control" placeholder="Choice 2">' +
                    '</div>' +
                '</div>' +
                '<button type="button" class="btn btn-secondary btn-sm" style="width: auto; margin-top: 0.5rem;" onclick="addNewChoice(' + nNum + ')">' +
                    '<i class="bi bi-plus"></i> Add Choice' +
                '</button>' +
            '</div>' +
        '</div>';

        container.insertAdjacentHTML('beforeend', html);
    }

    function removeNewQuestion(num){
        var el = document.getElementById('new_question_' + num);
        if(el) el.remove();
        var container = document.getElementById('newQuestionsContainer');
        if(container && container.children.length === 0){
            var noMsg = document.getElementById('noNewQuestionsMsg');
            if(noMsg) noMsg.style.display = '';
        }
    }

    function addNewChoice(qNum){
        var container = document.getElementById('new_choices_' + qNum);
        if(!container) return;
        var count = container.querySelectorAll('.choice-row').length + 1;
        var html = '<div class="choice-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">' +
            '<input type="text" name="new_question_choices[' + qNum + '][]" class="form-control" placeholder="Choice ' + count + '">' +
            '<button type="button" class="btn btn-danger btn-sm" style="width: auto; flex-shrink: 0;" onclick="removeChoice(this)"><i class="bi bi-x"></i></button>' +
        '</div>';
        container.insertAdjacentHTML('beforeend', html);
    }

    function toggleNewQuestionOptions(select, qNum){
        var optionsDiv = document.getElementById('new_options_' + qNum);
        if(!optionsDiv) return;
        var type = select.value;

        if(type === 'multiple_choice'){
            optionsDiv.innerHTML = '<div class="choices-container" id="new_choices_' + qNum + '">' +
                '<label style="font-weight: 600; color: var(--dark); font-size: 0.9rem; margin-bottom: 0.5rem; display: block;">Choices</label>' +
                '<div class="choice-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">' +
                    '<input type="text" name="new_question_choices[' + qNum + '][]" class="form-control" placeholder="Choice 1">' +
                '</div>' +
                '<div class="choice-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">' +
                    '<input type="text" name="new_question_choices[' + qNum + '][]" class="form-control" placeholder="Choice 2">' +
                '</div>' +
            '</div>' +
            '<button type="button" class="btn btn-secondary btn-sm" style="width: auto; margin-top: 0.5rem;" onclick="addNewChoice(' + qNum + ')">' +
                '<i class="bi bi-plus"></i> Add Choice' +
            '</button>';
        } else if(type === 'rating_scale'){
            optionsDiv.innerHTML = '<div class="form-group" style="margin-top: 0.5rem;">' +
                '<label>Maximum Scale Value</label>' +
                '<input type="number" name="new_question_scale[' + qNum + ']" class="form-control" min="2" max="10" value="5" style="max-width: 150px;">' +
            '</div>';
        } else {
            optionsDiv.innerHTML = '';
        }
    }

    function removeChoice(btn){
        var row = btn.closest('.choice-row');
        if(row) row.remove();
    }

    function validateSurveyForm(){
        var title = document.querySelector('input[name="title"]');
        if(!title || !title.value.trim()){
            alert('Please enter a survey title.');
            if(title) title.focus();
            return false;
        }
        return true;
    }
    </script>
    <?php endif; ?>
<?php if(!$is_partial){ ?>
</body>
</html>
<?php } ?>
