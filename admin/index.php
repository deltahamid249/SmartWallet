<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$stats = [
    'users' => 0,
    'active_users' => 0,
    'wallets' => 0,
    'balances' => 0,
    'pending_withdrawals' => 0,
    'pending_deposits' => 0,
    'transfers' => 0,
];

$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$stats['users'] = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
$stats['active_users'] = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM wallets");
$stats['wallets'] = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(balance), 0) FROM wallets");
$stats['balances'] = (float) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM withdrawal_requests WHERE status = 'pending'");
$stats['pending_withdrawals'] = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM deposit_requests WHERE status = 'pending'");
$stats['pending_deposits'] = (int) $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM transfers");
$stats['transfers'] = (int) $stmt->fetchColumn();

$currency = 'SDG';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الإدارة - المحفظة الذكية</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Tahoma, sans-serif;
            background: #f5f7fb;
            color: #111827;
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
            margin-right: 12px;
            font-weight: bold;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 18px;
        }

        .welcome {
            margin-bottom: 25px;
        }

        .welcome h2 {
            margin: 0 0 8px;
            font-size: 25px;
        }

        .welcome p {
            margin: 0;
            color: #6b7280;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 30px;
        }

        .stat {
            background: #fff;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .07);
        }

        .stat-title {
            color: #6b7280;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 900;
        }

        .services {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .service-card {
            display: block;
            background: #fff;
            border-radius: 18px;
            padding: 25px;
            text-decoration: none;
            color: #111827;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .07);
            transition: .2s;
        }

        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 9px 24px rgba(0, 0, 0, .10);
        }

        .service-icon {
            font-size: 35px;
            margin-bottom: 12px;
        }

        .service-card h3 {
            margin: 0 0 8px;
            font-size: 19px;
        }

        .service-card p {
            margin: 0;
            color: #6b7280;
            line-height: 1.7;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            margin-top: 12px;
            padding: 6px 10px;
            border-radius: 8px;
            background: #f3f4f6;
            font-size: 13px;
            font-weight: bold;
        }

        @media (max-width: 850px) {
            .stats,
            .services {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 560px) {
            .stats,
            .services {
                grid-template-columns: 1fr;
            }

            .topbar {
                align-items: flex-start;
            }

            .topbar h1 {
                font-size: 19px;
            }

            .container {
                margin-top: 20px;
            }
        }
    </style>
</head>

<body>

<header class="topbar">
    <h1>🛡️ لوحة الإدارة</h1>

    <div>
        <a href="../index.php">الرئيسية</a>
        <a href="../logout.php">تسجيل الخروج</a>
    </div>
</header>

<main class="container">

    <section class="welcome">
        <h2>مرحبًا بك في لوحة الإدارة</h2>
        <p>إدارة ومتابعة عمليات المحفظة الذكية من مكان واحد.</p>
    </section>

    <section class="stats">

        <div class="stat">
            <div class="stat-title">إجمالي المستخدمين</div>
            <div class="stat-value"><?= $stats['users'] ?></div>
        </div>

        <div class="stat">
            <div class="stat-title">المستخدمون النشطون</div>
            <div class="stat-value"><?= $stats['active_users'] ?></div>
        </div>

        <div class="stat">
            <div class="stat-title">إجمالي المحافظ</div>
            <div class="stat-value"><?= $stats['wallets'] ?></div>
        </div>

        <div class="stat">
            <div class="stat-title">إجمالي الأرصدة</div>
            <div class="stat-value">
                <?= number_format($stats['balances'], 2) ?>
                <?= $currency ?>
            </div>
        </div>

        <div class="stat">
            <div class="stat-title">السحوبات المعلقة</div>
            <div class="stat-value"><?= $stats['pending_withdrawals'] ?></div>
        </div>

        <div class="stat">
            <div class="stat-title">الإيداعات المعلقة</div>
            <div class="stat-value"><?= $stats['pending_deposits'] ?></div>
        </div>

    </section>

    <?php
    $notificationStmt = $pdo->query("
        SELECT COUNT(*)
        FROM admin_notifications
        WHERE is_read = 0
    ");
    $unreadNotifications = (int) $notificationStmt->fetchColumn();
    ?>

    <section class="services">

        <a href="users.php" class="service-card">
            <div class="service-icon">👥</div>
            <h3>إدارة المستخدمين</h3>
            <p>عرض المستخدمين والبحث عنهم وإدارة حالة الحساب والصلاحيات.</p>
            <span class="badge"><?= $stats['users'] ?> مستخدم</span>
        </a>

        <a href="deposits.php" class="service-card">
            <div class="service-icon">💰</div>
            <h3>إدارة الإيداعات</h3>
            <p>مراجعة طلبات الإيداع والموافقة عليها أو رفضها.</p>
            <span class="badge"><?= $stats['pending_deposits'] ?> معلقة</span>
        </a>

        <a href="withdrawals.php" class="service-card">
            <div class="service-icon">💸</div>
            <h3>إدارة السحوبات</h3>
            <p>مراجعة طلبات السحب والموافقة عليها أو رفضها.</p>
            <span class="badge"><?= $stats['pending_withdrawals'] ?> معلقة</span>
        </a>

        <a href="notifications.php" class="service-card">
            <div class="service-icon">🔔</div>
            <h3>الإشعارات الإدارية</h3>
            <p>متابعة الطلبات والأحداث التي تحتاج إلى مراجعة إدارية.</p>
            <span class="badge"><?= $unreadNotifications ?> غير مقروءة</span>
        </a>

        <a href="services.php" class="service-card">
            <div class="service-icon">🛠️</div>
            <h3>إدارة الخدمات</h3>
            <p>متابعة طلبات شحن الهاتف والخدمات الرقمية والعمليات المرتبطة بالمستخدمين.</p>
            <span class="badge">طلبات الخدمات</span>
        </a>

        <a href="transfers.php" class="service-card">
            <div class="service-icon">🔄</div>
            <h3>إدارة التحويلات</h3>
            <p>عرض ومتابعة جميع التحويلات بين المحافظ مع البحث والفلترة.</p>
            <span class="badge"><?= $stats['transfers'] ?> تحويل</span>
        </a>

    </section>

</main>

</body>
</html>
