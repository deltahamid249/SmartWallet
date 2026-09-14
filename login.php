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

    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

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

            if ((int) $rateStmt->fetchColumn() >= 5) {
                throw new RuntimeException(
                    'تم تجاوز عدد المحاولات. حاول بعد 15 دقيقة.'
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
                ':user_id' => $user ? (int) $user['id'] : null,
                ':phone' => $phone,
                ':ip_address' => $ipAddress,
            ]);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $error = $e instanceof PDOException
                ? 'حدث خطأ أثناء تسجيل الدخول.'
                : ($e instanceof RuntimeException
                ? $e->getMessage()
                : 'حدث خطأ أثناء تسجيل الدخول.');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>تسجيل الدخول - Smart Wallet</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            color: #222;
        }

        .container {
            width: min(92%, 460px);
            margin: 70px auto;
        }

        .card {
            background: #fff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        h1 {
            text-align: center;
            margin-top: 0;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin: 15px 0 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }

        button {
            width: 100%;
            margin-top: 24px;
            padding: 13px;
            border: 0;
            border-radius: 8px;
            background: #1565c0;
            color: #fff;
            font-size: 17px;
            cursor: pointer;
        }

        .error {
            background: #ffebee;
            color: #b71c1c;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .register-link {
            text-align: center;
            margin-top: 22px;
        }

        a {
            color: #1565c0;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="card">

        <h1>تسجيل الدخول</h1>

        <div class="subtitle">
            مرحبًا بك في Smart Wallet
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

            <label for="phone">رقم الهاتف</label>

            <input
                type="tel"
                id="phone"
                name="phone"
                required
                autocomplete="tel"
                value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
            >

            <label for="password">كلمة المرور</label>

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

        <div class="register-link">
            ليس لديك حساب؟
            <a href="register.php">إنشاء حساب جديد</a>
        </div>

    </div>

</div>

</body>
</html>
