<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$action = $_POST['action'] ?? '';
$notificationId = filter_input(INPUT_POST, 'notification_id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'الطلب غير صالح.');
        header('Location: notifications.php');
        exit;
    }

    try {
        if ($action === 'mark_read') {
            if (!$notificationId) {
                throw new RuntimeException('الإشعار غير صالح.');
            }

            $stmt = $pdo->prepare("
                UPDATE admin_notifications
                SET is_read = 1
                WHERE id = :id
                LIMIT 1
            ");

            $stmt->execute([
                ':id' => $notificationId
            ]);

            flash('success', 'تم تحديد الإشعار كمقروء.');
        }

        if ($action === 'mark_all_read') {
            $pdo->exec("
                UPDATE admin_notifications
                SET is_read = 1
                WHERE is_read = 0
            ");

            flash('success', 'تم تحديد جميع الإشعارات كمقروءة.');
        }

        header('Location: notifications.php');
        exit;

    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        header('Location: notifications.php');
        exit;
    }
}

$stmt = $pdo->query("
    SELECT
        id,
        title,
        message,
        type,
        target_type,
        target_id,
        is_read,
        created_at
    FROM admin_notifications
    ORDER BY id DESC
    LIMIT 100
");

$notifications = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM admin_notifications
    WHERE is_read = 0
");

$unreadCount = (int) $stmt->fetchColumn();

$successMessage = flash('success');
$errorMessage = flash('error');

$typeLabels = [
    'info' => 'معلومة',
    'deposit' => 'إيداع',
    'withdrawal' => 'سحب',
    'warning' => 'تنبيه',
    'success' => 'نجاح',
    'danger' => 'تحذير',
];

function notificationLink(?string $targetType, ?int $targetId): ?string
{
    if (!$targetType || !$targetId) {
        return null;
    }

    return match ($targetType) {
        'deposit' => 'deposits.php?status=pending',
        'withdrawal' => 'withdrawals.php?status=pending',
        'user' => 'user.php?id=' . $targetId,
        'transfer' => 'transfer.php?id=' . $targetId,
        default => null,
    };
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإشعارات الإدارية</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Tahoma, sans-serif;
            background: #f5f7fb;
            color: #111827;
        }

        .container {
            width: min(100% - 30px, 1050px);
            margin: 30px auto;
        }

        .header {
            background: #ffffff;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .07);
            margin-bottom: 20px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 26px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 14px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 30px;
            padding: 0 10px;
            border-radius: 20px;
            background: #dc2626;
            color: white;
            font-weight: bold;
            margin-right: 8px;
        }

        .actions {
            margin-top: 18px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        button,
        .btn {
            border: 0;
            border-radius: 10px;
            padding: 11px 16px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
        }

        .btn-light {
            background: #e5e7eb;
            color: #111827;
        }

        .btn-success {
            background: #16a34a;
            color: white;
        }

        .message {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .notifications {
            display: grid;
            gap: 14px;
        }

        .notification {
            background: white;
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .06);
            border-right: 5px solid #2563eb;
        }

        .notification.unread {
            background: #eff6ff;
        }

        .notification-head {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            align-items: flex-start;
        }

        .title {
            font-size: 18px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .message-text {
            color: #374151;
            line-height: 1.7;
        }

        .meta {
            color: #6b7280;
            font-size: 12px;
            margin-top: 12px;
        }

        .type {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 8px;
            background: #e0e7ff;
            color: #3730a3;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .notification-actions {
            margin-top: 14px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .empty {
            background: white;
            border-radius: 16px;
            padding: 45px 20px;
            text-align: center;
            color: #6b7280;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .06);
        }

        form {
            display: inline;
        }

        @media (max-width: 600px) {
            .container {
                width: min(100% - 20px, 1050px);
                margin: 15px auto;
            }

            .header {
                padding: 17px;
            }

            .notification-head {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <div class="header-top">
            <div>
                <h1>
                    🔔 الإشعارات الإدارية

                    <?php if ($unreadCount > 0): ?>
                        <span class="badge">
                            <?= $unreadCount ?>
                        </span>
                    <?php endif; ?>
                </h1>

                <div class="subtitle">
                    متابعة الأحداث والطلبات التي تحتاج إلى مراجعة إدارية.
                </div>
            </div>

            <a href="index.php" class="btn btn-light">
                ← لوحة الإدارة
            </a>
        </div>

        <?php if ($unreadCount > 0): ?>
            <div class="actions">
                <form method="post">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrfToken()) ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="mark_all_read"
                    >

                    <button type="submit" class="btn btn-success">
                        ✓ تحديد الكل كمقروء
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($successMessage): ?>
        <div class="message success">
            <?= e($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="message error">
            <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="notifications">

        <?php if (!$notifications): ?>

            <div class="empty">
                <div style="font-size: 42px; margin-bottom: 12px;">🔔</div>
                <strong>لا توجد إشعارات حاليًا</strong>
            </div>

        <?php else: ?>

            <?php foreach ($notifications as $notification): ?>

                <?php
                $link = notificationLink(
                    $notification['target_type'] ?? null,
                    isset($notification['target_id'])
                        ? (int) $notification['target_id']
                        : null
                );
                ?>

                <div class="notification <?= (int) $notification['is_read'] === 0 ? 'unread' : '' ?>">

                    <div class="notification-head">

                        <div>
                            <div class="title">
                                <?= e($notification['title']) ?>
                            </div>

                            <div class="message-text">
                                <?= e($notification['message']) ?>
                            </div>
                        </div>

                        <span class="type">
                            <?= e($typeLabels[$notification['type']] ?? $notification['type']) ?>
                        </span>

                    </div>

                    <div class="meta">
                        <?= e($notification['created_at']) ?>

                        <?php if ((int) $notification['is_read'] === 0): ?>
                            · غير مقروء
                        <?php else: ?>
                            · مقروء
                        <?php endif; ?>
                    </div>

                    <div class="notification-actions">

                        <?php if ($link): ?>
                            <a href="<?= e($link) ?>" class="btn btn-primary">
                                عرض الطلب
                            </a>
                        <?php endif; ?>

                        <?php if ((int) $notification['is_read'] === 0): ?>

                            <form method="post">
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= e(csrfToken()) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="mark_read"
                                >

                                <input
                                    type="hidden"
                                    name="notification_id"
                                    value="<?= (int) $notification['id'] ?>"
                                >

                                <button type="submit" class="btn btn-light">
                                    ✓ تحديد كمقروء
                                </button>
                            </form>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
