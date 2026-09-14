<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://127.0.0.1:8000');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

echo json_encode([
    'success' => true,
    'service' => 'Smart Wallet API',
    'version' => '1.0.0',
    'status' => 'online',
    'message' => 'API يعمل بنجاح'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
