<?php
// ============================================
// TSF ADMIN MFA VERIFICATION
// /admin/verify_mfa.php
// ============================================
define('TSF_LOADED', true);
require_once __DIR__ . '/../BACKEND/connect.php';
require_once __DIR__ . '/../BACKEND/mfa_helpers.php';

startSecureSession();

if (!empty($_SESSION['admin_id']) && !empty($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

$pendingAdminId = (int)($_SESSION['pending_mfa_admin_id'] ?? 0);
if ($pendingAdminId <= 0) {
    header('Location: login.php');
    exit;
}

$currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
if (!empty($_SESSION['pending_mfa_ip']) && $_SESSION['pending_mfa_ip'] !== $currentIp) {
    unset($_SESSION['pending_mfa_admin_id'], $_SESSION['pending_mfa_admin_name'], $_SESSION['pending_mfa_ip'], $_SESSION['pending_mfa_notice']);
    header('Location: login.php?restart=1');
    exit;
}

$pdo = getDB();
$adminStmt = $pdo->prepare('SELECT id, username, full_name, email, phone FROM admin WHERE id = ? LIMIT 1');
$adminStmt->execute([$pendingAdminId]);
$admin = $adminStmt->fetch();
if (!$admin) {
    unset($_SESSION['pending_mfa_admin_id'], $_SESSION['pending_mfa_admin_name'], $_SESSION['pending_mfa_ip'], $_SESSION['pending_mfa_notice']);
    header('Location: login.php?restart=1');
    exit;
}

$message = $_SESSION['pending_mfa_notice'] ?? '';
$error = '';
$csrfToken = generateCsrfToken('admin_mfa');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '', 'admin_mfa')) {
        $error = 'Security validation failed. Please try again.';
    } elseif (($_POST['action'] ?? '') === 'resend') {
        $result = sendAdminMfaCode($admin, $currentIp);
        if ($result['ok']) {
            $message = $result['message'];
            $_SESSION['pending_mfa_notice'] = $message;
        } else {
            $error = $result['message'];
        }
    } else {
        $result = verifyAdminMfaCode($pendingAdminId, $_POST['otp_code'] ?? '');
        if ($result['ok']) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'] ?: $admin['username'];
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['ip'] = $currentIp;
            unset($_SESSION['pending_mfa_admin_id'], $_SESSION['pending_mfa_admin_name'], $_SESSION['pending_mfa_ip'], $_SESSION['pending_mfa_notice']);

            $pdo->prepare('UPDATE admin SET last_login=NOW() WHERE id=?')->execute([$admin['id']]);
            header('Location: dashboard.php');
            exit;
        }
        $error = $result['message'];
    }
    $csrfToken = generateCsrfToken('admin_mfa');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify Admin Login - TSF</title>
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
    .otp-input { text-align: center; font-size: 1.5rem; letter-spacing: 0.35rem; font-weight: 800; }
    .login-btn { width: 100%; padding: 0.9rem; background: linear-gradient(135deg, var(--blue-dark), var(--blue)); color: var(--white); border: none; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 1rem; font-weight: 700; cursor: pointer; transition: var(--transition); margin-top: 0.5rem; }
    .login-btn:hover { opacity: 0.9; transform: translateY(-1px); }
    .resend-btn { width: 100%; padding: 0.85rem; border: 1px solid rgba(0, 57, 166, 0.2); background: #f4f7ff; color: var(--blue-dark); border-radius: 10px; font-family: 'DM Sans', sans-serif; font-weight: 800; cursor: pointer; margin-top: 0.8rem; }
    .back-link { display: block; text-align: center; margin-top: 1.5rem; font-size: 0.87rem; color: var(--gray); text-decoration: none; }
    .back-link:hover { color: var(--blue); }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="login-logo">
      <img src="../images/tsf-logo.png" alt="TSF Logo">
      <h1>Verify Login</h1>
      <p>Enter the 6-digit code sent to your admin contact.</p>
    </div>
    <div class="login-divider"></div>

    <?php if ($message): ?>
      <div class="alert alert-success show"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error show"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="action" value="verify">
      <div class="form-group">
        <label for="otp_code">Verification Code</label>
        <input type="text" id="otp_code" name="otp_code" class="form-control otp-input" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required autofocus>
      </div>
      <button type="submit" class="login-btn">Verify & Open Dashboard</button>
    </form>

    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="action" value="resend">
      <button type="submit" class="resend-btn">Send a New Code</button>
    </form>

    <a href="login.php?restart=1" class="back-link">Use a different admin login</a>
  </div>
</body>
</html>
