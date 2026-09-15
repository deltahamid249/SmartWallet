<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

global $pdo;

$userId = currentUserId();

/* جلب محفظة المستخدم */
$stmt = $pdo->prepare(
    'SELECT id, balance, currency
     FROM wallets
     WHERE user_id = :user_id
     LIMIT 1'
);
$stmt->execute([':user_id' => $userId]);
$wallet = $stmt->fetch();

if (!$wallet) {
    http_response_code(500);
    exit('Wallet not found');
}

$walletId = (int) $wallet['id'];
$currency = (string) $wallet['currency'];

/* معالجة طلب السحب */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'withdraw'
) {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'طلب غير صالح.');
        header('Location: /withdraw.php');
        exit;
    }

    if (!consumeActionToken('withdraw', $_POST['action_token'] ?? null)) {
        flash('error', 'انتهت صلاحية الطلب، حاول مرة أخرى.');
        header('Location: /withdraw.php');
        exit;
    }

    $amount = normalizeAmount($_POST['amount'] ?? null);
    $recipientName = trim((string) ($_POST['recipient_name'] ?? ''));
    $recipientPhone = trim((string) ($_POST['recipient_phone'] ?? ''));
    $withdrawalMethod = trim((string) ($_POST['withdrawal_method'] ?? 'manual'));
    $note = cleanNote($_POST['note'] ?? null);

    if ($amount === null || bccomp($amount, '0.00', 2) <= 0) {
        flash('error', 'أدخل مبلغ سحب صحيح.');
        header('Location: /withdraw.php');
        exit;
    }

    if ($recipientName === '') {
        flash('error', 'أدخل اسم المستفيد.');
        header('Location: /withdraw.php');
        exit;
    }

    if ($recipientPhone === '') {
        flash('error', 'أدخل رقم هاتف المستفيد.');
        header('Location: /withdraw.php');
        exit;
    }

    $allowedMethods = [
        'manual',
        'bank',
        'mobile_money',
    ];

    if (!in_array($withdrawalMethod, $allowedMethods, true)) {
        flash('error', 'طريقة السحب غير صالحة.');
        header('Location: /withdraw.php');
        exit;
    }

    try {
        $pdo->beginTransaction();

        /*
         * قفل المحفظة مؤقتًا أثناء التحقق.
         * لا يتم خصم الرصيد هنا.
         */
        $stmt = $pdo->prepare(
            'SELECT id, balance, currency
             FROM wallets
             WHERE id = :wallet_id
               AND user_id = :user_id
             LIMIT 1
             FOR UPDATE'
        );

        $stmt->execute([
            ':wallet_id' => $walletId,
            ':user_id' => $userId,
        ]);

        $lockedWallet = $stmt->fetch();

        if (!$lockedWallet) {
            throw new RuntimeException('Wallet not found');
        }

        $currentBalance = (string) $lockedWallet['balance'];
        $requestedAmount = (string) $amount;

        if (bccomp($requestedAmount, $currentBalance, 2) > 0) {
            throw new RuntimeException(
                'الرصيد غير كافٍ لتنفيذ طلب السحب.'
            );
        }

        $reference = 'WDR-' . strtoupper(bin2hex(random_bytes(6)));

        $stmt = $pdo->prepare(
            'INSERT INTO withdrawal_requests
                (
                    reference,
                    user_id,
                    wallet_id,
                    amount,
                    recipient_name,
                    recipient_phone,
                    withdrawal_method,
                    note,
                    status
                )
             VALUES
                (
                    :reference,
                    :user_id,
                    :wallet_id,
                    :amount,
                    :recipient_name,
                    :recipient_phone,
                    :withdrawal_method,
                    :note,
                    :status
                )'
        );

        $stmt->execute([
            ':reference' => $reference,
            ':user_id' => $userId,
            ':wallet_id' => $walletId,
            ':amount' => $amount,
            ':recipient_name' => $recipientName,
            ':recipient_phone' => $recipientPhone,
            ':withdrawal_method' => $withdrawalMethod,
            ':note' => $note,
            ':status' => 'pending',
        ]);

        $withdrawalRequestId = (int) $pdo->lastInsertId();

        createAdminNotification(
            'طلب سحب جديد',
            'تم إرسال طلب سحب جديد للمراجعة. المرجع: ' . $reference,
            'withdrawal',
            'withdrawal',
            $withdrawalRequestId
        );

        $pdo->commit();

        flash(
            'success',
            'تم إرسال طلب السحب للمراجعة. رقم الطلب: ' . $reference
        );

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash(
            'error',
            $e->getMessage() !== ''
                ? $e->getMessage()
                : 'تعذر إنشاء طلب السحب.'
        );
    }

    header('Location: /withdraw.php');
    exit;
}

