<?php
require __DIR__ . '/_init.php';
$id = (int)($_GET['id'] ?? 0);
$g = $id ? row('SELECT * FROM games WHERE id = ?', [$id]) : null;
if ($id && !$g) { flash('Item not found.', 'err'); redirect(url('admin/games.php')); }
$cats = rows('SELECT id, name FROM categories ORDER BY sort_order, name');
$d = $g ?: ['title' => '', 'slug' => '', 'short_desc' => '', 'description' => '', 'extra_content' => '', 'icon' => '', 'screenshots' => '', 'category_id' => null,
    'is_game' => 1, 'is_app' => 0, 'developer' => '', 'version' => '', 'size' => '', 'installs' => '', 'rating' => '0', 'download_url' => '', 'source_url' => '', 'meta_title' => '', 'meta_desc' => '', 'status' => 'published'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $t = trim((string)($_POST['title'] ?? ''));
    if ($t === '') { flash('Title is required.', 'err'); }
    else {
        $slug = slugify(trim((string)($_POST['slug'] ?? '')) !== '' ? (string)$_POST['slug'] : $t);
        $slug = unique_slug('games', $slug, $id);
        $icon = trim((string)($_POST['icon'] ?? ''));
        if (!empty($_FILES['icon_file']['name'])) { $u = save_upload($_FILES['icon_file']); if ($u) $icon = $u; else flash('Icon upload failed (jpg/png/webp/gif, max 4MB).', 'err'); }
        $shots = array_values(array_filter(array_map('trim', preg_split('/\R/', (string)($_POST['screenshots'] ?? '')))));
        if (!empty($_FILES['shot_files']['name'][0])) {
            foreach ($_FILES['shot_files']['name'] as $i => $nm) {
                if ($nm === '') continue;
                $u = save_upload(['tmp_name' => $_FILES['shot_files']['tmp_name'][$i], 'error' => $_FILES['shot_files']['error'][$i], 'size' => $_FILES['shot_files']['size'][$i]]);
                if ($u) $shots[] = $u;
            }
        }
        $cat = (int)($_POST['category_id'] ?? 0);
        if ($cat && !val('SELECT id FROM categories WHERE id = ?', [$cat])) $cat = 0;
        if (trim((string)($_POST['new_category'] ?? '')) !== '') $cat = get_or_create_category((string)$_POST['new_category']);
        $isGame = !empty($_POST['is_game']) ? 1 : 0; $isApp = !empty($_POST['is_app']) ? 1 : 0;
        if (!$isGame && !$isApp) $isGame = 1;
        $f = [
            'title' => $t, 'slug' => $slug, 'short_desc' => mb_substr(trim((string)$_POST['short_desc']), 0, 300), 'description' => trim((string)$_POST['description']),
            'extra_content' => (string)$_POST['extra_content'], 'icon' => $icon, 'screenshots' => implode("\n", $shots), 'category_id' => $cat ?: null,
            'developer' => trim((string)$_POST['developer']), 'version' => trim((string)$_POST['version']), 'size' => trim((string)$_POST['size']),
            'installs' => trim((string)$_POST['installs']), 'rating' => max(0, min(5, round((float)$_POST['rating'], 1))),
            'download_url' => clean_url((string)$_POST['download_url']), 'source_url' => clean_url((string)$_POST['source_url']),
            'meta_title' => mb_substr(trim((string)$_POST['meta_title']), 0, 200), 'meta_desc' => mb_substr(trim((string)$_POST['meta_desc']), 0, 300),
            'is_game' => $isGame, 'is_app' => $isApp,
            'status' => ($_POST['status'] ?? '') === 'draft' ? 'draft' : 'published',
        ];
        if ($id) {
            $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($f)));
            $f['id'] = $id;
            q("UPDATE games SET $set, updated_at = NOW() WHERE id = :id", $f);
        } else {
            $cols = implode(', ', array_keys($f)); $ph = implode(', ', array_map(fn($k) => ":$k", array_keys($f)));
            q("INSERT INTO games ($cols, created_at, updated_at) VALUES ($ph, NOW(), NOW())", $f);
            $id = (int)db()->lastInsertId();
        }
        regenerate_sitemap();
        flash('Saved.');
        redirect(url('admin/game_edit.php?id=' . $id));
    }
    $d = array_merge($d, $_POST);
}
admin_head($g ? 'Edit Game / App' : 'Add Game / App', 'games'); ?>
<form method="post" enctype="multipart/form-data" class="form"><?= csrf_field() ?>
<div class="cols">
<div class="col-main">
  <div class="panel"><label>Title *</label><input type="text" name="title" value="<?= e($d['title']) ?>" required>
  <label>Slug (URL) <small>leave empty to generate from the title</small></label><input type="text" name="slug" value="<?= e($d['slug']) ?>">
  <label>Short description <small>(used on cards and as meta description, max 300 characters)</small></label><textarea name="short_desc" rows="2" maxlength="300"><?= e($d['short_desc']) ?></textarea>
  <label>Description</label><textarea name="description" rows="10"><?= e($d['description']) ?></textarea>
  <label>Extra content (HTML) <small>Features, how to install, your own review – write original content here, it helps with AdSense approval</small></label><textarea name="extra_content" rows="9" class="mono"><?= e($d['extra_content']) ?></textarea></div>
  <div class="panel"><h2>Images</h2>
  <label>Icon / Logo (URL ya upload)</label><input type="text" name="icon" value="<?= e($d['icon']) ?>" placeholder="uploads/xyz.png ya https://…">
  <input type="file" name="icon_file" accept="image/*"><?php if ($d['icon'] !== ''): ?><br><img class="prev" src="<?= e(img_url($d['icon'])) ?>" alt="" width="72" height="72"><?php endif; ?>
  <label>Screenshots <small>one image path/URL per line</small></label><textarea name="screenshots" rows="4" class="mono"><?= e($d['screenshots']) ?></textarea>
  <label>Upload screenshots (multiple)</label><input type="file" name="shot_files[]" accept="image/*" multiple></div>
