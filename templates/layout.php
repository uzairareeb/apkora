<?php
$siteName = setting('site_name', 'Apkora');
$tagline = setting('tagline', '');
$title = !empty($meta['title']) ? $meta['title'] . ' | ' . $siteName : $siteName . ($tagline !== '' ? ' – ' . $tagline : '');
$desc = $meta['description'] ?? setting('meta_description', $tagline);
$canon = $meta['canonical'] ?? site_url(ltrim($GLOBALS['route_path'] ?? '', '/'));
$ogImg = $meta['image'] ?? (setting('logo') !== '' ? img_url(setting('logo')) : '');
$footPages = rows("SELECT title, slug FROM pages WHERE status = 'published' AND show_in_footer = 1 ORDER BY sort_order, title");
$allCats = rows('SELECT name, slug FROM categories ORDER BY sort_order, name LIMIT 12');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<?php if (setting('keywords') !== ''): ?><meta name="keywords" content="<?= e(setting('keywords')) ?>"><?php endif; ?>
<meta name="theme-color" content="#0b0b10">
<link rel="canonical" href="<?= e($canon) ?>">
<?php if (!empty($meta['noindex'])): ?><meta name="robots" content="noindex, follow"><?php else: ?><meta name="robots" content="index, follow, max-image-preview:large"><?php endif; ?>
<meta property="og:type" content="<?= !empty($meta['og_type']) ? e($meta['og_type']) : 'website' ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($canon) ?>">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<?php if ($ogImg !== ''): ?><meta property="og:image" content="<?= e($ogImg) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<?php if (setting('favicon') !== ''): ?><link rel="icon" href="<?= e(img_url(setting('favicon'))) ?>"><?php else: ?><link rel="icon" href="<?= e(url('assets/img/placeholder.svg')) ?>" type="image/svg+xml"><?php endif; ?>
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>?v=1">
<?php if (!empty($meta['jsonld'])): foreach ((array)$meta['jsonld'] as $j): ?>
<script type="application/ld+json"><?= json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endforeach; endif; ?>
<?= setting('header_code') ?>
</head>
<body>
<header class="site-header">
  <div class="wrap bar">
    <a class="brand" href="<?= e(url('')) ?>">
      <?php if (setting('logo') !== ''): ?><img src="<?= e(img_url(setting('logo'))) ?>" alt="<?= e($siteName) ?>" height="36"><?php else: ?><span class="brand-mark">▶</span><?php endif; ?>
      <span class="brand-name"><?= e($siteName) ?></span>
    </a>
    <button class="nav-toggle" aria-label="Menu" aria-expanded="false">☰</button>
    <nav class="main-nav" id="mainNav">
      <a href="<?= e(url('')) ?>">Home</a>
      <a href="<?= e(url('browse/popular')) ?>">Popular</a>
      <a href="<?= e(url('browse/games')) ?>">Games</a>
      <a href="<?= e(url('browse/apps')) ?>">Apps</a>
    </nav>
    <form class="search" action="<?= e(url('search')) ?>" method="get" role="search">
      <input type="search" name="q" placeholder="Search games…" aria-label="Search games" value="<?= e($_GET['q'] ?? '') ?>">
    </form>
  </div>
</header>
<main><?= $content ?></main>
<footer class="site-footer">
  <div class="wrap foot-grid">
    <div>
      <h4><?= e($siteName) ?></h4>
      <p><?= e(setting('footer_text', $tagline)) ?></p>
      <?php if (setting('contact_email') !== ''): ?><p><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></p><?php endif; ?>
    </div>
    <div>
      <h4>Categories</h4>
      <ul><?php foreach ($allCats as $c): ?><li><a href="<?= e(url('category/' . $c['slug'])) ?>"><?= e($c['name']) ?></a></li><?php endforeach; ?></ul>
    </div>
    <div>
      <h4>Quick Links</h4>
      <ul>
        <li><a href="<?= e(url('browse/popular')) ?>">Popular</a></li>
        <li><a href="<?= e(url('browse/games')) ?>">Games</a></li>
        <li><a href="<?= e(url('browse/apps')) ?>">Apps</a></li>
        <?php foreach ($footPages as $p): ?><li><a href="<?= e(url('p/' . $p['slug'])) ?>"><?= e($p['title']) ?></a></li><?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="copy">
    <p>© <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved. All trademarks, game and app names belong to their respective owners.</p>
    <a class="devby" href="https://www.youtube.com/@PROEDU76" target="_blank" rel="noopener">Developed by Pro EDU</a>
  </div>
</footer>
<script src="<?= e(url('assets/js/app.js')) ?>?v=1" defer></script>
<?= setting('footer_code') ?>
</body>
</html>
