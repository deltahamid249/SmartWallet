<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

global $pdo;

$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? 'all'));
$role = trim((string) ($_GET['role'] ?? 'all'));

$allowedStatuses = ['all', 'active', 'blocked'];
$allowedRoles = ['all', 'user', 'admin'];

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'all';
}

if (!in_array($role, $allowedRoles, true)) {
    $role = 'all';
}

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(
        u.full_name LIKE :search
        OR u.username LIKE :search
        OR u.phone LIKE :search
        OR u.email LIKE :search
    )";

    $params[':search'] = '%' . $search . '%';
}

if ($status !== 'all') {
    $where[] = 'u.status = :status';
    $params[':status'] = $status;
}

if ($role !== 'all') {
    $where[] = 'u.role = :role';
    $params[':role'] = $role;
}

$whereSql = '';

if ($where) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

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
        COALESCE(w.balance, 0) AS balance
    FROM users u
    LEFT JOIN wallets w
        ON w.user_id = u.id
    $whereSql
    ORDER BY u.id DESC
");

$stmt->execute($params);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statsStmt = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'active') AS active,
        SUM(status = 'blocked') AS inactive,
        SUM(role = 'admin') AS admins
    FROM users
");

$stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

/**
 * عرض حالة حساب المستخدم بالعربية.
 */
function userStatusLabel(string $status): string
{
    return match ($status) {
        'active' => 'نشط',
        'blocked' => 'معطل',
        default => $status
    };
}

/**
 * عرض دور المستخدم بالعربية.
 */
