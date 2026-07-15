<?php
// ============================================
// TSF ADMIN LOGIN
// /admin/login.php
// ============================================
define('TSF_LOADED', true);
require_once __DIR__ . '/../BACKEND/connect.php';
require_once __DIR__ . '/../BACKEND/mfa_helpers.php';

startSecureSession();

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

if (isset($_GET['restart'])) {
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_name'],
        $_SESSION['admin_logged_in'],
        $_SESSION['ip'],
        $_SESSION['pending_mfa_admin_id'],
        $_SESSION['pending_mfa_admin_name'],
        $_SESSION['pending_mfa_ip'],
        $_SESSION['pending_mfa_notice'],
        $_SESSION['csrf_tokens']
    );
}

// Already logged in
if (!empty($_SESSION['admin_id']) && !empty($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$csrfToken = generateCsrfToken('admin_login');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $token    = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($token, 'admin_login')) {
        $error = 'Security validation failed. Please try again.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        $pdo = getDB();

        // Check lockout
        $stmt = $pdo->prepare('SELECT * FROM admin WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && $admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
            $mins = ceil((strtotime($admin['locked_until']) - time()) / 60);
            $error = "Account locked. Try again in $mins minute(s).";
        } elseif ($admin && password_verify($password, $admin['password'])) {
            // Password accepted; MFA decides whether the full session opens now.
            $pdo->prepare('UPDATE admin SET login_attempts=0, locked_until=NULL WHERE id=?')
                ->execute([$admin['id']]);

            session_regenerate_id(true);
            if (!empty($admin['mfa_enabled'])) {
                $mfaNotice = sendAdminMfaCode($admin, $_SERVER['REMOTE_ADDR'] ?? '');
                if (!$mfaNotice['ok']) {
                    $error = $mfaNotice['message'];
                } else {
                    $_SESSION['pending_mfa_admin_id'] = (int)$admin['id'];
                    $_SESSION['pending_mfa_admin_name'] = $admin['full_name'] ?? $admin['username'];
                    $_SESSION['pending_mfa_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
                    $_SESSION['pending_mfa_notice'] = $mfaNotice['message'];

                    header('Location: verify_mfa.php');
                    exit;
                }
            } else {
                $_SESSION['admin_id']      = $admin['id'];
                $_SESSION['admin_name']    = $admin['full_name'] ?? $admin['username'];
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['ip']            = $_SERVER['REMOTE_ADDR'] ?? '';

                $pdo->prepare('UPDATE admin SET last_login=NOW() WHERE id=?')->execute([$admin['id']]);
                header('Location: dashboard.php');
                exit;
            }
        } else {
            // Failed attempt
            if ($admin) {
                $attempts = (int)$admin['login_attempts'] + 1;
                $lockedUntil = null;
                if ($attempts >= LOGIN_MAX_ATTEMPTS) {
                    $lockedUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_MINUTES * 60);
                    $error = "Too many failed attempts. Account locked for " . LOGIN_LOCKOUT_MINUTES . " minutes.";
                } else {
                    $remaining = LOGIN_MAX_ATTEMPTS - $attempts;
                    $error = "Invalid credentials. $remaining attempt(s) remaining.";
                }
                $pdo->prepare('UPDATE admin SET login_attempts=?, locked_until=? WHERE id=?')
                    ->execute([$attempts, $lockedUntil, $admin['id']]);
            } else {
                // Prevent username enumeration — same message
                $error = 'Invalid credentials. Please try again.';
            }
        }
    }
    // Regenerate CSRF token for next attempt
    $csrfToken = generateCsrfToken('admin_login');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login - TSF</title>
  <link rel="icon" href="../images/tsf-logo.png">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body { background: linear-gradient(135deg, var(--blue-dark) 0%, #0a1a5c 50%, var(--dark) 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
    .login-card { background: var(--white); border-radius: 24px; width: 100%; max-width: 440px; padding: 3rem 2.5rem; box-shadow: 0 30px 80px rgba(0,0,0,0.4); }
    .login-logo { text-align: center; margin-bottom: 2rem; }
    .login-logo img { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; background: var(--white); }
    .login-logo h1 { font-family: 'Playfair Display', serif; font-size: 1.5rem; color: var(--blue-dark); margin-top: 0.8rem; }
    .login-logo p { font-size: 0.85rem; color: var(--gray); margin-top: 0.3rem; }
    .login-divider { height: 2px; background: linear-gradient(to right, var(--blue), var(--gold)); border-radius: 2px; margin-bottom: 2rem; }
    .password-wrap { position: relative; }
    .toggle-pw { position: absolute; right: 0.9rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1rem; color: var(--gray); }
    .login-btn { width: 100%; padding: 0.9rem; background: linear-gradient(135deg, var(--blue-dark), var(--blue)); color: var(--white); border: none; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 1rem; font-weight: 700; cursor: pointer; transition: var(--transition); margin-top: 0.5rem; }
    .login-btn:hover { opacity: 0.9; transform: translateY(-1px); }
    .back-link { display: block; text-align: center; margin-top: 1.5rem; font-size: 0.87rem; color: var(--gray); text-decoration: none; }
    .back-link:hover { color: var(--blue); }
    .security-note { display: flex; align-items: center; gap: 0.5rem; font-size: 0.78rem; color: var(--gray); margin-top: 1.5rem; justify-content: center; }
    .login-lock { display: inline-flex; width: 1em; height: 1em; color: var(--blue); }
    .login-lock svg { width: 100%; height: 100%; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="login-logo">
      <img src="../images/tsf-logo.png" alt="TSF Logo">
      <h1>Admin Portal</h1>
      <p>Trenchkid Support Foundation</p>
    </div>
    <div class="login-divider"></div>

    <?php if ($error): ?>
      <div class="alert alert-error show"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" class="form-control" placeholder="Admin username" autocomplete="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <div class="password-wrap">
          <input type="password" id="password" name="password" class="form-control" placeholder="Password" autocomplete="current-password" required>
          <button type="button" class="toggle-pw" id="pw-eye" onclick="togglePw()">Show</button>
        </div>
      </div>
      <a href="forgot_password.php" style="display:block;text-align:right;margin:-0.4rem 0 1rem;color:var(--blue);font-size:0.83rem;font-weight:700;text-decoration:none">Forgot password?</a>
      <button type="submit" class="login-btn">Sign In to Dashboard</button>
    </form>

    <a href="../index.html" class="back-link">← Back to TSF Website</a>
    <div class="security-note"><span class="login-lock" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg></span> Secured &bull; Authorised personnel only</div>
  </div>
  <script>
    function togglePw() {
      const pw = document.getElementById('password');
      const eye = document.getElementById('pw-eye');
      pw.type = pw.type === 'password' ? 'text' : 'password';
      eye.textContent = pw.type === 'password' ? 'Show' : 'Hide';
    }
  </script>
</body>
</html>
