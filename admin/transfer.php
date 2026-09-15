<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$transferId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$transferId || $transferId < 1) {
    http_response_code(400);
    exit('رقم التحويل غير صالح.');
}

$sql = "
    SELECT
        t.id,
        t.reference,
        t.amount,
        t.status,
        t.note,
        t.created_at,

        t.sender_wallet_id,
        t.receiver_wallet_id,

        su.id AS sender_user_id,
        su.full_name AS sender_name,
        su.phone AS sender_phone,
        su.email AS sender_email,

        ru.id AS receiver_user_id,
        ru.full_name AS receiver_name,
        ru.phone AS receiver_phone,
        ru.email AS receiver_email

    FROM transfers t

    INNER JOIN wallets sw
        ON sw.id = t.sender_wallet_id

    INNER JOIN wallets rw
        ON rw.id = t.receiver_wallet_id

    INNER JOIN users su
        ON su.id = sw.user_id

    INNER JOIN users ru
        ON ru.id = rw.user_id

    WHERE t.id = :id

    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $transferId]);

$transfer = $stmt->fetch();

if (!$transfer) {
    http_response_code(404);
    exit('التحويل غير موجود.');
}

function transferStatusLabel(string $status): string
{
    return match ($status) {
        'completed' => 'مكتمل',
        'pending' => 'معلق',
        'failed' => 'فشل',
        'cancelled' => 'ملغي',
        default => $status,
    };
}

function transferStatusClass(string $status): string
{
    return match ($status) {
        'completed' => 'completed',
        'pending' => 'pending',
        'failed' => 'failed',
        'cancelled' => 'cancelled',
        default => 'default',
    };
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>تفاصيل التحويل - لوحة الإدارة</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f7fb;
            color: #111827;
            font-family: Arial, Tahoma, sans-serif;
        }

        .topbar {
            background: #111827;
            color: #fff;
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .topbar h1 {
            margin: 0;
            font-size: 21px;
        }

        .topbar a {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            margin-right: 15px;
        }

        .container {
            max-width: 1050px;
            margin: 30px auto;
            padding: 0 18px;
        }

        .back {
            display: inline-block;
            margin-bottom: 18px;
            color: #2563eb;
            text-decoration: none;
            font-weight: bold;
        }

        .card {
            background: #fff;
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .07);
        }

        .card h2 {
            margin: 0 0 20px;
            font-size: 20px;
        }

        .main-info {
            text-align: center;
        }

        .amount {
            font-size: 34px;
            font-weight: 900;
            margin: 12px 0;
        }

        .reference {
            color: #6b7280;
            font-size: 14px;
            word-break: break-all;
        }

        .status {
            display: inline-block;
            margin-top: 12px;
            padding: 8px 14px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 14px;
        }

        .status.completed {
            background: #dcfce7;
            color: #166534;
        }

        .status.pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status.failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .status.cancelled {
            background: #e5e7eb;
            color: #374151;
        }

        .status.default {
            background: #e5e7eb;
            color: #374151;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .person {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 20px;
        }

        .person h3 {
            margin: 0 0 18px;
            font-size: 18px;
        }

        .sender {
            border-right: 4px solid #2563eb;
        }

        .receiver {
            border-right: 4px solid #16a34a;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 11px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .row:last-child {
            border-bottom: 0;
        }

        .label {
            color: #6b7280;
            font-size: 14px;
        }

        .value {
            font-weight: bold;
            text-align: left;
            word-break: break-word;
        }

        .note {
            background: #f9fafb;
            border-radius: 12px;
            padding: 15px;
            line-height: 1.8;
            white-space: pre-wrap;
        }

        @media (max-width: 700px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .row {
                flex-direction: column;
                gap: 5px;
            }

            .value {
                text-align: right;
            }

            .amount {
                font-size: 28px;
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

<header class="topbar">

    <h1>🔄 تفاصيل التحويل</h1>

    <div>
        <a href="transfers.php">التحويلات</a>
        <a href="index.php">لوحة الإدارة</a>
        <a href="../logout.php">تسجيل الخروج</a>
    </div>

</header>

<main class="container">

    <a href="transfers.php" class="back">
        ← العودة إلى التحويلات
    </a>

    <section class="card main-info">

        <h2>عملية التحويل رقم #<?= (int) $transfer['id'] ?></h2>

        <div class="amount">
            <?= formatMoney($transfer['amount']) ?> SDG
        </div>

        <div class="reference">
            المرجع: <?= e($transfer['reference']) ?>
        </div>

        <div class="status <?= e(transferStatusClass((string) $transfer['status'])) ?>">
            <?= e(transferStatusLabel((string) $transfer['status'])) ?>
        </div>

    </section>

    <section class="grid">

        <div class="card person sender">

            <h3>👤 المرسل</h3>

            <div class="row">
                <span class="label">الاسم</span>
                <span class="value"><?= e($transfer['sender_name']) ?></span>
            </div>

            <div class="row">
                <span class="label">رقم الهاتف</span>
                <span class="value"><?= e($transfer['sender_phone']) ?></span>
            </div>

            <div class="row">
                <span class="label">البريد الإلكتروني</span>
                <span class="value"><?= e($transfer['sender_email'] ?: 'غير متوفر') ?></span>
            </div>

            <div class="row">
                <span class="label">رقم المستخدم</span>
                <span class="value">#<?= (int) $transfer['sender_user_id'] ?></span>
            </div>

            <div class="row">
                <span class="label">رقم المحفظة</span>
                <span class="value">#<?= (int) $transfer['sender_wallet_id'] ?></span>
            </div>

        </div>

        <div class="card person receiver">

            <h3>👤 المستلم</h3>

            <div class="row">
                <span class="label">الاسم</span>
                <span class="value"><?= e($transfer['receiver_name']) ?></span>
            </div>

            <div class="row">
                <span class="label">رقم الهاتف</span>
                <span class="value"><?= e($transfer['receiver_phone']) ?></span>
            </div>

            <div class="row">
                <span class="label">البريد الإلكتروني</span>
                <span class="value"><?= e($transfer['receiver_email'] ?: 'غير متوفر') ?></span>
            </div>

            <div class="row">
                <span class="label">رقم المستخدم</span>
                <span class="value">#<?= (int) $transfer['receiver_user_id'] ?></span>
            </div>

            <div class="row">
                <span class="label">رقم المحفظة</span>
                <span class="value">#<?= (int) $transfer['receiver_wallet_id'] ?></span>
            </div>

        </div>

    </section>

    <section class="card">

        <h2>📋 معلومات العملية</h2>

        <div class="row">
            <span class="label">رقم العملية</span>
            <span class="value">#<?= (int) $transfer['id'] ?></span>
        </div>

        <div class="row">
            <span class="label">المرجع</span>
            <span class="value"><?= e($transfer['reference']) ?></span>
        </div>

        <div class="row">
            <span class="label">المبلغ</span>
            <span class="value">
                <?= formatMoney($transfer['amount']) ?> SDG
            </span>
        </div>

        <div class="row">
            <span class="label">الحالة</span>
            <span class="value">
                <?= e(transferStatusLabel((string) $transfer['status'])) ?>
            </span>
        </div>

        <div class="row">
            <span class="label">تاريخ العملية</span>
            <span class="value"><?= e($transfer['created_at']) ?></span>
        </div>

    </section>

    <?php if (!empty($transfer['note'])): ?>

        <section class="card">

            <h2>📝 ملاحظة التحويل</h2>

            <div class="note">
                <?= e($transfer['note']) ?>
            </div>

        </section>

    <?php endif; ?>

</main>

</body>
</html>
