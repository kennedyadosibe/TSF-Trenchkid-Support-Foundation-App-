<?php
// ============================================
// TSF DATABASE CONNECTION
// /BACKEND/connect.php
// ============================================

if (!defined('TSF_LOADED')) {
    define('TSF_LOADED', true);
}
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'A server error occurred. Please try again.']));
    }
    return $pdo;
}

function appBasePath(): string {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = '';
    foreach (['/admin/', '/BACKEND/'] as $marker) {
        $pos = strpos($scriptName, $marker);
        if ($pos !== false) {
            $base = substr($scriptName, 0, $pos);
            break;
        }
    }
    if ($base === '') {
        $base = rtrim(dirname($scriptName), '/');
    }
    return $base === '/' ? '' : $base;
}

function appPath(string $path): string {
    return appBasePath() . '/' . ltrim($path, '/');
}

function appUrl(string $path): string {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
    }
    $scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443'))
        ? 'https'
        : 'http';
    return $scheme . '://' . $host . appPath($path);
}

// ---- SESSION HELPERS ----

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

function requireAdminAuth(): void {
    startSecureSession();
    if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_logged_in'])) {
        header('Location: ' . appPath('admin/login.php'));
        exit;
    }
    if (empty($_SESSION['ip']) || $_SESSION['ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
        session_destroy();
        header('Location: ' . appPath('admin/login.php'));
        exit;
    }
}

// ---- CSRF HELPERS ----

function generateCsrfToken(string $formType = 'general'): string {
    startSecureSession();
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_tokens'][$formType][$token] = time() + CSRF_TOKEN_EXPIRY;
    return $token;
}

function validateCsrfToken(string $token, string $formType = 'general'): bool {
    if ($token === '') return false;
    startSecureSession();
    $expiresAt = $_SESSION['csrf_tokens'][$formType][$token] ?? 0;
    if ($expiresAt > time()) {
        unset($_SESSION['csrf_tokens'][$formType][$token]);
        return true;
    }
    return false;
}

function cleanExpiredTokens(): void {
    startSecureSession();
    foreach (($_SESSION['csrf_tokens'] ?? []) as $formType => $tokens) {
        foreach ($tokens as $token => $expiresAt) {
            if ($expiresAt <= time()) {
                unset($_SESSION['csrf_tokens'][$formType][$token]);
            }
        }
    }
}

// ---- RESPONSE HELPERS ----

function jsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
