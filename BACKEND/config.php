<?php
// ============================================
// TSF CONFIGURATION FILE
// /BACKEND/config.php
// ============================================

// Prevent direct access
if (!defined('TSF_LOADED')) {
    http_response_code(403);
    die('Direct access not permitted.');
}

$localConfig = __DIR__ . '/config.local.php';
$local = is_file($localConfig) ? require $localConfig : [];
if (!is_array($local)) {
    $local = [];
}

function tsfConfigValue(string $key, $fallback = '') {
    global $local;
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    return $local[$key] ?? $fallback;
}

// ---- DATABASE ----
define('DB_HOST', tsfConfigValue('DB_HOST', 'localhost'));
define('DB_NAME', tsfConfigValue('DB_NAME', 'tsf'));
define('DB_USER', tsfConfigValue('DB_USER', 'root'));
define('DB_PASS', tsfConfigValue('DB_PASS', ''));
define('DB_CHARSET', tsfConfigValue('DB_CHARSET', 'utf8mb4'));

// ---- SITE ----
define('SITE_URL', tsfConfigValue('SITE_URL', 'https://www.tsfghana.org'));
define('SITE_NAME', tsfConfigValue('SITE_NAME', 'Trenchkid Support Foundation'));
define('ADMIN_EMAIL', tsfConfigValue('ADMIN_EMAIL', 'admin@tsfghana.org'));

// ---- SECURITY ----
define('CSRF_TOKEN_EXPIRY', 3600);       // 1 hour
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 5);
define('SESSION_LIFETIME', 3600);        // 1 hour

// ---- PAYMENT GATEWAY ----
// Paystack handles card and Ghana mobile money checkout.
define('PAYSTACK_SECRET_KEY', tsfConfigValue('PAYSTACK_SECRET_KEY', ''));
define('PAYSTACK_PUBLIC_KEY', tsfConfigValue('PAYSTACK_PUBLIC_KEY', ''));
define('PAYSTACK_CALLBACK_URL', tsfConfigValue('PAYSTACK_CALLBACK_URL', SITE_URL . '/donate.html'));

// ---- SMS ----
// Add your SMS provider endpoint and API key here to send donor thank-you texts.
// When these are empty, TSF stores the SMS message in the database for follow-up.
define('SMS_API_URL', tsfConfigValue('SMS_API_URL', ''));
define('SMS_API_KEY', tsfConfigValue('SMS_API_KEY', ''));
define('SMS_SENDER_ID', tsfConfigValue('SMS_SENDER_ID', 'TSF'));

// ---- EMAIL ----
define('SMTP_HOST', tsfConfigValue('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int)tsfConfigValue('SMTP_PORT', 587));
define('SMTP_USER', tsfConfigValue('SMTP_USER', 'noreply@tsfghana.org'));
define('SMTP_PASS', tsfConfigValue('SMTP_PASS', ''));
define('FROM_EMAIL', tsfConfigValue('FROM_EMAIL', 'noreply@tsfghana.org'));
define('FROM_NAME', SITE_NAME);

// ---- ERROR REPORTING (disable in production) ----
define('DEBUG_MODE', false);
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}
