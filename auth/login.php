<?php
require_once "../includes/session.php";

if(isLoggedIn()){
    if(isAdmin()){
        header("location: ../staff/dashboard.php");
    } else {
        header("location: ../resident/dashboard.php");
    }
    exit;
}

if(!defined('LOCKOUT_MINUTES')){
    define('LOCKOUT_MINUTES', round(LOCKOUT_TIME / 60));
}

// Check for initial lockout state (for users arriving at the page already locked)
$is_locked_out = false;
$lockout_seconds_remaining = 0;
$lockout_minutes_remaining = 0;

// We only check lockout on GET with a username hint – skip for clean page load
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Resident Information System</title>
    <meta name="description" content="Login to the Resident Information System – Barangay Health Center">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* ── Login page overrides ── */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: linear-gradient(135deg, #EDF6F9 0%, #ffffff 100%);
        }

        .login-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 1rem;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 2.5rem 2.5rem 2rem;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(90,169,230,.15), 0 4px 16px rgba(0,0,0,.06);
            background: rgba(255,255,255,.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,.6);
        }

        .login-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .login-logo .logo-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #5AA9E6 0%, #48CAE4 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.6rem;
            box-shadow: 0 8px 20px rgba(90,169,230,.35);
        }

        .login-card .brand-name {
            color: var(--primary);
            text-align: center;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.15rem;
            letter-spacing: .02em;
        }
        .login-card .page-title {
            text-align: center;
            font-size: 1.7rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }
        .login-card .page-subtitle {
            text-align: center;
            color: var(--gray);
            font-size: 0.88rem;
            margin-bottom: 1.8rem;
        }

        /* ── Form group ── */
        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.88rem;
        }
        .form-control {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1.5px solid #D5E8F7;
            border-radius: 12px;
            font-family: var(--font-secondary);
            font-size: 0.95rem;
            background: rgba(255,255,255,.6);
            box-shadow: inset 3px 3px 8px rgba(0,0,0,.04), inset -3px -3px 8px rgba(255,255,255,.9);
            transition: border-color .25s, box-shadow .25s;
            color: var(--dark);
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: inset 3px 3px 8px rgba(0,0,0,.04), 0 0 0 3px rgba(90,169,230,.15);
        }
        .form-control.is-invalid {
            border-color: var(--danger);
            box-shadow: inset 3px 3px 8px rgba(0,0,0,.04), 0 0 0 3px rgba(231,76,60,.1);
        }
        .form-control:disabled {
            background: #f0f4f8;
            cursor: not-allowed;
            opacity: .65;
        }
        .invalid-feedback {
            display: block;
            color: var(--danger);
            font-size: 0.78rem;
            margin-top: 0.3rem;
        }

        /* ── Password toggle wrapper ── */
        .password-wrapper {
            position: relative;
        }
        .password-wrapper .form-control {
            padding-right: 3rem;
        }
        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray);
            font-size: 1rem;
            padding: 0.2rem;
            display: flex;
            align-items: center;
            transition: color .2s;
            z-index: 2;
        }
        .password-toggle:hover { color: var(--primary); }

        /* ── Alerts ── */
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 10px;
            margin-bottom: 1.2rem;
            border-left: 4px solid;
            font-size: 0.88rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .alert i { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
        .alert-danger  { background: rgba(231,76,60,.08);  color: #c0392b; border-left-color: #e74c3c; }
        .alert-warning { background: rgba(255,193,7,.1);   color: #d68910; border-left-color: #FFC107; }
        .alert-info    { background: rgba(90,169,230,.1);  color: #2471a3; border-left-color: #5AA9E6; }

        /* lockout countdown */
        #lockout-timer-display {
            font-size: 0.82rem;
            color: #c0392b;
            margin-top: 0.25rem;
            font-weight: 600;
        }
        .countdown-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: rgba(231,76,60,.1);
            color: #c0392b;
            border-radius: 20px;
            padding: 0.2rem 0.6rem;
            font-size: 0.8rem;
            font-weight: 700;
        }

        /* ── Submit button ── */
        .btn-login {
            width: 100%;
            padding: 0.8rem 1rem;
            background: linear-gradient(135deg, #5AA9E6 0%, #48CAE4 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-family: var(--font-primary);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all .3s;
            box-shadow: 0 6px 20px rgba(90,169,230,.35);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(90,169,230,.45);
        }
        .btn-login:disabled {
            opacity: .5;
            cursor: not-allowed;
            transform: none;
        }
        .btn-login .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,.4);
            border-top-color: white;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .btn-login.loading .spinner { display: block; }
        .btn-login.loading .btn-text { display: none; }

        /* ── Case ID badge ── */
        .case-id-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: rgba(90,169,230,.1);
            color: var(--primary);
            border: 1px solid rgba(90,169,230,.25);
            border-radius: 6px;
            padding: 0.18rem 0.55rem;
            font-size: 0.7rem;
            font-weight: 600;
            font-family: 'Courier New', monospace;
            letter-spacing: .04em;
            margin-bottom: 1.2rem;
            width: fit-content;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">

            <div class="login-logo">
                <div class="logo-icon"><i class="bi bi-hospital"></i></div>
            </div>

            <p class="brand-name">Resident Information System</p>
            <h1 class="page-title">Login</h1>
            <p class="page-subtitle">Please fill in your credentials to login.</p>

            <!-- Message area injected by AJAX or PHP -->
            <div id="login-messages">
                <?php
                if(isset($_GET["timeout"])){
                    echo '<div class="alert alert-warning"><i class="bi bi-clock-history"></i><span>Your session has expired. Please log in again.</span></div>';
                }
                if(isset($_GET["first_login"])){
                    echo '<div class="alert alert-info"><i class="bi bi-info-circle"></i><span>Please change your password to proceed.</span></div>';
                }
                ?>
            </div>

            <div id="lockout-timer-wrap" style="display:none; margin-bottom:1rem;">
                <div class="alert alert-danger" style="margin-bottom:0.4rem;">
                    <i class="bi bi-lock-fill"></i>
                    <span id="lockout-msg-text">Account temporarily locked.</span>
                </div>
                <div id="lockout-timer-display">
                    <span class="countdown-badge">
                        <i class="bi bi-hourglass-split"></i>
                        <span id="lockout-countdown">--:--</span> remaining
                    </span>
                </div>
            </div>

            <form id="loginForm" novalidate>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="ex: RES-2026-0001"
                        autocomplete="username"
                    >
                    <span class="invalid-feedback" id="username-error"></span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                        >
                        <button
                            type="button"
                            class="password-toggle"
                            id="togglePassword"
                            aria-label="Show/hide password"
                        >
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    <span class="invalid-feedback" id="password-error"></span>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <button type="submit" class="btn-login" id="loginBtn">
                        <span class="spinner"></span>
                        <span class="btn-text"><i class="bi bi-box-arrow-in-right"></i> Login</span>
                    </button>
                </div>
                <div style="text-align:center; margin-top:1.25rem;">
                    <a href="../index.php" style="color: var(--primary); font-size: 0.85rem; text-decoration: none; font-weight: 500;">
                        <i class="bi bi-arrow-left"></i> Back to Welcome Page
                    </a>
                </div>
            </form>

        </div>
    </div>

    <script>
    (function () {
        // ── Password toggle ──
        const toggleBtn  = document.getElementById('togglePassword');
        const toggleIcon = document.getElementById('toggleIcon');
        const pwInput    = document.getElementById('password');

        toggleBtn.addEventListener('click', function () {
            const isHidden = pwInput.type === 'password';
            pwInput.type = isHidden ? 'text' : 'password';
            toggleIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        });

        // ── Password strength — REMOVED as per user request ──

        // ── Lockout countdown ──
        let countdownInterval = null;

        function startCountdown(seconds) {
            const wrap = document.getElementById('lockout-timer-wrap');
            const disp = document.getElementById('lockout-countdown');
            wrap.style.display = 'block';
            disableForm(true);

            clearInterval(countdownInterval);
            let remaining = seconds;

            function tick() {
                if (remaining <= 0) {
                    clearInterval(countdownInterval);
                    wrap.style.display = 'none';
                    disableForm(false);
                    showMessage('info', '<i class="bi bi-unlock"></i>', 'You can now try logging in again.');
                    return;
                }
                const m = Math.floor(remaining / 60).toString().padStart(2, '0');
                const s = (remaining % 60).toString().padStart(2, '0');
                disp.textContent = m + ':' + s;
                remaining--;
            }
            tick();
            countdownInterval = setInterval(tick, 1000);
        }

        function disableForm(disabled) {
            document.getElementById('username').disabled = disabled;
            document.getElementById('password').disabled = disabled;
            document.getElementById('loginBtn').disabled = disabled;
            if (disabled) {
                document.getElementById('togglePassword').style.pointerEvents = 'none';
            } else {
                document.getElementById('togglePassword').style.pointerEvents = '';
            }
        }

        // ── Message helpers ──
        function showMessage(type, iconHtml, text) {
            const area = document.getElementById('login-messages');
            area.innerHTML = '<div class="alert alert-' + type + '">' + iconHtml + '<span>' + text + '</span></div>';
        }
        function clearMessages() {
            document.getElementById('login-messages').innerHTML = '';
        }
        function clearFieldErrors() {
            document.getElementById('username').classList.remove('is-invalid');
            document.getElementById('password').classList.remove('is-invalid');
            document.getElementById('username-error').textContent = '';
            document.getElementById('password-error').textContent = '';
        }

        // ── AJAX login ──
        document.getElementById('loginForm').addEventListener('submit', function (e) {
            e.preventDefault();
            clearMessages();
            clearFieldErrors();

            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.disabled = true;

            const formData = new FormData(this);

            fetch('login_ajax.php', {
                method: 'POST',
                body: formData,
            })
            .then(function (res) {
                if (!res.ok) throw new Error('Network error');
                return res.json();
            })
            .then(function (data) {
                btn.classList.remove('loading');
                btn.disabled = false;

                if (data.success) {
                    // Redirect
                    btn.classList.add('loading');
                    btn.disabled = true;
                    window.location.href = data.redirect;
                    return;
                }

                if (data.locked) {
                    document.getElementById('lockout-msg-text').textContent = data.error;
                    startCountdown(data.lockout_seconds_remaining);
                    return;
                }

                // Field-level errors
                if (data.field === 'username') {
                    document.getElementById('username').classList.add('is-invalid');
                    document.getElementById('username-error').textContent = data.error;
                } else if (data.field === 'password') {
                    document.getElementById('password').classList.add('is-invalid');
                    document.getElementById('password-error').textContent = data.error;
                } else {
                    var msgText = data.error;
                    if (data.remaining_attempts > 0) {
                        msgText += ' You have ' + data.remaining_attempts + ' attempt' + (data.remaining_attempts !== 1 ? 's' : '') + ' remaining before lockout.';
                    }
                    showMessage('warning', '<i class="bi bi-exclamation-triangle"></i>', msgText);
                    // Shake effect on card
                    const card = document.querySelector('.login-card');
                    card.style.animation = 'none';
                    card.offsetHeight; // reflow
                    card.style.animation = 'shakeCard 0.45s ease';
                }
            })
            .catch(function (err) {
                btn.classList.remove('loading');
                btn.disabled = false;
            });
        });
    })();
    </script>

    <style>
    @keyframes shakeCard {
        0%,100% { transform: translateX(0); }
        20%      { transform: translateX(-8px); }
        40%      { transform: translateX(8px); }
        60%      { transform: translateX(-5px); }
        80%      { transform: translateX(5px); }
    }
    </style>

</body>
</html>
