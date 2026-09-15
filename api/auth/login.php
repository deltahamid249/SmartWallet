<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiJson([
        'success' => false,
        'message' => 'طريقة الطلب غير مدعومة'
    ], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    apiJson([
        'success' => false,
        'message' => 'بيانات الطلب غير صالحة'
    ], 400);
}

$phone = trim((string) ($input['phone'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($phone === '' || $password === '') {
    apiJson([
        'success' => false,
        'message' => 'رقم الهاتف وكلمة المرور مطلوبان'
    ], 422);
}

$stmt = $pdo->prepare(
    'SELECT id, full_name, username, phone, email, password_hash, status, role
     FROM users
     WHERE phone = :phone
     LIMIT 1'
);

$stmt->execute([
    ':phone' => $phone,
]);

$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    apiJson([
        'success' => false,
        'message' => 'رقم الهاتف أو كلمة المرور غير صحيحة'
    ], 401);
}

if ($user['status'] !== 'active') {
    apiJson([
        'success' => false,
        'message' => 'الحساب غير نشط حاليًا'
    ], 403);
}

$token = generateApiToken($pdo, (int) $user['id']);

unset($user['password_hash']);

apiJson([
    'success' => true,
    'message' => 'تم تسجيل الدخول بنجاح',
    'token' => $token,
    'token_type' => 'Bearer',
    'expires_in' => 30 * 24 * 60 * 60,
    'user' => $user,
]);
