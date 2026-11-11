<?php
require __DIR__ . '/_init.php';
$edit = (int)($_GET['edit'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? 'save';
    $id = (int)($_POST['id'] ?? 0);
    if ($act === 'delete' && $id) {
        q('UPDATE games SET category_id = NULL WHERE category_id = ?', [$id]);
        q('DELETE FROM categories WHERE id = ?', [$id]);
        flash('Category deleted (its items are now uncategorised).');
    } else {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') flash('Please enter a category name.', 'err');
        else {
            $slug = unique_slug('categories', slugify(trim((string)$_POST['slug']) !== '' ? (string)$_POST['slug'] : $name), $id);
            $desc = trim((string)$_POST['description']); $ord = (int)$_POST['sort_order'];
            if ($id) q('UPDATE categories SET name = ?, slug = ?, description = ?, sort_order = ? WHERE id = ?', [$name, $slug, $desc, $ord, $id]);
            else q('INSERT INTO categories (name, slug, description, sort_order) VALUES (?, ?, ?, ?)', [$name, $slug, $desc, $ord]);
            flash('Category saved.'); regenerate_sitemap();
        }
    }
    redirect(url('admin/categories.php'));
}
$cur = $edit ? row('SELECT * FROM categories WHERE id = ?', [$edit]) : null;
$list = rows('SELECT c.*, (SELECT COUNT(*) FROM games g WHERE g.category_id = c.id) AS n FROM categories c ORDER BY sort_order, name');
admin_head('Categories', 'categories'); ?>
<div class="cols"><div class="col-main"><div class="panel"><table class="list"><thead><tr><th>Name</th><th>Slug</th><th>Games</th><th>Order</th><th></th></tr></thead><tbody>
<?php foreach ($list as $c): ?><tr><td><strong><?= e($c['name']) ?></strong></td><td><?= e($c['slug']) ?></td><td><?= (int)$c['n'] ?></td><td><?= (int)$c['sort_order'] ?></td>
<td class="act"><a href="?edit=<?= (int)$c['id'] ?>">Edit</a><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button name="action" value="delete" class="link danger" onclick="return confirm('Category delete karni hai?')">Delete</button></form></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="5">No categories yet.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="col-side"><form method="post" class="panel"><?= csrf_field() ?><h2><?= $cur ? 'Edit category' : 'Add category' ?></h2><input type="hidden" name="id" value="<?= (int)($cur['id'] ?? 0) ?>">
<label>Name</label><input type="text" name="name" value="<?= e($cur['name'] ?? '') ?>" required>
<label>Slug</label><input type="text" name="slug" value="<?= e($cur['slug'] ?? '') ?>">
<label>Description (optional)</label><textarea name="description" rows="3"><?= e($cur['description'] ?? '') ?></textarea>
<label>Sort order</label><input type="text" name="sort_order" value="<?= (int)($cur['sort_order'] ?? 0) ?>">
<button class="btn full" type="submit">Save</button><?php if ($cur): ?><p><a href="categories.php">+ Add new instead</a></p><?php endif; ?></form></div></div>
<?php admin_foot();
