<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

/*
|--------------------------------------------------------------------------
| إنشاء جدول سجل النشاط الإداري إذا لم يكن موجودًا
|--------------------------------------------------------------------------
*/

$pdo->exec("
    CREATE TABLE IF NOT EXISTS admin_audit_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        admin_id BIGINT UNSIGNED NULL,
        action VARCHAR(100) NOT NULL,
        target_type VARCHAR(50) NULL,
        target_id BIGINT UNSIGNED NULL,
        description VARCHAR(500) NOT NULL,
        ip_address VARCHAR(45) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

        INDEX idx_admin_id (admin_id),
        INDEX idx_action (action),
        INDEX idx_target (target_type, target_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

/*
|--------------------------------------------------------------------------
| الفلاتر
|--------------------------------------------------------------------------
*/

$search = trim((string) ($_GET['search'] ?? ''));
$action = trim((string) ($_GET['action'] ?? ''));

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(
        l.description LIKE :search
        OR l.action LIKE :search
        OR u.full_name LIKE :search
        OR u.phone LIKE :search
    )";

    $params['search'] = '%' . $search . '%';
}

if ($action !== '') {
    $where[] = 'l.action = :action';
    $params['action'] = $action;
}

$whereSql = $where
    ? 'WHERE ' . implode(' AND ', $where)
    : '';

/*
|--------------------------------------------------------------------------
| أنواع الإجراءات الموجودة
|--------------------------------------------------------------------------
*/

$actionsStmt = $pdo->query("
    SELECT DISTINCT action
    FROM admin_audit_logs
    ORDER BY action ASC
");

$actions = $actionsStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| السجلات
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        l.id,
        l.admin_id,
        l.action,
        l.target_type,
        l.target_id,
        l.description,
        l.ip_address,
        l.created_at,

        u.full_name AS admin_name,
        u.phone AS admin_phone

    FROM admin_audit_logs l

    LEFT JOIN users u
        ON u.id = l.admin_id

    $whereSql

    ORDER BY l.id DESC

    LIMIT 200
");

$stmt->execute($params);

$logs = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| الإحصائيات
|--------------------------------------------------------------------------
*/

$totalLogs = (int) $pdo
    ->query("SELECT COUNT(*) FROM admin_audit_logs")
    ->fetchColumn();

$todayLogs = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM admin_audit_logs
        WHERE DATE(created_at) = CURDATE()
    ")
    ->fetchColumn();

$adminsActive = (int) $pdo
    ->query("
        SELECT COUNT(DISTINCT admin_id)
        FROM admin_audit_logs
        WHERE admin_id IS NOT NULL
    ")
    ->fetchColumn();

/**
 * تحويل رمز إجراء السجل إلى وصف عربي مفهوم.
 */
function auditActionLabel(string $action): string
{
    return match ($action) {
        'user_status_changed' => 'تغيير حالة مستخدم',
        'user_role_changed' => 'تغيير صلاحية مستخدم',
        'deposit_approved' => 'الموافقة على إيداع',
        'deposit_rejected' => 'رفض إيداع',
        'withdrawal_approved' => 'الموافقة على سحب',
        'withdrawal_rejected' => 'رفض سحب',
        'transfer_reviewed' => 'مراجعة تحويل',
        'login' => 'تسجيل دخول',
        'logout' => 'تسجيل خروج',
        default => $action,
    };
}

/**
 * اختيار CSS class المناسب لنوع إجراء السجل.
 */
function auditActionClass(string $action): string
{
    return match ($action) {
        'deposit_approved',
        'withdrawal_approved' => 'success',

        'deposit_rejected',
        'withdrawal_rejected' => 'danger',

        'user_role_changed' => 'warning',

        default => 'default',
    };
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

    <title>سجل النشاط الإداري - لوحة الإدارة</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f7fb;
            color: #111827;
            font-family: Arial, Tahoma, sans-serif;
        }

        .topbar {
            background: #111827;
            color: #fff;
            padding: 18px 24px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 15px;
            flex-wrap: wrap;
        }

        .topbar h1 {
            margin: 0;
            font-size: 21px;
        }

        .topbar a {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            margin-right: 15px;
        }

        .container {
            max-width: 1250px;
            margin: 30px auto;
            padding: 0 18px;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
            font-weight: bold;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 25px;
        }

        .stat {
            background: #fff;
            padding: 22px;
            border-radius: 16px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .07);
        }

        .stat-title {
            color: #6b7280;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 900;
        }

        .filters {
            background: #fff;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .07);
            margin-bottom: 20px;
        }

        .filters form {
            display: grid;
            grid-template-columns: 1fr 250px 120px;
            gap: 12px;
        }

        input,
        select,
        button {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            font-size: 15px;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #2563eb;
        }

        button {
            background: #2563eb;
            color: #fff;
            border: 0;
            cursor: pointer;
            font-weight: bold;
        }

        .table-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .07);
            overflow: hidden;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 12px;
            border-bottom: 1px solid #edf0f4;
            text-align: right;
            vertical-align: middle;
        }

        th {
            background: #f9fafb;
            color: #4b5563;
            font-size: 13px;
        }

        td {
            font-size: 14px;
        }

        .admin-name {
            font-weight: bold;
        }

        .admin-phone {
            color: #6b7280;
            font-size: 12px;
            margin-top: 4px;
        }

        .action {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            background: #f3f4f6;
            color: #374151;
            white-space: nowrap;
        }

        .action.success {
            background: #dcfce7;
            color: #166534;
        }

        .action.danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .action.warning {
            background: #fef3c7;
            color: #92400e;
        }

        .action.default {
            background: #e5e7eb;
            color: #374151;
        }

        .description {
            max-width: 380px;
            line-height: 1.6;
        }

        .ip {
            direction: ltr;
            text-align: right;
            font-family: monospace;
            font-size: 12px;
        }

        .empty {
            padding: 45px 20px;
            text-align: center;
            color: #6b7280;
        }

        @media (max-width: 850px) {

            .stats {
                grid-template-columns: 1fr;
            }

            .filters form {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {

            .topbar {
                align-items: flex-start;
            }

            .container {
                margin-top: 20px;
            }
        }

    </style>

</head>

<body>

<header class="topbar">

    <h1>📋 سجل النشاط الإداري</h1>

    <div>

        <a href="index.php">
            لوحة الإدارة
        </a>

        <a href="../index.php">
            الرئيسية
        </a>

        <a href="../logout.php">
            تسجيل الخروج
        </a>

    </div>

</header>

<main class="container">

    <a href="index.php" class="back">
        ← العودة إلى لوحة الإدارة
    </a>

    <section class="stats">

        <div class="stat">

            <div class="stat-title">
                إجمالي الأنشطة
            </div>

            <div class="stat-value">
                <?= $totalLogs ?>
            </div>

        </div>

        <div class="stat">

            <div class="stat-title">
                أنشطة اليوم
            </div>

            <div class="stat-value">
                <?= $todayLogs ?>
            </div>

        </div>

        <div class="stat">

            <div class="stat-title">
                المدراء المسجل نشاطهم
            </div>

            <div class="stat-value">
                <?= $adminsActive ?>
            </div>

        </div>

    </section>

    <section class="filters">

        <form method="get">

            <input
                type="text"
                name="search"
                value="<?= e($search) ?>"
                placeholder="ابحث في وصف النشاط أو اسم المدير أو الهاتف..."
            >

            <select name="action">

                <option value="">
                    جميع الإجراءات
                </option>

                <?php foreach ($actions as $item): ?>

                    <?php $itemAction = (string) $item['action']; ?>

                    <option
                        value="<?= e($itemAction) ?>"
                        <?= $action === $itemAction ? 'selected' : '' ?>
                    >
                        <?= e(auditActionLabel($itemAction)) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit">
                بحث
            </button>

        </form>

    </section>

    <section class="table-card">

        <?php if (!$logs): ?>

            <div class="empty">

                لا توجد أنشطة إدارية مسجلة حاليًا.

            </div>

        <?php else: ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                    <tr>

                        <th>
                            #
                        </th>

                        <th>
                            المدير
                        </th>

                        <th>
                            الإجراء
                        </th>

                        <th>
                            الوصف
                        </th>

                        <th>
                            الهدف
                        </th>

                        <th>
                            عنوان IP
                        </th>

                        <th>
                            التاريخ
                        </th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($logs as $log): ?>

                        <tr>

                            <td>
                                #<?= (int) $log['id'] ?>
                            </td>

                            <td>

                                <div class="admin-name">
                                    <?= e($log['admin_name'] ?: 'غير معروف') ?>
                                </div>

                                <?php if (!empty($log['admin_phone'])): ?>

                                    <div class="admin-phone">
                                        <?= e($log['admin_phone']) ?>
                                    </div>

                                <?php endif; ?>

                            </td>

                            <td>

                                <span
                                    class="action <?= e(
                                        auditActionClass(
                                            (string) $log['action']
                                        )
                                    ) ?>"
                                >
                                    <?= e(
                                        auditActionLabel(
                                            (string) $log['action']
                                        )
                                    ) ?>
                                </span>

                            </td>

                            <td class="description">

                                <?= e($log['description']) ?>

                            </td>

                            <td>

                                <?php if (!empty($log['target_type'])): ?>

                                    <?= e($log['target_type']) ?>

                                    <?php if (!empty($log['target_id'])): ?>

                                        #<?= (int) $log['target_id'] ?>

                                    <?php endif; ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>

                            <td class="ip">

                                <?= e($log['ip_address'] ?: '—') ?>

                            </td>

                            <td>

                                <?= e($log['created_at']) ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>

</html>
