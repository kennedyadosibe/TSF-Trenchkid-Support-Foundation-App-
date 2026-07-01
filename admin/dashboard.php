<?php
define('TSF_LOADED', true);
require_once __DIR__ . '/../BACKEND/connect.php';
require_once __DIR__ . '/../BACKEND/content_definitions.php';
requireAdminAuth();

$pdo = getDB();
$contentDefinitions = getContentDefinitions();
$activePanel = 'overview';
$publishMessage = '';
$publishError = '';
$settingsMessage = '';
$settingsError = '';
$galleryMessage = '';
$galleryError = '';
$contentMessage = '';
$contentError = '';
$accountMessage = '';
$accountError = '';
$publishedArticleUrl = '';
$contentTypes = [
    'team_member' => 'Team Member',
    'advisor' => 'Advisor',
    'impact_stat' => 'Impact Stat',
    'program' => 'Program',
    'testimonial' => 'Testimonial',
    'region' => 'Region',
    'faq' => 'FAQ',
];

function cleanText($value): string {
    return trim(strip_tags((string)$value));
}

function uploadManagedImage(array $uploaded, string $folder, string $title, string &$error): string {
    if (($uploaded['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($uploaded['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (($uploaded['size'] ?? 0) > 4 * 1024 * 1024)) {
        $error = 'Upload a valid image up to 4MB.';
        return '';
    }
    $tmpPath = $uploaded['tmp_name'] ?? '';
    $imageInfo = $tmpPath ? @getimagesize($tmpPath) : false;
    $allowedTypes = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    if (!$imageInfo || !isset($allowedTypes[$imageInfo[2]])) {
        $error = 'Upload a valid JPG, PNG, WebP, or GIF image.';
        return '';
    }
    $uploadDir = dirname(__DIR__) . '/images/' . $folder;
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $slugBase = trim(strtolower(preg_replace('/[^a-z0-9]+/', '-', $title)), '-') ?: $folder;
    $filename = $slugBase . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $allowedTypes[$imageInfo[2]];
    if (!move_uploaded_file($tmpPath, $uploadDir . '/' . $filename)) {
        $error = 'Could not save the uploaded image.';
        return '';
    }
    return 'images/' . $folder . '/' . $filename;
}

function buildPublicArticleUrl(string $slug): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443')
        ? 'https'
        : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return '../article.php?slug=' . urlencode($slug);
    }
    return $scheme . '://' . $host . '/article.php?slug=' . urlencode($slug);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'publish_article') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '', 'publish')) {
        $publishError = 'Security validation failed. Please try again.';
    } else {
        $title = cleanText($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category = cleanText($_POST['category'] ?? 'General');
        $author = cleanText($_POST['author'] ?? ($_SESSION['admin_name'] ?? 'TSF Admin'));
        $featured = !empty($_POST['is_featured']) ? 1 : 0;
        $coverImage = '';
        $uploaded = $_FILES['cover_image'] ?? null;
        $hasUpload = $uploaded && (($uploaded['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);
        $allowed = ['Education','Digital Skills','Health','Community','Partnership','Announcement','General'];
        if (!in_array($category, $allowed, true)) $category = 'General';
        if ($hasUpload) $coverImage = uploadManagedImage($uploaded, 'news', $title, $publishError);

        if (strlen($title) < 5) {
            $publishError = 'Title must be at least 5 characters.';
        } elseif (strlen($content) < 30) {
            $publishError = 'Content must be at least 30 characters.';
        } elseif ($publishError === '') {
            $slug = strtolower(trim($title));
            $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
            $slug = preg_replace('/[\s-]+/', '-', $slug);
            $slug = trim($slug, '-') . '-' . substr(uniqid(), -6);

            $stmt = $pdo->prepare(
                'INSERT INTO news (title, slug, content, category, author_name, author_id, cover_image, is_published, is_featured, published_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())'
            );
            $stmt->execute([$title, $slug, $content, $category, $author ?: 'TSF Admin', $_SESSION['admin_id'], $coverImage ?: null, $featured]);
            $publishMessage = 'Article published successfully.';
            $publishedArticleUrl = buildPublicArticleUrl($slug);
            $activePanel = 'publish';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_settings') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '', 'settings')) {
        $settingsError = 'Security validation failed. Please try again.';
    } else {
        $allowedSettings = getContentSettingKeys();
        $postedKeys = array_values(array_intersect($allowedSettings, $_POST['setting_keys'] ?? []));
        if (!$postedKeys) {
            $postedKeys = $allowedSettings;
        }
        $stmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($postedKeys as $key) {
            $value = trim($_POST[$key] ?? '');
            if (isset($_FILES['setting_upload']['name'][$key]) && ($_FILES['setting_upload']['error'][$key] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $settingUpload = [
                    'name' => $_FILES['setting_upload']['name'][$key],
                    'type' => $_FILES['setting_upload']['type'][$key],
                    'tmp_name' => $_FILES['setting_upload']['tmp_name'][$key],
                    'error' => $_FILES['setting_upload']['error'][$key],
                    'size' => $_FILES['setting_upload']['size'][$key],
                ];
                $uploadedPath = uploadManagedImage($settingUpload, 'page', $key, $settingsError);
                if ($uploadedPath !== '') {
                    $value = $uploadedPath;
                }
            }
            if ($settingsError !== '') break;
            $stmt->execute([$key, $value]);
        }
        if ($settingsError === '') $settingsMessage = 'Site content updated successfully.';
        $activePanel = sectionIdForSettingKey($postedKeys[0] ?? '', $contentDefinitions);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_gallery') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '', 'gallery')) {
        $galleryError = 'Security validation failed. Please try again.';
    } else {
        $title = cleanText($_POST['title'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $caption = trim($_POST['caption'] ?? '');
        $category = cleanText($_POST['category'] ?? 'General');
        $order = (int)($_POST['display_order'] ?? 0);
        $uploaded = $_FILES['gallery_image'] ?? null;
        $hasUpload = $uploaded && (($uploaded['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);

        if ($hasUpload) $imageUrl = uploadManagedImage($uploaded, 'gallery', $title, $galleryError);

        if ($title === '' || $imageUrl === '') {
            $galleryError = $galleryError ?: 'Gallery title and an uploaded image or image URL are required.';
        } elseif ($galleryError === '' && !filter_var($imageUrl, FILTER_VALIDATE_URL) && !preg_match('/^images\\//', $imageUrl)) {
            $galleryError = 'Use an uploaded image, a valid image URL, or an images/ path.';
        }

        if ($galleryError === '') {
            $stmt = $pdo->prepare('INSERT INTO gallery (title, caption, image_url, category, display_order, is_active) VALUES (?, ?, ?, ?, ?, 1)');
            $stmt->execute([$title, $caption, $imageUrl, $category ?: 'General', $order]);
            $galleryMessage = 'Gallery item added.';
            $activePanel = 'gallery';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_gallery') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '', 'gallery')) {
        $galleryError = 'Security validation failed. Please try again.';
    } else {
        $galleryId = (int)($_POST['gallery_id'] ?? 0);
        $title = cleanText($_POST['title'] ?? '');
        $caption = trim($_POST['caption'] ?? '');
        $category = cleanText($_POST['category'] ?? 'General');
        $order = (int)($_POST['display_order'] ?? 0);
        $imageUrl = trim($_POST['image_url'] ?? '');
        $uploaded = $_FILES['gallery_image'] ?? null;
        $hasUpload = $uploaded && (($uploaded['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);
        if ($hasUpload) $imageUrl = uploadManagedImage($uploaded, 'gallery', $title, $galleryError);
        if ($galleryId <= 0 || $title === '' || $imageUrl === '') {
            $galleryError = $galleryError ?: 'Title and image are required.';
        } elseif ($galleryError === '' && !filter_var($imageUrl, FILTER_VALIDATE_URL) && !preg_match('/^images\\//', $imageUrl)) {
            $galleryError = 'Use an uploaded image, a valid image URL, or an images/ path.';
        }
        if ($galleryError === '') {
            $stmt = $pdo->prepare('UPDATE gallery SET title = ?, caption = ?, image_url = ?, category = ?, display_order = ? WHERE id = ?');
            $stmt->execute([$title, $caption, $imageUrl, $category ?: 'General', $order, $galleryId]);
            $galleryMessage = 'Gallery item updated.';
            $activePanel = 'gallery';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_gallery') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '', 'gallery_delete')) {
        $galleryError = 'Security validation failed. Please try again.';
    } else {
        $stmt = $pdo->prepare('UPDATE gallery SET is_active = 0 WHERE id = ?');
        $stmt->execute([(int)($_POST['gallery_id'] ?? 0)]);
        $galleryMessage = 'Gallery item removed from the public page.';
        $activePanel = 'gallery';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_content_item') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '', 'content_item')) {
        $contentError = 'Security validation failed. Please try again.';
    } else {
        $itemType = $_POST['item_type'] ?? '';
        $title = cleanText($_POST['title'] ?? '');
        $subtitle = cleanText($_POST['subtitle'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $metaValue = cleanText($_POST['meta_value'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $order = (int)($_POST['display_order'] ?? 0);
        $uploaded = $_FILES['content_image'] ?? null;
        $hasUpload = $uploaded && (($uploaded['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);

        if (!isset($contentTypes[$itemType])) {
            $contentError = 'Choose a valid content type.';
        } elseif ($title === '') {
            $contentError = 'Title is required.';
        }

        if ($contentError === '' && $hasUpload) $imageUrl = uploadManagedImage($uploaded, 'content', $title, $contentError);

        if ($contentError === '') {
            $stmt = $pdo->prepare('INSERT INTO content_items (item_type, title, subtitle, body, meta_value, image_url, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
            $stmt->execute([$itemType, $title, $subtitle, $body, $metaValue, $imageUrl, $order]);
            $contentMessage = $contentTypes[$itemType] . ' added.';
            $activePanel = 'content-items';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_content_item') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '', 'content_item')) {
        $contentError = 'Security validation failed. Please try again.';
    } else {
        $itemId = (int)($_POST['content_item_id'] ?? 0);
        $itemType = $_POST['item_type'] ?? '';
        $title = cleanText($_POST['title'] ?? '');
        $subtitle = cleanText($_POST['subtitle'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $metaValue = cleanText($_POST['meta_value'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $order = (int)($_POST['display_order'] ?? 0);
        $uploaded = $_FILES['content_image'] ?? null;
        $hasUpload = $uploaded && (($uploaded['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);
        if ($hasUpload) $imageUrl = uploadManagedImage($uploaded, 'content', $title, $contentError);
        if ($itemId <= 0 || !isset($contentTypes[$itemType])) {
            $contentError = 'Choose a valid content item.';
        } elseif ($title === '') {
            $contentError = 'Title is required.';
        }
        if ($contentError === '') {
            $stmt = $pdo->prepare('UPDATE content_items SET item_type = ?, title = ?, subtitle = ?, body = ?, meta_value = ?, image_url = ?, display_order = ? WHERE id = ?');
            $stmt->execute([$itemType, $title, $subtitle, $body, $metaValue, $imageUrl, $order, $itemId]);
            $contentMessage = $contentTypes[$itemType] . ' updated.';
            $activePanel = 'content-items';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_content_item') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '', 'content_item_delete')) {
        $contentError = 'Security validation failed. Please try again.';
    } else {
        $stmt = $pdo->prepare('UPDATE content_items SET is_active = 0 WHERE id = ?');
        $stmt->execute([(int)($_POST['content_item_id'] ?? 0)]);
        $contentMessage = 'Content item removed.';
        $activePanel = 'content-items';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_account') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '', 'account')) {
        $accountError = 'Security validation failed. Please try again.';
    } else {
        $email = filter_var(trim($_POST['admin_email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $name = cleanText($_POST['admin_name'] ?? '');
        if (!$email) {
            $accountError = 'Enter a valid recovery email.';
        } else {
            $stmt = $pdo->prepare('UPDATE admin SET email = ?, full_name = ? WHERE id = ?');
            $stmt->execute([$email, $name ?: 'TSF Administrator', $_SESSION['admin_id']]);
            $_SESSION['admin_name'] = $name ?: 'TSF Administrator';
            $accountMessage = 'Admin account updated.';
            $activePanel = 'account';
        }
    }
}

$csrfPublish = generateCsrfToken('publish');
$csrfSettings = generateCsrfToken('settings');
$csrfGallery = generateCsrfToken('gallery');
$csrfGalleryDelete = generateCsrfToken('gallery_delete');
$csrfContentItem = generateCsrfToken('content_item');
$csrfContentItemDelete = generateCsrfToken('content_item_delete');
$csrfAccount = generateCsrfToken('account');
$settingsRows = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
$settings = getDefaultContentSettings();
foreach ($settingsRows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$summary = $pdo->query('SELECT total_donors, total_raised, last_donation_at FROM donation_summary')->fetch()
    ?: ['total_donors' => 0, 'total_raised' => 0, 'last_donation_at' => null];
$articleCount = (int)$pdo->query('SELECT COUNT(*) FROM news WHERE is_published = 1')->fetchColumn();
$messageCount = (int)$pdo->query('SELECT COUNT(*) FROM messages')->fetchColumn();
$donors = $pdo->query(
    'SELECT first_name, last_name, email, gender, amount, payment_method, mobile_network, payment_verified, created_at
     FROM donors ORDER BY created_at DESC LIMIT 100'
)->fetchAll();
$recentNews = $pdo->query(
    'SELECT title, slug, category, author_name, cover_image, published_at FROM news ORDER BY created_at DESC LIMIT 8'
)->fetchAll();
$messages = $pdo->query(
    'SELECT name, email, subject, message_type, message, created_at FROM messages ORDER BY created_at DESC LIMIT 50'
)->fetchAll();
$galleryItems = $pdo->query(
    'SELECT id, title, caption, image_url, category, display_order, created_at FROM gallery WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC'
)->fetchAll();
$contentItems = $pdo->query(
    'SELECT id, item_type, title, subtitle, body, meta_value, image_url, display_order FROM content_items WHERE is_active = 1 ORDER BY item_type ASC, display_order ASC, created_at ASC'
)->fetchAll();
$adminAccountStmt = $pdo->prepare('SELECT full_name, email FROM admin WHERE id = ?');
$adminAccountStmt->execute([$_SESSION['admin_id']]);
$adminAccount = $adminAccountStmt->fetch() ?: ['full_name' => '', 'email' => ''];

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function methodText($method): string {
    return [
        'mobile_money' => 'Mobile Money',
        'card' => 'Card',
    ][$method] ?? $method;
}

function networkText($network): string {
    return [
        'mtn' => 'MTN',
        'telecel' => 'Telecel',
        'airteltigo' => 'AirtelTigo',
    ][$network] ?? '';
}

function sectionIdFromGroup(string $groupName): string {
    return [
        'Global' => 'page-global',
        'Home Page' => 'page-home',
        'About Page' => 'page-about',
        'Impact Page' => 'page-impact',
        'Team Page' => 'page-team',
        'News Page' => 'page-news',
        'Donate Page' => 'page-donate',
        'Contact Page' => 'page-contact',
        'Gallery Page' => 'page-gallery',
        'Social Links' => 'page-social',
    ][$groupName] ?? 'page-' . strtolower(preg_replace('/[^a-z0-9]+/i', '-', $groupName));
}

function pageDescription(string $groupName): string {
    return [
        'Global' => 'Edit the site-wide name, tagline, and mission statement.',
        'Home Page' => 'Edit the homepage hero, intro, values, and call-to-action copy.',
        'About Page' => 'Edit the About page story, journey intro, and call-to-action.',
        'Impact Page' => 'Edit Impact page headings and reach text. Impact cards are managed below.',
        'Team Page' => 'Edit Team page headings and call-to-action. Team members are managed below.',
        'News Page' => 'Edit News page headings and sidebar text. Articles are managed below.',
        'Donate Page' => 'Edit the Donate page copy, form intro, security note, and thank-you message.',
        'Contact Page' => 'Edit contact copy, address, phone, email, office hours, and volunteer text.',
        'Gallery Page' => 'Edit gallery page copy. Gallery photos are managed below.',
        'Social Links' => 'Edit the public social media links used on the site.',
    ][$groupName] ?? 'Edit this page content.';
}

function sectionIdForSettingKey(string $key, array $definitions): string {
    foreach ($definitions as $groupName => $fields) {
        if (isset($fields[$key])) {
            return sectionIdFromGroup($groupName);
        }
    }
    return 'page-home';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - TSF Admin</title>
  <link rel="icon" href="../images/tsf-logo.png">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    html { scroll-behavior: smooth; scroll-padding-top: 90px; }
    body { background: #eef2f8; min-height: 100vh; overflow-x: hidden; }
    .admin-header { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; background: linear-gradient(135deg, #071844, var(--blue-dark) 52%, var(--blue)); padding: 0 1.5rem; height: 68px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 12px 35px rgba(5,16,45,0.22); }
    .admin-header-brand { display: flex; align-items: center; gap: 0.8rem; }
    .admin-header-brand img { height: 42px; filter: drop-shadow(0 4px 10px rgba(0,0,0,0.22)); }
    .admin-header-brand span { font-family: 'Playfair Display', serif; font-size: 1.05rem; font-weight: 700; color: var(--white); }
    .admin-header-brand small { font-size: 0.7rem; color: var(--gold-light); display: block; letter-spacing: 1px; text-transform: uppercase; }
    .admin-header-right { display: flex; align-items: center; gap: 1rem; }
    .admin-user { display: flex; align-items: center; gap: 0.6rem; font-size: 0.88rem; color: var(--white); }
    .admin-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--gold); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; color: var(--blue-dark); }
    .logout-btn { background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.22); color: var(--white); padding: 0.45rem 0.95rem; border-radius: 999px; font-size: 0.82rem; cursor: pointer; font-family: 'DM Sans', sans-serif; text-decoration: none; transition: var(--transition); }
    .logout-btn:hover { background: rgba(255,255,255,0.22); transform: translateY(-1px); }
    .admin-layout { display: flex; padding-top: 68px; min-height: 100vh; }
    .admin-sidebar { width: 270px; background: linear-gradient(180deg, #071844 0%, var(--blue-dark) 55%, var(--blue) 100%); box-shadow: 10px 0 30px rgba(12,30,72,0.16); position: fixed; top: 68px; left: 0; bottom: 0; overflow-y: auto; padding: 1.25rem 0.9rem; }
    .sidebar-nav { list-style: none; }
    .sidebar-nav li { margin: 0.16rem 0; }
    .sidebar-nav a { display: flex; align-items: center; gap: 0.75rem; padding: 0.74rem 0.9rem; border-radius: 12px; font-size: 0.9rem; font-weight: 800; color: rgba(255,255,255,0.78); text-decoration: none; transition: var(--transition); border: 1px solid transparent; }
    .sidebar-nav a span { width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 9px; background: rgba(255,255,255,0.1); color: var(--gold-light); font-size: 0.8rem; font-weight: 900; flex-shrink: 0; }
    .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.14); color: var(--white); border-color: rgba(255,255,255,0.16); transform: translateX(2px); }
    .sidebar-nav a.active { box-shadow: inset 3px 0 0 var(--gold); }
    .sidebar-nav a.active span { background: var(--gold); color: var(--blue-dark); }
    .sidebar-section-label { font-size: 0.67rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1.8px; color: rgba(255,255,255,0.42); padding: 0.95rem 0.75rem 0.35rem; }
    .admin-main { margin-left: 270px; flex: 1; padding: 2rem; max-width: 1480px; }
    .dashboard-hero { background: linear-gradient(135deg, #ffffff, #f7f9ff); border: 1px solid rgba(26,63,163,0.08); border-radius: 18px; box-shadow: 0 12px 36px rgba(12,30,72,0.08); padding: 1.45rem 1.6rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; }
    .dashboard-hero h2 { font-family:'Playfair Display',serif; font-size:1.65rem; color:var(--blue-dark); margin-bottom:0.25rem; }
    .dashboard-hero p { color:var(--gray); font-size:0.92rem; }
    .dashboard-actions { display: flex; gap: 0.7rem; flex-wrap: wrap; justify-content: flex-end; }
    .action-chip { display: inline-flex; align-items: center; gap: 0.45rem; background: var(--blue); color: var(--white); text-decoration: none; padding: 0.68rem 0.95rem; border-radius: 999px; font-weight: 800; font-size: 0.84rem; box-shadow: 0 8px 20px rgba(26,63,163,0.18); transition: var(--transition); }
    .action-chip.secondary { background: var(--white); color: var(--blue-dark); border: 1px solid rgba(26,63,163,0.14); box-shadow: none; }
    .action-chip:hover { transform: translateY(-2px); }
    .dashboard-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .dash-stat { background: var(--white); border: 1px solid rgba(26,63,163,0.08); border-radius: 16px; padding: 1.25rem; box-shadow: 0 10px 30px rgba(12,30,72,0.07); display: flex; align-items: center; gap: 1rem; position: relative; overflow: hidden; }
    .dash-stat::after { content: ""; position: absolute; right: -28px; top: -28px; width: 88px; height: 88px; border-radius: 50%; background: rgba(212,160,23,0.12); }
    .dash-stat-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; background: rgba(26,63,163,0.1); color: var(--blue); font-weight: 900; }
    .dash-stat-num { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 900; color: var(--blue-dark); line-height: 1; }
    .dash-stat-label { font-size: 0.8rem; color: var(--gray); margin-top: 0.3rem; }
    .admin-main .admin-section { display: none; padding: 0; margin-bottom: 2rem; animation: panelIn 0.22s ease; }
    .admin-main .admin-section.active-panel { display: block; }
    @keyframes panelIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .admin-section-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 0.9rem; gap: 1rem; }
    .admin-section-header h3 { font-family: 'Playfair Display', serif; font-size: 1.28rem; color: var(--blue-dark); }
    .admin-section-header p { color: var(--gray); font-size: 0.86rem; margin-top: 0.2rem; }
    .data-table-wrap, .publish-card { background: var(--white); border: 1px solid rgba(26,63,163,0.08); border-radius: 16px; box-shadow: 0 10px 30px rgba(12,30,72,0.07); overflow: hidden; }
    .publish-card { padding: 1.55rem; }
    .table-scroll { overflow-x: auto; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    .data-table th { background: #f7f9fe; padding: 0.85rem 1rem; text-align: left; font-size: 0.74rem; text-transform: uppercase; letter-spacing: 1.2px; color: var(--blue); font-weight: 900; border-bottom: 1px solid var(--gray-light); white-space: nowrap; }
    .data-table td { padding: 0.9rem 1rem; border-bottom: 1px solid #edf0f7; color: var(--text); vertical-align: top; }
    .data-table tbody tr:hover { background: #fbfcff; }
    .amount-cell { font-weight: 700; color: #276749; }
    .method-badge { font-size: 0.75rem; background: #eef2f8; color: #4a5873; padding: 0.24rem 0.62rem; border-radius: 999px; font-weight: 800; white-space: nowrap; }
    .status-ok { color: #276749; font-weight: 800; }
    .status-wait { color: var(--gold-dark); font-weight: 800; }
    .msg-content { color: var(--gray); white-space: pre-wrap; line-height: 1.65; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 120px; gap: 1rem; }
    .settings-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.2rem; flex-wrap: wrap; }
    .settings-search { max-width: 360px; flex: 1; min-width: 220px; }
    .settings-actions { display: flex; gap: 0.55rem; flex-wrap: wrap; }
    .mini-btn { border: 1px solid rgba(26,63,163,0.14); background: #f7f9fe; color: var(--blue-dark); border-radius: 999px; padding: 0.48rem 0.78rem; font-weight: 800; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: var(--transition); }
    .mini-btn:hover { background: #eef4ff; }
    .mini-btn.active { background: var(--blue); border-color: var(--blue); color: var(--white); }
    .content-group { border: 1px solid #e4e9f4; border-radius: 14px; margin-bottom: 0.8rem; background: #fbfcff; overflow: hidden; }
    .content-group[hidden] { display: none; }
    .content-group summary { list-style: none; cursor: pointer; padding: 1rem 1.15rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; color: var(--blue-dark); font-weight: 900; font-family: 'Playfair Display', serif; background: linear-gradient(135deg, #ffffff, #f8faff); }
    .content-group summary::-webkit-details-marker { display: none; }
    .content-group summary::after { content: "+"; width: 28px; height: 28px; border-radius: 50%; background: #eef4ff; color: var(--blue); display: inline-flex; align-items: center; justify-content: center; font-family: 'DM Sans', sans-serif; }
    .content-group[open] summary::after { content: "-"; }
    .content-group .form-grid { padding: 1.1rem; }
    textarea.form-control { resize: vertical; }
    .settings-help { color: var(--gray); font-size: 0.86rem; margin-bottom: 1.2rem; line-height: 1.6; }
    .thumb { width: 84px; height: 58px; object-fit: cover; border-radius: 10px; background: var(--gray-light); box-shadow: 0 5px 14px rgba(12,30,72,0.11); }
    .item-editor-list { display: grid; gap: 1rem; }
    .item-editor-card { background: var(--white); border: 1px solid rgba(26,63,163,0.1); border-radius: 14px; box-shadow: 0 8px 24px rgba(12,30,72,0.06); padding: 1rem; }
    .item-editor-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
    .item-editor-title { display: flex; align-items: center; gap: 0.75rem; min-width: 0; }
    .item-editor-title strong { display: block; color: var(--blue-dark); }
    .item-editor-title span { color: var(--gray); font-size: 0.84rem; }
    .item-editor-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 0.9rem; }
    .item-editor-fields .wide { grid-column: 1 / -1; }
    .item-editor-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; align-items: center; margin-top: 0.9rem; }
    .message-list { display: grid; gap: 1rem; }
    .message-card { background: var(--white); border: 1px solid rgba(26,63,163,0.1); border-radius: 14px; box-shadow: 0 8px 24px rgba(12,30,72,0.06); padding: 1rem; }
    .message-card-head { display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 0.85rem; }
    .message-meta { color: var(--gray); font-size: 0.84rem; line-height: 1.6; }
    .message-body { background: #f8faff; border: 1px solid #e6ecf8; border-radius: 10px; padding: 0.9rem 1rem; color: var(--text); white-space: pre-wrap; line-height: 1.7; }
    .table-input { min-width: 150px; padding: 0.5rem 0.65rem; font-size: 0.82rem; }
    .table-textarea { min-width: 230px; min-height: 76px; padding: 0.55rem 0.65rem; font-size: 0.82rem; }
    .row-actions { display: flex; flex-direction: column; gap: 0.45rem; align-items: flex-start; }
    .save-row-btn { background: var(--blue); color: var(--white); border: 0; border-radius: 999px; padding: 0.45rem 0.8rem; font-weight: 800; cursor: pointer; font-family: 'DM Sans', sans-serif; }
    .delete-row-btn { background: #fff1f1; color: #9b1c1c; border: 1px solid #ffd4d4; border-radius: 999px; padding: 0.42rem 0.75rem; font-weight: 800; cursor: pointer; font-family: 'DM Sans', sans-serif; }
    .admin-time { font-size: 0.8rem; color: rgba(255,255,255,0.7); background: rgba(255,255,255,0.08); padding: 0.3rem 0.8rem; border-radius: 15px; }
    @media(max-width:1180px) { .dashboard-stats { grid-template-columns: repeat(2,1fr); } .dashboard-hero { align-items: flex-start; flex-direction: column; } .dashboard-actions { justify-content: flex-start; } }
    @media(max-width:820px) { .admin-header { padding: 0 1rem; } .admin-header-brand small, .admin-time { display: none; } .admin-sidebar { position: static; width: 100%; padding: 0.8rem; box-shadow: none; } .admin-layout { display: block; } .sidebar-nav { display: flex; overflow-x: auto; gap: 0.4rem; padding-bottom: 0.2rem; } .sidebar-nav li { flex: 0 0 auto; } .sidebar-section-label { display: none; } .sidebar-nav a { white-space: nowrap; } .admin-main { margin-left: 0; padding: 1rem; } .dashboard-stats, .form-grid, .form-grid-3, .item-editor-fields { grid-template-columns: 1fr; } .publish-card { padding: 1rem; } }
  </style>
</head>
<body data-active-panel="<?= e($activePanel) ?>">
  <div class="admin-header">
    <div class="admin-header-brand">
      <img src="../images/tsf-logo.png" alt="TSF">
      <div><span>TSF Admin</span><small>Dashboard Portal</small></div>
    </div>
    <div class="admin-header-right">
      <div class="admin-time" id="admin-time"></div>
      <div class="admin-user"><div class="admin-avatar">A</div><span><?= e($_SESSION['admin_name'] ?? 'Admin') ?></span></div>
      <a class="logout-btn" href="logout.php">Logout</a>
    </div>
  </div>

  <div class="admin-layout">
    <aside class="admin-sidebar">
      <ul class="sidebar-nav">
        <li class="sidebar-section-label">Overview</li>
        <li><a class="active" href="#overview"><span>O</span>Dashboard</a></li>
        <li class="sidebar-section-label">Pages</li>
        <li><a href="#page-global"><span>G</span>Global</a></li>
        <li><a href="#page-home"><span>H</span>Home</a></li>
        <li><a href="#page-about"><span>A</span>About</a></li>
        <li><a href="#page-team"><span>T</span>Team</a></li>
        <li><a href="#page-impact"><span>I</span>Impact</a></li>
        <li><a href="#page-gallery"><span>P</span>Gallery</a></li>
        <li><a href="#page-news"><span>N</span>News</a></li>
        <li><a href="#page-donate"><span>D</span>Donate</a></li>
        <li><a href="#page-contact"><span>C</span>Contact</a></li>
        <li><a href="#page-social"><span>S</span>Social Links</a></li>
        <li class="sidebar-section-label">Donations</li>
        <li><a href="#donors"><span>D</span>Donor Records</a></li>
        <li class="sidebar-section-label">Managers</li>
        <li><a href="#content-items"><span>C</span>Page Items</a></li>
        <li><a href="#gallery"><span>G</span>Gallery Photos</a></li>
        <li><a href="#publish"><span>P</span>Articles</a></li>
        <li class="sidebar-section-label">Messages</li>
        <li><a href="#messages"><span>M</span>Messages</a></li>
        <li class="sidebar-section-label">Account</li>
        <li><a href="#account"><span>A</span>Admin Account</a></li>
        <li class="sidebar-section-label">Site</li>
        <li><a href="../index.html" target="_blank"><span>V</span>View Website</a></li>
      </ul>
    </aside>

    <main class="admin-main">
      <section class="admin-section" id="overview">
        <div class="dashboard-hero">
          <div>
            <h2>Welcome back, Admin</h2>
            <p>Manage donations, content, gallery items, articles, and site account settings from one place.</p>
          </div>
          <div class="dashboard-actions">
            <a class="action-chip" href="#page-home">Edit Site</a>
            <a class="action-chip secondary" href="#gallery">Manage Gallery</a>
            <a class="action-chip secondary" href="../index.html" target="_blank">View Website</a>
          </div>
        </div>
        <div class="dashboard-stats">
          <div class="dash-stat"><div class="dash-stat-icon">D</div><div><div class="dash-stat-num"><?= (int)$summary['total_donors'] ?></div><div class="dash-stat-label">Verified Donors</div></div></div>
          <div class="dash-stat"><div class="dash-stat-icon">G</div><div><div class="dash-stat-num">GHS <?= number_format((float)$summary['total_raised'], 2) ?></div><div class="dash-stat-label">Verified Raised</div></div></div>
          <div class="dash-stat"><div class="dash-stat-icon">N</div><div><div class="dash-stat-num"><?= $articleCount ?></div><div class="dash-stat-label">Published Articles</div></div></div>
          <div class="dash-stat"><div class="dash-stat-icon">M</div><div><div class="dash-stat-num"><?= $messageCount ?></div><div class="dash-stat-label">Messages Received</div></div></div>
        </div>
      </section>

      <section class="admin-section" id="donors">
        <div class="admin-section-header"><div><h3>Donor Records</h3><p>Recent donation attempts and verified payment status.</p></div></div>
        <div class="data-table-wrap">
          <div class="table-scroll">
          <table class="data-table">
            <thead><tr><th>Name</th><th>Email</th><th>Gender</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
              <?php if (!$donors): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--gray);padding:2rem">No donation records yet.</td></tr>
              <?php endif; ?>
              <?php foreach ($donors as $d): ?>
                <tr>
                  <td><strong><?= e($d['first_name'] . ' ' . $d['last_name']) ?></strong></td>
                  <td><?= e($d['email']) ?></td>
                  <td><?= e($d['gender'] ?: '-') ?></td>
                  <td class="amount-cell">GHS <?= number_format((float)$d['amount'], 2) ?></td>
                  <td><span class="method-badge"><?= e(trim(methodText($d['payment_method']) . ' ' . networkText($d['mobile_network']))) ?></span></td>
                  <td class="<?= $d['payment_verified'] ? 'status-ok' : 'status-wait' ?>"><?= $d['payment_verified'] ? 'Verified' : 'Pending' ?></td>
                  <td><?= e(date('d M Y', strtotime($d['created_at']))) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div>
        </div>
      </section>

      <?php foreach ($contentDefinitions as $groupName => $fields): ?>
        <section class="admin-section page-editor-section" id="<?= e(sectionIdFromGroup($groupName)) ?>">
          <div class="admin-section-header">
            <div>
              <h3><?= e($groupName) ?></h3>
              <p><?= e(pageDescription($groupName)) ?></p>
            </div>
            <a class="action-chip secondary" href="../<?= sectionIdFromGroup($groupName) === 'page-home' ? 'index' : str_replace(['page-', 'global', 'social'], ['', 'index', 'contact'], sectionIdFromGroup($groupName)) ?>.html" target="_blank">Preview</a>
          </div>
          <div class="publish-card">
            <?php if ($settingsMessage): ?><div class="alert alert-success show"><?= e($settingsMessage) ?></div><?php endif; ?>
            <?php if ($settingsError): ?><div class="alert alert-error show"><?= e($settingsError) ?></div><?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save_settings">
              <input type="hidden" name="csrf_token" value="<?= e($csrfSettings) ?>">
              <div class="form-grid">
                <?php foreach ($fields as $key => $field): ?>
                  <div class="form-group">
                    <input type="hidden" name="setting_keys[]" value="<?= e($key) ?>">
                    <label for="<?= e($key) ?>"><?= e($field['label']) ?></label>
                    <?php if (($field['type'] ?? 'text') === 'image'): ?>
                      <?php if (!empty($settings[$key])): ?><img class="thumb" src="<?= filter_var($settings[$key], FILTER_VALIDATE_URL) ? e($settings[$key]) : '../' . e($settings[$key]) ?>" alt=""><?php endif; ?>
                      <input class="form-control" type="file" name="setting_upload[<?= e($key) ?>]" accept="image/jpeg,image/png,image/webp,image/gif">
                      <input class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($settings[$key] ?? '') ?>" placeholder="Current image path or URL">
                    <?php elseif (($field['type'] ?? 'text') === 'textarea'): ?>
                      <textarea class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" rows="3"><?= e($settings[$key] ?? '') ?></textarea>
                    <?php else: ?>
                      <input class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($settings[$key] ?? '') ?>">
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
              <button type="submit" class="btn btn-blue">Save <?= e($groupName) ?></button>
            </form>
          </div>
        </section>
      <?php endforeach; ?>

      <section class="admin-section" id="content-items">
        <div class="admin-section-header"><div><h3>Page Items Manager</h3><p>Manage repeatable content used by Team, Impact, Contact FAQ, and program sections.</p></div></div>
        <div class="publish-card" style="margin-bottom:1.2rem">
          <?php if ($contentMessage): ?><div class="alert alert-success show"><?= e($contentMessage) ?></div><?php endif; ?>
          <?php if ($contentError): ?><div class="alert alert-error show"><?= e($contentError) ?></div><?php endif; ?>
          <div class="settings-actions" style="margin-bottom:1rem">
            <button type="button" class="mini-btn content-filter active" data-type="all">All</button>
            <?php foreach ($contentTypes as $value => $label): ?>
              <button type="button" class="mini-btn content-filter" data-type="<?= e($value) ?>"><?= e($label) ?></button>
            <?php endforeach; ?>
          </div>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_content_item">
            <input type="hidden" name="csrf_token" value="<?= e($csrfContentItem) ?>">
            <div class="form-grid">
              <div class="form-group">
                <label>Content Type *</label>
                <select class="form-control" name="item_type" required>
                  <?php foreach ($contentTypes as $value => $label): ?>
                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group"><label>Title / Name *</label><input class="form-control" name="title" placeholder="e.g. New team member, FAQ question, stat label" required></div>
            </div>
            <div class="form-grid">
              <div class="form-group"><label>Subtitle / Role / Category</label><input class="form-control" name="subtitle" placeholder="Role, category, location, or icon key"></div>
              <div class="form-group"><label>Meta Value</label><input class="form-control" name="meta_value" placeholder="Initials, stat number, or short stat text"></div>
            </div>
            <div class="form-group"><label>Description / Answer</label><textarea class="form-control" name="body" rows="4" placeholder="Bio, program text, testimonial, FAQ answer, etc."></textarea></div>
            <div class="form-grid-3">
              <div class="form-group"><label>Upload Image</label><input class="form-control" type="file" name="content_image" accept="image/jpeg,image/png,image/webp,image/gif"></div>
              <div class="form-group"><label>Image URL fallback</label><input class="form-control" name="image_url" placeholder="Optional external URL or images/..."></div>
              <div class="form-group"><label>Order</label><input class="form-control" type="number" name="display_order" value="0"></div>
            </div>
            <p class="settings-help">Tip: Team uses Title, Subtitle, Description, Initials/Image. Impact stats use Title and Meta Value. FAQ uses Title as the question and Description as the answer.</p>
            <button type="submit" class="btn btn-blue">Add Page Item</button>
          </form>
        </div>
        <div class="item-editor-list">
          <?php if (!$contentItems): ?>
            <div class="publish-card" style="text-align:center;color:var(--gray);padding:2rem">No page items yet.</div>
          <?php endif; ?>
          <?php foreach ($contentItems as $item): ?>
            <div class="item-editor-card" data-content-type="<?= e($item['item_type']) ?>">
              <div class="item-editor-head">
                <div class="item-editor-title">
                  <?php if ($item['image_url']): ?><img class="thumb" src="<?= e($item['image_url']) ?>" alt=""><?php endif; ?>
                  <div>
                    <strong><?= e($item['title']) ?></strong>
                    <span><?= e(($contentTypes[$item['item_type']] ?? $item['item_type']) . ' - ' . ($item['subtitle'] ?: 'No subtitle')) ?></span>
                  </div>
                </div>
                <span class="method-badge">Order <?= (int)$item['display_order'] ?></span>
              </div>
              <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_content_item">
                <input type="hidden" name="csrf_token" value="<?= e($csrfContentItem) ?>">
                <input type="hidden" name="content_item_id" value="<?= (int)$item['id'] ?>">
                <div class="item-editor-fields">
                  <div class="form-group">
                    <label>Content Type</label>
                    <select class="form-control" name="item_type">
                      <?php foreach ($contentTypes as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $item['item_type'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Title / Name *</label>
                    <input class="form-control" name="title" value="<?= e($item['title']) ?>" required>
                  </div>
                  <div class="form-group">
                    <label>Subtitle / Role / Category</label>
                    <input class="form-control" name="subtitle" value="<?= e($item['subtitle'] ?? '') ?>">
                  </div>
                  <div class="form-group">
                    <label>Meta Value</label>
                    <input class="form-control" name="meta_value" value="<?= e($item['meta_value'] ?? '') ?>" placeholder="Initials, number, or icon key">
                  </div>
                  <div class="form-group wide">
                    <label>Description / Answer</label>
                    <textarea class="form-control" name="body" rows="4"><?= e($item['body'] ?? '') ?></textarea>
                  </div>
                  <div class="form-group">
                    <label>Replacement Image</label>
                    <input class="form-control" type="file" name="content_image" accept="image/jpeg,image/png,image/webp,image/gif">
                  </div>
                  <div class="form-group">
                    <label>Image URL / Current Image</label>
                    <input class="form-control" name="image_url" value="<?= e($item['image_url'] ?? '') ?>" placeholder="images/... or URL">
                  </div>
                  <div class="form-group">
                    <label>Display Order</label>
                    <input class="form-control" type="number" name="display_order" value="<?= (int)$item['display_order'] ?>">
                  </div>
                </div>
                <div class="item-editor-actions">
                  <button class="save-row-btn" type="submit">Save Changes</button>
                </div>
              </form>
              <form method="POST" onsubmit="return confirm('Remove this page item?')">
                <input type="hidden" name="action" value="delete_content_item">
                <input type="hidden" name="csrf_token" value="<?= e($csrfContentItemDelete) ?>">
                <input type="hidden" name="content_item_id" value="<?= (int)$item['id'] ?>">
                <button class="delete-row-btn" type="submit">Remove</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="admin-section" id="gallery">
        <div class="admin-section-header"><div><h3>Gallery Manager</h3><p>Add, order, and remove public gallery images.</p></div></div>
        <div class="publish-card" style="margin-bottom:1.2rem">
          <?php if ($galleryMessage): ?><div class="alert alert-success show"><?= e($galleryMessage) ?></div><?php endif; ?>
          <?php if ($galleryError): ?><div class="alert alert-error show"><?= e($galleryError) ?></div><?php endif; ?>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_gallery">
            <input type="hidden" name="csrf_token" value="<?= e($csrfGallery) ?>">
            <div class="form-grid">
              <div class="form-group"><label>Photo Title *</label><input class="form-control" name="title" placeholder="e.g. School supplies outreach"></div>
              <div class="form-group"><label>Category</label><input class="form-control" name="category" placeholder="Education"></div>
            </div>
            <div class="form-grid-3">
              <div class="form-group"><label>Upload Image *</label><input class="form-control" type="file" name="gallery_image" accept="image/jpeg,image/png,image/webp,image/gif"></div>
              <div class="form-group"><label>Caption</label><input class="form-control" name="caption" placeholder="Short description"></div>
              <div class="form-group"><label>Order</label><input class="form-control" type="number" name="display_order" value="0"></div>
            </div>
            <div class="form-group"><label>Image URL fallback</label><input class="form-control" name="image_url" placeholder="Optional: https://... or images/photo.jpg"></div>
            <p class="settings-help">Upload a JPG, PNG, WebP, or GIF up to 4MB. Use the URL field only when the image is already hosted elsewhere.</p>
            <button type="submit" class="btn btn-blue">Add Gallery Item</button>
          </form>
        </div>
        <div class="data-table-wrap">
          <div class="table-scroll">
          <table class="data-table">
            <thead><tr><th>Image</th><th>Title</th><th>Category</th><th>Caption</th><th>Order</th><th>Action</th></tr></thead>
            <tbody>
              <?php if (!$galleryItems): ?><tr><td colspan="6" style="text-align:center;color:var(--gray);padding:2rem">No gallery items yet.</td></tr><?php endif; ?>
              <?php foreach ($galleryItems as $item): ?>
                <?php $galleryFormId = 'gallery-item-' . (int)$item['id']; ?>
                <tr>
                  <td>
                    <img class="thumb" src="<?= e($item['image_url']) ?>" alt="">
                    <input form="<?= e($galleryFormId) ?>" class="form-control table-input" type="file" name="gallery_image" accept="image/jpeg,image/png,image/webp,image/gif">
                    <input form="<?= e($galleryFormId) ?>" class="form-control table-input" name="image_url" value="<?= e($item['image_url']) ?>">
                  </td>
                  <td><input form="<?= e($galleryFormId) ?>" class="form-control table-input" name="title" value="<?= e($item['title']) ?>"></td>
                  <td><input form="<?= e($galleryFormId) ?>" class="form-control table-input" name="category" value="<?= e($item['category']) ?>"></td>
                  <td><textarea form="<?= e($galleryFormId) ?>" class="form-control table-textarea" name="caption"><?= e($item['caption']) ?></textarea></td>
                  <td><input form="<?= e($galleryFormId) ?>" class="form-control table-input" style="min-width:80px" type="number" name="display_order" value="<?= (int)$item['display_order'] ?>"></td>
                  <td>
                    <div class="row-actions">
                      <form id="<?= e($galleryFormId) ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_gallery">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfGallery) ?>">
                        <input type="hidden" name="gallery_id" value="<?= (int)$item['id'] ?>">
                        <button class="save-row-btn" type="submit">Save</button>
                      </form>
                      <form method="POST" onsubmit="return confirm('Remove this gallery item?')">
                        <input type="hidden" name="action" value="delete_gallery">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfGalleryDelete) ?>">
                        <input type="hidden" name="gallery_id" value="<?= (int)$item['id'] ?>">
                        <button class="delete-row-btn" type="submit">Remove</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div>
        </div>
      </section>

      <section class="admin-section" id="publish">
        <div class="admin-section-header"><div><h3>Publish Article</h3><p>Create a news update for the public News page.</p></div></div>
        <div class="publish-card">
          <?php if ($publishMessage): ?><div class="alert alert-success show"><?= e($publishMessage) ?></div><?php endif; ?>
          <?php if ($publishError): ?><div class="alert alert-error show"><?= e($publishError) ?></div><?php endif; ?>
          <form method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="action" value="publish_article">
            <input type="hidden" name="csrf_token" value="<?= e($csrfPublish) ?>">
            <div class="form-group">
              <label>Article Title *</label>
              <input type="text" name="title" class="form-control" placeholder="Enter article title" required>
            </div>
            <div class="form-grid">
              <div class="form-group">
                <label>Category</label>
                <select name="category" class="form-control">
                  <option>Education</option><option>Digital Skills</option><option>Health</option><option>Community</option><option>Partnership</option><option>Announcement</option><option>General</option>
                </select>
              </div>
              <div class="form-group">
                <label>Author Name</label>
                <input type="text" name="author" class="form-control" value="<?= e($_SESSION['admin_name'] ?? 'TSF Admin') ?>">
              </div>
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:0.6rem">
              <input type="checkbox" id="is_featured" name="is_featured" style="width:auto;accent-color:var(--blue)">
              <label for="is_featured" style="margin:0">Mark as featured</label>
            </div>
            <div class="form-group">
              <label>Cover Image</label>
              <input class="form-control" type="file" name="cover_image" accept="image/jpeg,image/png,image/webp,image/gif">
              <p class="settings-help" style="margin:0.45rem 0 0">Upload a JPG, PNG, WebP, or GIF up to 4MB. This image appears with the article on the public News page.</p>
            </div>
            <div class="form-group">
              <label>Article Content *</label>
              <textarea name="content" class="form-control" rows="8" placeholder="Write the full article content here..." required></textarea>
            </div>
            <button type="submit" class="btn btn-blue btn-lg">Publish Article</button>
          </form>
          <?php if ($publishedArticleUrl): ?>
            <div class="alert alert-success show" style="margin-top:1rem">
              Article URL:
              <a href="<?= e($publishedArticleUrl) ?>" target="_blank" rel="noopener" style="color:var(--blue);font-weight:800;word-break:break-all"><?= e($publishedArticleUrl) ?></a>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <section class="admin-section" id="news">
        <div class="admin-section-header"><div><h3>Recent Articles</h3><p>Latest published updates from the news system.</p></div></div>
        <div class="data-table-wrap">
          <div class="table-scroll">
          <table class="data-table">
            <thead><tr><th>Cover</th><th>Title</th><th>Category</th><th>Author</th><th>Date</th></tr></thead>
            <tbody>
              <?php if (!$recentNews): ?><tr><td colspan="5" style="text-align:center;color:var(--gray);padding:2rem">No articles published yet.</td></tr><?php endif; ?>
              <?php foreach ($recentNews as $n): ?>
                <tr>
                  <td><?php if ($n['cover_image']): ?><img class="thumb" src="<?= filter_var($n['cover_image'], FILTER_VALIDATE_URL) ? e($n['cover_image']) : '../' . e($n['cover_image']) ?>" alt=""><?php else: ?><span class="method-badge">No cover</span><?php endif; ?></td>
                  <td><strong><a href="../article.php?slug=<?= e($n['slug']) ?>" target="_blank" rel="noopener" style="color:var(--blue-dark)"><?= e($n['title']) ?></a></strong></td>
                  <td><?= e($n['category']) ?></td>
                  <td><?= e($n['author_name']) ?></td>
                  <td><?= $n['published_at'] ? e(date('d M Y', strtotime($n['published_at']))) : '-' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div>
        </div>
      </section>

      <section class="admin-section" id="messages">
        <div class="admin-section-header"><div><h3>Messages & Enquiries</h3><p>Contact form and floating message submissions.</p></div></div>
        <div class="message-list">
          <?php if (!$messages): ?>
            <div class="publish-card" style="text-align:center;color:var(--gray);padding:2rem">No messages received yet.</div>
          <?php endif; ?>
          <?php foreach ($messages as $m): ?>
            <article class="message-card">
              <div class="message-card-head">
                <div>
                  <h4 style="margin:0 0 0.25rem;color:var(--blue-dark)"><?= e($m['subject'] ?: ucfirst($m['message_type'])) ?></h4>
                  <div class="message-meta">
                    From <strong><?= e($m['name']) ?></strong>
                    - <?= e($m['email'] ?: 'No email provided') ?>
                    - <?= e(date('d M Y, h:i A', strtotime($m['created_at']))) ?>
                  </div>
                </div>
                <span class="method-badge"><?= e(ucfirst($m['message_type'])) ?></span>
              </div>
              <div class="message-body"><?= e($m['message']) ?></div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="admin-section" id="account">
        <div class="admin-section-header"><div><h3>Admin Account & Recovery Email</h3><p>Keep the account name and recovery email current.</p></div></div>
        <div class="publish-card">
          <?php if ($accountMessage): ?><div class="alert alert-success show"><?= e($accountMessage) ?></div><?php endif; ?>
          <?php if ($accountError): ?><div class="alert alert-error show"><?= e($accountError) ?></div><?php endif; ?>
          <p class="settings-help">Password recovery links are sent to this admin email address. Put your personal email here before relying on password recovery.</p>
          <form method="POST">
            <input type="hidden" name="action" value="update_account">
            <input type="hidden" name="csrf_token" value="<?= e($csrfAccount) ?>">
            <div class="form-grid">
              <div class="form-group"><label>Admin Name</label><input class="form-control" name="admin_name" value="<?= e($adminAccount['full_name'] ?? '') ?>"></div>
              <div class="form-group"><label>Recovery Email</label><input class="form-control" type="email" name="admin_email" value="<?= e($adminAccount['email'] ?? '') ?>"></div>
            </div>
            <button type="submit" class="btn btn-blue">Save Admin Account</button>
            <a href="forgot_password.php" class="btn btn-gold" style="margin-left:0.7rem">Test Password Recovery</a>
          </form>
        </div>
      </section>
    </main>
  </div>

  <script>
    function updateAdminTime() {
      const now = new Date();
      const opts = { weekday:'short', day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit' };
      document.getElementById('admin-time').textContent = now.toLocaleDateString('en-GB', opts);
    }
    setInterval(updateAdminTime, 1000);
    updateAdminTime();

    const navLinks = Array.from(document.querySelectorAll('.sidebar-nav a[href^="#"], .dashboard-actions a[href^="#"]'));
    const sideLinks = Array.from(document.querySelectorAll('.sidebar-nav a[href^="#"]'));
    const panels = Array.from(document.querySelectorAll('.admin-main .admin-section'));

    function showPanel(panelId, updateHash = true) {
      const target = document.getElementById(panelId) || document.getElementById('overview');
      panels.forEach(panel => panel.classList.toggle('active-panel', panel === target));
      sideLinks.forEach(link => link.classList.toggle('active', link.getAttribute('href') === `#${target.id}`));
      if (updateHash) history.replaceState(null, '', `#${target.id}`);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    navLinks.forEach(link => {
      link.addEventListener('click', e => {
        const panelId = link.getAttribute('href')?.slice(1);
        if (!panelId) return;
        e.preventDefault();
        showPanel(panelId);
      });
    });

    const initialPanel = window.location.hash?.slice(1) || document.body.dataset.activePanel || 'overview';
    showPanel(initialPanel, false);

    const search = document.getElementById('settings-search');
    const groups = Array.from(document.querySelectorAll('.content-group'));
    if (search) {
      search.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase();
        groups.forEach(group => {
          const match = group.textContent.toLowerCase().includes(query);
          group.hidden = query !== '' && !match;
          if (query && match) group.open = true;
        });
      });
    }

    document.getElementById('expand-settings')?.addEventListener('click', () => {
      groups.forEach(group => { group.hidden = false; group.open = true; });
      if (search) search.value = '';
    });
    document.getElementById('collapse-settings')?.addEventListener('click', () => {
      groups.forEach(group => { group.open = false; });
    });

    const contentFilters = Array.from(document.querySelectorAll('.content-filter'));
    const contentRows = Array.from(document.querySelectorAll('[data-content-type]'));
    contentFilters.forEach(button => {
      button.addEventListener('click', () => {
        const type = button.dataset.type;
        contentFilters.forEach(item => item.classList.remove('active'));
        button.classList.add('active');
        contentRows.forEach(row => {
          row.style.display = type === 'all' || row.dataset.contentType === type ? '' : 'none';
        });
      });
    });

    document.querySelectorAll('form[id^="content-item-"], form[id^="gallery-item-"]').forEach(form => {
      form.addEventListener('submit', () => {
        document.querySelectorAll(`[form="${form.id}"]`).forEach(control => {
          if (!control.name || control.type === 'file') return;
          let hidden = Array.from(form.querySelectorAll('input[type="hidden"][data-row-copy]'))
            .find(input => input.dataset.rowCopy === control.name);
          if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = control.name;
            hidden.dataset.rowCopy = control.name;
            form.appendChild(hidden);
          }
          hidden.value = control.value;
        });
      });
    });
  </script>
</body>
</html>
