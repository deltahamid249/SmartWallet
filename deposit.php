<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
requireLogin();

global $pdo;

$userId = currentUserId();

/* جلب المحفظة */
$stmt = $pdo->prepare(
    'SELECT id, balance FROM wallets WHERE user_id = :user_id LIMIT 1'
);
$stmt->execute([':user_id' => $userId]);
$wallet = $stmt->fetch();

if (!$wallet) {
    http_response_code(500);
    exit('Wallet not found');
}

$walletId = (int) $wallet['id'];

/* معالجة الرصيد التجريبي */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'demo_deposit') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'طلب غير صالح.');
        redirectTo('/deposit.php');
    }

    if (!consumeActionToken('demo_deposit', $_POST['action_token'] ?? null)) {
        flash('error', 'انتهت صلاحية الطلب، حاول مرة أخرى.');
        redirectTo('/deposit.php');
    }

    $amount = normalizeAmount($_POST['amount'] ?? null);

    if ($amount === null || bccomp($amount, '0.00', 2) <= 0) {
        flash('error', 'أدخل مبلغًا تجريبيًا صحيحًا.');
        redirectTo('/deposit.php');
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'SELECT id, balance
             FROM wallets
             WHERE id = :wallet_id AND user_id = :user_id
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

        $stmt = $pdo->prepare(
            'UPDATE wallets
             SET balance = balance + :amount
             WHERE id = :wallet_id'
        );
        $stmt->execute([
            ':amount' => $amount,
            ':wallet_id' => $walletId,
        ]);

        $reference = 'DEMO-' . strtoupper(bin2hex(random_bytes(6)));

        $stmt = $pdo->prepare(
            'INSERT INTO transactions
                (wallet_id, type, amount, reference, description, status)
             VALUES
                (:wallet_id, :type, :amount, :reference, :description, :status)'
        );

        $stmt->execute([
            ':wallet_id' => $walletId,
            ':type' => 'deposit',
            ':amount' => $amount,
            ':reference' => $reference,
            ':description' => 'إيداع تجريبي',
            ':status' => 'completed',
        ]);

        $pdo->commit();

        flash(
            'success',
            'تمت إضافة الرصيد التجريبي بنجاح: ' . formatMoney($amount)
        );

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash('error', 'تعذر إضافة الرصيد التجريبي.');
    }

    redirectTo('/deposit.php');
}

