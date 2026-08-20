<?php
require_once "../includes/session.php";

header('Content-Type: application/json');

if (isLoggedIn()) {
    $redirect = isAdmin() ? '../staff/dashboard.php' : '../resident/dashboard.php';
    echo json_encode(['success' => true, 'redirect' => $redirect]);
    exit;
}

if (!defined('LOCKOUT_MINUTES')) {
    define('LOCKOUT_MINUTES', round(LOCKOUT_TIME / 60));
}

$response = [
    'success'    => false,
    'locked'     => false,
    'error'      => '',
    'field'      => '',   // 'username' | 'password' | ''
    'redirect'   => '',
    'lockout_minutes_remaining' => 0,
    'lockout_seconds_remaining' => 0,
    'remaining_attempts' => 0,
];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $response['error'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

$username = trim($_POST["username"] ?? '');
$password = trim($_POST["password"] ?? '');

if (empty($username)) {
    $response['error'] = 'Please enter username.';
    $response['field'] = 'username';
    echo json_encode($response);
    exit;
}

if (empty($password)) {
    $response['error'] = 'Please enter your password.';
    $response['field'] = 'password';
    echo json_encode($response);
    exit;
}

$login_history_exists = tableExists('login_history');
$is_locked_out = false;
$lockout_seconds_remaining = 0;

// ---- Lockout check ----
if ($login_history_exists) {
    $lockout_sql = "SELECT id, failed_attempts, lockout_until FROM users WHERE username = ?";
    if ($lockout_stmt = mysqli_prepare($link, $lockout_sql)) {
        mysqli_stmt_bind_param($lockout_stmt, "s", $username);
        if (mysqli_stmt_execute($lockout_stmt)) {
            mysqli_stmt_store_result($lockout_stmt);
            if (mysqli_stmt_num_rows($lockout_stmt) == 1) {
                mysqli_stmt_bind_result($lockout_stmt, $user_id_chk, $failed_attempts_chk, $lockout_until_chk);
                mysqli_stmt_fetch($lockout_stmt);
                if ($lockout_until_chk !== null && strtotime($lockout_until_chk) > time()) {
                    $is_locked_out = true;
                    $lockout_seconds_remaining = strtotime($lockout_until_chk) - time();
                }
            }
        }
        mysqli_stmt_close($lockout_stmt);
    }
}

if ($is_locked_out) {
    $mins = ceil($lockout_seconds_remaining / 60);
    $response['locked'] = true;
    $response['lockout_seconds_remaining'] = $lockout_seconds_remaining;
    $response['lockout_minutes_remaining'] = $mins;
    $response['error'] = 'Too many failed login attempts. Please try again in ' . $mins . ' minute' . ($mins !== 1 ? 's' : '') . '.';
    echo json_encode($response);
    mysqli_close($link);
    exit;
}

// ---- Authenticate ----
$sql = "SELECT id, username, password, role, resident_id, is_first_login FROM users WHERE username = ?";
if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "s", $username);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $id, $db_username, $hashed_password, $role, $resident_id, $is_first_login);
            if (mysqli_stmt_fetch($stmt)) {
                if (password_verify($password, $hashed_password)) {
                    // ---- Success ----
                    if ($login_history_exists) {
                        $success_log_sql = "INSERT INTO login_history (user_id, username_attempt, ip_address, success) VALUES (?, ?, ?, 1)";
                        if ($sl = mysqli_prepare($link, $success_log_sql)) {
                            mysqli_stmt_bind_param($sl, "iss", $id, $db_username, $_SERVER['REMOTE_ADDR']);
                            mysqli_stmt_execute($sl);
                            mysqli_stmt_close($sl);
                        }
                        $reset_sql = "UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE id = ?";
                        if ($rs = mysqli_prepare($link, $reset_sql)) {
                            mysqli_stmt_bind_param($rs, "i", $id);
                            mysqli_stmt_execute($rs);
                            mysqli_stmt_close($rs);
                        }
                    }

                    $_SESSION["loggedin"]      = true;
                    $_SESSION["id"]            = $id;
                    $_SESSION["username"]      = $db_username;
                    $_SESSION["role"]          = $role;
                    $_SESSION["resident_id"]   = $resident_id;
                    $_SESSION["is_first_login"] = $is_first_login;
                    $_SESSION["last_activity"] = time();

                    logActivity($id, 'User Login', 'User ' . $db_username . ' logged in.');

                    if ($role === 'admin') {
                        $response['redirect'] = '../staff/dashboard.php';
                    } else {
                        $response['redirect'] = $is_first_login
                            ? '../resident/change_password.php?first_login=true'
                            : '../resident/dashboard.php';
                    }
                    $response['success'] = true;

                } else {
                    // ---- Wrong password ----
                    $response['error'] = 'Invalid username or password.';

                    if ($login_history_exists) {
                        $fail_sql = "UPDATE users SET failed_attempts = failed_attempts + 1,
                            lockout_until = IF(failed_attempts + 1 >= " . MAX_LOGIN_ATTEMPTS . ",
                            DATE_ADD(NOW(), INTERVAL " . LOCKOUT_MINUTES . " MINUTE), lockout_until)
                            WHERE username = ?";
                        if ($fs = mysqli_prepare($link, $fail_sql)) {
                            mysqli_stmt_bind_param($fs, "s", $username);
                            mysqli_stmt_execute($fs);
                            mysqli_stmt_close($fs);
                        }

                        $log_sql = "INSERT INTO login_history (user_id, username_attempt, ip_address, success) VALUES ((SELECT id FROM users WHERE username = ?), ?, ?, 0)";
                        if ($ls = mysqli_prepare($link, $log_sql)) {
                            mysqli_stmt_bind_param($ls, "sss", $username, $username, $_SERVER['REMOTE_ADDR']);
                            mysqli_stmt_execute($ls);
                            mysqli_stmt_close($ls);
                        }

                        // Check if now locked out
                        $chk_sql = "SELECT failed_attempts, lockout_until FROM users WHERE username = ?";
                        if ($cs = mysqli_prepare($link, $chk_sql)) {
                            mysqli_stmt_bind_param($cs, "s", $username);
                            if (mysqli_stmt_execute($cs)) {
                                mysqli_stmt_store_result($cs);
                                if (mysqli_stmt_num_rows($cs) == 1) {
                                    mysqli_stmt_bind_result($cs, $new_attempts, $new_lockout_until);
                                    mysqli_stmt_fetch($cs);
                                    $remaining = MAX_LOGIN_ATTEMPTS - $new_attempts;
                                    $response['remaining_attempts'] = max(0, $remaining);
                                    if ($new_lockout_until !== null && strtotime($new_lockout_until) > time()) {
                                        $secs = strtotime($new_lockout_until) - time();
                                        $mins = ceil($secs / 60);
                                        $response['locked'] = true;
                                        $response['lockout_seconds_remaining'] = $secs;
                                        $response['lockout_minutes_remaining'] = $mins;
                                        $response['remaining_attempts'] = 0;
                                        $response['error'] = 'Too many failed login attempts. Please try again in ' . $mins . ' minute' . ($mins !== 1 ? 's' : '') . '.';
                                    }
                                }
                            }
                            mysqli_stmt_close($cs);
                        }
                    }

                    logActivity(0, 'Login Attempt Failed', 'Invalid password for username: ' . $username);
                }
            }
        } else {
            // ---- Username not found ----
            $response['error'] = 'Invalid username or password.';

            if ($login_history_exists) {
                $log_sql = "INSERT INTO login_history (user_id, username_attempt, ip_address, success) VALUES (NULL, ?, ?, 0)";
                if ($ls = mysqli_prepare($link, $log_sql)) {
                    mysqli_stmt_bind_param($ls, "ss", $username, $_SERVER['REMOTE_ADDR']);
                    mysqli_stmt_execute($ls);
                    mysqli_stmt_close($ls);
                }
            }
            logActivity(0, 'Login Attempt Failed', 'Username not found: ' . $username);
        }
    } else {
        $response['error'] = 'Something went wrong. Please try again later.';
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($link);
echo json_encode($response);
