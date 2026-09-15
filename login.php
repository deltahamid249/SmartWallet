<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$phone = '';
$ipAddress = clientIp();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['_csrf'] ?? null)) {
        $error = 'انتهت صلاحية النموذج. أعد تحميل الصفحة وحاول مرة أخرى.';
    }

    $phone = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($error === '' && ($phone === '' || $password === '')) {
        $error = 'يرجى إدخال رقم الهاتف وكلمة المرور.';
    } elseif ($error === '') {
        try {
            $rateStmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM login_attempts
                 WHERE success = 0
                   AND created_at >= (CURRENT_TIMESTAMP - INTERVAL 15 MINUTE)
                   AND (phone = :phone OR ip_address = :ip_address)'
            );

            $rateStmt->execute([
                ':phone' => $phone,
                ':ip_address' => $ipAddress,
            ]);

            if ((int)$rateStmt->fetchColumn() >= 5) {
                throw new RuntimeException(
                    'تم تجاوز عدد محاولات تسجيل الدخول. حاول بعد 15 دقيقة.'
                );
            }

            $stmt = $pdo->prepare(
                'SELECT id, password_hash, status
                 FROM users
                 WHERE phone = :phone
                 LIMIT 1'
            );

            $stmt->execute([
                ':phone' => $phone,
            ]);

            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $error = 'رقم الهاتف أو كلمة المرور غير صحيحة.';
            } elseif (($user['status'] ?? '') !== 'active') {
                $error = 'هذا الحساب غير نشط.';
            } else {
                loginUser((int)$user['id']);

                $attempt = $pdo->prepare(
                    'INSERT INTO login_attempts
                    (user_id, phone, ip_address, success)
                    VALUES
                    (:user_id, :phone, :ip_address, 1)'
                );

                $attempt->execute([
                    ':user_id' => (int)$user['id'],
                    ':phone' => $phone,
                    ':ip_address' => $ipAddress,
                ]);

                header('Location: index.php');
                exit;
            }

            $attempt = $pdo->prepare(
                'INSERT INTO login_attempts
                (user_id, phone, ip_address, success)
                VALUES
                (:user_id, :phone, :ip_address, 0)'
            );

            $attempt->execute([
                ':user_id' => $user ? (int)$user['id'] : null,
                ':phone' => $phone,
                ':ip_address' => $ipAddress,
            ]);

        } catch (Throwable $e) {
            error_log($e->getMessage());

            if ($e instanceof RuntimeException) {
                $error = $e->getMessage();
            } else {
                $error = 'حدث خطأ أثناء تسجيل الدخول. يرجى المحاولة مرة أخرى.';
            }
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

    <title>تسجيل الدخول - المحفظة الذكية</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Tahoma, sans-serif;
            background: #f4f6f8;
            color: #111111;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 460px;
        }

        .card {
            background: #ffffff;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        h1 {
            text-align: center;
            margin: 0;
            font-size: 26px;
            font-weight: 900;
        }

        .subtitle {
            text-align: center;
            color: #222222;
            margin: 10px 0 25px;
            font-size: 14px;
        }

        label {
            display: block;
            margin: 15px 0 7px;
            font-weight: 800;
            color: #111111;
        }

        input {
            width: 100%;
            padding: 14px;
            border: 1px solid #d6dbe3;
            border-radius: 9px;
            font-size: 16px;
            color: #111111;
            background: #ffffff;
            outline: none;
        }

        input:focus {
            border-color: #1565c0;
        }

        button {
            width: 100%;
            margin-top: 24px;
            padding: 14px;
            border: 0;
            border-radius: 9px;
            background: #1565c0;
            color: #ffffff;
            font-size: 17px;
            font-weight: 800;
            cursor: pointer;
        }

        button:active {
            transform: scale(0.99);
        }

        .error {
            background: #ffebee;
            color: #b71c1c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: 700;
            line-height: 1.6;
        }

        .register-box {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e1e5ea;
            text-align: center;
        }

        .register-text {
            margin: 0 0 12px;
            font-size: 14px;
            color: #111111;
        }

        .register-button {
            display: block;
            width: 100%;
            padding: 13px;
            border: 2px solid #1565c0;
            border-radius: 9px;
            background: #ffffff;
            color: #1565c0;
            text-decoration: none;
            font-size: 16px;
            font-weight: 900;
        }

        .register-button:active {
            background: #f1f6fc;
        }

        @media (max-width: 480px) {
            body {
                padding: 14px;
            }

            .card {
                padding: 24px 18px;
                border-radius: 16px;
            }

            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="card">

        <h1>تسجيل الدخول</h1>

        <div class="subtitle">
            مرحبًا بك في المحفظة الذكية
        </div>

        <?php if ($error !== ''): ?>
            <div class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <input
                type="hidden"
                name="_csrf"
                value="<?= e(csrfToken()) ?>"
            >

            <label for="phone">
                رقم الهاتف
            </label>

            <input
                type="tel"
                id="phone"
                name="phone"
                required
                autocomplete="tel"
                inputmode="tel"
                value="<?= htmlspecialchars(
                    $phone,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                placeholder="أدخل رقم الهاتف"
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
                placeholder="أدخل كلمة المرور"
            >

            <button type="submit">
                تسجيل الدخول
            </button>

        </form>

        <div class="register-box">

            <p class="register-text">
                ليس لديك حساب؟
            </p>

            <a
                href="register.php"
                class="register-button"
            >
                إنشاء حساب جديد
            </a>

        </div>

    </div>

</div>

</body>
</html>