/* معالجة طلب الإيداع الحقيقي */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'deposit') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'طلب غير صالح.');
        redirectTo('/deposit.php');
    }

    if (!consumeActionToken('deposit', $_POST['action_token'] ?? null)) {
        flash('error', 'انتهت صلاحية الطلب، حاول مرة أخرى.');
        redirectTo('/deposit.php');
    }

    $amount = normalizeAmount($_POST['amount'] ?? null);
    $bankName = trim((string) ($_POST['bank_name'] ?? ''));
    $senderName = trim((string) ($_POST['sender_name'] ?? ''));
    $bankReference = trim((string) ($_POST['bank_reference'] ?? ''));
    $note = cleanNote($_POST['note'] ?? null);

    if ($amount === null || bccomp($amount, '0.00', 2) <= 0) {
        flash('error', 'أدخل مبلغًا صحيحًا.');
        redirectTo('/deposit.php');
    }

    if ($bankName === '') {
        flash('error', 'أدخل اسم البنك.');
        redirectTo('/deposit.php');
    }

    if ($bankReference === '') {
        flash('error', 'أدخل رقم مرجع التحويل.');
        redirectTo('/deposit.php');
    }

    $proofPath = null;

    try {
        if (
            isset($_FILES['proof_file']) &&
            is_array($_FILES['proof_file']) &&
            ($_FILES['proof_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
        ) {
            $file = $_FILES['proof_file'];

            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('فشل رفع الملف.');
            }

            if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
                throw new RuntimeException('حجم الملف يجب ألا يتجاوز 2MB.');
            }

            $tmpName = (string) ($file['tmp_name'] ?? '');

            if (!is_uploaded_file($tmpName)) {
                throw new RuntimeException('ملف غير صالح.');
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmpName);

            $allowed = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf',
            ];

            if (!isset($allowed[$mime])) {
                throw new RuntimeException(
                    'نوع الملف غير مسموح. استخدم JPG أو PNG أو WEBP أو PDF.'
                );
            }

            $uploadDir = __DIR__ . '/uploads/deposits';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
            $destination = $uploadDir . '/' . $filename;

            if (!move_uploaded_file($tmpName, $destination)) {
                throw new RuntimeException('تعذر حفظ الملف.');
            }

            $proofPath = 'uploads/deposits/' . $filename;
        }

        $reference = 'DEP-' . strtoupper(bin2hex(random_bytes(6)));

        $stmt = $pdo->prepare(
            'INSERT INTO deposit_requests
                (
                    reference,
                    user_id,
                    wallet_id,
                    amount,
                    bank_name,
                    sender_name,
                    bank_reference,
                    proof_file,
                    note,
                    status
                )
             VALUES
                (
                    :reference,
                    :user_id,
                    :wallet_id,
                    :amount,
                    :bank_name,
                    :sender_name,
                    :bank_reference,
                    :proof_file,
                    :note,
                    :status
                )'
        );

        $stmt->execute([
            ':reference' => $reference,
            ':user_id' => $userId,
            ':wallet_id' => $walletId,
            ':amount' => $amount,
            ':bank_name' => $bankName,
            ':sender_name' => $senderName !== '' ? $senderName : null,
            ':bank_reference' => $bankReference,
            ':proof_file' => $proofPath,
            ':note' => $note,
            ':status' => 'pending',
        ]);

        $depositRequestId = (int) $pdo->lastInsertId();

        createAdminNotification(
            'طلب إيداع جديد',
            'تم إرسال طلب إيداع جديد للمراجعة. المرجع: ' . $reference,
            'deposit',
            'deposit',
            $depositRequestId
        );

        flash(
            'success',
            'تم إرسال طلب الإيداع للمراجعة. المرجع: ' . $reference
        );

    } catch (Throwable $e) {
        if ($proofPath !== null) {
            $fullProofPath = __DIR__ . '/' . $proofPath;

            if (is_file($fullProofPath)) {
                @unlink($fullProofPath);
            }
        }

        flash(
            'error',
            $e->getMessage() !== ''
                ? $e->getMessage()
                : 'تعذر إرسال طلب الإيداع.'
        );
    }

    redirectTo('/deposit.php');
}

/* تحديث الرصيد بعد أي عملية */
$stmt = $pdo->prepare(
    'SELECT balance FROM wallets WHERE id = :wallet_id LIMIT 1'
);
$stmt->execute([':wallet_id' => $walletId]);
$currentBalance = $stmt->fetchColumn();

$currentBalance = $currentBalance !== false
    ? (string) $currentBalance
    : '0.00';

/* سجل طلبات الإيداع */
$stmt = $pdo->prepare(
    'SELECT
        reference,
        amount,
        bank_name,
        status,
        created_at
     FROM deposit_requests
     WHERE user_id = :user_id
     ORDER BY id DESC
     LIMIT 10'
);
$stmt->execute([':user_id' => $userId]);
$depositRequests = $stmt->fetchAll();

$successMessage = flash('success');
$errorMessage = flash('error');

$csrfToken = csrfToken();
$depositActionToken = actionToken('deposit');
$demoActionToken = actionToken('demo_deposit');

/**
 * تحويل حالة الإيداع المخزنة إلى عنوان عربي.
 */
