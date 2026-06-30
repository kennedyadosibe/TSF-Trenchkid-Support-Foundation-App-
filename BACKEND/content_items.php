<?php
define('TSF_LOADED', true);
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$allowedTypes = ['team_member', 'advisor', 'impact_stat', 'program', 'testimonial', 'region', 'faq'];
$types = $_GET['types'] ?? ($_GET['type'] ?? '');
$requested = array_values(array_filter(array_map('trim', explode(',', $types))));
$requested = array_values(array_intersect($requested, $allowedTypes));

if (!$requested) {
    $requested = $allowedTypes;
}

try {
    $placeholders = implode(',', array_fill(0, count($requested), '?'));
    $stmt = getDB()->prepare(
        "SELECT id, item_type, title, subtitle, body, meta_value, image_url, display_order
         FROM content_items
         WHERE is_active = 1 AND item_type IN ($placeholders)
         ORDER BY item_type ASC, display_order ASC, created_at ASC"
    );
    $stmt->execute($requested);
    $items = [];
    foreach ($stmt->fetchAll() as $row) {
        $items[$row['item_type']][] = $row;
    }
    jsonResponse(true, 'Content items loaded.', ['items' => $items]);
} catch (PDOException $e) {
    error_log('Content items load error: ' . $e->getMessage());
    jsonResponse(false, 'Unable to load page content.');
}
