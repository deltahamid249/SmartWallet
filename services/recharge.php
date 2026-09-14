<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$userId = currentUserId();

$providers = [
    'zain' => 'زين',
    'sudani' => 'سوداني',
    'mtn' => 'MTN',
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrf = $_POST['csrf_token'] ?? null;
    $actionToken = $_POST['action_token'] ?? null;

    if (!verifyCsrf($csrf)) {
        flash('error', 'جلسة الحماية غير صالحة. أعد المحاولة.');
        header('Location: recharge.php');
        exit;
    }

    if (!consumeActionToken('recharge', $actionToken)) {
        flash('error', 'رمز العملية غير صالح أو تم استخدامه مسبقًا.');
        header('Location: recharge.php');
        exit;
    }

    $provider = trim((string) ($_POST['provider'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $amountRaw = trim((string) ($_POST['amount'] ?? ''));

    if (!isset($providers[$provider])) {
        flash('error', 'اختر شركة اتصالات صحيحة.');
        header('Location: recharge.php');
        exit;
    }

    $phone = preg_replace('/\s+/', '', $phone) ?? '';

    if (!preg_match('/^[0-9]{9,15}$/', $phone)) {
        flash('error', 'أدخل رقم هاتف صحيحًا.');
        header('Location: recharge.php');
        exit;
    }

    $amount = normalizeAmount($amountRaw);

    if ($amount <= 0) {
        flash('error', 'أدخل مبلغًا صحيحًا.');
        header('Location: recharge.php');
        exit;
    }

    if ($amount < 100) {
        flash('error', 'الحد الأدنى للشحن هو 100 جنيه.');
        header('Location: recharge.php');
        exit;
    }

    if ($amount > 100000) {
        flash('error', 'الحد الأقصى للشحن هو 100,000 جنيه.');
        header('Location: recharge.php');
        exit;
    }

    $fee = 0.00;
    $totalAmount = $amount;
    $reference = 'RCH-' . strtoupper(bin2hex(random_bytes(6)));

    try {

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO service_requests
            (
                user_id,
                service_type,
                provider,
                phone_number,
                amount,
                fee,
                total_amount,
                reference,
                status,
                response_message
            )
            VALUES
            (
                :user_id,
                'mobile_recharge',
                :provider,
                :phone_number,
                :amount,
                :fee,
                :total_amount,
                :reference,
                'pending',
                :response_message
            )
        ");

        $responseMessage =
            'تم استلام طلب الشحن بنجاح، وهو الآن قيد المراجعة. ' .
            'التنفيذ الفعلي يتطلب ربط المنصة بالتكامل الرسمي لمزود الخدمة.';

        $stmt->execute([
            ':user_id' => $userId,
            ':provider' => $providers[$provider],
            ':phone_number' => $phone,
            ':amount' => $amount,
            ':fee' => $fee,
            ':total_amount' => $totalAmount,
            ':reference' => $reference,
            ':response_message' => $responseMessage,
        ]);

        $requestId = (int) $pdo->lastInsertId();

        if (function_exists('createAdminNotification')) {
            createAdminNotification(
                'طلب شحن هاتف جديد',
                'تم إنشاء طلب شحن ' .
                $providers[$provider] .
                ' للرقم ' .
                $phone .
                '. المرجع: ' .
                $reference,
                'recharge',
                'service_request',
                $requestId
            );
        }

        $pdo->commit();

        flash(
            'success',
            'تم إنشاء طلب الشحن بنجاح. المرجع: ' . $reference
        );

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash(
            'error',
            'حدث خطأ أثناء إنشاء طلب الشحن.'
        );
    }

    header('Location: recharge.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT
        id,
        provider,
        phone_number,
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
      AND service_type = 'mobile_recharge'
    ORDER BY id DESC
    LIMIT 10
");

$stmt->execute([
    ':user_id' => $userId,
]);

$requests = $stmt->fetchAll();

$successMessage = flash('success');
$errorMessage = flash('error');

$actionToken = actionToken('recharge');
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

    <title>شحن الهاتف</title>

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
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        h1 {
            margin: 0;
            font-size: 27px;
        }

        .back {
            display: inline-block;
            background: #111827;
            color: #fff;
            text-decoration: none;
            padding: 11px 16px;
            border-radius: 10px;
            font-weight: 800;
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
            color: #b91c1c;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 18px;
            font-weight: 700;
        }

        .notice {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            border-radius: 12px;
            padding: 15px;
            line-height: 1.8;
            margin-bottom: 18px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 15px;
        }

        label {
            display: block;
            font-weight: 800;
            margin-bottom: 7px;
        }

        input,
        select {
            width: 100%;
            padding: 13px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #fff;
            font-size: 15px;
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: #2563eb;
        }

        button {
            width: 100%;
            border: 0;
            background: #111827;
            color: #fff;
            padding: 14px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            margin-top: 18px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #edf0f5;
            text-align: right;
            vertical-align: top;
        }

        th {
            background: #f8fafc;
            font-size: 13px;
        }

        td {
            font-size: 14px;
        }

        .reference {
            font-weight: 800;
            word-break: break-word;
        }

        .muted {
            color: #6b7280;
            font-size: 12px;
            margin-top: 5px;
        }

        .status {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
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
            color: #b91c1c;
        }

        .cancelled {
            background: #f3f4f6;
            color: #4b5563;
        }

        .empty {
            text-align: center;
            color: #6b7280;
            padding: 30px;
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
                font-size: 23px;
            }

            .back {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="topbar">

        <h1>📱 شحن الهاتف</h1>

        <a
            class="back"
            href="index.php"
        >
            ← الخدمات
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

    <div class="notice">
        ⚠️ يتم حاليًا تسجيل طلب الشحن ومتابعته من لوحة الإدارة.
        التنفيذ الآلي الفعلي يحتاج إلى تكامل رسمي مع شركة الاتصالات أو
        مزود دفع معتمد.
    </div>

    <div class="card">

        <h2>إنشاء طلب شحن</h2>

        <form method="post">

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

            <div class="grid">

                <div>
                    <label for="provider">
                        شركة الاتصالات
                    </label>

                    <select
                        id="provider"
                        name="provider"
                        required
                    >
                        <option value="">
                            اختر الشركة
                        </option>

                        <?php foreach ($providers as $value => $label): ?>

                            <option value="<?= e($value) ?>">
                                <?= e($label) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div>
                    <label for="phone">
                        رقم الهاتف
                    </label>

                    <input
                        id="phone"
                        type="tel"
                        name="phone"
                        inputmode="numeric"
                        placeholder="مثال: 0912345678"
                        required
                    >
                </div>

                <div>
                    <label for="amount">
                        مبلغ الشحن
                    </label>

                    <input
                        id="amount"
                        type="number"
                        name="amount"
                        min="100"
                        max="100000"
                        step="0.01"
                        placeholder="مثال: 1000"
                        required
                    >
                </div>

            </div>

            <button type="submit">
                إنشاء طلب الشحن
            </button>

        </form>

    </div>

    <div class="card">

        <h2>📋 طلبات الشحن السابقة</h2>

        <?php if (!$requests): ?>

            <div class="empty">
                لا توجد طلبات شحن حتى الآن.
            </div>

        <?php else: ?>

            <div class="table-wrap">

                <table>

                    <thead>
                        <tr>
                            <th>الشركة</th>
                            <th>الرقم</th>
                            <th>المبلغ</th>
                            <th>المرجع</th>
                            <th>الحالة</th>
                            <th>الرسالة</th>
                            <th>التاريخ</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($requests as $request): ?>

                        <?php
                        $requestStatus =
                            (string) $request['status'];

                        $requestStatusLabel =
                            $statusLabels[$requestStatus]
                            ?? $requestStatus;

                        $requestStatusClass =
                            $statusClasses[$requestStatus]
                            ?? 'pending';
                        ?>

                        <tr>

                            <td>
                                <?= e($request['provider']) ?>
                            </td>

                            <td>
                                <?= e($request['phone_number']) ?>
                            </td>

                            <td>
                                <strong>
                                    <?= e(
                                        formatMoney(
                                            (float)
                                            $request['total_amount']
                                        )
                                    ) ?>
                                </strong>
                                SDG

                                <?php if ((float) $request['fee'] > 0): ?>
                                    <div class="muted">
                                        الرسوم:
                                        <?= e(
                                            formatMoney(
                                                (float)
                                                $request['fee']
                                            )
                                        ) ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="reference">
                                    <?= e($request['reference']) ?>
                                </div>
                            </td>

                            <td>
                                <span
                                    class="status <?= e(
                                        $requestStatusClass
                                    ) ?>"
                                >
                                    <?= e($requestStatusLabel) ?>
                                </span>
                            </td>

                            <td>
                                <?php if (!empty($request['response_message'])): ?>
                                    <?= nl2br(
                                        e(
                                            $request['response_message']
                                        )
                                    ) ?>
                                <?php else: ?>
                                    <span class="muted">
                                        لا توجد رسالة.
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= e($request['created_at']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
