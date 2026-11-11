<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
if (!is_file(ROOT . '/storage/installed.lock')) redirect(url('setup/'));
migrate();
if (!defined('ADMIN_PUBLIC')) require_admin();
header('X-Robots-Tag: noindex, nofollow');

function admin_head(string $title, string $active = ''): void {
    $nav = [
        'dashboard' => ['index.php', '📊 Dashboard'], 'games' => ['games.php', '🎮 Games'], 'import' => ['import.php', '⬇ Import (Play Store)'],
        'categories' => ['categories.php', '🗂 Categories'], 'pages' => ['pages.php', '📄 Pages'], 'faqs' => ['faqs.php', '❓ FAQs'],
        'settings' => ['settings.php', '⚙ Settings'], 'code' => ['code.php', '🧩 Code & ads.txt'], 'sitemap' => ['sitemap.php', '🗺 Sitemap'], 'account' => ['account.php', '👤 Account'],
    ];
    ?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> – Admin</title><link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>?v=1"></head><body>
<div class="layout">
<aside class="side"><div class="logo">🎮 <?= e(setting('site_name', 'Admin')) ?></div>
<nav><?php foreach ($nav as $k => $n): ?><a class="<?= $k === $active ? 'on' : '' ?>" href="<?= e(url('admin/' . $n[0])) ?>"><?= $n[1] ?></a><?php endforeach; ?>
<a href="<?= e(url('')) ?>" target="_blank" rel="noopener">🌐 View Site</a><a href="<?= e(url('admin/logout.php')) ?>">🚪 Logout</a></nav></aside>
<main class="content"><h1><?= e($title) ?></h1>
<?php foreach (flashes() as $f): ?><div class="flash <?= e($f[0]) ?>"><?= e($f[1]) ?></div><?php endforeach;
}
function admin_foot(): void { ?></main></div></body></html><?php }
