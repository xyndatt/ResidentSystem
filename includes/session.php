<?php
// Start the session
session_start();

// Include config file
require_once "config.php";

// Check if the user is logged in, otherwise redirect to login page
function isLoggedIn() {
    return isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;
}

// Redirect to login page if not logged in
function redirectToLogin() {
    header("location: ../auth/login.php");
    exit;
}

// Check for session timeout
function checkSessionTimeout() {
    if (isLoggedIn()) {
        if (isset($_SESSION["last_activity"]) && (time() - $_SESSION["last_activity"] > SESSION_TIMEOUT)) {
            // Last activity was longer than 30 minutes ago
            session_unset();     // unset $_SESSION variable for the run-time 
            session_destroy();   // destroy session data in storage
            header("location: ../auth/login.php?timeout=true");
            exit;
        }
        $_SESSION["last_activity"] = time(); // Update last activity time
    }
}

// Call checkSessionTimeout on every page load that includes session.php
checkSessionTimeout();

// Function to check user role
function isAdmin() {
    return isLoggedIn() && $_SESSION["role"] === "admin";
}

function isResident() {
    return isLoggedIn() && $_SESSION["role"] === "resident";
}

// Helper function to get a user's full name from their resident record
function getResidentName($user_id) {
    global $link;
    if (!$user_id || $user_id <= 0) {
        return null;
    }
    $sql = "SELECT r.first_name, r.last_name FROM users u INNER JOIN residents r ON u.resident_id = r.id WHERE u.id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            mysqli_stmt_close($stmt);
            return trim($row['first_name'] . ' ' . $row['last_name']);
        }
        mysqli_stmt_close($stmt);
    }
    return null;
}

// Function to log activity
function logActivity($user_id, $action, $details = null) {
    global $link;
    $param_user_id = ($user_id > 0) ? $user_id : NULL;

    $fullName = getResidentName($user_id);
    if ($fullName) {
        $details = ($details ? $details . ' | ' : '') . 'User: ' . $fullName;
    } elseif ($user_id > 0) {
        $sql_user = "SELECT username FROM users WHERE id = ?";
        if ($stmt_user = mysqli_prepare($link, $sql_user)) {
            mysqli_stmt_bind_param($stmt_user, "i", $user_id);
            mysqli_stmt_execute($stmt_user);
            $result_user = mysqli_stmt_get_result($stmt_user);
            if ($row_user = mysqli_fetch_assoc($result_user)) {
                $details = ($details ? $details . ' | ' : '') . 'User: ' . $row_user['username'];
            }
            mysqli_stmt_close($stmt_user);
        }
    }

    $sql = "INSERT INTO activity_logs (user_id, action, details) VALUES (?, ?, ?)";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "iss", $param_user_id, $action, $details);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Check if a database table exists
function tableExists($table_name) {
    global $link;
    $sql = "SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = ? AND table_name = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        $db_name = DB_NAME;
        mysqli_stmt_bind_param($stmt, "ss", $db_name, $table_name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $row['cnt'] > 0;
    }
    return false;
}

