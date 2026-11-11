<?php
require __DIR__ . '/_init.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete' && $id) { q('DELETE FROM pages WHERE id = ?', [$id]); flash('Page deleted.'); regenerate_sitemap(); redirect(url('admin/pages.php')); }
    $title = trim((string)$_POST['title']);
    if ($title === '') { flash('Title is required.', 'err'); redirect(url('admin/pages.php?edit=' . $id)); }
    $slug = unique_slug('pages', slugify(trim((string)$_POST['slug']) !== '' ? (string)$_POST['slug'] : $title), $id);
    $f = [$title, $slug, (string)$_POST['content'], mb_substr(trim((string)$_POST['meta_desc']), 0, 300), !empty($_POST['show_in_footer']) ? 1 : 0, ($_POST['status'] ?? '') === 'draft' ? 'draft' : 'published', (int)$_POST['sort_order']];
    if ($id) { q('UPDATE pages SET title=?, slug=?, content=?, meta_desc=?, show_in_footer=?, status=?, sort_order=?, updated_at=NOW() WHERE id=?', array_merge($f, [$id])); }
    else { q('INSERT INTO pages (title, slug, content, meta_desc, show_in_footer, status, sort_order, created_at, updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())', $f); $id = (int)db()->lastInsertId(); }
    regenerate_sitemap(); flash('Page saved. It appears in the footer automatically when "Show in footer" is on.');
    redirect(url('admin/pages.php?edit=' . $id));
}
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $p = $id ? row('SELECT * FROM pages WHERE id = ?', [$id]) : null;
    $d = $p ?: ['id' => 0, 'title' => '', 'slug' => '', 'content' => '', 'meta_desc' => '', 'show_in_footer' => 1, 'status' => 'published', 'sort_order' => 10];
    admin_head($p ? 'Edit Page' : 'New Page', 'pages'); ?>
<form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$d['id'] ?>"><div class="cols"><div class="col-main"><div class="panel">
<label>Title *</label><input type="text" name="title" value="<?= e($d['title']) ?>" required>
<label>Slug <small>(URL: /p/slug)</small></label><input type="text" name="slug" value="<?= e($d['slug']) ?>">
<label>Content (HTML)</label><textarea name="content" rows="18" class="mono"><?= e($d['content']) ?></textarea>
<label>Meta description</label><textarea name="meta_desc" rows="2" maxlength="300"><?= e($d['meta_desc']) ?></textarea></div></div>
<div class="col-side"><div class="panel"><h2>Publish</h2><label>Status</label><select name="status"><option value="published" <?= $d['status'] === 'published' ? 'selected' : '' ?>>Published</option><option value="draft" <?= $d['status'] === 'draft' ? 'selected' : '' ?>>Draft</option></select>
<label class="check"><input type="checkbox" name="show_in_footer" value="1" <?= $d['show_in_footer'] ? 'checked' : '' ?>> Show in footer</label>
<label>Footer order</label><input type="text" name="sort_order" value="<?= (int)$d['sort_order'] ?>">
<button class="btn full" type="submit">Save Page</button><?php if ($p): ?><p><a href="<?= e(url('p/' . $p['slug'])) ?>" target="_blank" rel="noopener">View page →</a></p><?php endif; ?></div></div></div></form>
<?php admin_foot(); exit; }
$list = rows('SELECT * FROM pages ORDER BY sort_order, title');
admin_head('Pages', 'pages'); ?>
<div class="actions"><a class="btn" href="?edit=0">+ New Page</a></div>
<div class="panel"><table class="list"><thead><tr><th>Title</th><th>URL</th><th>Status</th><th>Footer</th><th></th></tr></thead><tbody>
<?php foreach ($list as $p): ?><tr><td><a href="?edit=<?= (int)$p['id'] ?>"><strong><?= e($p['title']) ?></strong></a></td><td>/p/<?= e($p['slug']) ?></td><td><span class="tag <?= e($p['status']) ?>"><?= e($p['status']) ?></span></td><td><?= $p['show_in_footer'] ? 'Yes' : 'No' ?></td>
<td class="act"><a href="?edit=<?= (int)$p['id'] ?>">Edit</a><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button name="action" value="delete" class="link danger" onclick="return confirm('Page delete karna hai?')">Delete</button></form></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php admin_foot();
