<?php
define('TSF_LOADED', true);
require_once __DIR__ . '/../BACKEND/connect.php';

startSecureSession();

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$message = '';
$error = '';
$resetRow = null;
$csrfToken = generateCsrfToken('reset_password');

if ($token !== '') {
    $pdo = getDB();
    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare(
        'SELECT pr.id, pr.admin_id, a.email
         FROM password_resets pr
         INNER JOIN admin a ON a.id = pr.admin_id
         WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute([$tokenHash]);
    $resetRow = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$resetRow) {
        $error = 'This reset link is invalid or expired.';
    } elseif (!validateCsrfToken($_POST['csrf_token'] ?? '', 'reset_password')) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE admin SET password = ?, login_attempts = 0, locked_until = NULL WHERE id = ?')
                ->execute([$hash, $resetRow['admin_id']]);
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')
                ->execute([$resetRow['id']]);
            try {
                $pdo->prepare('DELETE FROM admin_mfa_tokens WHERE admin_id = ?')
                    ->execute([$resetRow['admin_id']]);
            } catch (Throwable $ignored) {
                // Older databases may not have MFA enabled yet.
            }
            $message = 'Password updated. You can sign in now.';
            $resetRow = null;
        }
    }
    $csrfToken = generateCsrfToken('reset_password');
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set New Password - TSF Admin</title>
  <link rel="icon" href="../images/tsf-logo.png">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body { background: linear-gradient(135deg, var(--blue-dark), var(--dark)); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
    .reset-card { background: var(--white); border-radius: 18px; width: 100%; max-width: 460px; padding: 2.4rem; box-shadow: 0 24px 70px rgba(0,0,0,0.34); }
    .reset-card img { width: 64px; height: 64px; border-radius: 50%; object-fit: cover; background: var(--white); display: block; margin: 0 auto 1rem; }
    .reset-card h1 { font-family: 'Playfair Display', serif; color: var(--blue-dark); font-size: 1.5rem; text-align: center; margin-bottom: 0.5rem; }
    .reset-card p { color: var(--gray); text-align: center; line-height: 1.6; font-size: 0.9rem; margin-bottom: 1.4rem; }
    .back-link { display: block; text-align: center; margin-top: 1.2rem; color: var(--gray); text-decoration: none; }
  </style>
</head>
<body>
  <div class="reset-card">
    <img src="../images/tsf-logo.png" alt="TSF Logo">
    <h1>Set New Password</h1>
    <p>Create a new password for the TSF admin dashboard.</p>
    <?php if ($message): ?><div class="alert alert-success show"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error show"><?= e($error) ?></div><?php endif; ?>
    <?php if ($resetRow): ?>
      <form method="POST">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
        <div class="form-group">
          <label for="password">New Password</label>
          <input id="password" class="form-control" type="password" name="password" autocomplete="new-password" required minlength="8">
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm Password</label>
          <input id="confirm_password" class="form-control" type="password" name="confirm_password" autocomplete="new-password" required minlength="8">
        </div>
        <button class="btn btn-blue btn-lg" type="submit" style="width:100%">Update Password</button>
      </form>
    <?php elseif (!$message): ?>
      <div class="alert alert-error show">This reset link is invalid or expired.</div>
    <?php endif; ?>
    <a class="back-link" href="login.php">Back to login</a>
  </div>
</body>
</html>
