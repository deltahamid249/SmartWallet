<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

requireLogin();

global $pdo;

$userId = currentUserId();

$items = [];

$stmt = $pdo->prepare("
    SELECT
        created_at,
        type,
        amount,
        status,
        description,
        reference
    FROM transactions
    WHERE wallet_id = (
        SELECT id FROM wallets WHERE user_id = :user_id LIMIT 1
    )
    ORDER BY id DESC
    LIMIT 20
");
$stmt->execute([':user_id' => $userId]);

foreach ($stmt->fetchAll() as $row) {
    $items[] = [
        'created_at' => $row['created_at'],
        'title' => 'عملية مالية',
        'message' => (string)($row['description'] ?: 'تم تسجيل عملية مالية على محفظتك.'),
        'status' => $row['status'],
        'amount' => $row['amount'],
        'reference' => $row['reference'],
    ];
}

$stmt = $pdo->prepare("
    SELECT
        created_at,
        service_type,
        status,
        amount,
        reference,
        response_message
    FROM service_requests
    WHERE user_id = :user_id
    ORDER BY id DESC
    LIMIT 20
");
$stmt->execute([':user_id' => $userId]);

foreach ($stmt->fetchAll() as $row) {
    $items[] = [
        'created_at' => $row['created_at'],
        'title' => 'تحديث خدمة',
        'message' => (string)($row['response_message'] ?: 'تم تسجيل طلب خدمة جديد.'),
        'status' => $row['status'],
        'amount' => $row['amount'],
        'reference' => $row['reference'],
    ];
}

$stmt = $pdo->prepare("
    SELECT created_at, status, amount, reference, 'إيداع' AS title
    FROM deposit_requests
    WHERE user_id = :user_id
    ORDER BY id DESC
    LIMIT 10
");
$stmt->execute([':user_id' => $userId]);

foreach ($stmt->fetchAll() as $row) {
    $items[] = [
        'created_at' => $row['created_at'],
        'title' => $row['title'],
        'message' => 'طلب إيداع بقيمة ' . formatMoney($row['amount']) . ' SDG.',
        'status' => $row['status'],
        'amount' => $row['amount'],
        'reference' => $row['reference'],
    ];
}

$stmt = $pdo->prepare("
    SELECT created_at, status, amount, reference, 'سحب' AS title
    FROM withdrawal_requests
    WHERE user_id = :user_id
    ORDER BY id DESC
    LIMIT 10
");
$stmt->execute([':user_id' => $userId]);

foreach ($stmt->fetchAll() as $row) {
    $items[] = [
        'created_at' => $row['created_at'],
        'title' => $row['title'],
        'message' => 'طلب سحب بقيمة ' . formatMoney($row['amount']) . ' SDG.',
        'status' => $row['status'],
        'amount' => $row['amount'],
        'reference' => $row['reference'],
    ];
}

usort($items, fn($a, $b) => strcmp((string)$b['created_at'], (string)$a['created_at']));
$items = array_slice($items, 0, 40);

function notificationStatus(string $status): string
{
    return match ($status) {
        'completed', 'approved' => 'مكتملة',
        'processing' => 'قيد المعالجة',
        'pending' => 'قيد الانتظار',
        'failed', 'rejected', 'cancelled' => 'لم تكتمل',
        default => $status,
    };
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>الإشعارات - المحفظة الذكية</title>
<style>
body{margin:0;background:#f5f7fb;font-family:Arial,sans-serif;color:#111827}
.container{max-width:700px;margin:auto;padding:20px}
.header,.card{background:#fff;border-radius:18px;padding:20px;margin-bottom:15px;box-shadow:0 5px 18px rgba(0,0,0,.07)}
h1{margin:0 0 8px}.back{display:inline-block;margin-top:15px;text-decoration:none;color:#2563eb}
.notice{padding:15px;border-radius:14px;background:#f8fafc;margin-bottom:12px}
.meta{color:#64748b;font-size:13px;margin-top:8px}
.status{font-weight:bold;margin-top:8px}
.empty{text-align:center;padding:40px;color:#64748b}
</style>
</head>
<body>
<div class="container">
<div class="header">
<h1>🔔 الإشعارات</h1>
<p>آخر التحديثات والعمليات المرتبطة بحسابك.</p>
<a class="back" href="index.php">← العودة إلى المحفظة</a>
</div>

<?php if (!$items): ?>
<div class="card empty">لا توجد إشعارات أو تحديثات حاليًا.</div>
<?php else: ?>
<?php foreach ($items as $item): ?>
<div class="card">
<strong><?= e($item['title']) ?></strong>
<div class="notice"><?= e($item['message']) ?></div>
<div class="status">الحالة: <?= e(notificationStatus((string)$item['status'])) ?></div>
<div class="meta">
<?= e((string)$item['created_at']) ?>
<?php if (!empty($item['reference'])): ?>
 — المرجع: <?= e((string)$item['reference']) ?>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</body>
</html>
