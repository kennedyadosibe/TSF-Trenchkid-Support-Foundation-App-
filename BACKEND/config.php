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

// ---- DATABASE ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'tsf');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---- SITE ----
define('SITE_URL', 'https://www.tsfghana.org'); // Replace with actual URL
define('SITE_NAME', 'Trenchkid Support Foundation');
define('ADMIN_EMAIL', 'admin@tsfghana.org');

// ---- SECURITY ----
define('CSRF_TOKEN_EXPIRY', 3600);       // 1 hour
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 5);
define('SESSION_LIFETIME', 3600);        // 1 hour

// ---- PAYMENT GATEWAY ----
// Paystack handles card and Ghana mobile money checkout.
define('PAYSTACK_SECRET_KEY', 'sk_test_d68cbc225843eda55191d6d8f6b9230ced01f15f');
define('PAYSTACK_PUBLIC_KEY', 'pk_test_xxxxxxxxxxxxxxxxxxxx');
define('PAYSTACK_CALLBACK_URL', SITE_URL . '/donate.html');

// ---- EMAIL ----
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@tsfghana.org');
define('SMTP_PASS', 'your_smtp_password');
define('FROM_EMAIL', 'noreply@tsfghana.org');
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
