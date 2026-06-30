<?php
define('TSF_LOADED', true);
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

try {
    $stmt = getDB()->query(
        'SELECT id, title, caption, image_url, category
         FROM gallery
         WHERE is_active = 1
         ORDER BY display_order ASC, created_at DESC'
    );
    jsonResponse(true, 'Gallery loaded.', ['items' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    error_log('Gallery load error: ' . $e->getMessage());
    jsonResponse(false, 'Unable to load gallery.');
}
