<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$search = trim((string) ($_GET['search'] ?? ''));
$status = (string) ($_GET['status'] ?? 'all');

$allowedStatuses = [
    'all',
    'pending',
    'completed',
    'failed',
    'cancelled',
];

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'all';
}

$where = [];
$params = [];

if ($status !== 'all') {
    $where[] = 't.status = :status';
    $params['status'] = $status;
}

if ($search !== '') {
    $where[] = '(
        t.reference LIKE :search
        OR t.note LIKE :search
        OR su.full_name LIKE :search
        OR su.phone LIKE :search
        OR ru.full_name LIKE :search
        OR ru.phone LIKE :search
    )';

    $params['search'] = '%' . $search . '%';
}

$whereSql = $where
    ? 'WHERE ' . implode(' AND ', $where)
    : '';

$stmt = $pdo->prepare("
    SELECT
        t.id,
        t.sender_wallet_id,
        t.receiver_wallet_id,
        t.amount,
        t.reference,
        t.status,
        t.note,
        t.created_at,

        su.id AS sender_user_id,
        su.full_name AS sender_name,
        su.phone AS sender_phone,

        ru.id AS receiver_user_id,
        ru.full_name AS receiver_name,
        ru.phone AS receiver_phone

    FROM transfers t

    INNER JOIN wallets sw
        ON sw.id = t.sender_wallet_id

    INNER JOIN wallets rw
        ON rw.id = t.receiver_wallet_id

    INNER JOIN users su
        ON su.id = sw.user_id

    INNER JOIN users ru
        ON ru.id = rw.user_id

    $whereSql

    ORDER BY t.id DESC
    LIMIT 100
");

$stmt->execute($params);
$transfers = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| الإحصائيات
|--------------------------------------------------------------------------
*/

$totalTransfers = (int) $pdo
    ->query("SELECT COUNT(*) FROM transfers")
    ->fetchColumn();

$totalAmount = (string) $pdo
    ->query("SELECT COALESCE(SUM(amount), 0) FROM transfers")
    ->fetchColumn();

$completedAmount = (string) $pdo
    ->query("
        SELECT COALESCE(SUM(amount), 0)
        FROM transfers
        WHERE status = 'completed'
    ")
    ->fetchColumn();

$pendingAmount = (string) $pdo
    ->query("
        SELECT COALESCE(SUM(amount), 0)
        FROM transfers
        WHERE status = 'pending'
    ")
    ->fetchColumn();

$pendingCount = (int) $pdo
    ->query("
        SELECT COUNT(*)
        FROM transfers
        WHERE status = 'pending'
    ")
    ->fetchColumn();

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

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>إدارة التحويلات - لوحة الإدارة</title>

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
            font-size: 22px;
        }

        .topbar a {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            margin-right: 15px;
        }

        .container {
            max-width: 1250px;
            margin: 30px auto;
            padding: 0 18px;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #2563eb;
            text-decoration: none;
            font-weight: bold;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat {
            background: #fff;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .07);
        }

        .stat-title {
            color: #6b7280;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 900;
        }

        .filters {
            background: #fff;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .07);
            margin-bottom: 20px;
        }

        .filters form {
            display: grid;
            grid-template-columns: 1fr 220px 120px;
            gap: 12px;
        }

        input,
        select,
        button {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            font-size: 15px;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #2563eb;
        }

        button {
            background: #2563eb;
            color: #fff;
            border: 0;
            cursor: pointer;
            font-weight: bold;
        }

        .table-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .07);
            overflow: hidden;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1050px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 12px;
            border-bottom: 1px solid #edf0f4;
            text-align: right;
            vertical-align: middle;
        }

        th {
            background: #f9fafb;
            font-size: 13px;
            color: #4b5563;
        }

        td {
            font-size: 14px;
        }

        .name {
            font-weight: bold;
        }

        .phone {
            color: #6b7280;
            font-size: 12px;
            margin-top: 4px;
        }

        .amount {
            font-weight: 900;
            white-space: nowrap;
        }

        .reference {
            font-family: monospace;
            font-size: 12px;
            direction: ltr;
            text-align: right;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
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

        .details-btn {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 8px;
            background: #111827;
            color: #fff;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .details-btn:hover {
            background: #2563eb;
        }

        .empty {
            padding: 40px 20px;
            text-align: center;
            color: #6b7280;
        }

        @media (max-width: 1000px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .filters form {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {

            .stats {
                grid-template-columns: 1fr;
            }

            .topbar {
                align-items: flex-start;
            }

            .container {
                margin-top: 20px;
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

    <h1>🔄 إدارة التحويلات</h1>

    <div>

        <a href="index.php">
            لوحة الإدارة
        </a>

        <a href="../index.php">
            الرئيسية
        </a>

        <a href="../logout.php">
            تسجيل الخروج
        </a>

    </div>

</header>

<main class="container">

    <a href="index.php" class="back">
        ← العودة إلى لوحة الإدارة
    </a>

    <section class="stats">

        <div class="stat">
            <div class="stat-title">
                إجمالي التحويلات
            </div>

            <div class="stat-value">
                <?= $totalTransfers ?>
            </div>
        </div>

        <div class="stat">
            <div class="stat-title">
                إجمالي المبالغ
            </div>

            <div class="stat-value">
                <?= formatMoney($totalAmount) ?> SDG
            </div>
        </div>

        <div class="stat">
            <div class="stat-title">
                التحويلات المكتملة
            </div>

            <div class="stat-value">
                <?= formatMoney($completedAmount) ?> SDG
            </div>
        </div>

        <div class="stat">
            <div class="stat-title">
                المبالغ المعلقة
            </div>

            <div class="stat-value">
                <?= formatMoney($pendingAmount) ?> SDG
            </div>
        </div>

        <div class="stat">
            <div class="stat-title">
                عدد التحويلات المعلقة
            </div>

            <div class="stat-value">
                <?= $pendingCount ?>
            </div>
        </div>

    </section>

    <section class="filters">

        <form method="get">

            <input
                type="text"
                name="search"
                value="<?= e($search) ?>"
                placeholder="ابحث بالمرجع أو اسم/هاتف المرسل أو المستلم..."
            >

            <select name="status">

                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>
                    جميع الحالات
                </option>

                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>
                    معلقة
                </option>

                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>
                    مكتملة
                </option>

                <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>
                    فاشلة
                </option>

                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>
                    ملغاة
                </option>

            </select>

            <button type="submit">
                بحث
            </button>

        </form>

    </section>

    <section class="table-card">

        <?php if (!$transfers): ?>

            <div class="empty">
                لا توجد تحويلات مطابقة للبحث الحالي.
            </div>

        <?php else: ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                    <tr>

                        <th>
                            #
                        </th>

                        <th>
                            المرسل
                        </th>

                        <th>
                            المستلم
                        </th>

                        <th>
                            المبلغ
                        </th>

                        <th>
                            المرجع
                        </th>

                        <th>
                            الحالة
                        </th>

                        <th>
                            التاريخ
                        </th>

                        <th>
                            التفاصيل
                        </th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($transfers as $transfer): ?>

                        <tr>

                            <td>
                                #<?= (int) $transfer['id'] ?>
                            </td>

                            <td>

                                <div class="name">
                                    <?= e($transfer['sender_name']) ?>
                                </div>

                                <div class="phone">
                                    <?= e($transfer['sender_phone']) ?>
                                </div>

                            </td>

                            <td>

                                <div class="name">
                                    <?= e($transfer['receiver_name']) ?>
                                </div>

                                <div class="phone">
                                    <?= e($transfer['receiver_phone']) ?>
                                </div>

                            </td>

                            <td class="amount">

                                <?= formatMoney($transfer['amount']) ?>

                                SDG

                            </td>

                            <td class="reference">

                                <?= e($transfer['reference']) ?>

                            </td>

                            <td>

                                <span
                                    class="status <?= e(
                                        transferStatusClass(
                                            (string) $transfer['status']
                                        )
                                    ) ?>"
                                >
                                    <?= e(
                                        transferStatusLabel(
                                            (string) $transfer['status']
                                        )
                                    ) ?>
                                </span>

                            </td>

                            <td>

                                <?= e($transfer['created_at']) ?>

                            </td>

                            <td>

                                <a
                                    class="details-btn"
                                    href="transfer.php?id=<?= (int) $transfer['id'] ?>"
                                >
                                    عرض التفاصيل
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>

</html>
