<?php
// Vercel entry point (community vercel-php runtime). Forwards every request to the main front controller.
// LIMIT: admin/*.php and setup/*.php are direct PHP files - Vercel only executes api/*.php, so those
// routes will NOT work on Vercel without a full refactor. See README-DEPLOY.md.
chdir(dirname(__DIR__));
$_SERVER['SCRIPT_NAME'] = '/index.php';
require dirname(__DIR__) . '/index.php';
