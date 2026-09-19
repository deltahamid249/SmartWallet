<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('رقم الطلب غير صالح.');
}

$stmt = $pdo->prepare("
    SELECT
        sr.*,
        u.full_name,
        u.phone AS user_phone,
        u.email AS user_email
    FROM service_requests sr
    INNER JOIN users u ON u.id = sr.user_id
    WHERE sr.id = :id
    LIMIT 1
");

$stmt->execute([
    ':id' => $id,
]);

$request = $stmt->fetch();

if (!$request) {
    http_response_code(404);
    exit('طلب الخدمة غير موجود.');
}

/*
|--------------------------------------------------------------------------
| تحديث حالة الطلب
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrf = $_POST['csrf_token'] ?? null;
    $actionToken = $_POST['action_token'] ?? null;
    $newStatus = $_POST['status'] ?? '';

    if (!verifyCsrf($csrf)) {
        flash('error', 'جلسة الحماية غير صالحة. أعد المحاولة.');
        header('Location: service.php?id=' . $id);
        exit;
    }

    if (!consumeActionToken('service_manage', $actionToken)) {
        flash('error', 'رمز العملية غير صالح أو تم استخدامه مسبقًا.');
        header('Location: service.php?id=' . $id);
        exit;
    }

    $allowedStatuses = [
        'pending',
        'processing',
        'completed',
        'failed',
        'cancelled',
    ];

    if (!in_array($newStatus, $allowedStatuses, true)) {
        flash('error', 'حالة الطلب غير صالحة.');
        header('Location: service.php?id=' . $id);
        exit;
    }

    $oldStatus = (string) $request['status'];

    if ($oldStatus === $newStatus) {
        flash('error', 'الطلب موجود بالفعل بهذه الحالة.');
        header('Location: service.php?id=' . $id);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $update = $pdo->prepare("
            UPDATE service_requests
            SET status = :status
            WHERE id = :id
            LIMIT 1
        ");

        $update->execute([
            ':status' => $newStatus,
            ':id' => $id,
        ]);

        $statusLabelsForLog = [
            'pending' => 'قيد المراجعة',
            'processing' => 'قيد المعالجة',
            'completed' => 'مكتمل',
            'failed' => 'فشل',
            'cancelled' => 'ملغى',
        ];

        $oldLabel = $statusLabelsForLog[$oldStatus] ?? $oldStatus;
        $newLabel = $statusLabelsForLog[$newStatus] ?? $newStatus;

        if (function_exists('logAdminAction')) {
            logAdminAction(
                'service_status_change',
                'تم تغيير حالة طلب الخدمة رقم #' . $id .
                ' من "' . $oldLabel . '" إلى "' . $newLabel . '".',
                'service_request',
                $id
            );
        }

        if (function_exists('createAdminNotification')) {
            createAdminNotification(
                'تحديث طلب خدمة',
                'تم تحديث حالة طلب الخدمة رقم #' . $id .
                ' إلى "' . $newLabel . '".',
                'service',
                'service_request',
                $id
            );
        }

        $pdo->commit();

        flash(
            'success',
            'تم تحديث حالة الطلب بنجاح إلى: ' . $newLabel
        );

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash(
            'error',
            'حدث خطأ أثناء تحديث حالة الطلب.'
        );
    }

    header('Location: service.php?id=' . $id);
    exit;
}

$serviceLabels = [
    'mobile_recharge' => 'شحن الهاتف',
    'electricity' => 'الكهرباء',
    'internet' => 'الإنترنت',
    'bill_payment' => 'دفع فاتورة',
    'education' => 'التعليم',
    'government' => 'خدمة حكومية',
];

$statusLabels = [
    'pending' => 'قيد المراجعة',
    'processing' => 'قيد المعالجة',
    'completed' => 'مكتمل',
    'failed' => 'فشل',
    'cancelled' => 'ملغى',
];

$statusClasses = [
    'pending' => 'pending',
    'processing' => 'processing',
    'completed' => 'completed',
    'failed' => 'failed',
    'cancelled' => 'cancelled',
];

$serviceType = (string) ($request['service_type'] ?? '');
$status = (string) ($request['status'] ?? 'pending');

$serviceLabel = $serviceLabels[$serviceType] ?? $serviceType;
$statusLabel = $statusLabels[$status] ?? $status;
$statusClass = $statusClasses[$status] ?? 'pending';

/**
 * تجهيز قيمة تفاصيل الخدمة للإظهار مع قيمة بديلة للفراغ.
 */
function adminServiceValue(mixed $value): string
{
    return e(
        $value === null || $value === ''
            ? '—'
            : $value
    );
}

$successMessage = flash('success');
$errorMessage = flash('error');

$actionToken = actionToken('service_manage');
$csrfToken = csrfToken();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>تفاصيل طلب الخدمة</title>

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
            width: min(100% - 30px, 1050px);
            margin: 30px auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        h1 {
            margin: 0;
            font-size: 26px;
        }

        .back {
            display: inline-block;
            padding: 11px 16px;
            border-radius: 10px;
            background: #111827;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
        }

        .card {
            background: #fff;
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 18px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .07);
        }

        .card h2 {
            margin-top: 0;
            font-size: 19px;
            border-bottom: 1px solid #edf0f5;
            padding-bottom: 12px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
        }

        .label {
            display: block;
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .value {
            font-weight: 800;
            word-break: break-word;
        }

        .amount {
            font-size: 22px;
        }

        /*
         * جميع القيم المالية باللون الأخضر
         */
        .money-value,
        .amount,
        .balance,
        .balance-number,
        .balance-value,
        .balance strong,
        .value.amount,
        .money {
            color: #16a34a !important;
            font-weight: 900 !important;
        }

        .money-value,
        .amount,
        .money {
            white-space: nowrap;
            direction: ltr;
            unicode-bidi: isolate;
        }

        .status {
            display: inline-block;
            padding: 7px 13px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 800;
        }

        .pending {
            background: #fff7ed;
            color: #c2410c;
        }

        .processing {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .completed {
            background: #ecfdf5;
            color: #047857;
        }

        .failed {
            background: #fef2f2;
            color: #16a34a;
        }

        .cancelled {
            background: #f3f4f6;
            color: #4b5563;
        }

        .notice {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            border-radius: 12px;
            padding: 15px;
            line-height: 1.8;
        }

        .success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 18px;
            font-weight: 700;
        }

        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #16a34a;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 18px;
            font-weight: 700;
        }

        .manage-box {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 18px;
        }

        .manage-form {
            display: flex;
            align-items: end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .field {
            flex: 1;
            min-width: 220px;
        }

        .field label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 7px;
        }

        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #fff;
            font-size: 15px;
        }

        button {
            border: 0;
            border-radius: 10px;
            padding: 12px 20px;
            background: #111827;
            color: #fff;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
        }

        button:hover {
            opacity: .92;
        }

        .warning {
            margin-top: 13px;
            color: #92400e;
            font-size: 13px;
            line-height: 1.7;
        }

        .balance-card,
        .balance,
        .money-card {
            max-width: 100%;
        }

        @media (max-width: 650px) {
            .container {
                width: min(100% - 20px, 1050px);
                margin: 18px auto;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 16px;
            }

            h1 {
                font-size: 22px;
            }

            .back {
                width: 100%;
                text-align: center;
            }

            .manage-form {
                display: block;
            }

            .field {
                margin-bottom: 12px;
            }

            button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="topbar">
        <h1>🛠️ تفاصيل طلب الخدمة</h1>

        <a
            class="back"
            href="services.php"
        >
            ← العودة إلى إدارة الخدمات
        </a>
    </div>

    <?php if ($successMessage): ?>
        <div class="success">
            <?= e($successMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="error">
            <?= e($errorMessage) ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>إدارة حالة الطلب</h2>

        <div class="manage-box">

            <form
                method="post"
                class="manage-form"
            >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($csrfToken) ?>"
                >

                <input
                    type="hidden"
                    name="action_token"
                    value="<?= e($actionToken) ?>"
                >

                <div class="field">
                    <label for="status">
                        الحالة الجديدة
                    </label>

                    <select
                        id="status"
                        name="status"
                        required
                    >
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

                <button type="submit">
                    تحديث الحالة
                </button>

            </form>

            <div class="warning">
                ⚠️ تغيير الحالة إلى «مكتمل» يعني أن الإدارة تعتبر الطلب
                منفذًا. لا تستخدم هذه الحالة قبل التأكد من تنفيذ الخدمة
                فعليًا من خلال مزود الخدمة أو التكامل الرسمي.
            </div>

        </div>
    </div>

    <div class="card">
        <h2>معلومات الطلب</h2>

        <div class="grid">

            <div class="item">
                <span class="label">رقم الطلب</span>

                <div class="value">
                    #<?= (int) $request['id'] ?>
                </div>
            </div>

            <div class="item">
                <span class="label">المرجع</span>

                <div class="value">
                    <?= adminServiceValue($request['reference']) ?>
                </div>
            </div>

            <div class="item">
                <span class="label">نوع الخدمة</span>

                <div class="value">
                    <?= adminServiceValue($serviceLabel) ?>
                </div>
            </div>

            <div class="item">
                <span class="label">مزود الخدمة</span>

                <div class="value">
                    <?= adminServiceValue($request['provider']) ?>
                </div>
            </div>

            <div class="item">
                <span class="label">حالة الطلب</span>

                <div class="value">
                    <span class="status <?= e($statusClass) ?>">
                        <?= e($statusLabel) ?>
                    </span>
                </div>
            </div>

            <div class="item">
                <span class="label">تاريخ الإنشاء</span>

                <div class="value">
                    <?= adminServiceValue($request['created_at']) ?>
                </div>
            </div>

        </div>
    </div>

    <div class="card">
        <h2>👤 معلومات المستخدم</h2>

        <div class="grid">

            <div class="item">
                <span class="label">الاسم</span>

                <div class="value">
                    <?= adminServiceValue($request['full_name']) ?>
                </div>
            </div>

            <div class="item">
                <span class="label">رقم الهاتف</span>

                <div class="value">
                    <?= adminServiceValue($request['user_phone']) ?>
                </div>
            </div>

            <div class="item">
                <span class="label">البريد الإلكتروني</span>

                <div class="value">
                    <?= adminServiceValue($request['user_email']) ?>
                </div>
            </div>

            <div class="item">
                <span class="label">معرّف المستخدم</span>

                <div class="value">
                    #<?= (int) $request['user_id'] ?>
                </div>
            </div>

        </div>
    </div>

    <div class="card">
        <h2>💰 تفاصيل العملية المالية</h2>

        <div class="grid">

            <div class="item">
                <span class="label">المبلغ</span>

                <div class="value money-value">
                    <?= e(formatMoney($request['amount'])) ?>
                    SDG
                </div>
            </div>

            <div class="item">
                <span class="label">الرسوم</span>

                <div class="value money-value">
                    <?= e(formatMoney($request['fee'])) ?>
                    SDG
                </div>
            </div>

            <div class="item">
                <span class="label">الإجمالي</span>

                <div class="value money-value">
                    <?= e(formatMoney($request['total_amount'])) ?>
                    SDG
                </div>
            </div>

            <div class="item">
                <span class="label">الهاتف / الحساب</span>

                <div class="value">
                    <?= adminServiceValue(
                        $request['phone_number']
                        ?: $request['account_number']
                    ) ?>
                </div>
            </div>

        </div>
    </div>

    <div class="card">
        <h2>📋 البيانات الإضافية</h2>

        <div class="grid">

            <div class="item">
                <span class="label">رقم الهاتف</span>

                <div class="value">
                    <?= adminServiceValue($request['phone_number']) ?>
                </div>
            </div>

            <div class="item">
                <span class="label">رقم الحساب</span>

                <div class="value">
                    <?= adminServiceValue($request['account_number']) ?>
                </div>
            </div>

        </div>
    </div>

    <div class="card">
        <h2>📡 نتيجة التنفيذ</h2>

        <div class="notice">
            <?= nl2br(
                adminServiceValue($request['response_message'])
            ) ?>
        </div>
    </div>

</div>

</body>
</html>
