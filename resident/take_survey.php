<?php
require_once "../includes/session.php";

if(!isResident()){
    redirectToLogin();
}

$resident_id = $_SESSION["resident_id"];
$survey_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$success_msg = $error_msg = "";

if($survey_id <= 0){
    header("Location: surveys.php");
    exit;
}

if(!tableExists('surveys') || !tableExists('survey_questions') || !tableExists('survey_responses')){
    $_SESSION['toast_messages'][] = ['message' => 'Survey system is not yet configured.', 'type' => 'error'];
    header("Location: surveys.php");
    exit;
}

// Fetch survey data
$survey = null;
$sql = "SELECT * FROM surveys WHERE id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $survey_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $survey = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if(!$survey){
    header("Location: surveys.php");
    exit;
}

// Verify survey is active and within date range
if(!$survey['is_active']){
    $_SESSION['toast_messages'][] = ['message' => 'This survey is no longer active.', 'type' => 'error'];
    header("Location: surveys.php");
    exit;
}

if(!empty($survey['open_date']) && $survey['open_date'] > date('Y-m-d')){
    $_SESSION['toast_messages'][] = ['message' => 'This survey has not opened yet.', 'type' => 'error'];
    header("Location: surveys.php");
    exit;
}

if(!empty($survey['close_date']) && $survey['close_date'] < date('Y-m-d')){
    $_SESSION['toast_messages'][] = ['message' => 'This survey has already closed.', 'type' => 'error'];
    header("Location: surveys.php");
    exit;
}

// Check if resident already completed this survey
$already_completed = false;
$sql = "SELECT COUNT(*) as count FROM survey_responses WHERE survey_id = ? AND resident_id = ?";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "ii", $survey_id, $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    if($row['count'] > 0){
        $already_completed = true;
    }
    mysqli_stmt_close($stmt);
}

if($already_completed){
    $_SESSION['toast_messages'][] = ['message' => 'You have already completed this survey.', 'type' => 'info'];
    header("Location: surveys.php");
    exit;
}

