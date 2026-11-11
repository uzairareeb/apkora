<?php
require __DIR__ . '/_init.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (['header_code', 'footer_code', 'ads_txt', 'robots_extra'] as $k) set_setting($k, (string)($_POST[$k] ?? ''));
    flash('Saved.');
    redirect(url('admin/code.php'));
}
$s = settings(true);
admin_head('Header / Footer Code & ads.txt', 'code'); ?>
<form method="post" class="panel form narrow"><?= csrf_field() ?>
<label>Header code <small>(&lt;head&gt; ke andar: Google Analytics, Search Console verification meta tag, AdSense script)</small></label>
<textarea name="header_code" rows="8" class="mono" placeholder="<meta name=&quot;google-site-verification&quot; content=&quot;...&quot;>"><?= e($s['header_code'] ?? '') ?></textarea>
<label>Footer code <small>(placed just before &lt;/body&gt;)</small></label>
<textarea name="footer_code" rows="6" class="mono"><?= e($s['footer_code'] ?? '') ?></textarea>
<label>ads.txt <small>(served automatically at yoursite.com/ads.txt – paste the line AdSense gives you, e.g. google.com, pub-XXXX, DIRECT, f08c47fec0942fa0)</small></label>
<textarea name="ads_txt" rows="4" class="mono"><?= e($s['ads_txt'] ?? '') ?></textarea>
<label>robots.txt extra lines <small>(optional)</small></label>
<textarea name="robots_extra" rows="3" class="mono"><?= e($s['robots_extra'] ?? '') ?></textarea>
<p class="hint">Note: only paste trusted code here – it runs on every page.</p>
<button class="btn" type="submit">Save</button></form>
<?php admin_foot();
