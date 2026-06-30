<?php
// ============================================
// CONTACT MESSAGE HANDLER
// /BACKEND/contact.php
// ============================================

define('TSF_LOADED', true);
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.');
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
if (!validateCsrfToken($input['csrf_token'] ?? '', 'contact')) {
    jsonResponse(false, 'Security validation failed. Please refresh the page and try again.');
}

$name = sanitize($input['name'] ?? '');
$rawEmail = trim($input['email'] ?? '');
$email = $rawEmail === '' ? '' : filter_var($rawEmail, FILTER_VALIDATE_EMAIL);
$phone = sanitize($input['phone'] ?? '');
$subject = sanitize($input['subject'] ?? '');
$type = sanitize($input['type'] ?? 'general');
$message = trim($input['message'] ?? '');

$allowedTypes = ['general', 'volunteer', 'partner', 'donate', 'comment'];
if (!in_array($type, $allowedTypes, true)) $type = 'general';

$errors = [];
if ($name === '') $errors[] = 'Name is required.';
if ($type !== 'comment' && !$email) $errors[] = 'A valid email address is required.';
if ($rawEmail !== '' && !$email) $errors[] = 'A valid email address is required.';
if (strlen($message) < 10) $errors[] = 'Message must be at least 10 characters.';
if ($phone !== '' && !preg_match('/^(0[0-9]{9}|\+233[0-9]{9})$/', preg_replace('/\s+/', '', $phone))) {
    $errors[] = 'Enter a valid Ghana phone number.';
}

if ($errors) {
    jsonResponse(false, implode(' ', $errors), ['errors' => $errors]);
}

try {
    $stmt = getDB()->prepare(
        'INSERT INTO messages (name, email, phone, subject, message_type, message)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$name, $email ?: null, $phone ?: null, $subject ?: null, $type, $message]);
    jsonResponse(true, 'Message sent successfully.');
} catch (PDOException $e) {
    error_log('Contact message error: ' . $e->getMessage());
    jsonResponse(false, 'Unable to send message at this time. Please try again.');
}
