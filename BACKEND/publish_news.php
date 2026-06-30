<?php
// ============================================
// PUBLISH NEWS — /BACKEND/publish_news.php
// ============================================
define('TSF_LOADED', true);
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');
requireAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.');
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// CSRF
if (!validateCsrfToken($input['csrf_token'] ?? '', 'publish')) {
    jsonResponse(false, 'Security validation failed.');
}

$title    = sanitize($input['title'] ?? '');
$content  = trim($input['content'] ?? '');
$category = sanitize($input['category'] ?? 'General');
$author   = sanitize($input['author'] ?? 'TSF Admin');
$featured = !empty($input['is_featured']) ? 1 : 0;
$publish  = !empty($input['publish']) ? 1 : 0;

$errors = [];
if (strlen($title) < 5)   $errors[] = 'Title must be at least 5 characters.';
if (strlen($content) < 30) $errors[] = 'Content must be at least 30 characters.';

$allowedCategories = ['Education','Digital Skills','Health','Community','Partnership','Announcement','General'];
if (!in_array($category, $allowedCategories)) $category = 'General';

if ($errors) jsonResponse(false, implode(' ', $errors), ['errors' => $errors]);

// Generate slug
function makeSlug(string $str): string {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    return trim($str, '-') . '-' . substr(uniqid(), -6);
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        'INSERT INTO news (title, slug, content, category, author_name, author_id, is_published, is_featured, published_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $title,
        makeSlug($title),
        $content,
        $category,
        $author,
        $_SESSION['admin_id'],
        $publish,
        $featured,
        $publish ? date('Y-m-d H:i:s') : null,
    ]);
    jsonResponse(true, 'Article ' . ($publish ? 'published' : 'saved as draft') . ' successfully.', [
        'article_id' => (int)$pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    error_log('Publish news error: ' . $e->getMessage());
    jsonResponse(false, 'Failed to save article. Please try again.');
}
