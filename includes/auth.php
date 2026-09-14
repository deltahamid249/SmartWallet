<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/**
 * هل المستخدم مسجل الدخول؟
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'])
        && (int) $_SESSION['user_id'] > 0;
}

/**
 * الحصول على رقم المستخدم الحالي.
 */
function currentUserId(): ?int
{
    return isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : null;
}

/**
 * هل المستخدم الحالي مدير؟
 */
function isAdmin(): bool
{
    if (!isLoggedIn()) {
        return false;
    }

    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT role, status FROM users WHERE id = :user_id LIMIT 1'
    );

    $stmt->execute([
        ':user_id' => currentUserId(),
    ]);

    $user = $stmt->fetch();

    if (!$user) {
        return false;
    }

    return $user['status'] === 'active'
        && $user['role'] === 'admin';
}

/**
 * إجبار المستخدم على تسجيل الدخول.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }

    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT status FROM users WHERE id = :user_id LIMIT 1'
    );

    $stmt->execute([
        ':user_id' => currentUserId(),
    ]);

    $status = $stmt->fetchColumn();

    if ($status !== 'active') {
        logoutUser();
        header('Location: /login.php');
        exit;
    }
}

/**
 * إجبار المستخدم على أن يكون مديرًا.
 */
function requireAdmin(): void
{
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }

    if (!isAdmin()) {
        http_response_code(403);

        echo '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>غير مصرح</title>
    <style>
        body {
            margin: 0;
            padding: 40px 20px;
            background: #f5f7fb;
            font-family: Arial, sans-serif;
            text-align: center;
            color: #111827;
        }

        .box {
            max-width: 500px;
            margin: 80px auto;
            background: #fff;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0,0,0,.08);
        }

        h1 {
            margin-top: 0;
            color: #dc2626;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 20px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>غير مصرح</h1>
        <p>ليس لديك صلاحية للوصول إلى لوحة الإدارة.</p>
        <a href="/">العودة للرئيسية</a>
    </div>
</body>
</html>';

        exit;
    }
}

/**
 * تسجيل الدخول.
 */
function loginUser(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

/**
 * تسجيل الخروج.
 */
function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
