<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

$csrfToken = csrfToken();
$actionToken = actionToken('bill_payment_request');

$successMessage = '';
$errorMessage = '';

$providers = [
    'water' => 'المياه',
    'telecom' => 'الاتصالات',
    'commercial' => 'خدمة تجارية',
    'other' => 'فاتورة أخرى',
];

$statusLabels = [
    'pending' => 'قيد المراجعة',
    'processing' => 'قيد التنفيذ',
    'completed' => 'مكتمل',
    'failed' => 'فشل',
    'cancelled' => 'ملغي',
];

$statusClasses = [
    'pending' => 'pending',
    'processing' => 'processing',
    'completed' => 'completed',
    'failed' => 'failed',
    'cancelled' => 'cancelled',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verifyCsrf($_POST['csrf_token'] ?? '');

        if (!consumeActionToken(
            'bill_payment_request',
            $_POST['action_token'] ?? ''
        )) {
            throw new RuntimeException(
                'انتهت صلاحية الطلب أو تم استخدامه مسبقًا.'
            );
        }

        $provider = trim((string) ($_POST['provider'] ?? ''));
        $accountNumber = trim(
            (string) ($_POST['account_number'] ?? '')
        );
        $amountRaw = trim(
            (string) ($_POST['amount'] ?? '')
        );

        if (!array_key_exists($provider, $providers)) {
            throw new RuntimeException(
                'يرجى اختيار نوع الفاتورة.'
            );
        }

        if ($accountNumber === '') {
            throw new RuntimeException(
                'يرجى إدخال رقم الحساب أو رقم الفاتورة.'
            );
        }

        if (mb_strlen($accountNumber) > 100) {
            throw new RuntimeException(
                'رقم الحساب أو الفاتورة طويل جدًا.'
            );
        }

        $amount = normalizeAmount($amountRaw);

        if (
            $amount === null ||
            bccomp($amount, '0.00', 2) <= 0
        ) {
            throw new RuntimeException(
                'يرجى إدخال مبلغ صحيح.'
            );
        }

        if (bccomp($amount, '100.00', 2) < 0) {
            throw new RuntimeException(
                'الحد الأدنى للدفع هو 100 SDG.'
            );
        }

        if (bccomp($amount, '1000000.00', 2) > 0) {
            throw new RuntimeException(
                'الحد الأقصى للدفع هو 1,000,000 SDG.'
            );
        }

        $fee = '0.00';
        $totalAmount = $amount;

        $reference =
            'BIL-' . strtoupper(bin2hex(random_bytes(6)));

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO service_requests (
                user_id,
                service_type,
                provider,
                phone_number,
                account_number,
                amount,
                fee,
                total_amount,
                reference,
                status
            )
            VALUES (
                :user_id,
                'bill_payment',
                :provider,
                NULL,
                :account_number,
                :amount,
                :fee,
                :total_amount,
                :reference,
                'pending'
            )
        ");

        $stmt->execute([
            ':user_id' => currentUserId(),
            ':provider' => $provider,
            ':account_number' => $accountNumber,
            ':amount' => $amount,
            ':fee' => $fee,
            ':total_amount' => $totalAmount,
            ':reference' => $reference,
        ]);

        $requestId = (int) $pdo->lastInsertId();

        createAdminNotification(
            'طلب دفع فاتورة جديد',
            'تم إنشاء طلب دفع فاتورة جديد بالمرجع '
            . $reference . '.',
            'service_request',
            'service_request',
            $requestId
        );

        $pdo->commit();

        flash(
            'success',
            'تم إنشاء طلب الفاتورة بنجاح. رقم المرجع: '
            . $reference
        );

        header(
            'Location: request.php?id=' . $requestId
        );
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Bill payment request error: '
            . $e->getMessage()
        );

        $errorMessage = $e instanceof RuntimeException
            ? $e->getMessage()
            : 'حدث خطأ أثناء إنشاء طلب الفاتورة. حاول مرة أخرى.';
    }
}

if (isset($_SESSION['flash_success'])) {
    $successMessage =
        (string) $_SESSION['flash_success'];

    unset($_SESSION['flash_success']);
}

