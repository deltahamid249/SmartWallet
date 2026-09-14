<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = currentUserId();
$message = flash('success') ?? '';
$error = flash('error') ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = normalizeAmount($_POST['amount'] ?? '');
    $merchantName = trim((string)($_POST['merchant_name'] ?? ''));

    if (!verifyCsrf($_POST['_csrf'] ?? null)) {
        $error = 'انتهت صلاحية النموذج. أعد تحميل الصفحة وحاول مرة أخرى.';
    } elseif (!consumeActionToken('payment', $_POST['_action'] ?? null)) {
        $error = 'تمت معالجة هذا الطلب مسبقًا أو انتهت صلاحيته.';
    } elseif ($amount === null) {
        $error = 'يرجى إدخال مبلغ صحيح من منزلتين عشريتين كحد أقصى.';
    } elseif ($merchantName === '') {
        $error = 'يرجى إدخال اسم الجهة أو التاجر.';
    } elseif (mb_strlen($merchantName) > 150) {
        $error = 'اسم الجهة طويل جدًا.';
    } else {
        try {
            $pdo->beginTransaction();

            $walletStmt = $pdo->prepare(
                'SELECT id, balance, currency
                 FROM wallets
                 WHERE user_id = :user_id
                 FOR UPDATE'
            );

            $walletStmt->execute([
                ':user_id' => $userId
            ]);

            $wallet = $walletStmt->fetch();

            if (!$wallet) {
                throw new RuntimeException('لم يتم العثور على محفظتك.');
            }

            if ((float)$wallet['balance'] < (float)$amount) {
                throw new RuntimeException('الرصيد غير كافٍ لإتمام عملية الدفع.');
            }

            $reference = 'PAY-' . strtoupper(bin2hex(random_bytes(8)));

            $updateWallet = $pdo->prepare(
                'UPDATE wallets
                 SET balance = balance - :amount
                 WHERE id = :wallet_id'
            );

            $updateWallet->execute([
                ':amount' => $amount,
                ':wallet_id' => $wallet['id']
            ]);

            if ($updateWallet->rowCount() !== 1) {
                throw new RuntimeException('تعذر تحديث رصيد المحفظة.');
            }

            $paymentStmt = $pdo->prepare(
                'INSERT INTO payments
                (user_id, amount, merchant_name, reference, status)
                VALUES
                (:user_id, :amount, :merchant_name, :reference, :status)'
            );

            $paymentStmt->execute([
                ':user_id' => $userId,
                ':amount' => $amount,
                ':merchant_name' => $merchantName,
                ':reference' => $reference,
                ':status' => 'completed'
            ]);

            $transactionStmt = $pdo->prepare(
                'INSERT INTO transactions
                (wallet_id, type, amount, reference, description, status)
                VALUES
                (:wallet_id, :type, :amount, :reference, :description, :status)'
            );

            $transactionStmt->execute([
                ':wallet_id' => $wallet['id'],
                ':type' => 'payment',
                ':amount' => $amount,
                ':reference' => $reference,
                ':description' => 'دفع إلى ' . $merchantName,
                ':status' => 'completed'
            ]);

            $pdo->commit();

            flash(
                'success',
                'تم دفع ' .
                formatMoney($amount) .
                ' ' .
                $wallet['currency'] .
                ' إلى ' .
                $merchantName .
                ' بنجاح.'
            );

            header('Location: payments.php');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $error = $e->getMessage() === 'الرصيد غير كافٍ لإتمام عملية الدفع.'
                ? $e->getMessage()
                : 'تعذر تنفيذ عملية الدفع.';
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

$userStmt->execute([
    ':user_id' => $userId
]);

$user = $userStmt->fetch();

if (!$user) {
    exit('تعذر تحميل بيانات المحفظة.');
}

$paymentsStmt = $pdo->prepare(
    'SELECT amount, merchant_name, reference, status, created_at
     FROM payments
     WHERE user_id = :user_id
     ORDER BY created_at DESC, id DESC
     LIMIT 20'
);

$paymentsStmt->execute([
    ':user_id' => $userId
]);

$payments = $paymentsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>المدفوعات - Smart Wallet</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: #f4f7fb;
            color: #172033;
        }

        .container {
            width: min(650px, 92%);
            margin: 30px auto;
        }

        .top {
            background: #172033;
            color: white;
            padding: 22px;
            border-radius: 18px;
            margin-bottom: 18px;
        }

        .top h1 {
            margin: 0 0 8px;
            font-size: 25px;
        }

        .top p {
            margin: 0;
            opacity: .85;
        }

        .balance {
            margin-top: 18px;
            background: rgba(255,255,255,.1);
            padding: 15px;
            border-radius: 12px;
        }

        .balance strong {
            display: block;
            font-size: 25px;
            margin-top: 5px;
        }

        .card {
            background: white;
            padding: 24px;
            border-radius: 18px;
            box-shadow: 0 5px 20px rgba(0,0,0,.06);
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 13px;
            margin-bottom: 18px;
            border: 1px solid #d8dee8;
            border-radius: 10px;
            font-size: 16px;
            outline: none;
        }

        input:focus {
            border-color: #2563eb;
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: #2563eb;
            color: white;
            font-size: 17px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            opacity: .92;
        }

        .message {
            background: #e8f7ee;
            color: #176b3a;
            padding: 13px;
            border-radius: 10px;
            margin-bottom: 18px;
        }

        .error {
            background: #fdecec;
            color: #a52222;
            padding: 13px;
            border-radius: 10px;
            margin-bottom: 18px;
        }

        .hint {
            color: #697386;
            font-size: 13px;
            margin-top: -10px;
            margin-bottom: 18px;
        }

        .payment {
            border: 1px solid #e2e7ef;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
        }

        .payment-top {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 8px;
        }

        .merchant {
            font-weight: bold;
        }

        .amount {
            font-weight: bold;
            color: #b91c1c;
        }

        .reference,
        .date {
            color: #697386;
            font-size: 13px;
            margin-top: 5px;
        }

        .status {
            display: inline-block;
            margin-top: 8px;
            padding: 5px 9px;
            border-radius: 8px;
            background: #e8f7ee;
            color: #176b3a;
            font-size: 12px;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            color: #697386;
            padding: 25px 10px;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 18px;
            color: #2563eb;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="top">

        <h1>🧾 المدفوعات</h1>

        <p>
            <?= htmlspecialchars($user['full_name']) ?>
        </p>

        <div class="balance">

            الرصيد الحالي

            <strong>
                <?= e(formatMoney($user['balance'])) ?>
                <?= htmlspecialchars($user['currency']) ?>
            </strong>

        </div>

    </div>

    <div class="card">

        <?php if ($message !== ''): ?>
            <div class="message">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="payments.php">

            <input
                type="hidden"
                name="_csrf"
                value="<?= e(csrfToken()) ?>"
            >

            <input
                type="hidden"
                name="_action"
                value="<?= e(actionToken('payment')) ?>"
            >

            <label for="merchant_name">
                الجهة أو التاجر
            </label>

            <input
                type="text"
                id="merchant_name"
                name="merchant_name"
                maxlength="150"
                placeholder="مثال: متجر إلكتروني"
                required
            >

            <label for="amount">
                مبلغ الدفع
            </label>

            <input
                type="number"
                id="amount"
                name="amount"
                min="0.01"
                step="0.01"
                placeholder="0.00"
                required
            >

            <div class="hint">
                العملة المستخدمة حاليًا:
                <?= htmlspecialchars($user['currency']) ?>
            </div>

            <button type="submit">
                💳 تنفيذ الدفع
            </button>

        </form>

    </div>

    <div class="card">

        <h2>آخر المدفوعات</h2>

        <?php if (!$payments): ?>

            <div class="empty">
                لا توجد مدفوعات حتى الآن.
            </div>

        <?php else: ?>

            <?php foreach ($payments as $payment): ?>

                <div class="payment">

                    <div class="payment-top">

                        <div class="merchant">
                            <?= htmlspecialchars($payment['merchant_name']) ?>
                        </div>

                        <div class="amount">
                            -<?= e(formatMoney($payment['amount'])) ?>
                            <?= htmlspecialchars($user['currency']) ?>
                        </div>

                    </div>

                    <div class="reference">
                        المرجع:
                        <?= htmlspecialchars($payment['reference']) ?>
                    </div>

                    <div class="date">
                        <?= htmlspecialchars($payment['created_at']) ?>
                    </div>

                    <span class="status">
                        <?= htmlspecialchars($payment['status']) ?>
                    </span>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <a class="back" href="index.php">
        ← العودة إلى المحفظة
    </a>

</div>

</body>
</html>
