<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = currentUserId();
$message = flash('success') ?? '';
$error = flash('error') ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiverPhone = trim((string) ($_POST['receiver_phone'] ?? ''));
    $amount = normalizeAmount($_POST['amount'] ?? '');
    $note = cleanNote($_POST['note'] ?? '');

    if (!verifyCsrf($_POST['_csrf'] ?? null)) {
        $error = 'انتهت صلاحية النموذج. أعد تحميل الصفحة وحاول مرة أخرى.';
    } elseif (!consumeActionToken('transfer', $_POST['_action'] ?? null)) {
        $error = 'تمت معالجة هذا الطلب مسبقًا أو انتهت صلاحيته.';
    } elseif ($receiverPhone === '') {
        $error = 'يرجى إدخال رقم هاتف المستلم.';
    } elseif ($amount === null) {
        $error = 'يرجى إدخال مبلغ صحيح من منزلتين عشريتين كحد أقصى.';
    } elseif (($_POST['note'] ?? '') !== '' && $note === null) {
        $error = 'الملاحظة طويلة جدًا.';
    } else {
        try {
            $pdo->beginTransaction();

            $senderLookup = $pdo->prepare(
                'SELECT w.id AS wallet_id, w.currency, u.id AS user_id,
                        u.full_name, u.phone
                 FROM wallets w
                 INNER JOIN users u ON u.id = w.user_id
                 WHERE u.id = :user_id
                 LIMIT 1'
            );
            $senderLookup->execute([':user_id' => $userId]);
            $sender = $senderLookup->fetch();

            if (!$sender) {
                throw new RuntimeException('لم يتم العثور على محفظتك.');
            }

            $receiverLookup = $pdo->prepare(
                'SELECT w.id AS wallet_id, w.currency, u.id AS user_id,
                        u.full_name, u.phone, u.status
                 FROM users u
                 INNER JOIN wallets w ON w.user_id = u.id
                 WHERE u.phone = :phone
                 LIMIT 1'
            );
            $receiverLookup->execute([':phone' => $receiverPhone]);
            $receiver = $receiverLookup->fetch();

            if (!$receiver) {
                throw new RuntimeException(
                    'لم يتم العثور على مستخدم بهذا الرقم.'
                );
            }

            if ((int) $receiver['user_id'] === $userId) {
                throw new RuntimeException(
                    'لا يمكنك إرسال الأموال إلى نفسك.'
                );
            }

            if ($receiver['status'] !== 'active') {
                throw new RuntimeException('حساب المستلم غير نشط.');
            }

            if ($sender['currency'] !== $receiver['currency']) {
                throw new RuntimeException('عملة المحافظ غير متطابقة.');
            }

            $walletIds = [
                (int) $sender['wallet_id'],
                (int) $receiver['wallet_id'],
            ];
            sort($walletIds, SORT_NUMERIC);

            $lockStmt = $pdo->prepare(
                'SELECT w.id AS wallet_id, w.balance, w.currency
                 FROM wallets w
                 WHERE w.id IN (:wallet_a, :wallet_b)
                 ORDER BY w.id
                 FOR UPDATE'
            );
            $lockStmt->execute([
                ':wallet_a' => $walletIds[0],
                ':wallet_b' => $walletIds[1],
            ]);

            $lockedWallets = [];
            foreach ($lockStmt->fetchAll() as $lockedWallet) {
                $lockedWallets[(int) $lockedWallet['wallet_id']] =
                    $lockedWallet;
            }

            if (
                !isset($lockedWallets[(int) $sender['wallet_id']])
                || !isset($lockedWallets[(int) $receiver['wallet_id']])
            ) {
                throw new RuntimeException('تعذر قفل المحافظ.');
            }

            $transferReference =
                'TRF-' . date('YmdHis') . '-' .
                strtoupper(bin2hex(random_bytes(16)));
            $senderReference =
                'OUT-' . date('YmdHis') . '-' .
                strtoupper(bin2hex(random_bytes(16)));
            $receiverReference =
                'IN-' . date('YmdHis') . '-' .
                strtoupper(bin2hex(random_bytes(16)));

            $debitStmt = $pdo->prepare(
                'UPDATE wallets
                 SET balance = balance - :amount
                 WHERE id = :wallet_id
                   AND balance >= :amount_check'
            );
            $debitStmt->execute([
                ':amount' => $amount,
                ':wallet_id' => $sender['wallet_id'],
                ':amount_check' => $amount,
            ]);

            if ($debitStmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'الرصيد غير كافٍ لإرسال هذا المبلغ.'
                );
            }

            $creditStmt = $pdo->prepare(
                'UPDATE wallets
                 SET balance = balance + :amount
                 WHERE id = :wallet_id'
            );
            $creditStmt->execute([
                ':amount' => $amount,
                ':wallet_id' => $receiver['wallet_id'],
            ]);

            if ($creditStmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'تعذر إضافة المبلغ إلى محفظة المستلم.'
                );
            }

            $transferStmt = $pdo->prepare(
                'INSERT INTO transfers
                (sender_wallet_id, receiver_wallet_id, amount, reference,
                 status, note)
                VALUES
                (:sender_wallet_id, :receiver_wallet_id, :amount,
                 :reference, :status, :note)'
            );
            $transferStmt->execute([
                ':sender_wallet_id' => $sender['wallet_id'],
                ':receiver_wallet_id' => $receiver['wallet_id'],
                ':amount' => $amount,
                ':reference' => $transferReference,
                ':status' => 'completed',
                ':note' => $note,
            ]);

            $transactionStmt = $pdo->prepare(
                'INSERT INTO transactions
                (wallet_id, type, amount, reference, description, status)
                VALUES
                (:wallet_id, :type, :amount, :reference, :description,
                 :status)'
            );

            $transactionStmt->execute([
                ':wallet_id' => $sender['wallet_id'],
                ':type' => 'transfer_out',
                ':amount' => $amount,
                ':reference' => $senderReference,
                ':description' =>
                    'إرسال أموال إلى ' . $receiver['full_name'] .
                    ' - ' . $transferReference,
                ':status' => 'completed',
            ]);

            $transactionStmt->execute([
                ':wallet_id' => $receiver['wallet_id'],
                ':type' => 'transfer_in',
                ':amount' => $amount,
                ':reference' => $receiverReference,
                ':description' =>
                    'استلام أموال من ' . $sender['full_name'] .
                    ' - ' . $transferReference,
                ':status' => 'completed',
            ]);

            $pdo->commit();

            flash(
                'success',
                'تم إرسال ' . formatMoney($amount) . ' ' .
                $sender['currency'] . ' بنجاح إلى ' .
                $receiver['full_name'] . '. رقم التحويل: ' .
                $transferReference
            );
            header('Location: transfer.php');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());
            $error = $e instanceof PDOException
                ? 'تعذر تنفيذ التحويل.'
                : $e->getMessage();
        }
    }
}