// Safe query helper - returns false if table doesn't exist
function safeQuery($sql, $params = '', $values = []) {
    global $link;
    if ($stmt = mysqli_prepare($link, $sql)) {
        if (!empty($params) && !empty($values)) {
            mysqli_stmt_bind_param($stmt, $params, ...$values);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
    return false;
}

// Generate a CSRF token and store in session
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// Validate a CSRF token against the session
function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Echo a hidden input field with the CSRF token
function outputCSRFHiddenField() {
    $token = generateCSRFToken();
    echo '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

// Store a flash message in session for display on next page load
function showToast($message, $type = 'success') {
    if (!isset($_SESSION['toast_messages'])) {
        $_SESSION['toast_messages'] = [];
    }
    $_SESSION['toast_messages'][] = [
        'message' => $message,
        'type' => $type
    ];
}

// Read flash messages from session and output HTML for toast notifications
function displayToasts() {
    if (empty($_SESSION['toast_messages'])) {
        return;
    }

    $toastId = 0;
    echo '<div id="toast-container" style="position:fixed;top:20px;right:20px;z-index:9999;">';
    foreach ($_SESSION['toast_messages'] as $toast) {
        $type = htmlspecialchars($toast['type']);
        $message = htmlspecialchars($toast['message']);
        $bgColor = 'background-color:#28a745;color:#fff;';
        if ($type === 'error') {
            $bgColor = 'background-color:#dc3545;color:#fff;';
        } elseif ($type === 'warning') {
            $bgColor = 'background-color:#ffc107;color:#000;';
        } elseif ($type === 'info') {
            $bgColor = 'background-color:#17a2b8;color:#fff;';
        }
        echo '<div class="toast-alert" id="toast-' . $toastId . '" data-type="' . $type . '" style="' . $bgColor . ' padding:15px 25px;margin-bottom:10px;border-radius:5px;box-shadow:0 2px 8px rgba(0,0,0,0.2);opacity:1;transition:opacity 0.5s;font-family:sans-serif;">';
        echo '<span style="margin-right:10px;font-weight:bold;text-transform:uppercase;">' . $type . ':</span> ';
        echo $message;
        echo '<span onclick="this.parentElement.style.display=\'none\'" style="float:right;cursor:pointer;margin-left:15px;font-weight:bold;">&times;</span>';
        echo '</div>';
        $toastId++;
    }
    echo '</div>';
    echo '<script>
        setTimeout(function() {
            var toasts = document.querySelectorAll(".toast-alert");
            toasts.forEach(function(toast) {
                toast.style.opacity = "0";
                setTimeout(function() { toast.style.display = "none"; }, 500);
            });
        }, 5000);
    </script>';
    unset($_SESSION['toast_messages']);
}

// Enhanced version that tries to get the user's actual name from the residents table
function logActivityEnhanced($user_id, $action, $details = null) {
    logActivity($user_id, $action, $details);
}

// Record a login attempt (success or failure) into login_history
function recordLoginAttempt($username, $success, $user_id = null) {
    global $link;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $sql = "INSERT INTO login_history (user_id, username_attempt, ip_address, success) VALUES (?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($link, $sql)) {
        $uid = $user_id ?: null;
        $s = $success ? 1 : 0;
        mysqli_stmt_bind_param($stmt, "issi", $uid, $username, $ip, $s);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Increment failed attempts and lock out if threshold reached
function registerFailedLogin($username) {
    global $link;
    $sql = "SELECT id, failed_attempts FROM users WHERE username = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        if ($row) {
            $new_count = $row['failed_attempts'] + 1;
            if ($new_count >= MAX_LOGIN_ATTEMPTS) {
                $lock_until = date('Y-m-d H:i:s', time() + LOCKOUT_TIME);
                $up = "UPDATE users SET failed_attempts = ?, lockout_until = ? WHERE id = ?";
                if ($up_stmt = mysqli_prepare($link, $up)) {
                    mysqli_stmt_bind_param($up_stmt, "isi", $new_count, $lock_until, $row['id']);
                    mysqli_stmt_execute($up_stmt);
                    mysqli_stmt_close($up_stmt);
                }
            } else {
                $up = "UPDATE users SET failed_attempts = ? WHERE id = ?";
                if ($up_stmt = mysqli_prepare($link, $up)) {
                    mysqli_stmt_bind_param($up_stmt, "ii", $new_count, $row['id']);
                    mysqli_stmt_execute($up_stmt);
                    mysqli_stmt_close($up_stmt);
                }
            }
        }
    }
}

// Reset failed attempts on successful login
function resetFailedLogins($user_id) {
    global $link;
    $sql = "UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Get number of failed attempts for a username
function getFailedAttemptsCount($username) {
    global $link;
    $sql = "SELECT failed_attempts FROM users WHERE username = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        if ($row) {
            if (!empty($row['lockout_until']) && strtotime($row['lockout_until']) > time()) {
                return MAX_LOGIN_ATTEMPTS;
            }
            return $row['failed_attempts'];
        }
    }
    return 0;
}

// Check if account is currently locked
function isAccountLocked($username) {
    global $link;
    $sql = "SELECT lockout_until FROM users WHERE username = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        if ($row && !empty($row['lockout_until'])) {
            $lock_time = strtotime($row['lockout_until']);
            if ($lock_time > time()) {
                return true;
            }
            // Lockout expired — auto-clear
            resetFailedLogins(0);
            $up = "UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE username = ?";
            if ($up_stmt = mysqli_prepare($link, $up)) {
                mysqli_stmt_bind_param($up_stmt, "s", $username);
                mysqli_stmt_execute($up_stmt);
                mysqli_stmt_close($up_stmt);
            }
        }
    }
    return false;
}