function depositStatusLabel(string $status): string
{
    return match ($status) {
        'pending' => 'قيد المراجعة',
        'approved' => 'مقبول',
        'rejected' => 'مرفوض',
        'cancelled' => 'ملغي',
        default => $status,
    };
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>إضافة الأموال - المحفظة الذكية</title>

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

        .demo-card {
            border: 2px solid #f59e0b;
            background: #fffbeb;
        }

        .demo-title {
            font-size: 20px;
            font-weight: 900;
            color: #92400e;
            margin-bottom: 8px;
        }

        .demo-note {
            color: #78350f;
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 18px;
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
        }

        .error {
            background: #fef2f2;
            border-right: 4px solid #ef4444;
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            color: #991b1b;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input,
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
        }

        .demo-button {
            background: #f59e0b;
            color: #ffffff;
        }

        .demo-button:hover {
            background: #d97706;
        }

        .primary-button {
            background: #2563eb;
            color: #ffffff;
        }

        .primary-button:hover {
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
            margin-bottom: 7px;
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
        <h1>إضافة الأموال</h1>
        <p>إدارة رصيد محفظتك وإرسال طلبات الإيداع.</p>
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
        <div class="balance-label">الرصيد الحالي</div>

        <div class="balance">
            <?= e(formatMoney($currentBalance)) ?>
        </div>
    </div>

    <!-- الرصيد التجريبي -->
    <div class="card demo-card">

        <div class="demo-title">
            🧪 إضافة رصيد تجريبي
        </div>

        <div class="demo-note">
            هذه الخاصية للاختبار فقط. المبلغ الذي تضيفه هنا سيظهر مباشرة
            داخل <strong>الرصيد الحالي</strong> في المحفظة، ويتم تسجيل العملية
            على أنها إيداع تجريبي.
        </div>

        <form method="post">

            <input
                type="hidden"
                name="action"
                value="demo_deposit"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($csrfToken) ?>"
            >

            <input
                type="hidden"
                name="action_token"
                value="<?= e($demoActionToken) ?>"
            >

            <label for="demo_amount">
                مبلغ الرصيد التجريبي
            </label>

            <input
                id="demo_amount"
                type="number"
                name="amount"
                min="0.01"
                step="0.01"
                placeholder="مثال: 10000"
                required
            >

            <button
                type="submit"
                class="demo-button"
            >
                إضافة الرصيد التجريبي
            </button>

        </form>

    </div>

    <!-- الإيداع الحقيقي -->
    <div class="card">

        <h2 class="section-title">
            إيداع أموال حقيقي
        </h2>

        <div class="warning">
            <strong>تنبيه:</strong>
            طلب الإيداع الحقيقي لا يزيد الرصيد مباشرة.
            سيتم تسجيل الطلب بحالة «قيد المراجعة»، وبعد التحقق من التحويل
            يمكن اعتماده.
        </div>

        <form
            method="post"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="action"
                value="deposit"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($csrfToken) ?>"
            >

            <input
                type="hidden"
                name="action_token"
                value="<?= e($depositActionToken) ?>"
            >

            <label for="amount">
                مبلغ الإيداع
            </label>

            <input
                id="amount"
                type="number"
                name="amount"
                min="0.01"
                step="0.01"
                placeholder="أدخل المبلغ"
                required
            >

            <label for="bank_name">
                اسم البنك
            </label>

            <input
                id="bank_name"
                type="text"
                name="bank_name"
                placeholder="مثال: بنك ..."
                maxlength="150"
                required
            >

            <label for="sender_name">
                اسم صاحب الحساب المحول
            </label>

            <input
                id="sender_name"
                type="text"
                name="sender_name"
                maxlength="150"
                placeholder="اسم صاحب التحويل"
            >

            <label for="bank_reference">
                رقم مرجع التحويل
            </label>

            <input
                id="bank_reference"
                type="text"
                name="bank_reference"
                maxlength="150"
                placeholder="رقم العملية أو المرجع"
                required
            >

            <label for="proof_file">
                إثبات التحويل
            </label>

            <input
                id="proof_file"
                type="file"
                name="proof_file"
                accept=".jpg,.jpeg,.png,.webp,.pdf"
            >

            <div class="muted">
                الأنواع المسموحة: JPG, PNG, WEBP, PDF — الحد الأقصى 2MB.
            </div>

            <br>

            <label for="note">
                ملاحظات
            </label>

            <textarea
                id="note"
                name="note"
                maxlength="255"
                placeholder="أي ملاحظات إضافية..."
            ></textarea>

            <button
                type="submit"
                class="primary-button"
            >
                إرسال طلب الإيداع
            </button>

        </form>

    </div>

    <!-- سجل الطلبات -->
    <div class="card">

        <h2 class="section-title">
            آخر طلبات الإيداع
        </h2>

        <?php if (!$depositRequests): ?>

            <p class="muted">
                لا توجد طلبات إيداع حتى الآن.
            </p>

        <?php else: ?>

            <?php foreach ($depositRequests as $request): ?>

                <div class="request">

                    <div class="request-row">
                        <strong>
                            <?= e(formatMoney($request['amount'])) ?>
                        </strong>

                        <span class="status status-<?= e($request['status']) ?>">
                            <?= e(depositStatusLabel((string) $request['status'])) ?>
                        </span>
                    </div>

                    <div class="muted">
                        البنك:
                        <?= e($request['bank_name']) ?>
                    </div>

                    <div class="muted">
                        المرجع:
                        <?= e($request['reference'] ?? '-') ?>
                    </div>

                    <div class="muted">
                        <?= e($request['created_at']) ?>
                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
