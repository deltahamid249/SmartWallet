<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && !verifyCsrf($_POST['_csrf'] ?? null)
) {
    http_response_code(400);
    exit('طلب تسجيل الخروج غير صالح.');
}

logoutUser();

header('Location: login.php');
exit;
