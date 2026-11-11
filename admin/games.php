<?php
require __DIR__ . '/_init.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = (string)($_POST['bulk_action'] ?? '');
    $ids = array_values(array_filter(array_map('intval', (array)($_POST['ids'] ?? []))));
    if (!empty($_POST['delete_all'])) {
        foreach (rows('SELECT * FROM games') as $g) delete_game_files($g);
        q('DELETE FROM games');
        flash('All games and apps were deleted.');
    } elseif ($ids && in_array($act, ['delete', 'publish', 'draft'], true)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        if ($act === 'delete') {
            foreach (rows("SELECT * FROM games WHERE id IN ($in)", $ids) as $g) delete_game_files($g);
            q("DELETE FROM games WHERE id IN ($in)", $ids);
            flash(count($ids) . ' item(s) deleted.');
        } else {
            q("UPDATE games SET status = ?, updated_at = NOW() WHERE id IN ($in)", array_merge([$act === 'publish' ? 'published' : 'draft'], $ids));
            flash(count($ids) . ' item(s) updated.');
        }
    } else {
        flash('Please select at least one item and an action.', 'err');
    }
    regenerate_sitemap();
    redirect(url('admin/games.php'));
}
$term = trim((string)($_GET['q'] ?? ''));
$type = (string)($_GET['type'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1)); $per = 100;
$w = []; $p = [];
if ($term !== '') { $w[] = 'g.title LIKE ?'; $p[] = '%' . addcslashes($term, '%_\\') . '%'; }
if ($type === 'game') $w[] = 'g.is_game = 1';
if ($type === 'app') $w[] = 'g.is_app = 1';
$where = $w ? 'WHERE ' . implode(' AND ', $w) : '';
$total = (int)val("SELECT COUNT(*) FROM games g $where", $p);
$all = (int)val('SELECT COUNT(*) FROM games');
$list = rows("SELECT g.*, c.name AS cat FROM games g LEFT JOIN categories c ON c.id = g.category_id $where ORDER BY g.created_at DESC, g.id DESC LIMIT $per OFFSET " . (($page - 1) * $per), $p);
$qs = http_build_query(array_filter(['q' => $term, 'type' => $type]));
admin_head('Games & Apps', 'games'); ?>
<div class="actions"><a class="btn" href="game_edit.php">+ Add New</a><a class="btn alt" href="import.php">⬇ Import from Play Store</a>
<form class="inline" method="get"><input type="search" name="q" placeholder="Search…" value="<?= e($term) ?>">
<select name="type"><option value="">All types</option><option value="game" <?= $type === 'game' ? 'selected' : '' ?>>Games</option><option value="app" <?= $type === 'app' ? 'selected' : '' ?>>Apps</option></select><button class="btn alt">Filter</button></form></div>
<form method="post" id="bulk"><?= csrf_field() ?>
<div class="bulkbar">
  <select name="bulk_action"><option value="">Bulk action…</option><option value="publish">Publish selected</option><option value="draft">Unpublish (draft) selected</option><option value="delete">Delete selected</option></select>
  <button class="btn alt" type="submit" onclick="var a=this.form.bulk_action.value;if(a==='delete')return confirm('Delete the selected items permanently?');return true;">Apply</button>
  <span class="grow"></span>
  <span class="hint"><?= $total ?> shown · <?= $all ?> total</span>
  <button class="btn danger-btn" type="submit" name="delete_all" value="1" onclick="return prompt('This deletes EVERY game and app. Type DELETE to confirm.')==='DELETE';">Delete ALL</button>
</div>
<div class="panel"><div class="tablewrap"><table class="list"><thead><tr><th><input type="checkbox" aria-label="Select all" onclick="document.querySelectorAll('.rowchk').forEach(function(c){c.checked=this.checked}.bind(this))"></th><th></th><th>Title</th><th>Type</th><th>Category</th><th>Status</th><th>Views</th><th>Downloads</th><th>Added</th><th></th></tr></thead><tbody>
<?php foreach ($list as $g): ?><tr>
<td><input class="rowchk" type="checkbox" name="ids[]" value="<?= (int)$g['id'] ?>"></td>
<td><img class="ico" src="<?= e(img_url($g['icon'])) ?>" alt="" width="40" height="40"></td>
<td><a href="game_edit.php?id=<?= (int)$g['id'] ?>"><strong><?= e($g['title']) ?></strong></a></td>
<td><?= e(type_label($g)) ?></td>
<td><?= e($g['cat'] ?? '—') ?></td>
<td><span class="tag <?= e($g['status']) ?>"><?= e($g['status']) ?></span></td>
<td><?= number_format((int)$g['views']) ?></td><td><?= number_format((int)$g['downloads']) ?></td><td><?= e(date('M j, Y', strtotime($g['created_at']))) ?></td>
<td class="act"><a href="game_edit.php?id=<?= (int)$g['id'] ?>">Edit</a> <a href="<?= e(url('game/' . $g['slug'])) ?>" target="_blank" rel="noopener">View</a></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="10">Nothing found.</td></tr><?php endif; ?></tbody></table></div></div>
</form>
<?= paginate_html($total, $per, $page, url('admin/games.php' . ($qs !== '' ? '?' . $qs : ''))) ?>
<?php admin_foot();
