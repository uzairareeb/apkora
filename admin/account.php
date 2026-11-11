<?php
require __DIR__ . '/_init.php';
$me = row('SELECT * FROM admins WHERE id = ?', [(int)$_SESSION['admin_id']]);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $user = trim((string)$_POST['username']); $new = (string)$_POST['new_password'];
    if (!password_verify((string)$_POST['current_password'], $me['password_hash'])) flash('Current password is incorrect.', 'err');
    elseif (!preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $user)) flash('Username must be 3-40 characters (letters, numbers, _ . -).', 'err');
    elseif ($new !== '' && strlen($new) < 8) flash('New password must be at least 8 characters.', 'err');
    elseif ($new !== '' && $new !== (string)$_POST['new_password2']) flash('New passwords do not match.', 'err');
    else {
        q('UPDATE admins SET username = ?' . ($new !== '' ? ', password_hash = ?' : '') . ' WHERE id = ?', $new !== '' ? [$user, password_hash($new, PASSWORD_DEFAULT), $me['id']] : [$user, $me['id']]);
        flash('Account updated.');
    }
    redirect(url('admin/account.php'));
}
admin_head('Account', 'account'); ?>
<form method="post" class="panel form narrow"><?= csrf_field() ?>
<label>Username</label><input type="text" name="username" value="<?= e($me['username']) ?>" required>
<label>New password <small>(leave empty to keep the current one)</small></label><input type="password" name="new_password" autocomplete="new-password">
<label>Confirm new password</label><input type="password" name="new_password2" autocomplete="new-password">
<label>Current password *</label><input type="password" name="current_password" required>
<button class="btn" type="submit">Update</button></form>
<?php admin_foot();
