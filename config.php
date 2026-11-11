<?php
// Database settings. Local: fill in below. Cloud/Vercel: set DB_HOST, DB_NAME, DB_USER, DB_PASS env vars.
// Setup wizard (/setup) overwrites this file with the values you enter there.
return [
    'db_host' => getenv('DB_HOST') ?: 'localhost',
    'db_name' => getenv('DB_NAME') ?: '',
    'db_user' => getenv('DB_USER') ?: '',
    'db_pass' => getenv('DB_PASS') ?: '',
];
