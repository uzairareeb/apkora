<?php
require __DIR__ . '/_init.php';
$edit = (int)($_GET['edit'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete' && $id) { q('DELETE FROM faqs WHERE id = ?', [$id]); flash('FAQ deleted.'); }
    else {
        $qn = trim((string)$_POST['question']); $an = trim((string)$_POST['answer']);
        if ($qn === '' || $an === '') flash('Please enter both a question and an answer.', 'err');
        else {
            if ($id) q('UPDATE faqs SET question=?, answer=?, sort_order=? WHERE id=?', [$qn, $an, (int)$_POST['sort_order'], $id]);
            else q('INSERT INTO faqs (question, answer, sort_order) VALUES (?,?,?)', [$qn, $an, (int)$_POST['sort_order']]);
            flash('FAQ saved.');
        }
    }
    redirect(url('admin/faqs.php'));
}
$cur = $edit ? row('SELECT * FROM faqs WHERE id = ?', [$edit]) : null;
$list = rows('SELECT * FROM faqs ORDER BY sort_order, id');
admin_head('FAQs (homepage)', 'faqs'); ?>
<div class="cols"><div class="col-main"><div class="panel"><table class="list"><thead><tr><th>Question</th><th>Order</th><th></th></tr></thead><tbody>
<?php foreach ($list as $f): ?><tr><td><?= e($f['question']) ?></td><td><?= (int)$f['sort_order'] ?></td><td class="act"><a href="?edit=<?= (int)$f['id'] ?>">Edit</a><form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><button name="action" value="delete" class="link danger" onclick="return confirm('Delete?')">Delete</button></form></td></tr>
<?php endforeach; if (!$list): ?><tr><td colspan="3">No FAQs yet.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="col-side"><form method="post" class="panel"><?= csrf_field() ?><h2><?= $cur ? 'Edit FAQ' : 'Add FAQ' ?></h2><input type="hidden" name="id" value="<?= (int)($cur['id'] ?? 0) ?>">
<label>Question</label><input type="text" name="question" value="<?= e($cur['question'] ?? '') ?>" required>
<label>Answer</label><textarea name="answer" rows="5" required><?= e($cur['answer'] ?? '') ?></textarea>
<label>Order</label><input type="text" name="sort_order" value="<?= (int)($cur['sort_order'] ?? count($list)) ?>">
<button class="btn full" type="submit">Save</button><?php if ($cur): ?><p><a href="faqs.php">+ Add new instead</a></p><?php endif; ?></form></div></div>
<?php admin_foot();
