<?php
require __DIR__ . '/_init.php';
$st = [
    'Games (total)' => (int)val('SELECT COUNT(*) FROM games'),
    'Published' => (int)val("SELECT COUNT(*) FROM games WHERE status = 'published'"),
    'Drafts' => (int)val("SELECT COUNT(*) FROM games WHERE status = 'draft'"),
    'Categories' => (int)val('SELECT COUNT(*) FROM categories'),
    'Pages' => (int)val('SELECT COUNT(*) FROM pages'),
    'Total views' => (int)val('SELECT COALESCE(SUM(views),0) FROM games'),
    'Download clicks' => (int)val('SELECT COALESCE(SUM(downloads),0) FROM games'),
];
$latest = rows('SELECT id, title, status, created_at FROM games ORDER BY created_at DESC LIMIT 6');
$top = rows('SELECT id, title, views, downloads FROM games ORDER BY views DESC LIMIT 6');
admin_head('Dashboard', 'dashboard'); ?>
<div class="stats"><?php foreach ($st as $k => $n): ?><div class="stat"><b><?= number_format($n) ?></b><span><?= e($k) ?></span></div><?php endforeach; ?></div>
<div class="actions"><a class="btn" href="game_edit.php">+ Add Game</a><a class="btn alt" href="import.php">⬇ Import from Play Store</a><a class="btn alt" href="pages.php?edit=0">+ New Page</a><a class="btn alt" href="sitemap.php">🗺 Regenerate Sitemap</a></div>
<div class="two">
<section class="panel"><h2>Latest games</h2><table><?php foreach ($latest as $g): ?><tr><td><a href="game_edit.php?id=<?= (int)$g['id'] ?>"><?= e($g['title']) ?></a></td><td><span class="tag <?= e($g['status']) ?>"><?= e($g['status']) ?></span></td><td><?= e(ago($g['created_at'])) ?></td></tr><?php endforeach; if (!$latest) echo '<tr><td>No games yet.</td></tr>'; ?></table></section>
<section class="panel"><h2>Most viewed</h2><table><?php foreach ($top as $g): ?><tr><td><a href="game_edit.php?id=<?= (int)$g['id'] ?>"><?= e($g['title']) ?></a></td><td><?= number_format((int)$g['views']) ?> views</td><td><?= number_format((int)$g['downloads']) ?> dl</td></tr><?php endforeach; if (!$top) echo '<tr><td>No data yet.</td></tr>'; ?></table></section>
</div>
<?php admin_foot();
