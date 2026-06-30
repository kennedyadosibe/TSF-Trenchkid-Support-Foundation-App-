<?php
// ============================================
// CSRF TOKEN ENDPOINT
// /BACKEND/csrf_token.php
// ============================================

define('TSF_LOADED', true);
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$form = sanitize($_GET['form'] ?? 'general');
if (!preg_match('/^[a-z_]{3,30}$/', $form)) {
    jsonResponse(false, 'Invalid form token request.');
}

cleanExpiredTokens();
jsonResponse(true, 'Token generated.', [
    'csrf_token' => generateCsrfToken($form),
    'expires_in' => CSRF_TOKEN_EXPIRY,
]);