$userStmt = $pdo->prepare(
    'SELECT u.full_name, u.phone, w.balance, w.currency
     FROM users u
     INNER JOIN wallets w ON w.user_id = u.id
     WHERE u.id = :user_id
     LIMIT 1'
);
$userStmt->execute([':user_id' => $userId]);
$user = $userStmt->fetch();

if (!$user) {
    http_response_code(404);
    exit('تعذر تحميل بيانات المحفظة.');
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إرسال الأموال - Smart Wallet</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: #f4f7fb;
            color: #172033;
        }
        .container { width: min(520px, 92%); margin: 30px auto; }
        .header, .card {
            background: #fff;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 5px 20px rgba(0,0,0,.06);
        }
        .header {
            background: #172033;
            color: #fff;
            margin-bottom: 18px;
        }
        h1, h2 { margin-top: 0; }
        .balance {
            margin-top: 18px;
            padding: 15px;
            background: rgba(255,255,255,.1);
            border-radius: 12px;
        }
        .balance-value {
            display: block;
            margin-top: 5px;
            font-size: 26px;
            font-weight: bold;
        }
        label { display: block; font-weight: bold; margin: 14px 0 7px; }
        input, textarea {
            width: 100%;
            padding: 13px;
            border: 1px solid #d8dee8;
            border-radius: 10px;
            font-size: 16px;
            font-family: inherit;
        }
        textarea { min-height: 90px; resize: vertical; }
        button {
            width: 100%;
            margin-top: 20px;
            padding: 14px;
            border: 0;
            border-radius: 10px;
            background: #2563eb;
            color: #fff;
            font-size: 17px;
            font-weight: bold;
        }
        .hint { color: #697386; font-size: 13px; line-height: 1.7; }
        .success, .error {
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            line-height: 1.8;
        }
        .success { background: #e8f7ee; color: #176b3a; }
        .error { background: #fdecec; color: #a52222; }
        .back { display: block; text-align: center; margin-top: 18px; color: #2563eb; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>💸 إرسال الأموال</h1>
        <p>تحويل داخلي بين مستخدمي النسخة المحلية</p>
        <div class="balance">
            رصيدك الحالي
            <strong class="balance-value">
                <?= e(formatMoney($user['balance'])) ?>
                <?= e($user['currency']) ?>
            </strong>
        </div>
    </div>

    <div class="card">
        <?php if ($message !== ''): ?>
            <div class="success"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="transfer.php">
            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
            <input
                type="hidden"
                name="_action"
                value="<?= e(actionToken('transfer')) ?>"
            >

            <label for="receiver_phone">رقم هاتف المستلم</label>
            <input
                type="tel"
                id="receiver_phone"
                name="receiver_phone"
                autocomplete="tel"
                required
            >

            <label for="amount">مبلغ الإرسال</label>
            <input
                type="text"
                inputmode="decimal"
                id="amount"
                name="amount"
                placeholder="0.00"
                required
            >
            <p class="hint">
                الرصيد المتاح:
                <?= e(formatMoney($user['balance'])) ?>
                <?= e($user['currency']) ?>
            </p>

            <label for="note">ملاحظة</label>
            <textarea
                id="note"
                name="note"
                maxlength="255"
                placeholder="ملاحظة اختيارية"
            ></textarea>

            <button type="submit">إرسال الأموال</button>
        </form>

        <a class="back" href="index.php">← العودة إلى المحفظة</a>
    </div>
</div>
</body>
</html>