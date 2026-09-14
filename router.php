<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? rawurldecode($path) : '/';

if (
    str_contains($path, '..')
    || preg_match('/\.(sql|env|log|ini|bak|backup)$/i', $path)
    || str_starts_with($path, '/config/')
) {
    http_response_code(404);
    exit('Not found');
}

$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

return false;