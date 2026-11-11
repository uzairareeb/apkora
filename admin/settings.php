<?php
require __DIR__ . '/_init.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $text = ['site_name', 'tagline', 'keywords', 'meta_description', 'contact_email', 'footer_text'];
    foreach ($text as $k) set_setting($k, trim((string)($_POST[$k] ?? '')));
    set_setting('site_url', rtrim(trim((string)($_POST['site_url'] ?? '')), '/'));
    set_setting('home_count', (string)max(4, min(24, (int)($_POST['home_count'] ?? 8))));
    foreach (['logo' => 'logo_file', 'favicon' => 'favicon_file'] as $k => $fk) {
        $val = trim((string)($_POST[$k] ?? ''));
        if (!empty($_FILES[$fk]['name'])) { $u = save_upload($_FILES[$fk]); if ($u) $val = $u; else flash('Upload failed: ' . $k, 'err'); }
        set_setting($k, $val);
    }
    regenerate_sitemap();
    flash('Settings saved.');
    redirect(url('admin/settings.php'));
}
$s = settings(true);
$g = fn($k, $d = '') => e($s[$k] ?? $d);
admin_head('Website Settings', 'settings'); ?>
<form method="post" enctype="multipart/form-data" class="panel form narrow"><?= csrf_field() ?>
<label>Website name</label><input type="text" name="site_name" value="<?= $g('site_name') ?>" required>
<label>Tagline</label><input type="text" name="tagline" value="<?= $g('tagline') ?>">
<label>Meta description (homepage)</label><textarea name="meta_description" rows="2" maxlength="300"><?= $g('meta_description') ?></textarea>
<label>Keywords / tags (comma separated)</label><input type="text" name="keywords" value="<?= $g('keywords') ?>">
<label>Contact email</label><input type="email" name="contact_email" value="<?= $g('contact_email') ?>">
<label>Footer text</label><textarea name="footer_text" rows="2"><?= $g('footer_text') ?></textarea>
<label>Site URL <small>(used for canonical links and the sitemap, e.g. https://example.com – no trailing slash)</small></label><input type="text" name="site_url" value="<?= $g('site_url') ?>">
<label>Items per homepage section (4–24)</label><input type="text" name="home_count" value="<?= $g('home_count', '12') ?>">
<label>Website logo <small>(URL or upload)</small></label><input type="text" name="logo" value="<?= $g('logo') ?>"><input type="file" name="logo_file" accept="image/*">
<p class="hint"><strong>Recommended size: 240 × 60 px</strong> (4:1 ratio, shown at 38 px height – works on mobile and desktop). Use PNG, WebP or SVG with a transparent background. The header is black, so use a white or light-coloured logo. Leave empty to show the website name as text.</p>
<?php if ($g('logo') !== ''): ?><div class="logoprev"><img src="<?= e(img_url($s['logo'])) ?>" alt="" height="38"></div><?php endif; ?>
<label>Favicon <small>(URL or upload)</small></label><input type="text" name="favicon" value="<?= $g('favicon') ?>"><input type="file" name="favicon_file" accept="image/*">
<p class="hint"><strong>Recommended size: 512 × 512 px</strong> (square PNG, minimum 192 × 192). Browsers shrink it automatically for tabs and phone home screens.</p>
<button class="btn" type="submit">Save Settings</button></form>
<?php admin_foot();
