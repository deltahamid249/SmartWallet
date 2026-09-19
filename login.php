<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirectTo('/index.php');
}

$error = '';
$loginValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginValue = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($loginValue === '' || $password === '') {
        $error = 'يرجى إدخال رقم الهاتف أو البريد الإلكتروني وكلمة المرور.';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT
                    id,
                    password_hash,
                    status
                FROM users
                WHERE phone = :phone
                   OR email = :email
                LIMIT 1
            ");

            $stmt->execute([
                ':phone' => $loginValue,
                ':email' => $loginValue,
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = 'بيانات تسجيل الدخول غير صحيحة.';
            } elseif ($user['status'] !== 'active') {
                $error = 'هذا الحساب غير نشط حاليًا.';
            } elseif (!password_verify($password, $user['password_hash'])) {
                $error = 'بيانات تسجيل الدخول غير صحيحة.';
            } else {
                loginUser((int) $user['id']);

                redirectTo('/index.php');
            }
        } catch (Throwable $e) {
            error_log(
                'Smart Wallet Login Error: '
                . $e->getMessage()
                . ' in '
                . $e->getFile()
                . ':'
                . $e->getLine()
            );

            $error = 'حدث خطأ أثناء تسجيل الدخول. حاول مرة أخرى.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>تسجيل الدخول - المحفظة الذكية</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Tahoma, sans-serif;
            background:
                linear-gradient(
                    135deg,
                    #eef4ff 0%,
                    #f8fafc 50%,
                    #eaf1ff 100%
                );
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 430px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 32px 24px;
            box-shadow: 0 15px 45px rgba(15, 23, 42, 0.12);
            border: 1px solid #e5e7eb;
        }

        .brand {
            text-align: center;
            margin-bottom: 24px;
        }

        .logo {
            width: 116px;
            height: 94px;
            margin: 0 auto 16px;
            border-radius: 20px;
            background: linear-gradient(145deg, #0f3d91, #1769d2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #ffffff;
            box-shadow:
                0 10px 25px rgba(15, 61, 145, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.25);
            position: relative;
            overflow: hidden;
            padding: 0 8px;
        }

        .logo::before {
            content: "";
            position: absolute;
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.14);
            top: -65px;
            right: -55px;
        }

        .logo::after {
            content: "";
            position: absolute;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.10);
            bottom: -55px;
            left: -45px;
        }

        .logo-main {
            position: relative;
            z-index: 2;
            font-size: 21px;
            font-weight: 900;
            line-height: 1.2;
            white-space: nowrap;
        }

        .logo-sub {
            position: relative;
            z-index: 2;
            margin-top: 2px;
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
        }

        .brand h1 {
            margin: 0;
            font-size: 27px;
            color: #0f172a;
            font-weight: 800;
        }

        .brand p {
            margin: 8px 0 0;
            font-size: 14px;
            color: #475569;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }

        .form-group input {
            width: 100%;
            height: 52px;
            border: 1px solid #d1d5db;
            border-radius: 13px;
            padding: 0 15px;
            font-size: 16px;
            color: #111827;
            background: #ffffff;
            outline: none;
            transition: 0.2s ease;
        }

        .form-group input:focus {
            border-color: #1769d2;
            box-shadow: 0 0 0 4px rgba(23, 105, 210, 0.10);
        }

        .form-group input::placeholder {
            color: #6b7280;
        }

        .error {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #b91c1c;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 17px;
            font-size: 14px;
            line-height: 1.6;
        }

        .login-button {
            width: 100%;
            height: 54px;
            border: 0;
            border-radius: 14px;
            background: linear-gradient(135deg, #0f3d91, #1769d2);
            color: #ffffff;
            font-size: 17px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(15, 61, 145, 0.20);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .login-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(15, 61, 145, 0.25);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #475569;
        }

        .register-link a {
            color: #0f3d91;
            font-weight: 800;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 18px;
            font-size: 12px;
            color: #64748b;
        }

        @media (max-width: 480px) {
            body {
                padding: 14px;
            }

            .login-card {
                padding: 27px 19px;
                border-radius: 20px;
            }

            .brand h1 {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>

<div class="login-wrapper">

    <div class="login-card">

        <div class="brand">
            <div class="logo">
                <div class="logo-main">المحفظة</div>
                <div class="logo-sub">الذكية</div>
            </div>

            <h1>المحفظة الذكية</h1>
            <p>تسجيل الدخول إلى حسابك</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label for="login">رقم الهاتف أو البريد الإلكتروني</label>

                <input
                    type="text"
                    id="login"
                    name="login"
                    value="<?= htmlspecialchars($loginValue, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="أدخل رقم الهاتف أو البريد الإلكتروني"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">كلمة المرور</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="أدخل كلمة المرور"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="login-button">
                تسجيل الدخول
            </button>

        </form>

        <div class="register-link">
            ليس لديك حساب؟
            <a href="<?= e(appUrl('/register.php')) ?>">إنشاء حساب جديد</a>
        </div>

    </div>

    <div class="footer">
        المحفظة الذكية
    </div>

</div>

</body>
</html>