</div>
<div class="col-side">
  <div class="panel"><h2>Publish</h2>
  <label>Status</label><select name="status"><option value="published" <?= $d['status'] === 'published' ? 'selected' : '' ?>>Published</option><option value="draft" <?= $d['status'] === 'draft' ? 'selected' : '' ?>>Draft</option></select>
  <label>Category <small>(optional)</small></label><select name="category_id"><option value="0">— No category —</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$d['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
  <label>Or create a new category</label><input type="text" name="new_category" placeholder="e.g. Racing">
  <label>This is a… <small>(tick one or both)</small></label>
  <label class="check"><input type="checkbox" name="is_game" value="1" <?= (int)$d['is_game'] ? 'checked' : '' ?>> Game</label>
  <label class="check"><input type="checkbox" name="is_app" value="1" <?= (int)$d['is_app'] ? 'checked' : '' ?>> App</label>
  <button class="btn full" type="submit">Save</button>
  <?php if ($g): ?><p><a href="<?= e(url('game/' . $g['slug'])) ?>" target="_blank" rel="noopener">View on site →</a></p><?php endif; ?></div>
  <div class="panel"><h2>Details</h2>
  <label>Developer</label><input type="text" name="developer" value="<?= e($d['developer']) ?>">
  <label>Version</label><input type="text" name="version" value="<?= e($d['version']) ?>">
  <label>Size (e.g. 85 MB)</label><input type="text" name="size" value="<?= e($d['size']) ?>">
  <label>Installs (e.g. 10M+)</label><input type="text" name="installs" value="<?= e($d['installs']) ?>">
  <label>Rating (0–5)</label><input type="text" name="rating" value="<?= e($d['rating']) ?>"></div>
  <div class="panel"><h2>Download link</h2>
  <label>Download / redirect URL</label><input type="text" name="download_url" value="<?= e($d['download_url']) ?>" placeholder="https://play.google.com/…">
  <label>Source URL <small>(optional, the link used for import)</small></label><input type="text" name="source_url" value="<?= e($d['source_url']) ?>"></div>
  <div class="panel"><h2>SEO</h2>
  <label>Meta title</label><input type="text" name="meta_title" value="<?= e($d['meta_title']) ?>">
  <label>Meta description</label><textarea name="meta_desc" rows="3" maxlength="300"><?= e($d['meta_desc']) ?></textarea></div>
</div></div></form>
<?php admin_foot();
