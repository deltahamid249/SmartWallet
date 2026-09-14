<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$userId = currentUserId();

$serviceNames = [
    'mobile_recharge' => 'شحن الهاتف',
    'electricity' => 'الكهرباء',
    'internet' => 'الإنترنت',
    'bill_payment' => 'دفع فاتورة',
    'education' => 'التعليم',
    'government' => 'خدمة حكومية',
];

$statusNames = [
    'pending' => 'قيد المراجعة',
    'processing' => 'قيد التنفيذ',
    'completed' => 'مكتمل',
    'failed' => 'فشل',
    'cancelled' => 'ملغي',
];

$statusClasses = [
    'pending' => 'status-pending',
    'processing' => 'status-processing',
    'completed' => 'status-success',
    'failed' => 'status-danger',
    'cancelled' => 'status-danger',
];

$stmt = $pdo->prepare("
    SELECT
        id,
        service_type,
        provider,
        phone_number,
        account_number,
        amount,
        fee,
        total_amount,
        reference,
        status,
        response_message,
        created_at,
        updated_at
    FROM service_requests
    WHERE user_id = :user_id
    ORDER BY id DESC
    LIMIT 100
");

$stmt->execute([
    ':user_id' => $userId,
]);

$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل الخدمات - المحفظة الذكية</title>

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
            width: min(100% - 24px, 800px);
            margin: 20px auto;
        }

        .card {
            background: #ffffff;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
            margin-bottom: 18px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 24px;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 20px;
        }

        .back {
            display: inline-block;
            margin-bottom: 15px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
        }

        .request {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 15px;
            margin-top: 12px;
        }

        .request-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .service {
            font-weight: 800;
            font-size: 17px;
        }

        .reference {
            color: #6b7280;
            font-size: 13px;
            margin-top: 5px;
        }

        .amount {
            font-size: 19px;
            font-weight: 800;
            margin-top: 12px;
        }

        .meta {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.9;
            margin-top: 8px;
        }

        .status-success,
        .status-danger,
        .status-processing,
        .status-pending {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-success {
            background: #dcfce7;
            color: #166534;
        }

        .status-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-processing {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .details {
            display: inline-block;
            margin-top: 10px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 700;
        }

        .empty {
            text-align: center;
            color: #6b7280;
            padding: 25px 10px;
        }
    </style>
</head>

<body>
<div class="container">

    <a class="back" href="index.php">← العودة إلى الخدمات</a>

    <div class="card">
        <h1>سجل الخدمات</h1>
        <div class="subtitle">
            جميع طلبات الخدمات التي قمت بإرسالها.
        </div>

        <?php if (!$requests): ?>

            <div class="empty">
                لا توجد طلبات خدمات حتى الآن.
            </div>

        <?php else: ?>

            <?php foreach ($requests as $request): ?>

                <?php
                $serviceType = (string)$request['service_type'];
                $status = (string)$request['status'];

                $serviceName = $serviceNames[$serviceType] ?? $serviceType;
                $statusName = $statusNames[$status] ?? $status;
                $statusClass = $statusClasses[$status] ?? 'status-pending';
                ?>

                <div class="request">

                    <div class="request-top">
                        <div>
                            <div class="service">
                                <?= e($serviceName) ?>
                            </div>

                            <div class="reference">
                                <?= e($request['reference']) ?>
                            </div>
                        </div>

                        <span class="<?= e($statusClass) ?>">
                            <?= e($statusName) ?>
                        </span>
                    </div>

                    <div class="amount">
                        <?= e(formatMoney($request['total_amount'])) ?> جنيه
                    </div>

                    <div class="meta">

                        المبلغ:
                        <?= e(formatMoney($request['amount'])) ?> جنيه

                        <br>

                        الرسوم:
                        <?= e(formatMoney($request['fee'])) ?> جنيه

                        <?php if (!empty($request['provider'])): ?>
                            <br>
                            المزود:
                            <?= e((string)$request['provider']) ?>
                        <?php endif; ?>

                        <?php if (!empty($request['phone_number'])): ?>
                            <br>
                            الهاتف:
                            <?= e((string)$request['phone_number']) ?>
                        <?php endif; ?>

                        <?php if (!empty($request['account_number'])): ?>
                            <br>
                            رقم الحساب/المرجع:
                            <?= e((string)$request['account_number']) ?>
                        <?php endif; ?>

                        <br>

                        تاريخ الطلب:
                        <?= e((string)$request['created_at']) ?>

                    </div>

                    <?php if (!empty($request['response_message'])): ?>
                        <div class="meta">
                            الرد:
                            <?= e((string)$request['response_message']) ?>
                        </div>
                    <?php endif; ?>

                    <a class="details"
                       href="request.php?id=<?= (int)$request['id'] ?>">
                        عرض تفاصيل الطلب →
                    </a>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>
</body>
</html>
