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

$stmt = $pdo->prepare(
    'SELECT balance, currency
     FROM wallets
     WHERE user_id = :user_id
     LIMIT 1'
);

$stmt->execute([
    ':user_id' => (int) $user['user_id'],
]);

$wallet = $stmt->fetch();

if (!$wallet) {
    apiJson([
        'success' => false,
        'message' => 'المحفظة غير موجودة'
    ], 404);
}

apiJson([
    'success' => true,
    'wallet' => [
        'balance' => $wallet['balance'],
        'currency' => $wallet['currency'],
    ],
]);
