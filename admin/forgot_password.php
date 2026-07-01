<?php
define('TSF_LOADED', true);
require_once __DIR__ . '/../BACKEND/connect.php';

startSecureSession();

$message = '';
$error = '';
$csrfToken = generateCsrfToken('forgot_password');

function buildResetUrl(string $token): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8080';
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin'), '/\\');
    return $scheme . '://' . $host . $base . '/reset_password.php?token=' . urlencode($token);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '', 'forgot_password')) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $error = 'Enter a valid recovery email.';
        } else {
            $pdo = getDB();
            $stmt = $pdo->prepare('SELECT id, full_name, email FROM admin WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin) {
                $pdo->prepare('DELETE FROM password_resets WHERE expires_at < NOW() OR used_at IS NOT NULL')
                    ->execute();

                $recentStmt = $pdo->prepare(
                    'SELECT id FROM password_resets
                     WHERE admin_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                     LIMIT 1'
                );
                $recentStmt->execute([$admin['id']]);

                if (!$recentStmt->fetch()) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    $expiresAt = date('Y-m-d H:i:s', time() + 1800);

                    $pdo->prepare('DELETE FROM password_resets WHERE admin_id = ?')
                        ->execute([$admin['id']]);
                    $pdo->prepare('INSERT INTO password_resets (admin_id, token_hash, expires_at) VALUES (?, ?, ?)')
                        ->execute([$admin['id'], $tokenHash, $expiresAt]);

                    $resetUrl = buildResetUrl($token);
                    $subject = 'TSF admin password reset';
                    $body = "Hello " . ($admin['full_name'] ?: 'Admin') . ",\n\n"
                        . "Use this link to reset your TSF admin password. It expires in 30 minutes:\n"
                        . $resetUrl . "\n\nIf you did not request this, ignore this email. Your password will not change unless this link is opened and a new password is saved.";
                    $headers = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n"
                        . "Reply-To: " . ADMIN_EMAIL . "\r\n"
                        . "X-Mailer: PHP/" . phpversion();
                    @mail($admin['email'], $subject, $body, $headers);
                }
            }

            $message = 'If that email matches the admin account, a reset link has been sent.';
        }
    }
    $csrfToken = generateCsrfToken('forgot_password');
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
  <title>Reset Password - TSF Admin</title>
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
    <h1>Password Recovery</h1>
    <p>Enter the admin recovery email. A secure reset link will be sent if the address is registered.</p>
    <?php if ($message): ?><div class="alert alert-success show"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error show"><?= e($error) ?></div><?php endif; ?>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
      <div class="form-group">
        <label for="email">Recovery Email</label>
        <input id="email" class="form-control" type="email" name="email" autocomplete="email" required>
      </div>
      <button class="btn btn-blue btn-lg" type="submit" style="width:100%">Send Reset Link</button>
    </form>
    <a class="back-link" href="login.php">Back to login</a>
  </div>
</body>
</html>
