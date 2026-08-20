<?php
require_once "../includes/session.php";

if(!isAdmin()){
    redirectToLogin();
}

$success_msg = $error_msg = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!tableExists('surveys')){
        $error_msg = "Survey system is not configured. Please run the database migration.";
    } else {
        $title = trim($_POST["title"]);
        $description = trim($_POST["description"]);
        $open_date = !empty($_POST["open_date"]) ? $_POST["open_date"] : null;
        $close_date = !empty($_POST["close_date"]) ? $_POST["close_date"] : null;
        $question_texts = $_POST["question_text"] ?? [];
        $question_types = $_POST["question_type"] ?? [];
        $question_required = $_POST["question_required"] ?? [];
        $question_scales = $_POST["question_scale"] ?? [];
        $question_choices = $_POST["question_choices"] ?? [];

        if(empty($title)){
            $error_msg = "Survey title is required.";
        } elseif(empty($question_texts) || count(array_filter($question_texts)) === 0){
            $error_msg = "At least one question is required.";
        } else {
            $sql = "INSERT INTO surveys (title, description, created_by, is_active, open_date, close_date) VALUES (?, ?, ?, 1, ?, ?)";
            if($stmt = mysqli_prepare($link, $sql)){
                $created_by = $_SESSION["id"];
                mysqli_stmt_bind_param($stmt, "ssiss", $title, $description, $created_by, $open_date, $close_date);
                
                if(mysqli_stmt_execute($stmt)){
                    $survey_id = mysqli_insert_id($link);
                    mysqli_stmt_close($stmt);

                    foreach($question_texts as $idx => $q_text){
                        if(empty($q_text)) continue;

                        $q_type = $question_types[$idx] ?? 'multiple_choice';
                        $is_required = isset($question_required[$idx]) ? 1 : 0;
                        $sort_order = $idx + 1;
                        $max_scale = $question_scales[$idx] ?? 5;

                        $sql_q = "INSERT INTO survey_questions (survey_id, question_text, question_type, is_required, sort_order) VALUES (?, ?, ?, ?, ?)";
                        if($stmt_q = mysqli_prepare($link, $sql_q)){
                            mysqli_stmt_bind_param($stmt_q, "issii", $survey_id, $q_text, $q_type, $is_required, $sort_order);
                            
                            if(mysqli_stmt_execute($stmt_q)){
                                $question_id = mysqli_insert_id($link);
                                mysqli_stmt_close($stmt_q);

                                if($q_type === 'multiple_choice' && isset($question_choices[$idx])){
                                    foreach($question_choices[$idx] as $c_idx => $choice_text){
                                        if(empty($choice_text)) continue;
                                        $sql_c = "INSERT INTO survey_choices (question_id, choice_text, sort_order) VALUES (?, ?, ?)";
                                        if($stmt_c = mysqli_prepare($link, $sql_c)){
                                            $c_sort = $c_idx + 1;
                                            mysqli_stmt_bind_param($stmt_c, "isi", $question_id, $choice_text, $c_sort);
                                            mysqli_stmt_execute($stmt_c);
                                            mysqli_stmt_close($stmt_c);
                                        }
                                    }
                                }
                            } else {
                                mysqli_stmt_close($stmt_q);
                            }
                        }
                    }

                    logActivity($_SESSION["id"], 'Create Survey', 'Survey "' . $title . '" created.');
                    showToast("Survey created successfully!");
                    header("Location: surveys.php");
                    exit;
                } else {
                    $error_msg = "Error creating survey.";
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
    <title>Create Survey - Resident Information System</title>
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
                <div class="navbar-brand">Create Survey</div>
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
            <span class="active">Create Survey</span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1>Create New Survey</h1>
            <p>Design a survey to gather feedback from residents</p>
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
        <?php else: ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="surveyForm">
                <!-- Survey Details -->
                <div class="card">
                    <div class="card-header">
                        <h3>Survey Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Survey Title <span style="color: red;">*</span></label>
                            <input type="text" name="title" class="form-control" required placeholder="Enter survey title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Enter survey description (optional)"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div class="form-group">
                                <label>Open Date</label>
                                <input type="date" name="open_date" class="form-control" value="<?php echo isset($_POST['open_date']) ? htmlspecialchars($_POST['open_date']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Close Date</label>
                                <input type="date" name="close_date" class="form-control" value="<?php echo isset($_POST['close_date']) ? htmlspecialchars($_POST['close_date']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Questions Section -->
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3>Questions</h3>
                        <button type="button" class="btn btn-primary" style="width: auto;" onclick="addQuestion()">
                            <i class="bi bi-plus-circle"></i> Add Question
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="questionsContainer">
                        </div>
                        <div id="noQuestionsMsg" class="empty-state" style="padding: 2rem;">
                            <span class="empty-icon" style="font-size: 2.5rem;"><i class="bi bi-question-circle"></i></span>
                            <h3 style="font-size: 1.1rem;">No questions added yet</h3>
                            <p style="font-size: 0.9rem;">Click "Add Question" to start building your survey.</p>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div style="display: flex; gap: 1rem; margin-top: 1rem; margin-bottom: 2rem;">
                    <button type="submit" class="btn btn-primary" style="width: auto;" onclick="return validateSurveyForm()">
                        <i class="bi bi-check-circle"></i> Create Survey
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
<?php } ?>
    <?php if(tableExists('surveys')): ?>
    <script>
    var questionCounter = 0;

    function addQuestion(){
        questionCounter++;
        var qNum = questionCounter;
        var container = document.getElementById('questionsContainer');
        var noMsg = document.getElementById('noQuestionsMsg');
        if(noMsg) noMsg.style.display = 'none';

        var html = '<div class="survey-question-card" id="question_' + qNum + '" style="background: var(--primary-lightest); border-radius: var(--radius-sm); padding: 1.5rem; margin-bottom: 1rem; border-left: 4px solid var(--primary); position: relative;">' +
            '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">' +
                '<h4 style="margin: 0; color: var(--primary);">Question ' + qNum + '</h4>' +
                '<button type="button" class="btn btn-danger btn-sm" style="width: auto;" onclick="removeQuestion(' + qNum + ')">' +
                    '<i class="bi bi-trash"></i> Remove' +
                '</button>' +
            '</div>' +
            '<div class="form-group">' +
                '<label>Question Text <span style="color: red;">*</span></label>' +
                '<input type="text" name="question_text[]" class="form-control" required placeholder="Enter your question">' +
            '</div>' +
            '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">' +
                '<div class="form-group">' +
                    '<label>Question Type</label>' +
                    '<select name="question_type[]" class="form-control" onchange="toggleQuestionOptions(this, ' + qNum + ')">' +
                        '<option value="multiple_choice">Multiple Choice</option>' +
                        '<option value="yes_no">Yes/No</option>' +
                        '<option value="rating_scale">Rating Scale</option>' +
                        '<option value="short_answer">Short Answer</option>' +
                    '</select>' +
                '</div>' +
                '<div class="form-group">' +
                    '<label>&nbsp;</label>' +
                    '<label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">' +
                        '<input type="checkbox" name="question_required[]" value="' + qNum + '"> Required' +
                    '</label>' +
                '</div>' +
            '</div>' +
            '<div id="options_' + qNum + '">' +
                '<div class="choices-container" id="choices_' + qNum + '">' +
                    '<label style="font-weight: 600; color: var(--dark); font-size: 0.9rem; margin-bottom: 0.5rem; display: block;">Choices</label>' +
                    '<div class="choice-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">' +
                        '<input type="text" name="question_choices[' + qNum + '][]" class="form-control" placeholder="Choice 1">' +
                    '</div>' +
                    '<div class="choice-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">' +
                        '<input type="text" name="question_choices[' + qNum + '][]" class="form-control" placeholder="Choice 2">' +
                    '</div>' +
                '</div>' +
                '<button type="button" class="btn btn-secondary btn-sm" style="width: auto; margin-top: 0.5rem;" onclick="addChoice(' + qNum + ')">' +
                    '<i class="bi bi-plus"></i> Add Choice' +
                '</button>' +
            '</div>' +
        '</div>';

        container.insertAdjacentHTML('beforeend', html);
    }

    function removeQuestion(num){
        var el = document.getElementById('question_' + num);
        if(el){
            el.remove();
        }
        var container = document.getElementById('questionsContainer');
        if(container && container.children.length === 0){
            var noMsg = document.getElementById('noQuestionsMsg');
            if(noMsg) noMsg.style.display = '';
        }
    }

    function addChoice(qNum){
        var container = document.getElementById('choices_' + qNum);
        if(!container) return;
        var count = container.querySelectorAll('.choice-row').length + 1;
        var html = '<div class="choice-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">' +
            '<input type="text" name="question_choices[' + qNum + '][]" class="form-control" placeholder="Choice ' + count + '">' +
            '<button type="button" class="btn btn-danger btn-sm" style="width: auto; flex-shrink: 0;" onclick="removeChoice(this)">' +
                '<i class="bi bi-x"></i>' +
            '</button>' +
        '</div>';
        container.insertAdjacentHTML('beforeend', html);
    }

    function removeChoice(btn){
        var row = btn.closest('.choice-row');
        if(row) row.remove();
    }

    function toggleQuestionOptions(select, qNum){
        var optionsDiv = document.getElementById('options_' + qNum);
        if(!optionsDiv) return;
        var type = select.value;

        if(type === 'multiple_choice'){
            optionsDiv.innerHTML = '<div class="choices-container" id="choices_' + qNum + '">' +
                '<label style="font-weight: 600; color: var(--dark); font-size: 0.9rem; margin-bottom: 0.5rem; display: block;">Choices</label>' +
                '<div class="choice-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">' +
                    '<input type="text" name="question_choices[' + qNum + '][]" class="form-control" placeholder="Choice 1">' +
                '</div>' +
                '<div class="choice-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">' +
                    '<input type="text" name="question_choices[' + qNum + '][]" class="form-control" placeholder="Choice 2">' +
                '</div>' +
            '</div>' +
            '<button type="button" class="btn btn-secondary btn-sm" style="width: auto; margin-top: 0.5rem;" onclick="addChoice(' + qNum + ')">' +
                '<i class="bi bi-plus"></i> Add Choice' +
            '</button>';
        } else if(type === 'rating_scale'){
            optionsDiv.innerHTML = '<div class="form-group" style="margin-top: 0.5rem;">' +
                '<label>Maximum Scale Value</label>' +
                '<input type="number" name="question_scale[' + qNum + ']" class="form-control" min="2" max="10" value="5" style="max-width: 150px;">' +
                '<small class="text-muted">Residents will rate from 1 to this value</small>' +
            '</div>';
        } else {
            optionsDiv.innerHTML = '';
        }
    }

    function validateSurveyForm(){
        var title = document.querySelector('input[name="title"]');
        if(!title || !title.value.trim()){
            alert('Please enter a survey title.');
            if(title) title.focus();
            return false;
        }
        var questionTexts = document.querySelectorAll('input[name="question_text[]"]');
        var hasQuestion = false;
        for(var i = 0; i < questionTexts.length; i++){
            if(questionTexts[i].value.trim()){
                hasQuestion = true;
                break;
            }
        }
        if(!hasQuestion){
            alert('Please add at least one question with text.');
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
