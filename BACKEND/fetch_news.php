<?php
// ============================================
// FETCH NEWS — /BACKEND/fetch_news.php
// Public endpoint
// ============================================
define('TSF_LOADED', true);
require_once __DIR__ . '/connect.php';

header('Content-Type: application/json');

$pdo = getDB();

$category = sanitize($_GET['category'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = (int)($_GET['per_page'] ?? 10);
$perPage  = min(50, max(1, $perPage));
$offset   = ($page - 1) * $perPage;
$featured = isset($_GET['featured']) ? 1 : null;

$where  = ['is_published = 1'];
$params = [];

if ($category) {
    $where[] = 'category = ?';
    $params[] = $category;
}
if ($featured !== null) {
    $where[] = 'is_featured = ?';
    $params[] = $featured;
}

$whereSQL = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM news WHERE $whereSQL");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT id, title, slug, category, author_name, is_featured, published_at,
            cover_image,
            SUBSTRING(content, 1, 250) AS excerpt
     FROM news WHERE $whereSQL
     ORDER BY is_featured DESC, published_at DESC LIMIT ? OFFSET ?"
);
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$articles = $stmt->fetchAll();

echo json_encode([
    'success'  => true,
    'articles' => $articles,
    'total'    => $total,
    'page'     => $page,
    'per_page' => $perPage,
]);
