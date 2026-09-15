<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = currentUserId();

/*
 * جلب بيانات المحفظة
 */
$stmt = $pdo->prepare("
    SELECT
        w.id AS wallet_id,
        w.balance
    FROM wallets w
    WHERE w.user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    'user_id' => $userId
]);

$wallet = $stmt->fetch();

if (!$wallet) {
    http_response_code(404);
    exit('المحفظة غير موجودة.');
}

$walletId = (int) $wallet['wallet_id'];
$balance = $wallet['balance'];

/*
 * جلب جميع العمليات الخاصة بالمحفظة
 */
$stmt = $pdo->prepare("
    SELECT
        id,
        type,
        amount,
        reference,
        description,
        status,
        created_at
    FROM transactions
    WHERE wallet_id = :wallet_id
    ORDER BY created_at DESC, id DESC
");

$stmt->execute([
    'wallet_id' => $walletId
]);

$transactions = $stmt->fetchAll();

/*
 * معلومات عرض العملية
 */
function transactionInfo(string $type): array
{
    return match ($type) {
        'deposit' => [
            'icon' => '💵',
            'title' => 'إضافة أموال',
            'class' => 'positive',
            'sign' => '+'
        ],

        'withdraw' => [
            'icon' => '💴',
            'title' => 'سحب أموال',
            'class' => 'negative',
            'sign' => '-'
        ],

        'transfer_out' => [
            'icon' => '📤',
            'title' => 'إرسال أموال',
            'class' => 'negative',
            'sign' => '-'
        ],

        'transfer_in' => [
            'icon' => '📥',
            'title' => 'استلام أموال',
            'class' => 'positive',
            'sign' => '+'
        ],

        'payment' => [
            'icon' => '🧾',
            'title' => 'دفع',
            'class' => 'negative',
            'sign' => '-'
        ],

        default => [
            'icon' => '💰',
            'title' => 'عملية مالية',
            'class' => 'neutral',
            'sign' => ''
        ],
    };
}

/*
 * حالة العملية
 */
function transactionStatus(string $status): string
{
    return match ($status) {
        'completed' => 'مكتملة',
        'pending' => 'قيد الانتظار',
        'failed' => 'فشلت',
        'cancelled' => 'ملغاة',
        default => $status
    };
}

/*
 * تنسيق التاريخ
 */
function formatTransactionDate(string $date): string
{
    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date('Y-m-d', $timestamp) . ' — ' . date('H:i', $timestamp);
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

    <title>سجل العمليات - المحفظة الذكية</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family:
                Tahoma,
                Arial,
                "Noto Sans Arabic",
                sans-serif;

            background: #f4f6f8;
            color: #111111;
            min-height: 100vh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .app {
            width: 100%;
            max-width: 560px;
            margin: 0 auto;
            min-height: 100vh;
        }

        /* =========================
           HEADER
        ========================== */

        .header {
            background: #0d2238;
            color: #ffffff;

            padding: 18px 16px 24px;

            border-radius: 0 0 24px 24px;
        }

        .header-top {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .back-button {
            width: 46px;
            height: 46px;

            border-radius: 14px;

            background: #ffffff;
            color: #0d2238;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 25px;
            font-weight: 900;
        }

        .header-title {
            flex: 1;
        }

        .header-title h1 {
            font-size: 21px;
            font-weight: 900;
        }

        .header-title p {
            margin-top: 4px;

            font-size: 12px;
            font-weight: 700;

            color: #ffffff;
        }

        .sdg-logo {
            width: 54px;
            height: 44px;

            background: #ffffff;
            color: #0d2238;

            border-radius: 13px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 15px;
            font-weight: 900;
        }

        /* =========================
           CONTENT
        ========================== */

        .content {
            padding: 18px 15px 30px;
        }

        /* =========================
           BALANCE
        ========================== */

        .balance-card {
            background: #ffffff;

            border: 2px solid #e1e5e9;
            border-radius: 20px;

            padding: 18px;

            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.06);

            margin-bottom: 18px;
        }

        .balance-label {
            font-size: 14px;
            font-weight: 900;

            margin-bottom: 8px;
        }

        .balance-row {
            display: flex;
            align-items: baseline;
            gap: 9px;
        }

        .balance {
            font-size: 30px;
            font-weight: 900;
            color: #111111;
        }

        .currency {
            font-size: 17px;
            font-weight: 900;
            color: #0d2238;
        }

        /* =========================
           TITLE
        ========================== */

        .section-title {
            font-size: 18px;
            font-weight: 900;

            color: #111111;

            margin-bottom: 12px;
        }

        /* =========================
           TRANSACTION CARD
        ========================== */

        .transaction {
            background: #ffffff;

            border: 2px solid #e1e5e9;
            border-radius: 18px;

            padding: 14px;

            margin-bottom: 11px;

            box-shadow:
                0 3px 9px rgba(0, 0, 0, 0.05);
        }

        .transaction-top {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .transaction-icon {
            width: 56px;
            height: 56px;

            flex-shrink: 0;

            border-radius: 16px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 29px;

            background: #eef3f7;
        }

        .transaction-main {
            flex: 1;
            min-width: 0;
        }

        .transaction-title {
            font-size: 15px;
            font-weight: 900;

            color: #111111;
        }

        .transaction-date {
            margin-top: 5px;

            font-size: 12px;
            font-weight: 700;

            color: #111111;
        }

        .transaction-amount {
            text-align: left;
            white-space: nowrap;
        }

        .amount-number {
            font-size: 17px;
            font-weight: 900;
        }

        .amount-currency {
            display: block;

            margin-top: 2px;

            font-size: 11px;
            font-weight: 900;

            color: #111111;
        }

        .positive .amount-number {
            color: #087f23;
        }

        .negative .amount-number {
            color: #b00020;
        }

        .neutral .amount-number {
            color: #111111;
        }

        /* =========================
           DETAILS
        ========================== */

        .transaction-details {
            margin-top: 12px;

            padding-top: 11px;

            border-top: 1px solid #dedede;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;

            font-size: 12px;
            font-weight: 700;

            padding: 4px 0;

            color: #111111;
        }

        .detail-value {
            text-align: left;
            word-break: break-word;
        }

        /* =========================
           STATUS
        ========================== */

        .status {
            display: inline-block;

            margin-top: 9px;

            padding: 5px 9px;

            border-radius: 8px;

            font-size: 11px;
            font-weight: 900;

            background: #e8f6eb;
            color: #087f23;
        }

        .status.pending {
            background: #fff4d6;
            color: #765400;
        }

        .status.failed,
        .status.cancelled {
            background: #fde7ea;
            color: #b00020;
        }

        /* =========================
           EMPTY
        ========================== */

        .empty {
            background: #ffffff;

            border: 2px solid #e1e5e9;
            border-radius: 20px;

            padding: 35px 20px;

            text-align: center;
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .empty h2 {
            font-size: 18px;
            font-weight: 900;

            color: #111111;
        }

        .empty p {
            margin-top: 8px;

            font-size: 13px;
            font-weight: 700;

            color: #111111;
        }

        /* =========================
           HOME BUTTON
        ========================== */

        .home-button {
            display: block;

            margin-top: 18px;

            padding: 15px;

            border-radius: 16px;

            background: #0d2238;
            color: #ffffff;

            text-align: center;

            font-size: 15px;
            font-weight: 900;
        }

        /* =========================
           MOBILE
        ========================== */

        @media (max-width: 380px) {

            .transaction {
                padding: 12px;
            }

            .transaction-icon {
                width: 51px;
                height: 51px;
                font-size: 26px;
            }

            .amount-number {
                font-size: 15px;
            }

            .transaction-title {
                font-size: 14px;
            }

            .balance {
                font-size: 27px;
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

<div class="app">

    <!-- HEADER -->

    <header class="header">

        <div class="header-top">

            <a href="index.php" class="back-button" aria-label="العودة">
                →
            </a>

            <div class="header-title">

                <h1>
                    سجل العمليات
                </h1>

                <p>
                    جميع عمليات المحفظة بالجنيه السوداني
                </p>

            </div>

            <div class="sdg-logo">
                SDG
            </div>

        </div>

    </header>


    <!-- CONTENT -->

    <main class="content">

        <!-- BALANCE -->

        <div class="balance-card">

            <div class="balance-label">
                الرصيد الحالي
            </div>

            <div class="balance-row">

                <span class="balance">
                    <?= e(formatMoney($balance)) ?>
                </span>

                <span class="currency">
                    SDG
                </span>

            </div>

        </div>


        <h2 class="section-title">
            العمليات
        </h2>


        <?php if (empty($transactions)): ?>

            <div class="empty">

                <div class="empty-icon">
                    📋
                </div>

                <h2>
                    لا توجد عمليات حتى الآن
                </h2>

                <p>
                    ستظهر عمليات الإضافة والسحب والإرسال والاستلام هنا.
                </p>

            </div>

        <?php else: ?>

            <?php foreach ($transactions as $transaction): ?>

                <?php
                    $info = transactionInfo(
                        (string) $transaction['type']
                    );

                    $amount = $transaction['amount'];

                    $status = (string) $transaction['status'];
                ?>

                <div class="transaction">

                    <div class="transaction-top">

                        <div class="transaction-icon">
                            <?= $info['icon'] ?>
                        </div>


                        <div class="transaction-main">

                            <div class="transaction-title">
                                <?= htmlspecialchars(
                                    $info['title'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>

                            <div class="transaction-date">
                                <?= htmlspecialchars(
                                    formatTransactionDate(
                                        (string) $transaction['created_at']
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>

                        </div>


                        <div class="transaction-amount <?= $info['class'] ?>">

                            <div class="amount-number">
                                <?= $info['sign'] ?>
                                <?= e(formatMoney($amount)) ?>
                            </div>

                            <span class="amount-currency">
                                SDG
                            </span>

                        </div>

                    </div>


                    <div class="transaction-details">

                        <?php if (!empty($transaction['description'])): ?>

                            <div class="detail-row">

                                <span>
                                    البيان
                                </span>

                                <span class="detail-value">
                                    <?= htmlspecialchars(
                                        (string) $transaction['description'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </span>

                            </div>

                        <?php endif; ?>


                        <div class="detail-row">

                            <span>
                                الرقم المرجعي
                            </span>

                            <span class="detail-value" dir="ltr">
                                <?= htmlspecialchars(
                                    (string) $transaction['reference'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </span>

                        </div>


                        <span class="status <?= htmlspecialchars(
                            $status,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>">

                            <?= htmlspecialchars(
                                transactionStatus($status),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </span>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>


        <!-- العودة -->

        <a href="index.php" class="home-button">
            🏠 العودة إلى الصفحة الرئيسية
        </a>

    </main>

</div>

</body>

</html>
