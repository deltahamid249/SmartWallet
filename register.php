<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['_csrf'] ?? null)) {
        $errors[] = 'انتهت صلاحية النموذج. أعد تحميل الصفحة وحاول مرة أخرى.';
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($fullName === '') {
        $errors[] = 'الاسم الكامل مطلوب.';
    } elseif (strlen($fullName) > 150) {
        $errors[] = 'الاسم الكامل طويل جدًا.';
    }

    if ($phone === '') {
        $errors[] = 'رقم الهاتف مطلوب.';
    } elseif (!preg_match('/^[0-9+][0-9\s-]{6,29}$/', $phone)) {
        $errors[] = 'صيغة رقم الهاتف غير صحيحة.';
    }

    if ($password === '') {
        $errors[] = 'كلمة المرور مطلوبة.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'كلمتا المرور غير متطابقتين.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صحيح.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // فحص رقم الهاتف
            $phoneCheck = $pdo->prepare(
                'SELECT id FROM users WHERE phone = :phone_check LIMIT 1'
            );

            $phoneCheck->execute([
                ':phone_check' => $phone,
            ]);

            if ($phoneCheck->fetch()) {
                throw new RuntimeException(
                    'رقم الهاتف مستخدم بالفعل.'
                );
            }

            // فحص البريد الإلكتروني إذا تم إدخاله
            if ($email !== '') {
                $emailCheck = $pdo->prepare(
                    'SELECT id FROM users WHERE email = :email_check LIMIT 1'
                );

                $emailCheck->execute([
                    ':email_check' => $email,
                ]);

                if ($emailCheck->fetch()) {
                    throw new RuntimeException(
                        'البريد الإلكتروني مستخدم بالفعل.'
                    );
                }
            }

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                'INSERT INTO users
                (full_name, phone, email, password_hash)
                VALUES
                (:full_name, :phone, :email, :password_hash)'
            );

            $stmt->execute([
                ':full_name' => $fullName,
                ':phone' => $phone,
                ':email' => $email !== '' ? $email : null,
                ':password_hash' => $passwordHash,
            ]);

            $userId = (int) $pdo->lastInsertId();

            $wallet = $pdo->prepare(
                'INSERT INTO wallets
                (user_id, balance, currency)
                VALUES
                (:user_id, 0.00, :currency)'
            );

            $wallet->execute([
                ':user_id' => $userId,
                ':currency' => 'SDG',
            ]);

            $pdo->commit();

            $success =
                'تم إنشاء الحساب والمحفظة بنجاح. يمكنك تسجيل الدخول الآن.';

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());
            $errors[] = $e instanceof PDOException
                ? 'تعذر إنشاء الحساب. تحقق من البيانات وحاول مرة أخرى.'
                : $e->getMessage();
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

    <title>إنشاء حساب - Smart Wallet</title>

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
            margin: 50px auto;
        }

        .card {
            background: #fff;
            padding: 28px;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin-top: 0;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin: 14px 0 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }

        button {
            width: 100%;
            margin-top: 22px;
            padding: 13px;
            border: 0;
            border-radius: 8px;
            background: #1565c0;
            color: white;
            font-size: 17px;
            cursor: pointer;
        }

        .error {
            background: #ffebee;
            color: #b71c1c;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .success {
            background: #e8f5e9;
            color: #1b5e20;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
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

        <h1>إنشاء حساب</h1>

        <div class="subtitle">
            أنشئ حسابك في Smart Wallet
        </div>

        <?php foreach ($errors as $error): ?>

            <div class="error">
                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

        <?php endforeach; ?>

        <?php if ($success): ?>

            <div class="success">
                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="register.php">

            <input
                type="hidden"
                name="_csrf"
                value="<?= e(csrfToken()) ?>"
            >

            <label for="full_name">
                الاسم الكامل
            </label>

            <input
                type="text"
                id="full_name"
                name="full_name"
                required
                value="<?= htmlspecialchars(
                    $_POST['full_name'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <label for="phone">
                رقم الهاتف
            </label>

            <input
                type="tel"
                id="phone"
                name="phone"
                required
                value="<?= htmlspecialchars(
                    $_POST['phone'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <label for="email">
                البريد الإلكتروني
            </label>

            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars(
                    $_POST['email'] ?? '',
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
                minlength="8"
                required
            >

            <label for="confirm_password">
                تأكيد كلمة المرور
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                required
            >

            <button type="submit">
                إنشاء الحساب
            </button>

        </form>

        <div class="login-link">
            لديك حساب بالفعل؟
            <a href="login.php">
                تسجيل الدخول
            </a>
        </div>

    </div>

</div>

</body>

</html>
