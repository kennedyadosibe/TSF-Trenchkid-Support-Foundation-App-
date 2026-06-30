<?php
define('TSF_LOADED', true);
require_once __DIR__ . '/../BACKEND/connect.php';
require_once __DIR__ . '/../BACKEND/content_definitions.php';
requireAdminAuth();

$pdo = getDB();
$publishMessage = '';
$publishError = '';
$settingsMessage = '';
$settingsError = '';
$galleryMessage = '';
$galleryError = '';
$accountMessage = '';
$accountError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'publish_article') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '', 'publish')) {
        $publishError = 'Security validation failed. Please try again.';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $category = sanitize($_POST['category'] ?? 'General');
        $author = sanitize($_POST['author'] ?? ($_SESSION['admin_name'] ?? 'TSF Admin'));
        $featured = !empty($_POST['is_featured']) ? 1 : 0;
        $allowed = ['Education','Digital Skills','Health','Community','Partnership','Announcement','General'];
        if (!in_array($category, $allowed, true)) $category = 'General';

        if (strlen($title) < 5) {
            $publishError = 'Title must be at least 5 characters.';
        } elseif (strlen($content) < 30) {
            $publishError = 'Content must be at least 30 characters.';
        } else {
            $slug = strtolower(trim($title));
            $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
            $slug = preg_replace('/[\s-]+/', '-', $slug);
            $slug = trim($slug, '-') . '-' . substr(uniqid(), -6);

            $stmt = $pdo->prepare(
                'INSERT INTO news (title, slug, content, category, author_name, author_id, is_published, is_featured, published_at)
                 VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())'
            );
            $stmt->execute([$title, $slug, $content, $category, $author ?: 'TSF Admin', $_SESSION['admin_id'], $featured]);
            $publishMessage = 'Article published successfully.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_settings') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '', 'settings')) {
        $settingsError = 'Security validation failed. Please try again.';
    } else {
        $allowedSettings = getContentSettingKeys();
        $stmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        foreach ($allowedSettings as $key) {
            $stmt->execute([$key, trim($_POST[$key] ?? '')]);
        }
        $settingsMessage = 'Site content updated successfully.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_gallery') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '', 'gallery')) {
        $galleryError = 'Security validation failed. Please try again.';
    } else {
        $title = sanitize($_POST['title'] ?? '');
        $imageUrl = trim($_POST['image_url'] ?? '');
        $caption = trim($_POST['caption'] ?? '');
        $category = sanitize($_POST['category'] ?? 'General');
        $order = (int)($_POST['display_order'] ?? 0);
        if ($title === '' || $imageUrl === '') {
            $galleryError = 'Gallery title and image URL are required.';
        } elseif (!filter_var($imageUrl, FILTER_VALIDATE_URL) && !preg_match('/^images\\//', $imageUrl)) {
            $galleryError = 'Use a valid image URL or an images/ path.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO gallery (title, caption, image_url, category, display_order, is_active) VALUES (?, ?, ?, ?, ?, 1)');
            $stmt->execute([$title, $caption, $imageUrl, $category ?: 'General', $order]);
            $galleryMessage = 'Gallery item added.';
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
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_account') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '', 'account')) {
        $accountError = 'Security validation failed. Please try again.';
    } else {
        $email = filter_var(trim($_POST['admin_email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $name = sanitize($_POST['admin_name'] ?? '');
        if (!$email) {
            $accountError = 'Enter a valid recovery email.';
        } else {
            $stmt = $pdo->prepare('UPDATE admin SET email = ?, full_name = ? WHERE id = ?');
            $stmt->execute([$email, $name ?: 'TSF Administrator', $_SESSION['admin_id']]);
            $_SESSION['admin_name'] = $name ?: 'TSF Administrator';
            $accountMessage = 'Admin account updated.';
        }
    }
}

$csrfPublish = generateCsrfToken('publish');
$csrfSettings = generateCsrfToken('settings');
$csrfGallery = generateCsrfToken('gallery');
$csrfGalleryDelete = generateCsrfToken('gallery_delete');
$csrfAccount = generateCsrfToken('account');
$settingsRows = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
$contentDefinitions = getContentDefinitions();
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
    'SELECT title, category, author_name, published_at FROM news ORDER BY created_at DESC LIMIT 8'
)->fetchAll();
$messages = $pdo->query(
    'SELECT name, email, subject, message_type, message, created_at FROM messages ORDER BY created_at DESC LIMIT 50'
)->fetchAll();
$galleryItems = $pdo->query(
    'SELECT id, title, caption, image_url, category, display_order, created_at FROM gallery WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC'
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
    body { background: #f0f3fc; }
    .admin-header { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; background: linear-gradient(135deg, var(--blue-dark), var(--blue)); padding: 0 1.5rem; height: 62px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 20px rgba(0,0,0,0.25); }
    .admin-header-brand { display: flex; align-items: center; gap: 0.8rem; }
    .admin-header-brand img { height: 38px; }
    .admin-header-brand span { font-family: 'Playfair Display', serif; font-size: 1.05rem; font-weight: 700; color: var(--white); }
    .admin-header-brand small { font-size: 0.7rem; color: var(--gold-light); display: block; letter-spacing: 1px; text-transform: uppercase; }
    .admin-header-right { display: flex; align-items: center; gap: 1rem; }
    .admin-user { display: flex; align-items: center; gap: 0.6rem; font-size: 0.88rem; color: var(--white); }
    .admin-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--gold); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; color: var(--blue-dark); }
    .logout-btn { background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); color: var(--white); padding: 0.35rem 0.9rem; border-radius: 20px; font-size: 0.82rem; cursor: pointer; font-family: 'DM Sans', sans-serif; text-decoration: none; }
    .admin-layout { display: flex; padding-top: 62px; min-height: 100vh; }
    .admin-sidebar { width: 230px; background: var(--white); box-shadow: 2px 0 20px rgba(26,63,163,0.08); position: fixed; top: 62px; left: 0; bottom: 0; overflow-y: auto; padding: 1.5rem 0; }
    .sidebar-nav { list-style: none; }
    .sidebar-nav li { margin: 0.15rem 0.8rem; }
    .sidebar-nav a { display: flex; align-items: center; gap: 0.7rem; padding: 0.7rem 1rem; border-radius: 10px; font-size: 0.9rem; font-weight: 500; color: var(--gray); text-decoration: none; transition: var(--transition); }
    .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(26,63,163,0.1); color: var(--blue); font-weight: 700; }
    .sidebar-section-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: var(--gray); padding: 0.8rem 1.8rem 0.3rem; }
    .admin-main { margin-left: 230px; flex: 1; padding: 2rem; }
    .dashboard-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.2rem; margin-bottom: 2rem; }
    .dash-stat { background: var(--white); border-radius: 14px; padding: 1.4rem; box-shadow: 0 4px 20px rgba(26,63,163,0.08); display: flex; align-items: center; gap: 1rem; }
    .dash-stat-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; background: rgba(26,63,163,0.1); color: var(--blue); font-weight: 900; }
    .dash-stat-num { font-family: 'Playfair Display', serif; font-size: 1.8rem; font-weight: 900; color: var(--blue-dark); line-height: 1; }
    .dash-stat-label { font-size: 0.8rem; color: var(--gray); margin-top: 0.3rem; }
    .admin-main .admin-section { padding: 0; margin-bottom: 2rem; }
    .admin-section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.2rem; }
    .admin-section-header h3 { font-family: 'Playfair Display', serif; font-size: 1.2rem; color: var(--blue-dark); }
    .data-table-wrap, .publish-card { background: var(--white); border-radius: 14px; box-shadow: 0 4px 20px rgba(26,63,163,0.08); overflow: hidden; }
    .publish-card { padding: 2rem; }
    .data-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
    .data-table th { background: rgba(26,63,163,0.05); padding: 0.8rem 1rem; text-align: left; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 1.2px; color: var(--blue); font-weight: 700; border-bottom: 2px solid var(--gray-light); }
    .data-table td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--gray-light); color: var(--text); vertical-align: top; }
    .amount-cell { font-weight: 700; color: #276749; }
    .method-badge { font-size: 0.75rem; background: var(--gray-light); padding: 0.2rem 0.6rem; border-radius: 6px; }
    .status-ok { color: #276749; font-weight: 700; }
    .status-wait { color: var(--gold-dark); font-weight: 700; }
    .msg-content { max-width: 360px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--gray); }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 120px; gap: 1rem; }
    .content-group { border: 1px solid var(--gray-light); border-radius: 14px; padding: 1.2rem; margin-bottom: 1.2rem; background: #fbfcff; }
    .content-group h4 { color: var(--blue-dark); font-size: 1rem; margin-bottom: 1rem; font-family: 'Playfair Display', serif; }
    textarea.form-control { resize: vertical; }
    .settings-help { color: var(--gray); font-size: 0.86rem; margin-bottom: 1.2rem; line-height: 1.6; }
    .thumb { width: 72px; height: 54px; object-fit: cover; border-radius: 8px; background: var(--gray-light); }
    .admin-time { font-size: 0.8rem; color: rgba(255,255,255,0.7); background: rgba(255,255,255,0.08); padding: 0.3rem 0.8rem; border-radius: 15px; }
    @media(max-width:1100px) { .dashboard-stats { grid-template-columns: repeat(2,1fr); } }
    @media(max-width:768px) { .admin-sidebar { display: none; } .admin-main { margin-left: 0; padding: 1rem; } .dashboard-stats, .form-grid, .form-grid-3 { grid-template-columns: 1fr; } .admin-time { display: none; } }
  </style>
</head>
<body>
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
        <li><a class="active" href="#overview">Dashboard</a></li>
        <li class="sidebar-section-label">Donations</li>
        <li><a href="#donors">Donor Records</a></li>
        <li class="sidebar-section-label">Content</li>
        <li><a href="#settings">Site Settings</a></li>
        <li><a href="#gallery">Gallery</a></li>
        <li><a href="#publish">Publish Article</a></li>
        <li class="sidebar-section-label">Messages</li>
        <li><a href="#messages">Messages</a></li>
        <li class="sidebar-section-label">Account</li>
        <li><a href="#account">Admin Account</a></li>
        <li class="sidebar-section-label">Site</li>
        <li><a href="../index.html" target="_blank">View Website</a></li>
      </ul>
    </aside>

    <main class="admin-main">
      <section class="admin-section" id="overview">
        <div style="margin-bottom:1.5rem">
          <h2 style="font-family:'Playfair Display',serif;font-size:1.6rem;color:var(--blue-dark)">Welcome back, Admin</h2>
          <p style="color:var(--gray);font-size:0.9rem">Here is what is happening with TSF today.</p>
        </div>
        <div class="dashboard-stats">
          <div class="dash-stat"><div class="dash-stat-icon">D</div><div><div class="dash-stat-num"><?= (int)$summary['total_donors'] ?></div><div class="dash-stat-label">Verified Donors</div></div></div>
          <div class="dash-stat"><div class="dash-stat-icon">G</div><div><div class="dash-stat-num">GHS <?= number_format((float)$summary['total_raised'], 2) ?></div><div class="dash-stat-label">Verified Raised</div></div></div>
          <div class="dash-stat"><div class="dash-stat-icon">N</div><div><div class="dash-stat-num"><?= $articleCount ?></div><div class="dash-stat-label">Published Articles</div></div></div>
          <div class="dash-stat"><div class="dash-stat-icon">M</div><div><div class="dash-stat-num"><?= $messageCount ?></div><div class="dash-stat-label">Messages Received</div></div></div>
        </div>
      </section>

      <section class="admin-section" id="donors">
        <div class="admin-section-header"><h3>Donor Records</h3></div>
        <div class="data-table-wrap">
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
      </section>

      <section class="admin-section" id="settings">
        <div class="admin-section-header"><h3>Site Settings</h3></div>
        <div class="publish-card">
          <?php if ($settingsMessage): ?><div class="alert alert-success show"><?= e($settingsMessage) ?></div><?php endif; ?>
          <?php if ($settingsError): ?><div class="alert alert-error show"><?= e($settingsError) ?></div><?php endif; ?>
          <p class="settings-help">Edit public site text, contact details, and social links here. The public pages load these values from the backend, so you do not need to touch code for ordinary content updates.</p>
          <form method="POST">
            <input type="hidden" name="action" value="save_settings">
            <input type="hidden" name="csrf_token" value="<?= e($csrfSettings) ?>">
            <?php foreach ($contentDefinitions as $groupName => $fields): ?>
              <div class="content-group">
                <h4><?= e($groupName) ?></h4>
                <div class="form-grid">
                  <?php foreach ($fields as $key => $field): ?>
                    <div class="form-group">
                      <label for="<?= e($key) ?>"><?= e($field['label']) ?></label>
                      <?php if (($field['type'] ?? 'text') === 'textarea'): ?>
                        <textarea class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" rows="3"><?= e($settings[$key] ?? '') ?></textarea>
                      <?php else: ?>
                        <input class="form-control" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($settings[$key] ?? '') ?>">
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-blue btn-lg">Save Site Settings</button>
          </form>
        </div>
      </section>

      <section class="admin-section" id="gallery">
        <div class="admin-section-header"><h3>Gallery Manager</h3></div>
        <div class="publish-card" style="margin-bottom:1.2rem">
          <?php if ($galleryMessage): ?><div class="alert alert-success show"><?= e($galleryMessage) ?></div><?php endif; ?>
          <?php if ($galleryError): ?><div class="alert alert-error show"><?= e($galleryError) ?></div><?php endif; ?>
          <form method="POST">
            <input type="hidden" name="action" value="add_gallery">
            <input type="hidden" name="csrf_token" value="<?= e($csrfGallery) ?>">
            <div class="form-grid">
              <div class="form-group"><label>Photo Title *</label><input class="form-control" name="title" placeholder="e.g. School supplies outreach"></div>
              <div class="form-group"><label>Category</label><input class="form-control" name="category" placeholder="Education"></div>
            </div>
            <div class="form-grid-3">
              <div class="form-group"><label>Image URL or images/ path *</label><input class="form-control" name="image_url" placeholder="https://... or images/photo.jpg"></div>
              <div class="form-group"><label>Caption</label><input class="form-control" name="caption" placeholder="Short description"></div>
              <div class="form-group"><label>Order</label><input class="form-control" type="number" name="display_order" value="0"></div>
            </div>
            <button type="submit" class="btn btn-blue">Add Gallery Item</button>
          </form>
        </div>
        <div class="data-table-wrap">
          <table class="data-table">
            <thead><tr><th>Image</th><th>Title</th><th>Category</th><th>Order</th><th>Action</th></tr></thead>
            <tbody>
              <?php if (!$galleryItems): ?><tr><td colspan="5" style="text-align:center;color:var(--gray);padding:2rem">No gallery items yet.</td></tr><?php endif; ?>
              <?php foreach ($galleryItems as $item): ?>
                <tr>
                  <td><img class="thumb" src="<?= e($item['image_url']) ?>" alt=""></td>
                  <td><strong><?= e($item['title']) ?></strong><br><span style="color:var(--gray);font-size:0.8rem"><?= e($item['caption']) ?></span></td>
                  <td><?= e($item['category']) ?></td>
                  <td><?= (int)$item['display_order'] ?></td>
                  <td>
                    <form method="POST" onsubmit="return confirm('Remove this gallery item?')">
                      <input type="hidden" name="action" value="delete_gallery">
                      <input type="hidden" name="csrf_token" value="<?= e($csrfGalleryDelete) ?>">
                      <input type="hidden" name="gallery_id" value="<?= (int)$item['id'] ?>">
                      <button class="logout-btn" type="submit" style="color:#9b1c1c">Remove</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section class="admin-section" id="publish">
        <div class="admin-section-header"><h3>Publish Article</h3></div>
        <div class="publish-card">
          <?php if ($publishMessage): ?><div class="alert alert-success show"><?= e($publishMessage) ?></div><?php endif; ?>
          <?php if ($publishError): ?><div class="alert alert-error show"><?= e($publishError) ?></div><?php endif; ?>
          <form method="POST" novalidate>
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
              <label>Article Content *</label>
              <textarea name="content" class="form-control" rows="8" placeholder="Write the full article content here..." required></textarea>
            </div>
            <button type="submit" class="btn btn-blue btn-lg">Publish Article</button>
          </form>
        </div>
      </section>

      <section class="admin-section" id="news">
        <div class="admin-section-header"><h3>Recent Articles</h3></div>
        <div class="data-table-wrap">
          <table class="data-table">
            <thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Date</th></tr></thead>
            <tbody>
              <?php if (!$recentNews): ?><tr><td colspan="4" style="text-align:center;color:var(--gray);padding:2rem">No articles published yet.</td></tr><?php endif; ?>
              <?php foreach ($recentNews as $n): ?>
                <tr><td><strong><?= e($n['title']) ?></strong></td><td><?= e($n['category']) ?></td><td><?= e($n['author_name']) ?></td><td><?= $n['published_at'] ? e(date('d M Y', strtotime($n['published_at']))) : '-' ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section class="admin-section" id="messages">
        <div class="admin-section-header"><h3>Messages & Enquiries</h3></div>
        <div class="data-table-wrap">
          <table class="data-table">
            <thead><tr><th>Name</th><th>Email</th><th>Subject / Type</th><th>Message</th><th>Date</th></tr></thead>
            <tbody>
              <?php if (!$messages): ?><tr><td colspan="5" style="text-align:center;color:var(--gray);padding:2rem">No messages received yet.</td></tr><?php endif; ?>
              <?php foreach ($messages as $m): ?>
                <tr><td><strong><?= e($m['name']) ?></strong></td><td><?= e($m['email'] ?: '-') ?></td><td><?= e($m['subject'] ?: $m['message_type']) ?></td><td class="msg-content"><?= e($m['message']) ?></td><td><?= e(date('d M Y', strtotime($m['created_at']))) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <section class="admin-section" id="account">
        <div class="admin-section-header"><h3>Admin Account & Recovery Email</h3></div>
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
  </script>
</body>
</html>
