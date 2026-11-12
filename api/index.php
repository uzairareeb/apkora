<?php
// Vercel entry point (community vercel-php runtime). Forwards every request to the main front controller.
// Admin pages are served through the front controller's /admin router (index.php).
chdir(dirname(__DIR__));
$_SERVER['SCRIPT_NAME'] = '/index.php';
require dirname(__DIR__) . '/index.php';
