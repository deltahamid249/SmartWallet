<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
            throw new RuntimeException('رمز الحماية غير صالح. أعد تحميل الصفحة وحاول مرة أخرى.');
        }

        if (!consumeActionToken(
            'withdrawal_review',
            $_POST['action_token'] ?? null
        )) {
            throw new RuntimeException('رمز العملية غير صالح أو انتهت صلاحيته. أعد تحميل الصفحة.');
        }

        $requestId = (int) ($_POST['request_id'] ?? 0);
        $action = trim((string) ($_POST['action'] ?? ''));
        $reviewNote = trim((string) ($_POST['review_note'] ?? ''));

        if ($requestId <= 0) {
            throw new RuntimeException('طلب السحب غير صالح.');
        }

        if (!in_array($action, ['approve', 'reject'], true)) {
            throw new RuntimeException('إجراء غير صالح.');
        }

        if (mb_strlen($reviewNote) > 255) {
            throw new RuntimeException('ملاحظة المراجعة طويلة جدًا.');
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT *
            FROM withdrawal_requests
            WHERE id = :id
            FOR UPDATE
        ");

        $stmt->execute([
            ':id' => $requestId
        ]);

        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new RuntimeException('طلب السحب غير موجود.');
        }

        if ($request['status'] !== 'pending') {
            throw new RuntimeException('هذا الطلب تمت مراجعته مسبقًا.');
        }

        $adminId = currentUserId();

        if ($action === 'reject') {
            $stmt = $pdo->prepare("
                UPDATE withdrawal_requests
                SET
                    status = 'rejected',
                    reviewed_by = :reviewed_by,
                    reviewed_at = NOW(),
                    review_note = :review_note
                WHERE id = :id
                  AND status = 'pending'
            ");

            $stmt->execute([
                ':reviewed_by' => $adminId,
                ':review_note' => $reviewNote !== '' ? $reviewNote : null,
                ':id' => $requestId
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('تعذر رفض طلب السحب.');
            }

            logAdminAction(
                'reject_withdrawal',
                'تم رفض طلب سحب رقم ' . $requestId . '.',
                'withdrawal',
                $requestId
            );

            $pdo->commit();

            flash('success', 'تم رفض طلب السحب بنجاح.');
            header('Location: withdrawals.php?status=pending');
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT id, user_id, balance
            FROM wallets
            WHERE id = :wallet_id
            FOR UPDATE
        ");

        $stmt->execute([
            ':wallet_id' => $request['wallet_id']
        ]);

        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$wallet) {
            throw new RuntimeException('محفظة المستخدم غير موجودة.');
        }

        $amount = (string) $request['amount'];
        $balance = (string) $wallet['balance'];

        if (bccomp($amount, '0.00', 2) <= 0) {
            throw new RuntimeException('مبلغ السحب غير صالح.');
        }

        if (bccomp($balance, $amount, 2) < 0) {
            throw new RuntimeException(
                'الرصيد الحالي غير كافٍ لاعتماد طلب السحب.'
            );
        }

        $stmt = $pdo->prepare("
            UPDATE wallets
            SET balance = balance - :amount
            WHERE id = :wallet_id
              AND balance >= :amount_check
        ");

        $stmt->execute([
            ':amount' => $amount,
            ':wallet_id' => $request['wallet_id'],
            ':amount_check' => $amount
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('تعذر خصم المبلغ من المحفظة.');
        }

        $transactionReference =
            'WDR-' . strtoupper(bin2hex(random_bytes(6)));

        $stmt = $pdo->prepare("
            INSERT INTO transactions
            (
                wallet_id,
                type,
                amount,
                reference,
                description,
                status
            )
            VALUES
            (
                :wallet_id,
                'withdraw',
                :amount,
                :reference,
                :description,
                'completed'
            )
        ");

        $stmt->execute([
            ':wallet_id' => $request['wallet_id'],
            ':amount' => $amount,
            ':reference' => $transactionReference,
            ':description' => 'اعتماد طلب سحب ' . $request['reference']
        ]);

        $stmt = $pdo->prepare("
            UPDATE withdrawal_requests
            SET
                status = 'approved',
                reviewed_by = :reviewed_by,
                reviewed_at = NOW(),
                review_note = :review_note
            WHERE id = :id
              AND status = 'pending'
        ");

        $stmt->execute([
            ':reviewed_by' => $adminId,
            ':review_note' => $reviewNote !== '' ? $reviewNote : null,
            ':id' => $requestId
        ]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException('تعذر تحديث حالة طلب السحب.');
        }

        logAdminAction(
            'approve_withdrawal',
            'تم اعتماد طلب سحب رقم ' . $requestId . ' وخصم المبلغ من المحفظة.',
            'withdrawal',
            $requestId
        );

        $pdo->commit();

        flash(
            'success',
            'تم اعتماد طلب السحب وخصم المبلغ من المحفظة بنجاح.'
        );

        header('Location: withdrawals.php?status=pending');
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash('error', $e->getMessage());

        header('Location: withdrawals.php?status=pending');
        exit;
    }
}

$status = trim((string) ($_GET['status'] ?? 'all'));

$allowedStatuses = [
    'all',
    'pending',
    'approved',
    'rejected',
    'cancelled'
];

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'all';
}

$where = '';
$params = [];

if ($status !== 'all') {
    $where = 'WHERE wr.status = :status';
    $params[':status'] = $status;
}

$stmt = $pdo->prepare("
    SELECT
        wr.*,
        u.full_name,
        u.phone
    FROM withdrawal_requests wr
    INNER JOIN users u
        ON u.id = wr.user_id
    $where
    ORDER BY wr.id DESC
");

$stmt->execute($params);

$withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statsStmt = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'pending') AS pending,
        SUM(status = 'approved') AS approved,
        SUM(status = 'rejected') AS rejected
    FROM withdrawal_requests
");

$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$flashSuccess = flash('success');
$flashError = flash('error');

$reviewToken = actionToken('withdrawal_review');
$csrfToken = csrfToken();

/**
 * عرض حالة طلب السحب بصياغة عربية.
 */
function statusLabel(string $status): string
{
    return match ($status) {
        'pending' => 'قيد المراجعة',
        'approved' => 'معتمد',
        'rejected' => 'مرفوض',
        'cancelled' => 'ملغي',
        default => $status
    };
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة طلبات السحب</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: #f5f7fb;
            color: #172033;
        }

        .container {
            width: min(1200px, 94%);
            margin: 30px auto;
        }

        .topbar {
            background: #ffffff;
            padding: 18px 20px;
            border-radius: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 16px rgba(0,0,0,.07);
            margin-bottom: 20px;
        }

        .topbar h1 {
            margin: 0;
            font-size: 22px;
        }

        .links a {
            text-decoration: none;
            margin-right: 8px;
            color: #2563eb;
            font-weight: bold;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-weight: bold;
        }

        .success {
            background: #e9f9ef;
            color: #166534;
        }

        .error {
            background: #fff0f0;
            color: #16a34a;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat {
            background: #ffffff;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,.06);
        }

        .stat span {
            display: block;
            color: #64748b;
            margin-bottom: 8px;
        }

        .stat strong {
            font-size: 25px;
        }

        .filters {
            background: #ffffff;
            padding: 15px;
            border-radius: 14px;
            margin-bottom: 20px;
            box-shadow: 0 4px 16px rgba(0,0,0,.05);
        }

        .filters a {
            display: inline-block;
            padding: 9px 14px;
            margin: 4px;
            border-radius: 9px;
            text-decoration: none;
            background: #eef2f7;
            color: #334155;
            font-weight: bold;
        }

        .filters a.active {
            background: #2563eb;
            color: #ffffff;
        }

        .table-wrap {
            background: #ffffff;
            border-radius: 16px;
            overflow-x: auto;
            box-shadow: 0 4px 16px rgba(0,0,0,.06);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        th,
        td {
            padding: 14px;
            border-bottom: 1px solid #edf0f4;
            text-align: right;
        }

        th {
            background: #f8fafc;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
        }

        .status.pending {
            background: #fff7d6;
            color: #92400e;
        }

        .status.approved {
            background: #dcfce7;
            color: #166534;
        }

        .status.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .status.cancelled {
            background: #e5e7eb;
            color: #374151;
        }

        .actions {
            min-width: 230px;
        }

        .actions input {
            width: 100%;
            padding: 9px;
            border: 1px solid #d8dee8;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .buttons {
            display: flex;
            gap: 7px;
        }

        button {
            border: 0;
            border-radius: 8px;
            padding: 9px 13px;
            cursor: pointer;
            font-weight: bold;
        }

        .approve {
            background: #16a34a;
            color: white;
        }

        .reject {
            background: #dc2626;
            color: white;
        }

        .empty {
            padding: 35px;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 700px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
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

<div class="container">

    <div class="topbar">
        <h1>إدارة طلبات السحب</h1>

        <div class="links">
            <a href="index.php">لوحة الإدارة</a>
            <a href="../index.php">الرئيسية</a>
        </div>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="alert success">
            <?= e($flashSuccess) ?>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="alert error">
            <?= e($flashError) ?>
        </div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat">
            <span>إجمالي الطلبات</span>
            <strong><?= (int) ($stats['total'] ?? 0) ?></strong>
        </div>

        <div class="stat">
            <span>قيد المراجعة</span>
            <strong><?= (int) ($stats['pending'] ?? 0) ?></strong>
        </div>

        <div class="stat">
            <span>المعتمدة</span>
            <strong><?= (int) ($stats['approved'] ?? 0) ?></strong>
        </div>

        <div class="stat">
            <span>المرفوضة</span>
            <strong><?= (int) ($stats['rejected'] ?? 0) ?></strong>
        </div>
    </div>

    <div class="filters">
        <?php foreach ($allowedStatuses as $filter): ?>
            <a
                href="?status=<?= urlencode($filter) ?>"
                class="<?= $status === $filter ? 'active' : '' ?>"
            >
                <?= e(statusLabel($filter)) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="table-wrap">

        <?php if (!$withdrawals): ?>

            <div class="empty">
                لا توجد طلبات سحب في هذه القائمة.
            </div>

        <?php else: ?>

            <table>
                <thead>
                    <tr>
                        <th>الطلب</th>
                        <th>المستخدم</th>
                        <th>المبلغ</th>
                        <th>بيانات المستلم</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th>الإجراء</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($withdrawals as $item): ?>

                    <tr>

                        <td>
                            <strong>
                                <?= e($item['reference'] ?? ('#' . $item['id'])) ?>
                            </strong>
                            <br>
                            <small>
                                ID: <?= (int) $item['id'] ?>
                            </small>
                        </td>

                        <td>
                            <?= e($item['full_name']) ?>
                            <br>
                            <small>
                                <?= e($item['phone']) ?>
                            </small>
                        </td>

                        <td>
                            <strong>
                                <?= formatMoney($item['amount']) ?>
                                SDG
                            </strong>
                        </td>

                        <td>
                            <?= e($item['recipient_name']) ?>
                            <br>
                            <small>
                                <?= e($item['recipient_phone']) ?>
                            </small>
                        </td>

                        <td>
                            <span class="status <?= e($item['status']) ?>">
                                <?= e(statusLabel($item['status'])) ?>
                            </span>
                        </td>

                        <td>
                            <?= e($item['created_at']) ?>
                        </td>

                        <td class="actions">

                            <?php if ($item['status'] === 'pending'): ?>

                                <form method="post">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($csrfToken) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action_token"
                                        value="<?= e($reviewToken) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="request_id"
                                        value="<?= (int) $item['id'] ?>"
                                    >

                                    <input
                                        type="text"
                                        name="review_note"
                                        maxlength="255"
                                        placeholder="ملاحظة المراجعة"
                                    >

                                    <div class="buttons">

                                        <button
                                            class="approve"
                                            type="submit"
                                            name="action"
                                            value="approve"
                                            onclick="return confirm('هل أنت متأكد من اعتماد طلب السحب وخصم المبلغ من المحفظة؟');"
                                        >
                                            اعتماد
                                        </button>

                                        <button
                                            class="reject"
                                            type="submit"
                                            name="action"
                                            value="reject"
                                            onclick="return confirm('هل أنت متأكد من رفض طلب السحب؟');"
                                        >
                                            رفض
                                        </button>

                                    </div>

                                </form>

                            <?php else: ?>

                                <small>
                                    تمت المراجعة
                                </small>

                                <?php if (!empty($item['review_note'])): ?>
                                    <br>
                                    <small>
                                        <?= e($item['review_note']) ?>
                                    </small>
                                <?php endif; ?>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>
            </table>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
