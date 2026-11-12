<?php
// The one-time seed for Apkora has already run. This endpoint is retired.
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
exit('Gone — seeding is complete.');
