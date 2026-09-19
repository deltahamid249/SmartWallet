<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

requireLogin();

global $pdo;

$stmt = $pdo->prepare("
    SELECT full_name, phone, email, role, status
    FROM users
    WHERE id = :user_id
    LIMIT 1
");
$stmt->execute([':user_id' => currentUserId()]);
$user = $stmt->fetch();

if (!$user) {
    logoutUser();
    redirectTo('/login.php');
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>الإعدادات - المحفظة الذكية</title>
<style>
body{margin:0;background:#f5f7fb;font-family:Arial,sans-serif;color:#111827}
.container{max-width:650px;margin:auto;padding:20px}
.header,.card{background:#fff;border-radius:18px;padding:20px;margin-bottom:15px;box-shadow:0 5px 18px rgba(0,0,0,.07)}
h1{margin:0 0 8px}
.back{display:inline-block;margin-top:15px;color:#2563eb;text-decoration:none}
.item{padding:15px 0;border-bottom:1px solid #eef2f7}
.item:last-child{border-bottom:0}
.label{color:#64748b;font-size:13px;margin-bottom:5px}
.action{display:block;padding:14px;margin-top:10px;border-radius:12px;background:#f8fafc;color:#111827;text-decoration:none}
.logout{background:#fee2e2;color:#b91c1c}
</style>
</head>
<body>
<div class="container">
<div class="header">
<h1>⚙️ الإعدادات</h1>
<p>إدارة معلومات حسابك وأمان الوصول إليه.</p>
<a class="back" href="index.php">← العودة إلى المحفظة</a>
</div>

<div class="card">
<h2>الحساب</h2>
<div class="item"><div class="label">الاسم</div><?= e($user['full_name']) ?></div>
<div class="item"><div class="label">الهاتف</div><?= e($user['phone']) ?></div>
<div class="item"><div class="label">البريد الإلكتروني</div><?= e($user['email']) ?></div>
<div class="item"><div class="label">حالة الحساب</div><?= e($user['status']) ?></div>

<a class="action" href="profile.php">👤 فتح الملف الشخصي</a>
<a class="action logout" href="logout.php">🚪 تسجيل الخروج</a>
</div>
</div>
</body>
</html>
