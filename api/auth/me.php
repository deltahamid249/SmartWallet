<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    apiJson([
        'success' => false,
        'message' => 'طريقة الطلب غير مدعومة'
    ], 405);
}

$user = requireApiUser($pdo);

apiJson([
    'success' => true,
    'user' => [
        'id' => (int) $user['user_id'],
        'full_name' => $user['full_name'],
        'username' => $user['username'],
        'phone' => $user['phone'],
        'email' => $user['email'],
        'status' => $user['status'],
        'role' => $user['role'],
    ],
]);
