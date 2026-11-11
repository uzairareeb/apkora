<?php
// Database settings. Local: fill in below. Cloud: set DB_HOST, DB_NAME, DB_USER, DB_PASS env vars.
// TiDB Cloud (MySQL-compatible, free): DB_PORT=4000, DB_SSL=1. Render: set these in the dashboard.
// Setup wizard (/setup) overwrites this file with the values you enter there (host only; port/ssl stay env-driven).
return [
    'db_host' => getenv('DB_HOST') ?: 'localhost',
    'db_port' => getenv('DB_PORT') ?: '3306',
    'db_name' => getenv('DB_NAME') ?: '',
    'db_user' => getenv('DB_USER') ?: '',
    'db_pass' => getenv('DB_PASS') ?: '',
    'db_ssl'  => getenv('DB_SSL') ?: '',
];
