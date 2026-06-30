<?php
define('TSF_LOADED', true);
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/content_definitions.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

try {
    $stmt = getDB()->query('SELECT setting_key, setting_value FROM site_settings');
    $settings = getDefaultContentSettings();
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    jsonResponse(true, 'Settings loaded.', ['settings' => $settings]);
} catch (PDOException $e) {
    error_log('Site settings error: ' . $e->getMessage());
    jsonResponse(false, 'Unable to load site settings.');
}
