<?php

// A stand-in site for the smoke.sh test (PRD 11 E7), served by `php -S`. SMOKE_MODE=good answers like the pilot;
// SMOKE_MODE=bad forgets X-Frame-Options.

$mode = getenv('SMOKE_MODE') ?: 'good';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('X-Robots-Tag: noindex, nofollow');
if ($mode === 'good') {
    header('X-Frame-Options: SAMEORIGIN');
}

if ($path === '/robots.txt') {
    header('Content-Type: text/plain');
    echo "User-agent: *\nDisallow: /\n";

    return true;
}

if (! in_array($path, ['/', '/login', '/register', '/privasi', '/syarat', '/up'], true)) {
    http_response_code(404);
}

echo 'ok';

return true;
