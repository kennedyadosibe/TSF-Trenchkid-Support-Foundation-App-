<?php
define('TSF_LOADED', true);
require_once __DIR__ . '/BACKEND/connect.php';

$slug = trim($_GET['slug'] ?? '');
$article = null;

if ($slug !== '') {
    $stmt = getDB()->prepare(
        'SELECT title, slug, content, category, author_name, cover_image, published_at
         FROM news
         WHERE slug = ? AND is_published = 1
         LIMIT 1'
    );
    $stmt->execute([$slug]);
    $article = $stmt->fetch();
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function articleImage(?string $path): string {
    if (!$path) {
        return 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?w=1200&q=80';
    }
    return $path;
}

function articleDate($value): string {
    return $value ? date('d M Y', strtotime($value)) : '';
}

$pageTitle = $article ? $article['title'] . ' - TSF' : 'Article Not Found - TSF';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" href="images/tsf-logo.png">
  <link rel="stylesheet" href="css/style.css">
  <style>
    .article-hero { min-height: 420px; display: flex; align-items: flex-end; background-size: cover; background-position: center; position: relative; color: var(--white); }
    .article-hero::before { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(7,24,68,0.38), rgba(7,24,68,0.86)); }
    .article-hero-inner { position: relative; z-index: 1; width: min(980px, calc(100% - 2rem)); margin: 0 auto; padding: 7rem 0 3rem; }
    .article-hero .breadcrumb { color: rgba(255,255,255,0.82); margin-bottom: 1rem; }
    .article-hero .breadcrumb a { color: var(--gold); }
    .article-hero h1 { font-family: 'Playfair Display', serif; font-size: clamp(2rem, 5vw, 4rem); line-height: 1.08; margin: 0 0 1rem; }
    .article-meta { display: flex; gap: 0.8rem; flex-wrap: wrap; align-items: center; color: rgba(255,255,255,0.86); }
    .article-cat { background: var(--gold); color: var(--blue-dark); border-radius: 999px; padding: 0.25rem 0.8rem; font-size: 0.76rem; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; }
    .article-wrap { width: min(880px, calc(100% - 2rem)); margin: 0 auto; padding: 3rem 0 4rem; }
    .article-body { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); padding: clamp(1.4rem, 4vw, 2.6rem); }
    .article-body p { color: var(--text); line-height: 1.9; margin-bottom: 1.1rem; font-size: 1rem; }
    .article-actions { display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-top: 1.5rem; }
    .not-found { min-height: 70vh; display: grid; place-items: center; padding: 2rem; text-align: center; }
    .not-found-card { width: min(560px, 100%); background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); padding: 2rem; }
  </style>
</head>
<body>
  <?php if ($article): ?>
    <header class="article-hero" style="background-image: url('<?= e(articleImage($article['cover_image'] ?? '')) ?>')">
      <div class="article-hero-inner">
        <div class="breadcrumb"><a href="index.html">Home</a> &gt; <a href="news.html">News</a> &gt; Article</div>
        <h1><?= e($article['title']) ?></h1>
        <div class="article-meta">
          <span class="article-cat"><?= e($article['category'] ?: 'General') ?></span>
          <span><?= e(articleDate($article['published_at'])) ?></span>
          <span>By <?= e($article['author_name'] ?: 'TSF Communications') ?></span>
        </div>
      </div>
    </header>

    <main class="article-wrap">
      <article class="article-body">
        <?php foreach (preg_split("/\R{2,}/", trim($article['content'])) as $paragraph): ?>
          <?php if (trim($paragraph) !== ''): ?><p><?= nl2br(e(trim($paragraph))) ?></p><?php endif; ?>
        <?php endforeach; ?>
        <div class="article-actions">
          <a class="btn btn-blue" href="news.html">Back to News</a>
          <a class="btn btn-gold" href="donate.html">Support TSF</a>
        </div>
      </article>
    </main>
  <?php else: ?>
    <main class="not-found">
      <div class="not-found-card">
        <h1 style="font-family:'Playfair Display',serif;color:var(--blue-dark);margin-bottom:0.8rem">Article Not Found</h1>
        <p style="color:var(--gray);line-height:1.7;margin-bottom:1.4rem">The article link may be invalid, unpublished, or expired.</p>
        <a class="btn btn-blue" href="news.html">Go to News</a>
      </div>
    </main>
  <?php endif; ?>
  <script src="js/time.js"></script>
  <script src="js/components.js"></script>
</body>
</html>
