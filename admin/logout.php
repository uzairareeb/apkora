<?php
define('ADMIN_PUBLIC', true);
require __DIR__ . '/_init.php';
$_SESSION = [];
session_destroy();
redirect(url('admin/login.php'));
