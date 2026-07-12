<?php
// CLI-only admin password reset utility.
// Usage:
//   php tools/reset_admin_password.php --username=tsf_admin --generate
//   echo "NewStrongPassword123!" | php tools/reset_admin_password.php --username=tsf_admin --password-stdin

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This command can only be run from the command line.\n");
}

define('TSF_LOADED', true);
require_once __DIR__ . '/../BACKEND/connect.php';

function printUsage(): void {
    echo "TSF Admin Password Reset\n";
    echo "\n";
    echo "Usage:\n";
    echo "  php tools/reset_admin_password.php --username=tsf_admin --generate\n";
    echo "  echo \"NewStrongPassword123!\" | php tools/reset_admin_password.php --username=tsf_admin --password-stdin\n";
    echo "\n";
    echo "Options:\n";
    echo "  --username=USER      Admin username to update. Default: tsf_admin\n";
    echo "  --email=EMAIL        Admin recovery email to update after reset.\n";
    echo "  --generate           Generate a strong temporary password and print it once.\n";
    echo "  --password-stdin     Read the new password from STDIN.\n";
    echo "  --show-hash-only     Print a bcrypt hash instead of updating the database.\n";
    echo "  --help               Show this help text.\n";
}

function generatePassword(int $length = 20): string {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%*-_=+';
    $password = '';
    $max = strlen($alphabet) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $alphabet[random_int(0, $max)];
    }
    return $password;
}

function readPasswordFromStdin(): string {
    $input = stream_get_contents(STDIN);
    return trim((string)$input);
}

$options = getopt('', [
    'username::',
    'email::',
    'generate',
    'password-stdin',
    'show-hash-only',
    'help',
]);

if (isset($options['help'])) {
    printUsage();
    exit(0);
}

$username = trim((string)($options['username'] ?? 'tsf_admin'));
$email = isset($options['email']) ? trim((string)$options['email']) : '';
$shouldGenerate = isset($options['generate']);
$useStdin = isset($options['password-stdin']);
$showHashOnly = isset($options['show-hash-only']);

if ($username === '') {
    fwrite(STDERR, "Username cannot be empty.\n");
    exit(1);
}

if ($shouldGenerate === $useStdin) {
    fwrite(STDERR, "Choose exactly one password source: --generate or --password-stdin.\n\n");
    printUsage();
    exit(1);
}

$password = $shouldGenerate ? generatePassword() : readPasswordFromStdin();

if (strlen($password) < 12) {
    fwrite(STDERR, "Password must be at least 12 characters for CLI resets.\n");
    exit(1);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Recovery email is not valid.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
if ($hash === false) {
    fwrite(STDERR, "Unable to hash the password.\n");
    exit(1);
}

if ($showHashOnly) {
    echo "Password hash:\n";
    echo $hash . "\n";
    if ($shouldGenerate) {
        echo "\nGenerated password, shown once:\n";
        echo $password . "\n";
    }
    exit(0);
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, email FROM admin WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin) {
        fwrite(STDERR, "Admin user not found: $username\n");
        exit(1);
    }

    if ($email !== '') {
        $update = $pdo->prepare(
            'UPDATE admin
             SET password = ?, email = ?, login_attempts = 0, locked_until = NULL
             WHERE id = ?'
        );
        $update->execute([$hash, $email, $admin['id']]);
    } else {
        $update = $pdo->prepare(
            'UPDATE admin
             SET password = ?, login_attempts = 0, locked_until = NULL
             WHERE id = ?'
        );
        $update->execute([$hash, $admin['id']]);
    }

    $pdo->prepare('DELETE FROM password_resets WHERE admin_id = ?')->execute([$admin['id']]);

    echo "Admin password reset successfully.\n";
    echo "Username: $username\n";
    echo "Recovery email: " . ($email !== '' ? $email : $admin['email']) . "\n";
    echo "Plain password was not stored. The admin table only received a bcrypt hash.\n";

    if ($shouldGenerate) {
        echo "\nGenerated password, shown once:\n";
        echo $password . "\n";
        echo "\nSave it somewhere safe, then change it after signing in.\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Password reset failed: " . $e->getMessage() . "\n");
    exit(1);
}