function userRoleLabel(string $role): string
{
    return match ($role) {
        'admin' => 'مدير',
        'user' => 'مستخدم',
        default => $role
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

    <title>إدارة المستخدمين - المحفظة الذكية</title>

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
            width: min(1250px, 94%);
            margin: 20px auto 35px;
        }

        .topbar {
            background: #ffffff;
            padding: 17px 18px;
            border-radius: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 16px rgba(0,0,0,.07);
            margin-bottom: 16px;
        }

        .title-box {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .title-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: #eff6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
        }

        .topbar h1 {
            margin: 0;
            font-size: 21px;
            font-weight: 900;
        }

        .subtitle {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .links {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .links a {
            text-decoration: none;
            color: #2563eb;
            background: #eff6ff;
            padding: 9px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 900;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat {
            background: #ffffff;
            padding: 17px;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,.06);
            text-align: center;
        }

        .stat span {
            display: block;
            color: #64748b;
            margin-bottom: 7px;
            font-size: 12px;
            font-weight: 800;
        }

        .stat strong {
            font-size: 23px;
            font-weight: 900;
        }

        .filters {
            background: #ffffff;
            padding: 15px;
            border-radius: 16px;
            margin-bottom: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,.06);
        }

        .filters form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 9px;
        }

        input,
        select {
            width: 100%;
            padding: 11px;
            border: 1px solid #d8dee8;
            border-radius: 9px;
            font-size: 14px;
            outline: none;
            background: #ffffff;
        }

        input:focus,
        select:focus {
            border-color: #2563eb;
        }

        .search-btn {
            border: 0;
            border-radius: 9px;
            padding: 11px 18px;
            background: #2563eb;
            color: #ffffff;
            font-weight: 900;
            cursor: pointer;
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
            min-width: 1120px;
        }

        th,
        td {
            padding: 13px;
            border-bottom: 1px solid #edf0f4;
            text-align: right;
            vertical-align: middle;
        }

        th {
            background: #f8fafc;
            font-size: 13px;
            font-weight: 900;
        }

        td {
            font-size: 13px;
        }

        .user-name {
            font-weight: 900;
        }

        .username {
            display: block;
            color: #64748b;
            font-size: 11px;
            margin-top: 3px;
        }

        .status,
        .role {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 900;
        }

        .status.active {
            background: #dcfce7;
            color: #166534;
        }

        .status.blocked {
            background: #fee2e2;
            color: #991b1b;
        }

        .role.admin {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .role.user {
            background: #f1f5f9;
            color: #475569;
        }

        .balance {
            color: #16a34a !important;
            font-weight: 900;
            white-space: nowrap;
        }

        .actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 7px 10px;
            border-radius: 9px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .edit-btn {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .delete-btn {
            background: #fef2f2;
            color: #b91c1c;
        }

        .protected-btn {
            background: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
        }

        .empty {
            padding: 45px 20px;
            text-align: center;
            color: #64748b;
            font-weight: 800;
        }

        .user-id {
            color: #64748b;
            font-size: 11px;
        }

        .protected-label {
            display: block;
            margin-top: 5px;
            color: #64748b;
            font-size: 10px;
            font-weight: 800;
        }

        @media (max-width: 800px) {

            .container {
                width: min(96%, 1250px);
                margin-top: 10px;
            }

            .topbar {
                align-items: stretch;
                flex-direction: column;
            }

            .links {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
            }

            .links a {
                text-align: center;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 9px;
            }

            .stat {
                padding: 14px 8px;
            }

            .filters form {
                grid-template-columns: 1fr;
            }

            .search-btn {
                width: 100%;
            }

        }

        @media (max-width: 420px) {

            .title-icon {
                width: 44px;
                height: 44px;
                font-size: 22px;
            }

            .topbar h1 {
                font-size: 18px;
            }

            .subtitle {
                font-size: 10px;
            }

            .stat strong {
                font-size: 20px;
            }

            .stat span {
                font-size: 10px;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <div class="topbar">

        <div class="title-box">

            <div class="title-icon">
                👥
            </div>

            <div>

                <h1>
                    إدارة المستخدمين
                </h1>

                <p class="subtitle">
                    عرض وتعديل وإدارة حسابات مستخدمي المحفظة
                </p>

            </div>

        </div>

        <div class="links">

            <a href="index.php">
                🛡️ لوحة الإدارة
            </a>

            <a href="../index.php">
                🏠 الرئيسية
            </a>

        </div>

    </div>


    <div class="stats">

        <div class="stat">

            <span>
                إجمالي المستخدمين
            </span>

            <strong>
                <?= (int) ($stats['total'] ?? 0) ?>
            </strong>

        </div>

        <div class="stat">

            <span>
                المستخدمون النشطون
            </span>

            <strong>
                <?= (int) ($stats['active'] ?? 0) ?>
            </strong>

        </div>

        <div class="stat">

            <span>
                المستخدمون المعطلون
            </span>

            <strong>
                <?= (int) ($stats['inactive'] ?? 0) ?>
            </strong>

        </div>

        <div class="stat">

            <span>
                المدراء
            </span>

            <strong>
                <?= (int) ($stats['admins'] ?? 0) ?>
            </strong>

        </div>

    </div>


    <div class="filters">

        <form method="get">

            <input
                type="text"
                name="search"
                value="<?= e($search) ?>"
                placeholder="ابحث بالاسم أو اسم المستخدم أو الهاتف أو البريد"
            >

            <select name="status">

                <option
                    value="all"
                    <?= $status === 'all' ? 'selected' : '' ?>
                >
                    كل الحالات
                </option>

                <option
                    value="active"
                    <?= $status === 'active' ? 'selected' : '' ?>
                >
                    نشط
                </option>

                <option
                    value="blocked"
                    <?= $status === 'blocked' ? 'selected' : '' ?>
                >
                    معطل
                </option>

            </select>

            <select name="role">

                <option
                    value="all"
                    <?= $role === 'all' ? 'selected' : '' ?>
                >
                    كل الأدوار
                </option>

                <option
                    value="user"
                    <?= $role === 'user' ? 'selected' : '' ?>
                >
                    مستخدم
                </option>

                <option
                    value="admin"
                    <?= $role === 'admin' ? 'selected' : '' ?>
                >
                    مدير
                </option>

            </select>

            <button
                type="submit"
                class="search-btn"
            >
                🔎 بحث
            </button>

        </form>

    </div>


    <div class="table-wrap">

        <?php if (!$users): ?>

            <div class="empty">
                لا توجد نتائج مطابقة.
            </div>

        <?php else: ?>

            <table>

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            المستخدم
                        </th>

                        <th>
                            الهاتف
                        </th>

                        <th>
                            البريد الإلكتروني
                        </th>

                        <th>
                            الدور
                        </th>

                        <th>
                            الحالة
                        </th>

                        <th>
                            الرصيد
                        </th>

                        <th>
                            تاريخ التسجيل
                        </th>

                        <th>
                            إدارة الحساب
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($users as $user): ?>

                    <?php
                    $userId = (int) $user['id'];
                    $isProtectedAdmin = $userId === SYSTEM_ADMIN_ID;
                    ?>

                    <tr>

                        <td>

                            <span class="user-id">
                                #<?= $userId ?>
                            </span>

                        </td>


                        <td>

                            <span class="user-name">
                                <?= e($user['full_name']) ?>
                            </span>

                            <?php if (!empty($user['username'])): ?>

                                <span class="username">
                                    @<?= e($user['username']) ?>
                                </span>

                            <?php endif; ?>

                            <?php if ($isProtectedAdmin): ?>

                                <span class="protected-label">
                                    🔐 مدير النظام الرئيسي
                                </span>

                            <?php endif; ?>

                        </td>


                        <td>
                            <?= e($user['phone']) ?>
                        </td>


                        <td>
                            <?= e($user['email'] ?? '') ?>
                        </td>


                        <td>

                            <span class="role <?= e($user['role']) ?>">
                                <?= e(userRoleLabel($user['role'])) ?>
                            </span>

                        </td>


                        <td>

                            <span class="status <?= e($user['status']) ?>">
                                <?= e(userStatusLabel($user['status'])) ?>
                            </span>

                        </td>


                        <td class="balance">

                            <?= formatMoney($user['balance']) ?>
                            SDG

                        </td>


                        <td>
                            <?= e($user['created_at']) ?>
                        </td>


                        <td>

                            <div class="actions">

                                <a
                                    href="user.php?id=<?= $userId ?>"
                                    class="action-btn edit-btn"
                                >
                                    ✏️ تعديل
                                </a>

                                <?php if ($isProtectedAdmin): ?>

                                    <span
                                        class="action-btn protected-btn"
                                        title="حساب مدير النظام الرئيسي محمي"
                                    >
                                        🔐 محمي
                                    </span>

                                <?php else: ?>

                                    <a
                                        href="user.php?id=<?= $userId ?>#delete"
                                        class="action-btn delete-btn"
                                    >
                                        🗑️ حذف
                                    </a>

                                <?php endif; ?>

                            </div>

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
