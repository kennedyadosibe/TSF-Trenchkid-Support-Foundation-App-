<?php
if (!defined('TSF_LOADED')) {
    define('TSF_LOADED', true);
}
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/email_helpers.php';

const ADMIN_MFA_EXPIRY_MINUTES = 10;
const ADMIN_MFA_MAX_ATTEMPTS = 5;

function generateAdminMfaCode(): string {
    return (string)random_int(100000, 999999);
}

function adminMfaHash(string $code): string {
    return hash('sha256', preg_replace('/\D+/', '', $code));
}

function maskAdminEmail(string $email): string {
    [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
    if ($domain === '') {
        return 'configured email';
    }
    return substr($name, 0, 2) . str_repeat('*', max(2, strlen($name) - 2)) . '@' . $domain;
}

function maskAdminPhone(?string $phone): string {
    $digits = preg_replace('/\D+/', '', (string)$phone);
    if (strlen($digits) < 4) {
        return 'configured phone';
    }
    return str_repeat('*', max(3, strlen($digits) - 4)) . substr($digits, -4);
}

function normalizeAdminPhone(?string $phone): string {
    $phone = trim((string)$phone);
    if ($phone === '') {
        return '';
    }
    $phone = preg_replace('/[^\d+]/', '', $phone);
    if (preg_match('/^0\d{9}$/', $phone)) {
        return '+233' . substr($phone, 1);
    }
    return $phone;
}

function logLocalAdminMfaCode(string $email, string $code): bool {
    if (!isLocalRequest()) {
        return false;
    }
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $email . ' -> ' . $code . PHP_EOL;
    file_put_contents($logDir . '/admin-mfa-codes.log', $line, FILE_APPEND | LOCK_EX);
    return true;
}

function sendAdminMfaCode(array $admin, string $ipAddress = ''): array {
    $email = trim((string)($admin['email'] ?? ''));
    $phone = normalizeAdminPhone($admin['phone'] ?? '');

    if ($email === '' && $phone === '') {
        return ['ok' => false, 'message' => 'No admin email or phone is configured for MFA.'];
    }

    $pdo = getDB();
    $adminId = (int)$admin['id'];
    $code = generateAdminMfaCode();
    $expiresAt = date('Y-m-d H:i:s', time() + ADMIN_MFA_EXPIRY_MINUTES * 60);
    $targets = [];
    $method = $email !== '' ? 'email' : 'sms';
    $deliveryTarget = $email !== '' ? $email : $phone;

    $pdo->prepare('UPDATE admin_mfa_tokens SET used_at = NOW() WHERE admin_id = ? AND used_at IS NULL')
        ->execute([$adminId]);

    $stmt = $pdo->prepare(
        'INSERT INTO admin_mfa_tokens (admin_id, token_hash, delivery_target, delivery_method, expires_at)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$adminId, adminMfaHash($code), $deliveryTarget, $method, $expiresAt]);

    if ($email !== '') {
        $subject = 'TSF admin login verification code';
        $body = "Your TSF admin verification code is: $code\n\n"
            . "It expires in " . ADMIN_MFA_EXPIRY_MINUTES . " minutes. "
            . "If you did not try to sign in, change your admin password immediately.\n\n"
            . "Request IP: " . ($ipAddress !== '' ? $ipAddress : 'unknown');
        if (sendAppEmail($email, $subject, $body)) {
            $targets[] = maskAdminEmail($email);
        } elseif (logLocalAdminMfaCode($email, $code)) {
            $targets[] = 'local MFA log';
        } else {
            appLog('Admin MFA email delivery failed for admin id ' . $adminId);
        }
    }

    if ($phone !== '') {
        $sms = appSmsRequest($phone, 'Your TSF admin verification code is ' . $code . '. It expires in ' . ADMIN_MFA_EXPIRY_MINUTES . ' minutes.');
        if ($sms['status'] === 'sent') {
            $targets[] = maskAdminPhone($phone);
        } elseif ($sms['status'] === 'failed') {
            appLog('Admin MFA SMS delivery failed for admin id ' . $adminId . ': ' . $sms['message']);
        }
    }

    if (!$targets) {
        return ['ok' => false, 'message' => 'The verification code could not be delivered. Check SMTP or SMS settings.'];
    }

    return [
        'ok' => true,
        'message' => 'Verification code sent to ' . implode(' and ', $targets) . '.',
        'expires_at' => $expiresAt,
    ];
}

function verifyAdminMfaCode(int $adminId, string $code): array {
    $code = preg_replace('/\D+/', '', $code);
    if (!preg_match('/^\d{6}$/', $code)) {
        return ['ok' => false, 'message' => 'Enter the 6-digit verification code.'];
    }

    $pdo = getDB();
    $stmt = $pdo->prepare(
        'SELECT id, token_hash, attempts
         FROM admin_mfa_tokens
         WHERE admin_id = ? AND used_at IS NULL AND expires_at > NOW()
         ORDER BY id DESC
         LIMIT 1'
    );
    $stmt->execute([$adminId]);
    $token = $stmt->fetch();

    if (!$token) {
        return ['ok' => false, 'message' => 'Verification code expired. Please request a new code.'];
    }

    if ((int)$token['attempts'] >= ADMIN_MFA_MAX_ATTEMPTS) {
        $pdo->prepare('UPDATE admin_mfa_tokens SET used_at = NOW() WHERE id = ?')->execute([$token['id']]);
        return ['ok' => false, 'message' => 'Too many attempts. Please request a new code.'];
    }

    if (!hash_equals($token['token_hash'], adminMfaHash($code))) {
        $pdo->prepare('UPDATE admin_mfa_tokens SET attempts = attempts + 1 WHERE id = ?')->execute([$token['id']]);
        return ['ok' => false, 'message' => 'Invalid verification code. Please try again.'];
    }

    $pdo->prepare('UPDATE admin_mfa_tokens SET used_at = NOW() WHERE id = ?')->execute([$token['id']]);
    $pdo->prepare('UPDATE admin_mfa_tokens SET used_at = NOW() WHERE admin_id = ? AND used_at IS NULL')->execute([$adminId]);

    return ['ok' => true, 'message' => 'Verification complete.'];
}
