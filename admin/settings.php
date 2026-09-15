<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['_csrf'] ?? null)) {
        $error = 'انتهت صلاحية النموذج. أعد تحميل الصفحة وحاول مرة أخرى.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($username === '') {
            $error = 'اسم المستخدم مطلوب.';
        } elseif (mb_strlen($username) > 100) {
            $error = 'اسم المستخدم طويل جدًا.';
        } elseif ($newPassword !== '' && strlen($newPassword) < 8) {
            $error = 'كلمة المرور يجب ألا تقل عن 8 أحرف.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'تأكيد كلمة المرور غير مطابق.';
        } else {
            try {
                $check = $pdo->prepare(
                    'SELECT id FROM users
                     WHERE username = :username
                       AND id <> :id
                     LIMIT 1'
                );

                $check->execute([
                    ':username' => $username,
                    ':id' => currentUserId(),
                ]);

                if ($check->fetch()) {
                    $error = 'اسم المستخدم مستخدم بالفعل.';
                } else {
                    if ($newPassword !== '') {
                        $stmt = $pdo->prepare(
                            'UPDATE users
                             SET username = :username,
                                 password_hash = :password_hash,
                                 updated_at = CURRENT_TIMESTAMP
                             WHERE id = :id
                             LIMIT 1'
                        );

                        $stmt->execute([
                            ':username' => $username,
                            ':password_hash' => password_hash(
                                $newPassword,
                                PASSWORD_DEFAULT
                            ),
                            ':id' => currentUserId(),
                        ]);
                    } else {
                        $stmt = $pdo->prepare(
                            'UPDATE users
                             SET username = :username,
                                 updated_at = CURRENT_TIMESTAMP
                             WHERE id = :id
                             LIMIT 1'
                        );

                        $stmt->execute([
                            ':username' => $username,
                            ':id' => currentUserId(),
                        ]);
                    }

                    $message = 'تم تحديث بيانات دخول المدير بنجاح.';
                }
            } catch (Throwable $e) {
                error_log($e->getMessage());
                $error = 'تعذر تحديث بيانات الدخول.';
            }
        }
    }
}

$stmt = $pdo->prepare(
    'SELECT username
     FROM users
     WHERE id = :id
     LIMIT 1'
);

$stmt->execute([
    ':id' => currentUserId(),
]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>إعدادات المدير - المحفظة الذكية</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #f4f6f8;
    color: #111827;
    font-family: Arial, sans-serif;
}

.container {
    width: min(92%, 520px);
    margin: 40px auto;
}

.card {
    background: #fff;
    padding: 25px;
    border-radius: 18px;
    box-shadow: 0 8px 25px rgba(0,0,0,.08);
}

h1 {
    text-align: center;
    margin-top: 0;
}

.note {
    background: #fff8e1;
    padding: 14px;
    border-radius: 10px;
    margin: 18px 0;
    line-height: 1.7;
}

label {
    display: block;
    font-weight: bold;
    margin: 16px 0 7px;
}

input {
    width: 100%;
    padding: 13px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    font-size: 16px;
}

button {
    width: 100%;
    margin-top: 22px;
    padding: 14px;
    border: 0;
    border-radius: 10px;
    background: #1565c0;
    color: #fff;
    font-size: 17px;
    font-weight: bold;
}

.success {
    background: #dcfce7;
    color: #166534;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 15px;
}

.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 15px;
}

a {
    display: block;
    text-align: center;
    margin-top: 18px;
    color: #1565c0;
    text-decoration: none;
}
</style>
</head>

<body>

<div class="container">
<div class="card">

<h1>⚙️ إعدادات المدير</h1>

<div class="note">
يمكن لمالك النظام تغيير اسم المستخدم وكلمة المرور من هنا.
اترك كلمة المرور فارغة إذا كنت تريد تغيير اسم المستخدم فقط.
</div>

<?php if ($message !== ''): ?>
<div class="success">
<?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<?php if ($error !== ''): ?>
<div class="error">
<?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<form method="POST">

<input
    type="hidden"
    name="_csrf"
    value="<?= e(csrfToken()) ?>"
>

<label for="username">اسم المستخدم</label>

<input
    type="text"
    id="username"
    name="username"
    required
    maxlength="100"
    autocomplete="username"
    value="<?= htmlspecialchars(
        (string)($admin['username'] ?? ''),
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>

<label for="new_password">كلمة المرور الجديدة</label>

<input
    type="password"
    id="new_password"
    name="new_password"
    minlength="8"
    autocomplete="new-password"
>

<label for="confirm_password">تأكيد كلمة المرور</label>

<input
    type="password"
    id="confirm_password"
    name="confirm_password"
    minlength="8"
    autocomplete="new-password"
>

<button type="submit">
حفظ إعدادات الدخول
</button>

</form>

<a href="index.php">العودة إلى لوحة الإدارة</a>

</div>
</div>

</body>
</html>