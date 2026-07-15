<?php
if (!defined('TSF_LOADED')) {
    define('TSF_LOADED', true);
}
require_once __DIR__ . '/connect.php';

function appLog(string $message): void {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    error_log('[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, 3, $logDir . '/app.log');
}

function smtpReadLine($socket): string {
    $data = '';
    while (($line = fgets($socket, 515)) !== false) {
        $data .= $line;
        if (strlen($line) < 4 || $line[3] !== '-') {
            break;
        }
    }
    return $data;
}

function smtpExpect($socket, array $codes, string $step): bool {
    $response = smtpReadLine($socket);
    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $codes, true)) {
        appLog('SMTP ' . $step . ' failed: ' . trim($response));
        return false;
    }
    return true;
}

function smtpCommand($socket, string $command, array $codes, string $step): bool {
    fwrite($socket, $command . "\r\n");
    return smtpExpect($socket, $codes, $step);
}

function sendSmtpEmail(string $to, string $subject, string $body, array $headers = []): bool {
    $smtpPass = preg_replace('/\s+/', '', SMTP_PASS);
    if (SMTP_HOST === '' || SMTP_USER === '' || $smtpPass === '') {
        return false;
    }

    $port = SMTP_PORT ?: 587;
    $socket = @stream_socket_client(SMTP_HOST . ':' . $port, $errno, $errstr, 15);
    if (!$socket) {
        appLog('SMTP connection failed: ' . $errstr . ' (' . $errno . ')');
        return false;
    }
    stream_set_timeout($socket, 15);

    $hostName = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $from = FROM_EMAIL ?: SMTP_USER;
    $headerLines = array_merge([
        'From: ' . FROM_NAME . ' <' . $from . '>',
        'Reply-To: ' . ADMIN_EMAIL,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ], $headers);
    $message = implode("\r\n", $headerLines)
        . "\r\nSubject: " . str_replace(["\r", "\n"], '', $subject)
        . "\r\nTo: " . $to
        . "\r\n\r\n" . str_replace(["\r\n", "\r"], "\n", $body);

    $ok = smtpExpect($socket, [220], 'greeting')
        && smtpCommand($socket, 'EHLO ' . $hostName, [250], 'ehlo');

    if ($ok && $port === 587) {
        $ok = smtpCommand($socket, 'STARTTLS', [220], 'starttls')
            && @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)
            && smtpCommand($socket, 'EHLO ' . $hostName, [250], 'ehlo after tls');
    }

    $ok = $ok
        && smtpCommand($socket, 'AUTH LOGIN', [334], 'auth login')
        && smtpCommand($socket, base64_encode(SMTP_USER), [334], 'auth username')
        && smtpCommand($socket, base64_encode($smtpPass), [235], 'auth password')
        && smtpCommand($socket, 'MAIL FROM:<' . $from . '>', [250], 'mail from')
        && smtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251], 'rcpt to')
        && smtpCommand($socket, 'DATA', [354], 'data');

    if ($ok) {
        fwrite($socket, $message . "\r\n.\r\n");
        $ok = smtpExpect($socket, [250], 'message body');
    }

    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    return $ok;
}

function sendAppEmail(string $to, string $subject, string $body, array $headers = []): bool {
    if (sendSmtpEmail($to, $subject, $body, $headers)) {
        return true;
    }

    if (isLocalRequest() && SMTP_PASS === '') {
        appLog('Local email delivery skipped for recipient ' . $to . '. Configure SMTP_* environment variables to send real email.');
        return false;
    }

    $headerLines = array_merge([
        'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
        'Reply-To: ' . ADMIN_EMAIL,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ], $headers);
    $sent = @mail($to, $subject, $body, implode("\r\n", $headerLines));
    if (!$sent) {
        appLog('Email delivery failed for recipient ' . $to . ' and subject "' . $subject . '". Configure SMTP_* environment variables.');
    }
    return $sent;
}

function appSmsRequest(string $phone, string $message): array {
    if (!defined('SMS_API_URL') || SMS_API_URL === '' || !defined('SMS_API_KEY') || SMS_API_KEY === '') {
        return ['status' => 'skipped', 'message' => 'SMS provider is not configured.'];
    }

    $payload = json_encode([
        'to' => $phone,
        'from' => defined('SMS_SENDER_ID') && SMS_SENDER_ID !== '' ? SMS_SENDER_ID : 'TSF',
        'message' => $message,
    ]);

    $ch = curl_init(SMS_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . SMS_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
        return ['status' => 'sent', 'message' => $response];
    }

    return [
        'status' => 'failed',
        'message' => $error ?: ($response ?: 'SMS provider returned HTTP ' . $httpCode),
    ];
}

function isLocalRequest(): bool {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $addr = $_SERVER['REMOTE_ADDR'] ?? '';
    return strpos($host, '127.0.0.1') !== false
        || strpos($host, 'localhost') !== false
        || $addr === '127.0.0.1'
        || $addr === '::1';
}

function logLocalPasswordResetLink(string $email, string $resetUrl): void {
    if (!isLocalRequest()) {
        return;
    }
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $email . ' -> ' . $resetUrl . PHP_EOL;
    file_put_contents($logDir . '/password-reset-links.log', $line, FILE_APPEND | LOCK_EX);
}