/* تحديث الرصيد بعد الطلب */
$stmt = $pdo->prepare(
    'SELECT balance
     FROM wallets
     WHERE id = :wallet_id
     LIMIT 1'
);
$stmt->execute([':wallet_id' => $walletId]);

$currentBalance = $stmt->fetchColumn();

$currentBalance = $currentBalance !== false
    ? (string) $currentBalance
    : '0.00';

/* جلب آخر طلبات السحب */
$stmt = $pdo->prepare(
    'SELECT
        reference,
        amount,
        recipient_name,
        recipient_phone,
        withdrawal_method,
        status,
        review_note,
        created_at
     FROM withdrawal_requests
     WHERE user_id = :user_id
     ORDER BY id DESC
     LIMIT 10'
);

$stmt->execute([':user_id' => $userId]);

$withdrawalRequests = $stmt->fetchAll();

$successMessage = flash('success');
$errorMessage = flash('error');

$csrfToken = csrfToken();
$actionToken = actionToken('withdraw');

function withdrawalStatusLabel(string $status): string
{
    return match ($status) {
        'pending' => 'قيد المراجعة',
        'approved' => 'مقبول',
        'rejected' => 'مرفوض',
        'cancelled' => 'ملغي',
        default => $status,
    };
}

function withdrawalMethodLabel(string $method): string
{
    return match ($method) {
        'bank' => 'تحويل بنكي',
        'mobile_money' => 'محفظة إلكترونية',
        'manual' => 'استلام يدوي',
        default => $method,
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

    <title>سحب الأموال - المحفظة الذكية</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f7fb;
            color: #111827;
            font-family: Tahoma, Arial, sans-serif;
        }

        .container {
            width: min(900px, calc(100% - 30px));
            margin: 30px auto;
        }

        .header {
            background: #ffffff;
            padding: 22px;
            border-radius: 18px;
            margin-bottom: 18px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.07);
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 25px;
        }

        .header p {
            margin: 0;
            color: #6b7280;
            line-height: 1.8;
        }

        .card {
            background: #ffffff;
            border-radius: 18px;
            padding: 22px;
            margin-bottom: 18px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.07);
        }

        .balance-card {
            text-align: center;
            border: 2px solid #2563eb;
        }

        .balance-label {
            font-size: 14px;
            font-weight: bold;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .balance {
            font-size: 34px;
            font-weight: 900;
            color: #2563eb;
        }

        .currency {
            font-size: 15px;
            color: #6b7280;
            margin-top: 5px;
        }

        .info {
            background: #eff6ff;
            border-right: 4px solid #2563eb;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            line-height: 1.8;
        }

        .warning {
            background: #fff7ed;
            border-right: 4px solid #f97316;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            line-height: 1.8;
        }

        .success {
            background: #ecfdf5;
            border-right: 4px solid #10b981;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            color: #065f46;
            line-height: 1.8;
        }

        .error {
            background: #fef2f2;
            border-right: 4px solid #ef4444;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            color: #991b1b;
            line-height: 1.8;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 13px;
            margin-bottom: 18px;
            border: 1px solid #d8dee8;
            border-radius: 10px;
            font-size: 16px;
            outline: none;
            background: #ffffff;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #2563eb;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 10px;
            padding: 14px;
            font-size: 16px;
            font-weight: 900;
            cursor: pointer;
            background: #2563eb;
            color: #ffffff;
        }

        button:hover {
            background: #1d4ed8;
        }

        .section-title {
            margin-top: 0;
            margin-bottom: 18px;
            font-size: 21px;
        }

        .request {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
        }

        .request-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }

        .status {
            font-weight: bold;
        }

        .status-pending {
            color: #d97706;
        }

        .status-approved {
            color: #059669;
        }

        .status-rejected {
            color: #16a34a;
        }

        .status-cancelled {
            color: #6b7280;
        }

        .muted {
            color: #6b7280;
            font-size: 13px;
            line-height: 1.8;
        }

        .amount-note {
            color: #6b7280;
            font-size: 13px;
            margin-top: -10px;
            margin-bottom: 18px;
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

        <h1>
            سحب الأموال
        </h1>

        <p>
            يمكنك تقديم طلب سحب من رصيد محفظتك.
            تتم مراجعة الطلب قبل تنفيذ عملية السحب.
        </p>

    </div>


    <?php if ($successMessage !== null): ?>

        <div class="success">
            <?= e($successMessage) ?>
        </div>

    <?php endif; ?>


    <?php if ($errorMessage !== null): ?>

        <div class="error">
            <?= e($errorMessage) ?>
        </div>

    <?php endif; ?>


    <!-- الرصيد الحالي -->

    <div class="card balance-card">

        <div class="balance-label">
            الرصيد المتاح حاليًا
        </div>

        <div class="balance">
            <?= e(formatMoney($currentBalance)) ?>
        </div>

        <div class="currency">
            <?= e($currency) ?>
        </div>

    </div>


    <!-- نموذج السحب -->

    <div class="card">

        <h2 class="section-title">
            إنشاء طلب سحب
        </h2>

        <div class="warning">

            <strong>مهم:</strong>

            إنشاء الطلب لا يعني خصم المبلغ من رصيدك مباشرة.
            سيتم خصم المبلغ فقط بعد اعتماد الطلب من الإدارة.

        </div>


        <form method="post">

            <input
                type="hidden"
                name="action"
                value="withdraw"
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


            <label for="amount">
                مبلغ السحب
            </label>

            <input
                id="amount"
                type="number"
                name="amount"
                min="0.01"
                step="0.01"
                placeholder="مثال: 5000"
                required
            >

            <div class="amount-note">
                الحد الأقصى النظري للسحب:
                <?= e(formatMoney($currentBalance)) ?>
                <?= e($currency) ?>
            </div>


            <label for="recipient_name">
                اسم المستفيد
            </label>

            <input
                id="recipient_name"
                type="text"
                name="recipient_name"
                maxlength="150"
                placeholder="الاسم الكامل للمستفيد"
                required
            >


            <label for="recipient_phone">
                رقم هاتف المستفيد
            </label>

            <input
                id="recipient_phone"
                type="tel"
                name="recipient_phone"
                maxlength="30"
                placeholder="مثال: 09xxxxxxxx"
                required
            >


            <label for="withdrawal_method">
                طريقة استلام الأموال
            </label>

            <select
                id="withdrawal_method"
                name="withdrawal_method"
                required
            >

                <option value="manual">
                    استلام يدوي
                </option>

                <option value="bank">
                    تحويل بنكي
                </option>

                <option value="mobile_money">
                    محفظة إلكترونية
                </option>

            </select>


            <label for="note">
                ملاحظات
            </label>

            <textarea
                id="note"
                name="note"
                maxlength="255"
                placeholder="أي معلومات إضافية..."
            ></textarea>


            <button type="submit">
                إرسال طلب السحب
            </button>

        </form>

    </div>


    <!-- سجل السحوبات -->

    <div class="card">

        <h2 class="section-title">
            سجل طلبات السحب
        </h2>


        <?php if (!$withdrawalRequests): ?>

            <p class="muted">
                لا توجد طلبات سحب حتى الآن.
            </p>

        <?php else: ?>


            <?php foreach ($withdrawalRequests as $request): ?>

                <div class="request">

                    <div class="request-row">

                        <strong>
                            <?= e(formatMoney($request['amount'])) ?>
                            <?= e($currency) ?>
                        </strong>

                        <span
                            class="status status-<?= e($request['status']) ?>"
                        >
                            <?= e(
                                withdrawalStatusLabel(
                                    (string) $request['status']
                                )
                            ) ?>
                        </span>

                    </div>


                    <div class="muted">

                        المستفيد:
                        <?= e($request['recipient_name']) ?>

                    </div>


                    <div class="muted">

                        الهاتف:
                        <?= e($request['recipient_phone']) ?>

                    </div>


                    <div class="muted">

                        الطريقة:
                        <?= e(
                            withdrawalMethodLabel(
                                (string) $request['withdrawal_method']
                            )
                        ) ?>

                    </div>


                    <div class="muted">

                        المرجع:
                        <?= e($request['reference'] ?? '-') ?>

                    </div>


                    <?php if (!empty($request['review_note'])): ?>

                        <div class="muted">

                            ملاحظة الإدارة:
                            <?= e($request['review_note']) ?>

                        </div>

                    <?php endif; ?>


                    <div class="muted">

                        تاريخ الطلب:
                        <?= e($request['created_at']) ?>

                    </div>

                </div>

            <?php endforeach; ?>


        <?php endif; ?>

    </div>

</div>

</body>

</html>
