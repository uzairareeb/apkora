<?php
define('ADMIN_PUBLIC', true);
require __DIR__ . '/_init.php';
if (is_admin()) redirect(url('admin/index.php'));
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_SESSION['fails'] ?? 0) >= 5 && time() - ($_SESSION['fail_t'] ?? 0) < 300) {
        $err = 'Too many failed attempts. Please try again in 5 minutes.';
    } else {
        $a = row('SELECT * FROM admins WHERE username = ?', [trim((string)($_POST['username'] ?? ''))]);
        if ($a && password_verify((string)($_POST['password'] ?? ''), $a['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$a['id'];
            $_SESSION['fails'] = 0;
            redirect(url('admin/index.php'));
        }
        $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1; $_SESSION['fail_t'] = time();
        sleep(1);
        $err = 'Incorrect username or password.';
    }
}
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title>Admin Login</title><link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>?v=1"></head>
<body class="loginpage"><form method="post" class="loginbox"><h1>🎮 <?= e(setting('site_name', 'Admin')) ?></h1><p>Admin Login</p>
<?php if (!empty($_GET['installed'])): ?><div class="flash ok">Setup complete! Please log in.</div><?php endif; ?>
<?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
<?= csrf_field() ?>
<label>Username</label><input type="text" name="username" required autofocus>
<label>Password</label><input type="password" name="password" required>
<button class="btn" type="submit">Login</button></form></body></html>
