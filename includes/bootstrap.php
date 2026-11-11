<?php
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
if (session_status() === PHP_SESSION_NONE) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $https, 'path' => '/']);
    session_name('gh_sess');
    session_start();
}
mb_internal_encoding('UTF-8');
$GLOBALS['cfg'] = is_file(ROOT . '/config.php') ? (require ROOT . '/config.php') : [];
require __DIR__ . '/functions.php';
require __DIR__ . '/playstore.php';
