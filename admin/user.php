<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$userId || $userId < 1) {
    http_response_code(400);
    exit('معرّف المستخدم غير صالح.');
}

$currentAdminId = currentUserId();

/*
|--------------------------------------------------------------------------
| معالجة الإجراءات الإدارية
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = $_POST['csrf_token'] ?? null;
    $actionTokenValue = $_POST['action_token'] ?? null;
    $action = $_POST['action'] ?? '';

    if (!verifyCsrf($csrfToken)) {
        flash('error', 'رمز الحماية غير صالح.');
        header('Location: user.php?id=' . $userId);
        exit;
    }

    if (!consumeActionToken('user_manage', $actionTokenValue)) {
        flash('error', 'الطلب غير صالح أو تم استخدامه مسبقًا.');
        header('Location: user.php?id=' . $userId);
        exit;
    }

    try {

        $protectedAdminId = SYSTEM_ADMIN_ID;

        if ($userId === $protectedAdminId) {
            if (in_array($action, ['toggle_status', 'change_role', 'delete_user'], true)) {
                throw new RuntimeException(
                    'حساب مدير النظام الرئيسي لا يمكن تعطيله أو حذفه أو تغيير دوره.'
                );
            }
        }

        /*
         * تعديل بيانات المستخدم.
         */
        if ($action === 'edit_user') {

            $fullName = trim((string) ($_POST['full_name'] ?? ''));
            $username = trim((string) ($_POST['username'] ?? ''));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if ($fullName === '' || mb_strlen($fullName) > 150) {
                throw new RuntimeException('الاسم الكامل غير صالح.');
            }

            if ($username === '' || mb_strlen($username) > 100) {
                throw new RuntimeException('اسم المستخدم غير صالح.');
            }

            if ($phone === '' || mb_strlen($phone) > 30) {
                throw new RuntimeException('رقم الهاتف غير صالح.');
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('البريد الإلكتروني غير صالح.');
            }

            $check = $pdo->prepare("
                SELECT id
                FROM users
                WHERE (username = :username OR phone = :phone OR email = :email)
                  AND id <> :user_id
                LIMIT 1
            ");

            $check->execute([
                ':username' => $username,
                ':phone' => $phone,
                ':email' => $email !== '' ? $email : null,
                ':user_id' => $userId,
            ]);

            if ($check->fetch()) {
                throw new RuntimeException(
                    'اسم المستخدم أو رقم الهاتف أو البريد الإلكتروني مستخدم بالفعل.'
                );
            }

            if ($password !== '') {

                if (strlen($password) < 8) {
                    throw new RuntimeException('كلمة المرور يجب أن تكون 8 أحرف على الأقل.');
                }

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET full_name = :full_name,
                        username = :username,
                        phone = :phone,
                        email = :email,
                        password_hash = :password_hash
                    WHERE id = :user_id
                    LIMIT 1
                ");

                $stmt->execute([
                    ':full_name' => $fullName,
                    ':username' => $username,
                    ':phone' => $phone,
                    ':email' => $email !== '' ? $email : null,
                    ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ':user_id' => $userId,
                ]);

            } else {

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET full_name = :full_name,
                        username = :username,
                        phone = :phone,
                        email = :email
                    WHERE id = :user_id
                    LIMIT 1
                ");

                $stmt->execute([
                    ':full_name' => $fullName,
                    ':username' => $username,
                    ':phone' => $phone,
                    ':email' => $email !== '' ? $email : null,
                    ':user_id' => $userId,
                ]);
            }

            logAdminAction(
                'edit_user',
                'تم تعديل بيانات المستخدم.',
                'user',
                $userId
            );

            flash('success', 'تم تعديل بيانات المستخدم بنجاح.');
            header('Location: user.php?id=' . $userId);
            exit;
        }

        /*
         * حذف المستخدم مع بياناته التابعة.
         */
        if ($action === 'delete_user') {

            if ($userId === $currentAdminId) {
                throw new RuntimeException('لا يمكنك حذف حساب المدير الذي تستخدمه حاليًا.');
            }

            $stmt = $pdo->prepare("
                SELECT id, full_name, role
                FROM users
                WHERE id = :user_id
                LIMIT 1
            ");

            $stmt->execute([':user_id' => $userId]);
            $targetUser = $stmt->fetch();

            if (!$targetUser) {
                throw new RuntimeException('المستخدم غير موجود.');
            }

            if ($targetUser['role'] === 'admin') {
                throw new RuntimeException(
                    'لا يمكن حذف حساب مدير من هذه الواجهة.'
                );
            }

            $financialChecks = [
                'transactions' => 'SELECT COUNT(*) FROM transactions t INNER JOIN wallets w ON w.id = t.wallet_id WHERE w.user_id = :user_id',
                'transfers' => 'SELECT COUNT(*) FROM transfers t INNER JOIN wallets w ON w.id = t.sender_wallet_id WHERE w.user_id = :user_id',
                'payments' => 'SELECT COUNT(*) FROM payments WHERE user_id = :user_id',
                'deposit_requests' => 'SELECT COUNT(*) FROM deposit_requests WHERE user_id = :user_id',
                'withdrawal_requests' => 'SELECT COUNT(*) FROM withdrawal_requests WHERE user_id = :user_id',
                'service_requests' => 'SELECT COUNT(*) FROM service_requests WHERE user_id = :user_id'
            ];

            foreach ($financialChecks as $label => $sql) {

                $check = $pdo->prepare($sql);
                $check->execute([':user_id' => $userId]);

                if ((int) $check->fetchColumn() > 0) {
                    throw new RuntimeException(
                        'لا يمكن حذف هذا المستخدم لأن لديه سجلًا ماليًا أو تشغيليًا. استخدم تعطيل الحساب بدلًا من الحذف.'
                    );
                }
            }

            $pdo->beginTransaction();

            try {

                $stmt = $pdo->prepare("
                    DELETE FROM users
                    WHERE id = :user_id
                    LIMIT 1
                ");

                $stmt->execute([':user_id' => $userId]);

                logAdminAction(
                    'delete_user',
                    'تم حذف المستخدم: ' . $targetUser['full_name'],
                    'user',
                    $userId
                );

                $pdo->commit();

            } catch (Throwable $deleteError) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $deleteError;
            }

            flash('success', 'تم حذف المستخدم بنجاح.');
            header('Location: users.php');
            exit;
        }

        /*
         * منع المدير من تغيير حالة حسابه بنفسه.
         */
        if ($action === 'toggle_status' && $userId === $currentAdminId) {
            throw new RuntimeException(
                'لا يمكنك تعطيل حساب المدير الذي تستخدمه حاليًا.'
            );
        }

        /*
         * تفعيل / تعطيل المستخدم
         */
        if ($action === 'toggle_status') {

            $stmt = $pdo->prepare("
                SELECT status
                FROM users
                WHERE id = :user_id
                LIMIT 1
            ");

            $stmt->execute([
                ':user_id' => $userId
            ]);

            $targetUser = $stmt->fetch();

            if (!$targetUser) {
                throw new RuntimeException('المستخدم غير موجود.');
            }

            $newStatus = $targetUser['status'] === 'active'
                ? 'blocked'
                : 'active';

            $stmt = $pdo->prepare("
                UPDATE users
                SET status = :status
                WHERE id = :user_id
                LIMIT 1
            ");

            $stmt->execute([
                ':status' => $newStatus,
                ':user_id' => $userId
            ]);

            logAdminAction(
                'toggle_user_status',
                $newStatus === 'active'
                    ? 'تم تفعيل حساب المستخدم.'
                    : 'تم تعطيل حساب المستخدم.',
                'user',
                $userId
            );

            flash(
                'success',
                $newStatus === 'active'
                    ? 'تم تفعيل حساب المستخدم بنجاح.'
                    : 'تم تعطيل حساب المستخدم بنجاح.'
            );

            header('Location: user.php?id=' . $userId);
            exit;
        }

        /*
         * تغيير الدور
         */
        if ($action === 'change_role') {

            $newRole = $_POST['role'] ?? '';

            if (!in_array($newRole, ['user', 'admin'], true)) {
                throw new RuntimeException('الدور المحدد غير صالح.');
            }

            if ($newRole === 'admin' && $userId !== SYSTEM_ADMIN_ID) {
                throw new RuntimeException(
                    'لا يمكن منح صلاحية المدير إلا لحساب النظام الرئيسي.'
                );
            }

            if ($userId === $currentAdminId && $newRole !== 'admin') {
                throw new RuntimeException(
                    'لا يمكنك إزالة صلاحية المدير من حسابك الحالي.'
                );
            }

            $stmt = $pdo->prepare("
                SELECT id
                FROM users
                WHERE id = :user_id
                LIMIT 1
            ");

            $stmt->execute([
                ':user_id' => $userId
            ]);

            if (!$stmt->fetch()) {
                throw new RuntimeException('المستخدم غير موجود.');
            }

            $stmt = $pdo->prepare("
                UPDATE users
                SET role = :role
                WHERE id = :user_id
                LIMIT 1
            ");

            $stmt->execute([
                ':role' => $newRole,
                ':user_id' => $userId
            ]);

            logAdminAction(
                'change_user_role',
                $newRole === 'admin'
                    ? 'تم منح المستخدم صلاحيات المدير.'
                    : 'تم تحويل المستخدم إلى مستخدم عادي.',
                'user',
                $userId
            );

            flash(
                'success',
                $newRole === 'admin'
                    ? 'تم منح المستخدم صلاحيات المدير.'
                    : 'تم تحويل المستخدم إلى حساب مستخدم عادي.'
            );

            header('Location: user.php?id=' . $userId);
            exit;
        }

        throw new RuntimeException('الإجراء المطلوب غير معروف.');

    } catch (Throwable $e) {

        flash('error', $e->getMessage());

        header('Location: user.php?id=' . $userId);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| بيانات المستخدم والمحفظة
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.full_name,
        u.username,
        u.phone,
        u.email,
        u.role,
        u.status,
        u.created_at,
        COALESCE(w.id, 0) AS wallet_id,
        COALESCE(w.balance, 0) AS balance
    FROM users u
    LEFT JOIN wallets w ON w.user_id = u.id
    WHERE u.id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':user_id' => $userId
]);

$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    exit('المستخدم غير موجود.');
}

/*
|--------------------------------------------------------------------------
| طلبات الإيداع
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        reference,
        amount,
        bank_name,
        sender_name,
        bank_reference,
        status,
        created_at,
        reviewed_at
    FROM deposit_requests
    WHERE user_id = :user_id
    ORDER BY id DESC
    LIMIT 10
");

$stmt->execute([
    ':user_id' => $userId
]);

$deposits = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| طلبات السحب
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        reference,
        amount,
        recipient_name,
        recipient_phone,
        withdrawal_method,
        status,
        created_at,
        reviewed_at
    FROM withdrawal_requests
    WHERE user_id = :user_id
    ORDER BY id DESC
    LIMIT 10
");

$stmt->execute([
    ':user_id' => $userId
]);

$withdrawals = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| معاملات المحفظة
|--------------------------------------------------------------------------
*/

$transactions = [];

if ((int) $user['wallet_id'] > 0) {

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
        ORDER BY id DESC
        LIMIT 20
    ");

    $stmt->execute([
        ':wallet_id' => (int) $user['wallet_id']
    ]);

    $transactions = $stmt->fetchAll();
}

/*
|--------------------------------------------------------------------------
| دوال العرض
|--------------------------------------------------------------------------
*/

/**
 * تحويل أي حالة معروضة في صفحة المستخدم إلى وصف عربي.
 */
function statusLabel(string $status): string
{
    return match ($status) {
        'active' => 'نشط',
        'blocked' => 'غير نشط',
        'approved' => 'مقبول',
        'completed' => 'مكتمل',
        'pending' => 'قيد المراجعة',
        'rejected' => 'مرفوض',
        'failed' => 'فشل',
        'cancelled' => 'ملغي',
        default => $status,
    };
}

/**
 * تحويل نوع الحركة المالية إلى اسم عربي.
 */
function transactionTypeLabel(string $type): string
{
    return match ($type) {
        'deposit' => 'إيداع',
        'withdraw' => 'سحب',
        'transfer_in' => 'تحويل وارد',
        'transfer_out' => 'تحويل صادر',
        'payment' => 'دفع',
        default => $type,
    };
}

/**
 * تحديد مظهر الحركة المالية حسب كونها دائنة أو مدينة.
 */
function transactionClass(string $type): string
{
    return match ($type) {
        'deposit', 'transfer_in' => 'positive',
        'withdraw', 'transfer_out', 'payment' => 'negative',
        default => '',
    };
}

/**
 * تنسيق مبلغ المستخدم مع عملة النظام.
 */
function money(float|int|string $amount): string
{
    return formatMoney($amount) . ' SDG';
}

/**
 * تنسيق تاريخ الحركة أو إرجاع شرطة عند عدم وجوده.
 */
function dateLabel(?string $date): string
{
    if (!$date) {
        return '-';
    }

    $timestamp = strtotime($date);

    return $timestamp
        ? date('Y-m-d H:i', $timestamp)
        : $date;
}

$successMessage = flash('success');
$errorMessage = flash('error');

$roleLabel = $user['role'] === 'admin'
    ? 'مدير'
    : 'مستخدم';

$isCurrentAdmin = (int) $user['id'] === $currentAdminId;

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        إدارة المستخدم - <?= e($user['full_name']) ?>
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f7fb;
            color: #172033;
            font-family:
                Tahoma,
                Arial,
                sans-serif;
        }

        .container {
            width: min(1200px, calc(100% - 30px));
            margin: 30px auto;
        }

        .topbar {
            background: #ffffff;
            border-radius: 18px;
            padding: 18px 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .06);
        }

        .topbar h1 {
            margin: 0 0 6px;
            font-size: 24px;
        }

        .topbar p {
            margin: 0;
            color: #667085;
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            border: 0;
            border-radius: 10px;
            padding: 10px 15px;
            font-weight: 800;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-primary {
            background: #2563eb;
            color: #ffffff;
        }

        .btn-light {
            background: #eef2f7;
            color: #172033;
        }

        .btn-success {
            background: #16a34a;
            color: #ffffff;
        }

        .btn-danger {
            background: #dc2626;
            color: #ffffff;
        }

        .btn-warning {
            background: #d97706;
            color: #ffffff;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            font-weight: 800;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .profile {
            background: #ffffff;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .06);
            margin-bottom: 20px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 25px;
        }

        .avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #2563eb;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 900;
        }

        .profile-header h2 {
            margin: 0 0 8px;
        }

        .badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
        }

        .badge-admin {
            background: #ede9fe;
            color: #6d28d9;
        }

        .badge-user {
            background: #e0f2fe;
            color: #0369a1;
        }

        .badge-active {
            background: #dcfce7;
            color: #166534;
        }

        .badge-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .management {
            background: #f8fafc;
            border-radius: 16px;
            padding: 18px;
            margin-top: 20px;
        }

        .management h3 {
            margin: 0 0 15px;
            font-size: 17px;
        }

        .management-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .management-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px;
        }

        .management-card h4 {
            margin: 0 0 8px;
        }

        .management-card p {
            margin: 0 0 13px;
            color: #667085;
            font-size: 13px;
        }

        .form-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        select {
            border: 1px solid #d0d5dd;
            border-radius: 9px;
            padding: 9px 12px;
            background: #ffffff;
            font-size: 14px;
            min-width: 140px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        .info-box {
            background: #f8fafc;
            border-radius: 14px;
            padding: 16px;
        }

        .info-label {
            color: #667085;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .info-value {
            font-weight: 900;
            word-break: break-word;
        }

        /* جميع القيم المالية باللون الأخضر */
        .money-value {
            color: #16a34a !important;
            font-weight: 900 !important;
            white-space: nowrap;
            direction: ltr;
            unicode-bidi: isolate;
        }

        .balance {
            color: #16a34a !important;
            font-size: 21px;
            font-weight: 900;
        }

        .section {
            background: #ffffff;
            border-radius: 20px;
            padding: 22px;
            margin-bottom: 20px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, .06);
        }

        .section h2 {
            margin: 0 0 18px;
            font-size: 19px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        th,
        td {
            padding: 13px 10px;
            border-bottom: 1px solid #edf0f5;
            text-align: right;
            white-space: nowrap;
        }

        th {
            background: #f8fafc;
            font-size: 13px;
            color: #475467;
        }

        td {
            font-size: 14px;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved,
        .status-completed,
        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-rejected,
        .status-failed,
        .status-cancelled,
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .positive,
        .negative {
            color: #16a34a !important;
            font-weight: 900 !important;
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #667085;
            background: #f8fafc;
            border-radius: 12px;
        }

        .notice {
            margin-top: 12px;
            color: #667085;
            font-size: 12px;
        }

        @media (max-width: 900px) {

            .info-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .management-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {

            .container {
                width: min(100% - 18px, 1200px);
                margin: 12px auto;
            }

            .topbar {
                padding: 16px;
                align-items: flex-start;
                flex-direction: column;
            }

            .profile,
            .section {
                padding: 16px;
            }

            .profile-header {
                align-items: flex-start;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .avatar {
                width: 58px;
                height: 58px;
                font-size: 23px;
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

        <div>

            <h1>إدارة المستخدم</h1>

            <p>
                التحكم في حالة الحساب وصلاحيات المستخدم
            </p>

        </div>

        <div class="actions">

            <a
                href="users.php"
                class="btn btn-light"
            >
                ← المستخدمون
            </a>

            <a
                href="index.php"
                class="btn btn-primary"
            >
                لوحة الإدارة
            </a>

        </div>

    </div>

    <?php if ($successMessage): ?>

        <div class="alert alert-success">
            <?= e($successMessage) ?>
        </div>

    <?php endif; ?>

    <?php if ($errorMessage): ?>

        <div class="alert alert-error">
            <?= e($errorMessage) ?>
        </div>

    <?php endif; ?>

    <!-- بيانات المستخدم -->

    <div class="profile">

        <div class="profile-header">

            <div class="avatar">
                <?= e(
                    mb_substr(
                        (string) $user['full_name'],
                        0,
                        1,
                        'UTF-8'
                    )
                ) ?>
            </div>

            <div>

                <h2>
                    <?= e($user['full_name']) ?>
                </h2>

                <div class="badges">

                    <span
                        class="badge <?= $user['role'] === 'admin'
                            ? 'badge-admin'
                            : 'badge-user' ?>"
                    >
                        <?= e($roleLabel) ?>
                    </span>

                    <span
                        class="badge <?= $user['status'] === 'active'
                            ? 'badge-active'
                            : 'badge-inactive' ?>"
                    >
                        <?= e(
                            $user['status'] === 'active'
                                ? 'نشط'
                                : 'غير نشط'
                        ) ?>
                    </span>

                </div>

            </div>

        </div>

        <div class="info-grid">

            <div class="info-box">

                <div class="info-label">
                    رقم المستخدم
                </div>

                <div class="info-value">
                    #<?= (int) $user['id'] ?>
                </div>

            </div>

            <div class="info-box">

                <div class="info-label">
                    رقم الهاتف
                </div>

                <div class="info-value">
                    <?= e($user['phone']) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="info-label">
                    البريد الإلكتروني
                </div>

                <div class="info-value">
                    <?= e($user['email'] ?: '-') ?>
                </div>

            </div>

            <div class="info-box">

                <div class="info-label">
                    تاريخ التسجيل
                </div>

                <div class="info-value">
                    <?= e(dateLabel($user['created_at'])) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="info-label">
                    رقم المحفظة
                </div>

                <div class="info-value">

                    <?= (int) $user['wallet_id'] > 0
                        ? '#' . (int) $user['wallet_id']
                        : 'لا توجد' ?>

                </div>

            </div>

            <div class="info-box">

                <div class="info-label">
                    الرصيد الحالي
                </div>

                <div class="info-value balance money-value">
                    <?= e(money($user['balance'])) ?>
                </div>

            </div>

        </div>

        <!-- الإدارة -->

        <div class="management">

            <h3>
                ⚙️ إجراءات الإدارة
            </h3>

            <div class="management-grid">

                <!-- الحالة -->

                <div class="management-card">

                    <h4>
                        حالة الحساب
                    </h4>

                    <p>
                        تفعيل أو تعطيل قدرة المستخدم على تسجيل الدخول.
                    </p>

                    <?php if ($isCurrentAdmin): ?>

                        <button
                            type="button"
                            class="btn btn-light"
                            disabled
                        >
                            حساب المدير الحالي
                        </button>

                        <div class="notice">
                            لا يمكن تعطيل الحساب الذي تستخدمه حاليًا.
                        </div>

                    <?php else: ?>

                        <form
                            method="post"
                            onsubmit="return confirm('هل أنت متأكد من تغيير حالة هذا الحساب؟');"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrfToken()) ?>"
                            >

                            <input
                                type="hidden"
                                name="action_token"
                                value="<?= e(actionToken('user_manage')) ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="toggle_status"
                            >

                            <?php if ($user['status'] === 'active'): ?>

                                <button
                                    type="submit"
                                    class="btn btn-danger"
                                >
                                    تعطيل الحساب
                                </button>

                            <?php else: ?>

                                <button
                                    type="submit"
                                    class="btn btn-success"
                                >
                                    تفعيل الحساب
                                </button>

                            <?php endif; ?>

                        </form>

                    <?php endif; ?>

                </div>

                <!-- الدور -->

                <div class="management-card">

                    <h4>
                        صلاحيات الحساب
                    </h4>

                    <p>
                        تحديد ما إذا كان الحساب مستخدمًا عاديًا أو مديرًا.
                    </p>

                    <form
                        method="post"
                        class="form-row"
                        onsubmit="return confirm('هل أنت متأكد من تغيير صلاحيات هذا الحساب؟');"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e(csrfToken()) ?>"
                        >

                        <input
                            type="hidden"
                            name="action_token"
                            value="<?= e(actionToken('user_manage')) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="change_role"
                        >

                        <select name="role">

                            <option
                                value="user"
                                <?= $user['role'] === 'user'
                                    ? 'selected'
                                    : '' ?>
                            >
                                مستخدم عادي
                            </option>

                            <option
                                value="admin"
                                <?= $user['role'] === 'admin'
                                    ? 'selected'
                                    : '' ?>
                            >
                                مدير
                            </option>

                        </select>

                        <button
                            type="submit"
                            class="btn btn-warning"
                        >
                            حفظ الصلاحية
                        </button>

                    </form>

                    <?php if ($isCurrentAdmin): ?>

                        <div class="notice">
                            لا يمكن إزالة صلاحية المدير من حسابك الحالي.
                        </div>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

    <!-- طلبات الإيداع -->

    <div class="section">

        <h2>
            طلبات الإيداع الأخيرة
        </h2>

        <?php if (!$deposits): ?>

            <div class="empty">
                لا توجد طلبات إيداع لهذا المستخدم.
            </div>

        <?php else: ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                    <tr>
                        <th>المرجع</th>
                        <th>المبلغ</th>
                        <th>البنك</th>
                        <th>اسم المرسل</th>
                        <th>مرجع البنك</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                    </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($deposits as $deposit): ?>

                        <tr>

                            <td>
                                <?= e(
                                    $deposit['reference'] ?: '-'
                                ) ?>
                            </td>

                            <td class="money-value">
                                <?= e(
                                    money($deposit['amount'])
                                ) ?>
                            </td>

                            <td>
                                <?= e($deposit['bank_name']) ?>
                            </td>

                            <td>
                                <?= e(
                                    $deposit['sender_name'] ?: '-'
                                ) ?>
                            </td>

                            <td>
                                <?= e($deposit['bank_reference']) ?>
                            </td>

                            <td>

                                <span
                                    class="status status-<?= e(
                                        $deposit['status']
                                    ) ?>"
                                >
                                    <?= e(
                                        statusLabel(
                                            $deposit['status']
                                        )
                                    ) ?>
                                </span>

                            </td>

                            <td>
                                <?= e(
                                    dateLabel(
                                        $deposit['created_at']
                                    )
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

    <!-- طلبات السحب -->

    <div class="section">

        <h2>
            طلبات السحب الأخيرة
        </h2>

        <?php if (!$withdrawals): ?>

            <div class="empty">
                لا توجد طلبات سحب لهذا المستخدم.
            </div>

        <?php else: ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                    <tr>
                        <th>المرجع</th>
                        <th>المبلغ</th>
                        <th>المستلم</th>
                        <th>الهاتف</th>
                        <th>طريقة السحب</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                    </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($withdrawals as $withdrawal): ?>

                        <tr>

                            <td>
                                <?= e(
                                    $withdrawal['reference'] ?: '-'
                                ) ?>
                            </td>

                            <td class="money-value">
                                <?= e(
                                    money($withdrawal['amount'])
                                ) ?>
                            </td>

                            <td>
                                <?= e(
                                    $withdrawal['recipient_name']
                                ) ?>
                            </td>

                            <td>
                                <?= e(
                                    $withdrawal['recipient_phone']
                                ) ?>
                            </td>

                            <td>
                                <?= e(
                                    $withdrawal['withdrawal_method']
                                ) ?>
                            </td>

                            <td>

                                <span
                                    class="status status-<?= e(
                                        $withdrawal['status']
                                    ) ?>"
                                >
                                    <?= e(
                                        statusLabel(
                                            $withdrawal['status']
                                        )
                                    ) ?>
                                </span>

                            </td>

                            <td>
                                <?= e(
                                    dateLabel(
                                        $withdrawal['created_at']
                                    )
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

    <!-- سجل المعاملات -->

    <div class="section">

        <h2>
            سجل معاملات المحفظة
        </h2>

        <?php if (!$transactions): ?>

            <div class="empty">
                لا توجد معاملات مالية لهذا المستخدم.
            </div>

        <?php else: ?>

            <div class="table-wrapper">

                <table>

                    <thead>

                    <tr>
                        <th>#</th>
                        <th>نوع العملية</th>
                        <th>المبلغ</th>
                        <th>المرجع</th>
                        <th>الوصف</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                    </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($transactions as $transaction): ?>

                        <tr>

                            <td>
                                <?= (int) $transaction['id'] ?>
                            </td>

                            <td>
                                <?= e(
                                    transactionTypeLabel(
                                        $transaction['type']
                                    )
                                ) ?>
                            </td>

                            <td class="money-value">
                                <?= e(
                                    money(
                                        $transaction['amount']
                                    )
                                ) ?>
                            </td>

                            <td>
                                <?= e(
                                    $transaction['reference'] ?: '-'
                                ) ?>
                            </td>

                            <td>
                                <?= e(
                                    $transaction['description'] ?: '-'
                                ) ?>
                            </td>

                            <td>

                                <span
                                    class="status status-<?= e(
                                        $transaction['status']
                                    ) ?>"
                                >
                                    <?= e(
                                        statusLabel(
                                            $transaction['status']
                                        )
                                    ) ?>
                                </span>

                            </td>

                            <td>
                                <?= e(
                                    dateLabel(
                                        $transaction['created_at']
                                    )
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
