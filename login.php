<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$ipAddress = clientIp();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['_csrf'] ?? null)) {
        $error = 'انتهت صلاحية النموذج. أعد تحميل الصفحة وحاول مرة أخرى.';
    }

    $loginIdentifier = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($error === '' && ($loginIdentifier === '' || $password === '')) {
        $error = 'يرجى إدخال اسم المستخدم أو رقم الهاتف وكلمة المرور.';
    }

    if ($error === '') {
        try {
            $rateStmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM login_attempts
                 WHERE success = 0
                   AND created_at >= (CURRENT_TIMESTAMP - INTERVAL 15 MINUTE)
                   AND (phone = :phone OR ip_address = :ip_address)'
            );

            $rateStmt->execute([
                ':phone' => $loginIdentifier,
                ':ip_address' => $ipAddress,
            ]);

            if ((int) $rateStmt->fetchColumn() >= 5) {
                throw new RuntimeException(
                    'تم تجاوز عدد المحاولات. حاول بعد 15 دقيقة.'
                );
            }

            $stmt = $pdo->prepare(
                'SELECT id, password_hash, status, role
                 FROM users
                 WHERE username = :username
                    OR phone = :phone
                 LIMIT 1'
            );

            $stmt->execute([
                ':username' => $loginIdentifier,
                ':phone' => $loginIdentifier,
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $error = 'اسم المستخدم أو رقم الهاتف أو كلمة المرور غير صحيحة.';
            } elseif ($user['status'] !== 'active') {
                $error = 'هذا الحساب غير نشط.';
            } else {
                loginUser((int) $user['id']);

                $attempt = $pdo->prepare(
                    'INSERT INTO login_attempts
                    (user_id, phone, ip_address, success)
                    VALUES
                    (:user_id, :phone, :ip_address, 1)'
                );

                $attempt->execute([
                    ':user_id' => (int) $user['id'],
                    ':phone' => $loginIdentifier,
                    ':ip_address' => $ipAddress,
                ]);

                if (
                    ($user['role'] ?? '') === 'admin'
                    && (int) $user['id'] === SYSTEM_ADMIN_ID
                ) {
                    header('Location: admin/index.php');
                } else {
                    header('Location: index.php');
                }

                exit;
            }

            $attempt = $pdo->prepare(
                'INSERT INTO login_attempts
                (user_id, phone, ip_address, success)
                VALUES
                (:user_id, :phone, :ip_address, 0)'
            );

            $attempt->execute([
                ':user_id' => $user ? (int) $user['id'] : null,
                ':phone' => $loginIdentifier,
                ':ip_address' => $ipAddress,
            ]);
        } catch (Throwable $e) {
            error_log($e->getMessage());

            $error = $e instanceof PDOException
                ? 'حدث خطأ أثناء تسجيل الدخول.'
                : (
                    $e instanceof RuntimeException
                        ? $e->getMessage()
                        : 'حدث خطأ أثناء تسجيل الدخول.'
                );
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>تسجيل الدخول - Smart Wallet</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Tahoma, sans-serif;
            background: #f5f7fb;
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 430px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 22px;
            padding: 30px 24px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }

        .logo {
            width: 72px;
            height: 72px;
            margin: 0 auto 18px;
            border-radius: 20px;
            background: #2563eb;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            font-weight: 900;
        }

        h1 {
            text-align: center;
            margin: 0 0 8px;
            font-size: 26px;
            font-weight: 900;
        }

        .subtitle {
            text-align: center;
            color: #6b7280;
            margin: 0 0 26px;
            font-size: 14px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 800;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 14px;
            margin-bottom: 18px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            font-size: 16px;
            outline: none;
            background: #ffffff;
        }

        input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 14px;
            background: #2563eb;
            color: #ffffff;
            font-size: 16px;
            font-weight: 900;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 18px;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.6;
        }

        .footer {
            text-align: center;
            margin-top: 18px;
            color: #6b7280;
            font-size: 13px;
        }
    </style>
</head>

<body>

<div class="login-container">

    <div class="login-card">

        <div class="logo">₿</div>

        <h1>المحفظة الذكية</h1>

        <p class="subtitle">
            تسجيل الدخول إلى حسابك
        </p>

        <?php if ($error !== ''): ?>
            <div class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <?= csrfField() ?>

            <label for="phone">
                اسم المستخدم أو رقم الهاتف
            </label>

            <input
                type="text"
                inputmode="text"
                id="phone"
                name="phone"
                required
                autocomplete="username"
                value="<?= htmlspecialchars(
                    $_POST['phone'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <label for="password">
                كلمة المرور
            </label>

            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
            >

            <button type="submit">
                تسجيل الدخول
            </button>

        </form>

        <div class="footer">
            نظام المحفظة الذكية
        </div>

    </div>

</div>

</body>
</html>