// Fetch questions ordered by sort_order
$questions = [];
$sql = "SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY sort_order ASC";
if($stmt = mysqli_prepare($link, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $survey_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while($row = mysqli_fetch_assoc($result)){
        // Fetch choices for multiple choice questions
        if($row['question_type'] === 'multiple_choice'){
            $choices = [];
            $sql_c = "SELECT * FROM survey_choices WHERE question_id = ? ORDER BY sort_order ASC";
            if($stmt_c = mysqli_prepare($link, $sql_c)){
                mysqli_stmt_bind_param($stmt_c, "i", $row['id']);
                mysqli_stmt_execute($stmt_c);
                $result_c = mysqli_stmt_get_result($stmt_c);
                while($choice = mysqli_fetch_assoc($result_c)){
                    $choices[] = $choice;
                }
                mysqli_stmt_close($stmt_c);
            }
            $row['choices'] = $choices;
        }
        $questions[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Handle form submission
if($_SERVER["REQUEST_METHOD"] == "POST"){
    // Server-side check: verify no existing responses for this survey+resident
    $check_sql = "SELECT COUNT(*) as count FROM survey_responses WHERE survey_id = ? AND resident_id = ?";
    if($check_stmt = mysqli_prepare($link, $check_sql)){
        mysqli_stmt_bind_param($check_stmt, "ii", $survey_id, $resident_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $check_row = mysqli_fetch_assoc($check_result);
        if($check_row['count'] > 0){
            $error_msg = "You have already submitted this survey.";
        }
        mysqli_stmt_close($check_stmt);
    }

    if(empty($error_msg)){
        // Validate all required questions are answered
        $missing_required = false;
        foreach($questions as $question){
            if($question['is_required']){
                $response_key = 'q_' . $question['id'];
                $response_text = isset($_POST[$response_key]) ? trim($_POST[$response_key]) : '';
                if(empty($response_text)){
                    $missing_required = true;
                    break;
                }
            }
        }

        if($missing_required){
            $error_msg = "Please answer all required questions before submitting.";
        } else {
            // Insert all responses
            $success = true;
            foreach($questions as $question){
                $response_key = 'q_' . $question['id'];
                $response_text = isset($_POST[$response_key]) ? trim($_POST[$response_key]) : null;

                $sql = "INSERT INTO survey_responses (survey_id, question_id, resident_id, response_text) VALUES (?, ?, ?, ?)";
                if($stmt = mysqli_prepare($link, $sql)){
                    mysqli_stmt_bind_param($stmt, "iiis", $survey_id, $question['id'], $resident_id, $response_text);
                    if(!mysqli_stmt_execute($stmt)){
                        $success = false;
                    }
                    mysqli_stmt_close($stmt);
                }
            }

            if($success){
                logActivity($_SESSION["id"], 'Submit Survey', 'Resident submitted survey: ' . $survey['title']);
                header("Location: survey_confirm.php?title=" . urlencode($survey['title']));
                exit;
            } else {
                $error_msg = "An error occurred while submitting your responses. Please try again.";
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
    <title><?php echo htmlspecialchars($survey['title']); ?> - Resident Information System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .rating-circles {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        .rating-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 2px solid #D5E8F7;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            color: #95A5A6;
            background: white;
            transition: all 0.2s ease;
        }
        .rating-circle:hover {
            border-color: #5AA9E6;
            color: #5AA9E6;
        }
        .rating-circle.selected {
            background: #5AA9E6;
            border-color: #5AA9E6;
            color: white;
        }
        .rating-circle input {
            display: none;
        }
        .yes-no-options {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        .yes-no-option {
            position: relative;
        }
        .yes-no-option input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .yes-no-option label {
            display: inline-block;
            padding: 0.6rem 1.5rem;
            border: 2px solid #D5E8F7;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .yes-no-option input:checked + label {
            background: #5AA9E6;
            border-color: #5AA9E6;
            color: white;
        }
        .question-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-soft);
        }
        .question-number {
            display: inline-block;
            background: #5AA9E6;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            text-align: center;
            line-height: 28px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        .question-text {
            font-weight: 600;
            color: #2C3E50;
            display: inline;
        }
        .required-mark {
            color: #E74C3C;
            margin-left: 0.25rem;
        }
    </style>
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
                <div class="navbar-brand"><?php echo htmlspecialchars($survey['title']); ?></div>
                <div class="navbar-menu">
                    <a href="surveys.php">Back to Surveys</a>
                    <a href="../auth/logout.php">Logout</a>
                </div>
            </div>
        </nav>
<?php } ?>

        <!-- Breadcrumbs -->
        <div class="breadcrumbs">
            <a href="dashboard.php">Dashboard</a>
            <span class="separator">&gt;</span>
            <a href="surveys.php">Health Surveys</a>
            <span class="separator">&gt;</span>
            <span class="active"><?php echo htmlspecialchars($survey['title']); ?></span>
        </div>

        <!-- Page Header -->
        <div class="page-header">
            <h1><?php echo htmlspecialchars($survey['title']); ?></h1>
            <?php if(!empty($survey['description'])): ?>
                <p><?php echo nl2br(htmlspecialchars($survey['description'])); ?></p>
            <?php endif; ?>
        </div>

        <!-- Messages -->
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <!-- Survey Form -->
        <form method="POST" action="take_survey.php?id=<?php echo $survey_id; ?>" id="surveyForm" onsubmit="preventDuplicateSubmit(this.querySelector('button[type=submit]'))">
            
            <?php foreach($questions as $index => $question): ?>
                <div class="question-card">
                    <div style="margin-bottom: 1rem;">
                        <span class="question-number"><?php echo $index + 1; ?></span>
                        <span class="question-text"><?php echo htmlspecialchars($question['question_text']); ?></span>
                        <?php if($question['is_required']): ?>
                            <span class="required-mark">*</span>
                        <?php endif; ?>
                    </div>

                    <?php if($question['question_type'] === 'multiple_choice'): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            <?php foreach($question['choices'] as $choice): ?>
                                <div style="position: relative; padding-left: 1.75rem;">
                                    <input type="radio" name="q_<?php echo $question['id']; ?>" value="<?php echo htmlspecialchars($choice['choice_text']); ?>" id="q<?php echo $question['id']; ?>_<?php echo $choice['id']; ?>" style="position: absolute; left: 0; top: 3px;">
                                    <label for="q<?php echo $question['id']; ?>_<?php echo $choice['id']; ?>" style="cursor: pointer; padding: 0.5rem 0; color: #2C3E50;">
                                        <?php echo htmlspecialchars($choice['choice_text']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif($question['question_type'] === 'yes_no'): ?>
                        <div class="yes-no-options">
                            <div class="yes-no-option">
                                <input type="radio" name="q_<?php echo $question['id']; ?>" value="Yes" id="q<?php echo $question['id']; ?>_yes">
                                <label for="q<?php echo $question['id']; ?>_yes">Yes</label>
                            </div>
                            <div class="yes-no-option">
                                <input type="radio" name="q_<?php echo $question['id']; ?>" value="No" id="q<?php echo $question['id']; ?>_no">
                                <label for="q<?php echo $question['id']; ?>_no">No</label>
                            </div>
                        </div>

                    <?php elseif($question['question_type'] === 'rating_scale'): ?>
                        <?php 
                        $max_scale = 5;
                        ?>
                        <div class="rating-circles">
                            <?php for($i = 1; $i <= $max_scale; $i++): ?>
                                <label class="rating-circle" for="q<?php echo $question['id']; ?>_<?php echo $i; ?>" onclick="
                                    var siblings = this.parentElement.querySelectorAll('.rating-circle');
                                    siblings.forEach(function(s){ s.classList.remove('selected'); });
                                    this.classList.add('selected');
                                    document.getElementById('q<?php echo $question['id']; ?>_<?php echo $i; ?>').checked = true;
                                ">
                                    <?php echo $i; ?>
                                    <input type="radio" name="q_<?php echo $question['id']; ?>" value="<?php echo $i; ?>" id="q<?php echo $question['id']; ?>_<?php echo $i; ?>" style="display: none;">
                                </label>
                            <?php endfor; ?>
                        </div>

                    <?php elseif($question['question_type'] === 'short_answer'): ?>
                        <textarea name="q_<?php echo $question['id']; ?>" id="short_answer_<?php echo $question['id']; ?>" class="form-control" rows="3" placeholder="Type your answer here..." autocomplete="off" aria-label="Your answer"><?php echo htmlspecialchars($_POST['q_' . $question['id']] ?? ''); ?></textarea>

                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if(count($questions) > 0): ?>
                <div style="display: flex; gap: 1rem; margin-top: 2rem; margin-bottom: 2rem;">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-check-circle"></i> Submit Survey
                    </button>
                    <a href="surveys.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body" style="text-align: center; padding: 2rem;">
                        <p style="color: #95A5A6;">This survey has no questions.</p>
                        <a href="surveys.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Back to Surveys
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </form>
<?php if(!$is_partial){ ?>
    </div><!-- end main-content -->

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
</body>
</html>
<?php } ?>