// Get remaining lockout time in minutes (rounded up)
function getLockoutRemainingMinutes($username) {
    global $link;
    $sql = "SELECT lockout_until FROM users WHERE username = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        if ($row && !empty($row['lockout_until'])) {
            $remaining = strtotime($row['lockout_until']) - time();
            if ($remaining > 0) {
                return max(1, ceil($remaining / 60));
            }
        }
    }
    return 0;
}

// Auto-detect and create missing database tables/columns
function ensureDatabaseSchema() {
    global $link;

    // Add staff_role column to users if missing
    if (tableExists('users')) {
        $check = mysqli_query($link, "SHOW COLUMNS FROM users LIKE 'staff_role'");
        if (mysqli_num_rows($check) === 0) {
            mysqli_query($link, "ALTER TABLE users ADD COLUMN staff_role varchar(50) DEFAULT NULL AFTER full_name");
        }
    }

    // surveys table
    if (!tableExists('surveys')) {
        mysqli_query($link, "CREATE TABLE surveys (
            id int(11) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text DEFAULT NULL,
            created_by int(11) DEFAULT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            open_date date DEFAULT NULL,
            close_date date DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT current_timestamp(),
            updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (id),
            KEY created_by (created_by),
            CONSTRAINT surveys_ibfk_1 FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // survey_questions table
    if (!tableExists('survey_questions')) {
        mysqli_query($link, "CREATE TABLE survey_questions (
            id int(11) NOT NULL AUTO_INCREMENT,
            survey_id int(11) NOT NULL,
            question_text text NOT NULL,
            question_type enum('multiple_choice','yes_no','rating_scale','short_answer') NOT NULL,
            is_required tinyint(1) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (id),
            KEY survey_id (survey_id),
            CONSTRAINT survey_questions_ibfk_1 FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // survey_choices table
    if (!tableExists('survey_choices')) {
        mysqli_query($link, "CREATE TABLE survey_choices (
            id int(11) NOT NULL AUTO_INCREMENT,
            question_id int(11) NOT NULL,
            choice_text varchar(500) NOT NULL,
            sort_order int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY question_id (question_id),
            CONSTRAINT survey_choices_ibfk_1 FOREIGN KEY (question_id) REFERENCES survey_questions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // survey_responses table
    if (!tableExists('survey_responses')) {
        mysqli_query($link, "CREATE TABLE survey_responses (
            id int(11) NOT NULL AUTO_INCREMENT,
            survey_id int(11) NOT NULL,
            question_id int(11) NOT NULL,
            resident_id int(11) NOT NULL,
            response_text text DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (id),
            KEY survey_id (survey_id),
            KEY question_id (question_id),
            KEY resident_id (resident_id),
            CONSTRAINT survey_responses_ibfk_1 FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
            CONSTRAINT survey_responses_ibfk_2 FOREIGN KEY (question_id) REFERENCES survey_questions(id) ON DELETE CASCADE,
            CONSTRAINT survey_responses_ibfk_3 FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // survey_results table
    if (!tableExists('survey_results')) {
        mysqli_query($link, "CREATE TABLE survey_results (
            id int(11) NOT NULL AUTO_INCREMENT,
            survey_id int(11) NOT NULL,
            total_responses int(11) DEFAULT 0,
            avg_rating decimal(5,2) DEFAULT NULL,
            calculated_at timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (id),
            KEY survey_id (survey_id),
            CONSTRAINT survey_results_ibfk_1 FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // login_history table
    if (!tableExists('login_history')) {
        mysqli_query($link, "CREATE TABLE login_history (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) DEFAULT NULL,
            username_attempt varchar(50) NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            success tinyint(1) NOT NULL DEFAULT 0,
            attempted_at timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (id),
            KEY user_id (user_id),
            CONSTRAINT login_history_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

?>