if (isset($_SESSION['flash_error'])) {
    $errorMessage =
        (string) $_SESSION['flash_error'];

    unset($_SESSION['flash_error']);
}

$stmt = $pdo->prepare("
    SELECT
        id,
        provider,
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
      AND service_type = 'bill_payment'
    ORDER BY id DESC
    LIMIT 100
");

$stmt->execute([
    ':user_id' => currentUserId(),
]);

$requests = $stmt->fetchAll();

function billProviderLabel(
    string $provider,
    array $providers
): string {
    return $providers[$provider] ?? $provider;
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

    <title>دفع الفواتير</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: Tahoma, Arial, sans-serif;
        }

        .container {
            width: min(100% - 30px, 1050px);
            margin: 30px auto;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
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
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
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

        <h1>🧾 دفع الفواتير</h1>

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
        ⚠️ يتم حاليًا تسجيل طلب الفاتورة ومتابعته من لوحة الإدارة.
        التنفيذ الآلي الفعلي يحتاج إلى تكامل رسمي مع الجهة المقدمة
        للخدمة أو مزود دفع معتمد.
    </div>

    <div class="card">

        <h2>إنشاء طلب دفع فاتورة</h2>

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
                        نوع الفاتورة
                    </label>

                    <select
                        id="provider"
                        name="provider"
                        required
                    >

                        <option value="">
                            اختر نوع الفاتورة
                        </option>

                        <?php foreach (
                            $providers as $value => $label
                        ): ?>

                            <option
                                value="<?= e($value) ?>"
                            >
                                <?= e($label) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div>

                    <label for="account_number">
                        رقم الحساب أو الفاتورة
                    </label>

                    <input
                        id="account_number"
                        type="text"
                        name="account_number"
                        maxlength="100"
                        placeholder="أدخل الرقم"
                        required
                    >

                </div>

                <div>

                    <label for="amount">
                        مبلغ الفاتورة
                    </label>

                    <input
                        id="amount"
                        type="number"
                        name="amount"
                        min="100"
                        max="1000000"
                        step="0.01"
                        placeholder="مثال: 5000"
                        required
                    >

                </div>

            </div>

            <button type="submit">
                إنشاء طلب الفاتورة
            </button>

        </form>

    </div>

    <div class="card">

        <h2>📋 الفواتير السابقة</h2>

        <?php if (!$requests): ?>

            <div class="empty">
                لا توجد طلبات فواتير حتى الآن.
            </div>

        <?php else: ?>

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>
                            <th>النوع</th>
                            <th>رقم الحساب</th>
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
                                <?= e(
                                    billProviderLabel(
                                        (string) $request['provider'],
                                        $providers
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= e(
                                    $request['account_number']
                                    ?? ''
                                ) ?>
                            </td>

                            <td>

                                <strong>
                                    <?= e(
                                        formatMoney(
                                            $request['total_amount']
                                        )
                                    ) ?>
                                </strong>

                                SDG

                                <?php if (
                                    bccomp(
                                        (string) $request['fee'],
                                        '0.00',
                                        2
                                    ) > 0
                                ): ?>

                                    <div class="muted">
                                        الرسوم:
                                        <?= e(
                                            formatMoney(
                                                $request['fee']
                                            )
                                        ) ?>
                                    </div>

                                <?php endif; ?>

                            </td>

                            <td>

                                <div class="reference">
                                    <?= e(
                                        $request['reference']
                                    ) ?>
                                </div>

                            </td>

                            <td>

                                <span
                                    class="status <?= e(
                                        $requestStatusClass
                                    ) ?>"
                                >
                                    <?= e(
                                        $requestStatusLabel
                                    ) ?>
                                </span>

                            </td>

                            <td>

                                <?php if (
                                    !empty(
                                        $request['response_message']
                                    )
                                ): ?>

                                    <?= nl2br(
                                        e(
                                            $request[
                                                'response_message'
                                            ]
                                        )
                                    ) ?>

                                <?php else: ?>

                                    <span class="muted">
                                        لا توجد رسالة.
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= e(
                                    $request['created_at']
                                ) ?>
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
