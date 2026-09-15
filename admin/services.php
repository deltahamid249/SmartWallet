<?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$status = $_GET['status'] ?? 'all';
$serviceType = $_GET['service_type'] ?? 'all';

$allowedStatuses = [
    'all',
    'pending',
    'processing',
    'completed',
    'failed',
    'cancelled',
];

$allowedServiceTypes = [
    'all',
    'mobile_recharge',
    'electricity',
    'internet',
    'bill_payment',
    'education',
    'government',
];

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'all';
}

if (!in_array($serviceType, $allowedServiceTypes, true)) {
    $serviceType = 'all';
}

$where = [];
$params = [];

if ($status !== 'all') {
    $where[] = 'sr.status = :status';
    $params['status'] = $status;
}

if ($serviceType !== 'all') {
    $where[] = 'sr.service_type = :service_type';
    $params['service_type'] = $serviceType;
}

$whereSql = $where
    ? 'WHERE ' . implode(' AND ', $where)
    : '';

$stmt = $pdo->prepare("
    SELECT
        sr.id,
        sr.user_id,
        sr.service_type,
        sr.provider,
        sr.phone_number,
        sr.account_number,
        sr.amount,
        sr.fee,
        sr.total_amount,
        sr.reference,
        sr.status,
        sr.response_message,
        sr.created_at,
        sr.updated_at,
        u.full_name AS user_name,
        u.phone AS user_phone
    FROM service_requests sr
    INNER JOIN users u ON u.id = sr.user_id
    {$whereSql}
    ORDER BY sr.id DESC
    LIMIT 200
");

$stmt->execute($params);
$requests = $stmt->fetchAll();

$serviceLabels = [
    'mobile_recharge' => 'شحن رصيد',
    'electricity' => 'الكهرباء',
    'internet' => 'الإنترنت',
    'bill_payment' => 'دفع الفواتير',
    'education' => 'الخدمات التعليمية',
    'government' => 'الخدمات الحكومية',
];

$statusLabels = [
    'pending' => 'قيد الانتظار',
    'processing' => 'قيد المعالجة',
    'completed' => 'مكتمل',
    'failed' => 'فشل',
    'cancelled' => 'ملغي',
];

$statusClasses = [
    'pending' => 'status-pending',
    'processing' => 'status-processing',
    'completed' => 'status-completed',
    'failed' => 'status-failed',
    'cancelled' => 'status-cancelled',
];

$serviceTypeLabels = [
    'all' => 'كل الخدمات',
    'mobile_recharge' => 'شحن الرصيد',
    'electricity' => 'الكهرباء',
    'internet' => 'الإنترنت',
    'bill_payment' => 'دفع الفواتير',
    'education' => 'التعليم',
    'government' => 'الحكومة',
];

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الخدمات - المحفظة الذكية</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: #f5f7fb;
            color: #111827;
        }

        .container {
            width: min(1200px, calc(100% - 24px));
            margin: 25px auto;
        }

        .header {
            background: #ffffff;
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 18px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 24px;
        }

        .header p {
            margin: 0;
            color: #6b7280;
        }

        .actions {
            margin-top: 18px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 11px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            border: 0;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-primary {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #111827;
        }

        .filters {
            background: #ffffff;
            border-radius: 18px;
            padding: 18px;
            margin-bottom: 18px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        }

        .filters form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 12px;
            align-items: end;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 700;
        }

        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #ffffff;
            font-size: 14px;
        }

        .table-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 18px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        th,
        td {
            padding: 13px 10px;
            border-bottom: 1px solid #e5e7eb;
            text-align: right;
            vertical-align: middle;
        }

        th {
            background: #f9fafb;
            font-size: 13px;
        }

        td {
            font-size: 13px;
        }

        .service-name {
            font-weight: 800;
        }

        .user-name {
            font-weight: 700;
        }

        .reference {
            font-family: monospace;
            direction: ltr;
            text-align: right;
        }

        .amount {
            font-weight: 800;
            white-space: nowrap;
        }

        .status {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .status-pending {
            background: #fff7ed;
            color: #c2410c;
        }

        .status-processing {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .status-completed {
            background: #ecfdf5;
            color: #047857;
        }

        .status-failed {
            background: #fef2f2;
            color: #16a34a;
        }

        .status-cancelled {
            background: #f3f4f6;
            color: #4b5563;
        }

        .view-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 800;
        }

        .empty {
            text-align: center;
            padding: 45px 20px;
            color: #6b7280;
        }

        @media (max-width: 700px) {
            .container {
                width: min(100% - 14px, 1200px);
                margin: 10px auto;
            }

            .header {
                padding: 18px;
                border-radius: 14px;
            }

            .header h1 {
                font-size: 20px;
            }

            .filters {
                padding: 14px;
                border-radius: 14px;
            }

            .filters form {
                grid-template-columns: 1fr;
            }

            .table-card {
                padding: 10px;
                border-radius: 14px;
            }
        }
    
        /* Unified financial amount style */
        .amount,
        .balance,
        .balance-number,
        .balance-value,
        .balance strong,
        .value.amount,
        .money,
        .money-value {
            color: #16a34a !important;
            font-weight: 900;
        }

        .amount,
        .money,
        .money-value {
            white-space: nowrap;
        }

        .balance-card,
        .balance,
        .money-card {
            max-width: 100%;
        }

    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <h1>إدارة الخدمات</h1>
        <p>متابعة جميع طلبات الخدمات المقدمة من المستخدمين.</p>

        <div class="actions">
            <a href="index.php" class="btn btn-primary">لوحة الإدارة</a>
            <a href="service.php" class="btn btn-secondary">تفاصيل طلب خدمة</a>
        </div>
    </div>

    <div class="filters">

        <form method="get">

            <div>
                <label for="service_type">نوع الخدمة</label>

                <select name="service_type" id="service_type">
                    <?php foreach ($serviceTypeLabels as $value => $label): ?>
                        <option
                            value="<?= e($value) ?>"
                            <?= $serviceType === $value ? 'selected' : '' ?>
                        >
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="status">حالة الطلب</label>

                <select name="status" id="status">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>
                        كل الحالات
                    </option>

                    <?php foreach ($statusLabels as $value => $label): ?>
                        <option
                            value="<?= e($value) ?>"
                            <?= $status === $value ? 'selected' : '' ?>
                        >
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">
                تطبيق الفلتر
            </button>

        </form>

    </div>

    <div class="table-card">

        <?php if (!$requests): ?>

            <div class="empty">
                لا توجد طلبات خدمات مطابقة للبحث.
            </div>

        <?php else: ?>

            <table>

                <thead>
                <tr>
                    <th>#</th>
                    <th>المستخدم</th>
                    <th>الخدمة</th>
                    <th>المزود</th>
                    <th>المبلغ</th>
                    <th>الإجمالي</th>
                    <th>المرجع</th>
                    <th>الحالة</th>
                    <th>التاريخ</th>
                    <th>الإجراء</th>
                </tr>
                </thead>

                <tbody>

                <?php foreach ($requests as $request): ?>

                    <?php
                    $serviceName =
                        $serviceLabels[$request['service_type']]
                        ?? $request['service_type'];

                    $statusLabel =
                        $statusLabels[$request['status']]
                        ?? $request['status'];

                    $statusClass =
                        $statusClasses[$request['status']]
                        ?? '';
                    ?>

                    <tr>

                        <td>
                            <?= (int) $request['id'] ?>
                        </td>

                        <td>
                            <div class="user-name">
                                <?= e($request['user_name']) ?>
                            </div>

                            <?php if (!empty($request['user_phone'])): ?>
                                <small>
                                    <?= e($request['user_phone']) ?>
                                </small>
                            <?php endif; ?>
                        </td>

                        <td>
                            <div class="service-name">
                                <?= e($serviceName) ?>
                            </div>
                        </td>

                        <td>
                            <?= e($request['provider'] ?? '—') ?>
                        </td>

                        <td class="amount">
                            <?= e(formatMoney($request['amount'])) ?>
                        </td>

                        <td class="amount">
                            <?= e(formatMoney($request['total_amount'])) ?>
                        </td>

                        <td class="reference">
                            <?= e($request['reference']) ?>
                        </td>

                        <td>
                            <span class="status <?= e($statusClass) ?>">
                                <?= e($statusLabel) ?>
                            </span>
                        </td>

                        <td>
                            <?= e($request['created_at']) ?>
                        </td>

                        <td>
                            <a
                                href="service.php?id=<?= (int) $request['id'] ?>"
                                class="view-link"
                            >
                                عرض
                            </a>
                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
