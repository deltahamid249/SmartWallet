<?php
declare(strict_types=1);

/**
 * المصادقة والجلسات.
 *
 * يجهز هذا الملف اتصال قاعدة البيانات، يبدأ جلسة المستخدم، ويتحقق من
 * الصلاحيات قبل السماح بفتح الصفحات المحمية أو صفحات الإدارة.
 */

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
 * رقم حساب مدير النظام الرئيسي.
 */
const SYSTEM_ADMIN_ID = 9;

/**
 * هل الحساب الحالي هو مدير النظام الرئيسي؟
 */
function isSystemAdmin(): bool
{
    return isLoggedIn()
        && currentUserId() === SYSTEM_ADMIN_ID;
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
        && $user['role'] === 'admin'
        && (int) currentUserId() === SYSTEM_ADMIN_ID;
}

/**
 * شريط التنقل الموحد للصفحات المحمية.
 *
 * يتم حقنه تلقائيًا في نهاية الصفحة:
 * - زر رجوع.
 * - زر الرئيسية.
 * - زر تسجيل الخروج.
 */
function registerGlobalNavigation(): void
{
    if (!isLoggedIn()) {
        return;
    }

    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');

    /*
     * لا نضيف شريط HTML إلى API.
     */
    if (str_contains($scriptName, '/api/')) {
        return;
    }

    register_shutdown_function(
        static function (): void {
            if (!isLoggedIn()) {
                return;
            }

            $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');

            if (str_contains($scriptName, '/api/')) {
                return;
            }

            echo str_replace(
                [
                    '__SMART_WALLET_HOME_URL__',
                    '__SMART_WALLET_LOGOUT_URL__',
                ],
                [
                    e(appUrl('/index.php')),
                    e(appUrl('/logout.php')),
                ],
                <<<'HTML'
<style id="smart-wallet-global-navigation">
    .sw-global-navigation {
        position: fixed;
        right: 14px;
        left: 14px;
        bottom: 14px;
        z-index: 99999;

        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 9px;

        max-width: 620px;
        margin: 0 auto;

        padding: 9px;

        background: rgba(255, 255, 255, 0.97);
        border: 1px solid #dbe3ef;
        border-radius: 18px;

        box-shadow:
            0 12px 35px rgba(15, 23, 42, 0.16);

        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }

    .sw-global-navigation a,
    .sw-global-navigation button {
        min-height: 46px;

        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;

        border: 0;
        border-radius: 12px;

        text-decoration: none;

        font-family: Arial, Tahoma, sans-serif;
        font-size: 14px;
        font-weight: 800;

        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
    }

    .sw-nav-back {
        background: #eef4ff;
        color: #0f3d91;
    }

    .sw-nav-home {
        background: #f1f5f9;
        color: #111827;
    }

    .sw-nav-logout {
        background: #fff1f2;
        color: #b91c1c;
    }

    .sw-global-navigation a:active,
    .sw-global-navigation button:active {
        transform: scale(0.97);
    }

    @media (min-width: 700px) {
        .sw-global-navigation {
            right: 20px;
            left: auto;
            width: 420px;
        }
    }

    /*
     * مساحة أسفل الصفحة حتى لا يغطي شريط التنقل
     * آخر محتوى في الصفحات الطويلة.
     */
    body {
        padding-bottom: 88px !important;
    }
</style>

<nav
    class="sw-global-navigation"
    aria-label="التنقل العام"
>
    <button
        type="button"
        class="sw-nav-back"
        onclick="smartWalletGoBack()"
    >
        ← رجوع
    </button>

    <a
        href="__SMART_WALLET_HOME_URL__"
        class="sw-nav-home"
    >
        🏠 الرئيسية
    </a>

    <a
        href="__SMART_WALLET_LOGOUT_URL__"
        class="sw-nav-logout"
    >
        🚪 تسجيل الخروج
    </a>
</nav>

<script>
function smartWalletGoBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '__SMART_WALLET_HOME_URL__';
    }
}
</script>
HTML
            );
        }
    );
}

/**
 * إجبار المستخدم على تسجيل الدخول.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirectTo('/login.php');
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

        redirectTo('/login.php');
    }

    registerGlobalNavigation();
}

/**
 * إجبار المستخدم على أن يكون مديرًا.
 */
function requireAdmin(): void
{
    if (!isLoggedIn()) {
        redirectTo('/login.php');
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
        <a href="' . e(appUrl('/index.php')) . '">العودة للرئيسية</a>
    </div>
</body>
</html>';

        exit;
    }

    registerGlobalNavigation();
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
