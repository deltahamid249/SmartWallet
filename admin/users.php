<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

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

function userStatusLabel(string $status): string
{
    return match ($status) {
        'active' => 'نشط',
        'blocked' => 'معطل',
        default => $status
    };
}

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

    <title>إدارة المستخدمين</title>

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
            color: #2563eb;
            font-weight: bold;
            margin-right: 10px;
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
            padding: 18px;
            border-radius: 16px;
            margin-bottom: 20px;
            box-shadow: 0 4px 16px rgba(0,0,0,.06);
        }

        .filters form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 10px;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d8dee8;
            border-radius: 9px;
            font-size: 15px;
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: #2563eb;
        }

        .search-btn {
            border: 0;
            border-radius: 9px;
            padding: 12px 18px;
            background: #2563eb;
            color: #ffffff;
            font-weight: bold;
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
            min-width: 1050px;
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

        .status,
        .role {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
        }

        .status.active {
            background: #dcfce7;
            color: #166534;
        }

        .status.inactive {
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
            font-weight: bold;
            white-space: nowrap;
        }

        .view-btn {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 8px;
            background: #2563eb;
            color: #ffffff;
            text-decoration: none;
            font-weight: bold;
        }

        .empty {
            padding: 40px;
            text-align: center;
            color: #64748b;
        }

        .user-id {
            color: #64748b;
            font-size: 12px;
        }

        @media (max-width: 800px) {

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .topbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .filters form {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <div class="topbar">

        <h1>👥 إدارة المستخدمين</h1>

        <div class="links">

            <a href="index.php">
                لوحة الإدارة
            </a>

            <a href="../index.php">
                الرئيسية
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
                <?= (int) ($stats['blocked'] ?? 0) ?>
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
                placeholder="ابحث بالاسم أو الهاتف أو البريد الإلكتروني"
            >

            <select name="status">

                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>
                    كل الحالات
                </option>

                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>
                    نشط
                </option>

                <option value="blocked" <?= $status === 'blocked' ? 'selected' : '' ?>>
                    معطل
                </option>

            </select>

            <select name="role">

                <option value="all" <?= $role === 'all' ? 'selected' : '' ?>>
                    كل الأدوار
                </option>

                <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>
                    مستخدم
                </option>

                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>
                    مدير
                </option>

            </select>

            <button
                type="submit"
                class="search-btn"
            >
                بحث
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
                        <th>ID</th>
                        <th>المستخدم</th>
                        <th>الهاتف</th>
                        <th>البريد الإلكتروني</th>
                        <th>الدور</th>
                        <th>الحالة</th>
                        <th>الرصيد</th>
                        <th>تاريخ التسجيل</th>
                        <th>الإجراء</th>
                    </tr>

                </thead>

                <tbody>

                <?php foreach ($users as $user): ?>

                    <tr>

                        <td>
                            <span class="user-id">
                                #<?= (int) $user['id'] ?>
                            </span>
                        </td>

                        <td>
                            <strong>
                                <?= e($user['full_name']) ?>
                            </strong>
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

                            <a
                                class="view-btn"
                                href="user.php?id=<?= (int) $user['id'] ?>"
                            >
                                عرض
                            </a>

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
