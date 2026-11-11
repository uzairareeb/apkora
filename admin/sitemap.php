<?php
require __DIR__ . '/_init.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $ok = regenerate_sitemap();
    flash($ok ? 'Sitemap regenerated.' : 'Could not write sitemap.xml (permission denied) – the dynamic sitemap will still work.', $ok ? 'ok' : 'err');
    redirect(url('admin/sitemap.php'));
}
$n = 1 + 3 + (int)val("SELECT COUNT(*) FROM games WHERE status='published'") + (int)val('SELECT COUNT(*) FROM categories') + (int)val("SELECT COUNT(*) FROM pages WHERE status='published'");
$file = ROOT . '/sitemap.xml';
admin_head('Sitemap', 'sitemap'); ?>
<div class="panel narrow"><p>Sitemap URL: <a href="<?= e(site_url('sitemap.xml')) ?>" target="_blank" rel="noopener"><?= e(site_url('sitemap.xml')) ?></a></p>
<p>Total URLs: <strong><?= $n ?></strong><?= is_file($file) ? ' · Last generated: <strong>' . e(date('M j, Y H:i', filemtime($file))) . '</strong>' : '' ?></p>
<form method="post"><?= csrf_field() ?><button class="btn" type="submit">🔄 Regenerate Sitemap</button></form>
<p class="hint">The sitemap also updates automatically whenever you save or delete a game, page or category. Submit <code>sitemap.xml</code> in Google Search Console &gt; Sitemaps. The sitemap is not linked publicly on the website.</p></div>
<?php admin_foot();
