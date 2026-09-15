<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdmin();

global $pdo;

$stats = [
    'users' => 0,
    'active_users' => 0,
    'pending_users' => 0,
    'balances' => '0.00',
];

try {
    $stmt = $pdo->query("
        SELECT
            COUNT(*) AS users,
            SUM(status = 'active') AS active_users,
            SUM(status = 'pending') AS pending_users
        FROM users
    ");

    $userStats = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userStats) {
        $stats['users'] = (int) ($userStats['users'] ?? 0);
        $stats['active_users'] = (int) ($userStats['active_users'] ?? 0);
        $stats['pending_users'] = (int) ($userStats['pending_users'] ?? 0);
    }

    $stmt = $pdo->query("
        SELECT COALESCE(SUM(balance), 0)
        FROM wallets
    ");

    $stats['balances'] = (string) ($stmt->fetchColumn() ?? '0.00');
} catch (Throwable $e) {
    // إبقاء لوحة الإدارة تعمل في حال تعذر تحميل الإحصاءات.
}

$cards = [
    [
        'icon' => '👥',
        'title' => 'إدارة المستخدمين',
        'description' => 'عرض وتعديل وإدارة المستخدمين',
        'url' => 'users.php',
    ],
    [
        'icon' => '💰',
        'title' => 'الإيداعات',
        'description' => 'إدارة طلبات الإيداع',
        'url' => 'deposits.php',
    ],
    [
        'icon' => '💸',
        'title' => 'السحوبات',
        'description' => 'إدارة طلبات السحب',
        'url' => 'withdrawals.php',
    ],
    [
        'icon' => '🔔',
        'title' => 'الإشعارات',
        'description' => 'إدارة إشعارات النظام',
        'url' => 'notifications.php',
    ],
    [
        'icon' => '🛠️',
        'title' => 'الخدمات',
        'description' => 'إدارة الخدمات والطلبات',
        'url' => 'services.php',
    ],
    [
        'icon' => '🔄',
        'title' => 'التحويلات',
        'description' => 'متابعة التحويلات',
        'url' => 'transfers.php',
    ],
    [
        'icon' => '⚙️',
        'title' => 'إعدادات النظام',
        'description' => 'إعدادات حساب مدير النظام',
        'url' => 'settings.php',
    ],
];

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>لوحة الإدارة - المحفظة الذكية</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    background: #f5f7fb;
    color: #111827;
    font-family: Tahoma, Arial, sans-serif;
}

.container {
    width: min(1100px, calc(100% - 24px));
    margin: 0 auto;
    padding: 18px 0 30px;
}

.header {
    background: #ffffff;
    border-radius: 20px;
    padding: 18px;
    margin-bottom: 15px;
    box-shadow: 0 5px 18px rgba(0,0,0,.07);
}

.header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.brand-icon {
    width: 52px;
    height: 52px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #2563eb;
    color: #ffffff;
    font-size: 26px;
    flex-shrink: 0;
}

.brand h1 {
    margin: 0;
    font-size: 21px;
    font-weight: 900;
}

.brand p {
    margin: 5px 0 0;
    color: #6b7280;
    font-size: 13px;
    font-weight: 700;
}

.logout {
    text-decoration: none;
    background: #f3f4f6;
    color: #374151;
    border-radius: 12px;
    padding: 10px 13px;
    font-size: 13px;
    font-weight: 900;
    white-space: nowrap;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-top: 15px;
}

.stat {
    background: #f8fafc;
    border-radius: 14px;
    padding: 12px;
    text-align: center;
    border: 1px solid #eef2f7;
}

.stat-value {
    font-size: 20px;
    font-weight: 900;
    color: #2563eb;
}

.stat-label {
    margin-top: 4px;
    color: #6b7280;
    font-size: 11px;
    font-weight: 800;
}


/* =========================
   شريط الأخبار المتحرك
   ========================= */

.news-ticker {
    width: 100%;
    height: 52px;
    background: #ffffff;
    border-radius: 15px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    overflow: hidden;
    box-shadow: 0 4px 14px rgba(0,0,0,.06);
    border: 1px solid #edf0f5;
}

.news-ticker-label {
    width: 52px;
    height: 100%;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #2563eb;
    color: #ffffff;
    font-size: 21px;
    z-index: 2;
}

.news-ticker-window {
    flex: 1;
    overflow: hidden;
    direction: ltr;
}

.news-ticker-track {
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
    animation: smartWalletTicker 24s linear infinite;
}

.news-ticker-track span {
    display: inline-flex;
    align-items: center;
    font-size: 14px;
    font-weight: 900;
    color: #172033;
    padding: 0 28px;
}

.news-ticker-track span::after {
    content: "•";
    margin-right: 28px;
    color: #2563eb;
}

@keyframes smartWalletTicker {

    from {
        transform: translateX(0);
    }

    to {
        transform: translateX(-50%);
    }

}


/* =========================
   شبكة لوحة الإدارة 3 × 3
   ========================= */

.admin-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

.admin-card {
    min-width: 0;
    min-height: 145px;
    background: #ffffff;
    border-radius: 18px;
    padding: 14px 10px;
    text-decoration: none;
    color: #111827;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    border: 1px solid #edf0f5;
    box-shadow: 0 5px 16px rgba(0,0,0,.06);
    transition: transform .15s ease, box-shadow .15s ease;
}

.admin-card:active {
    transform: scale(.97);
}

.card-icon {
    width: 58px;
    height: 58px;
    border-radius: 17px;
    background: #eff6ff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 29px;
    margin-bottom: 9px;
}

.card-title {
    font-size: 14px;
    font-weight: 900;
    line-height: 1.35;
}

.card-description {
    margin-top: 5px;
    color: #6b7280;
    font-size: 10px;
    line-height: 1.4;
    font-weight: 700;
}

.footer {
    text-align: center;
    color: #9ca3af;
    font-size: 11px;
    font-weight: 700;
    padding-top: 20px;
}


/* =========================
   الهاتف
   ========================= */

@media (max-width: 420px) {

    .container {
        width: calc(100% - 16px);
        padding-top: 10px;
    }

    .admin-grid {
        gap: 9px;
    }

    .admin-card {
        min-height: 125px;
        border-radius: 15px;
        padding: 10px 6px;
    }

    .card-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        font-size: 24px;
        margin-bottom: 7px;
    }

    .card-title {
        font-size: 12px;
    }

    .card-description {
        font-size: 9px;
    }

    .brand-icon {
        width: 46px;
        height: 46px;
        font-size: 23px;
    }

    .brand h1 {
        font-size: 18px;
    }

    .logout {
        padding: 8px 9px;
        font-size: 11px;
    }

    .news-ticker {
        height: 46px;
        border-radius: 13px;
    }

    .news-ticker-label {
        width: 45px;
        font-size: 18px;
    }

    .news-ticker-track span {
        font-size: 12px;
        padding: 0 18px;
    }

    .news-ticker-track span::after {
        margin-right: 18px;
    }

}

</style>

</head>

<body>

<div class="container">


<header class="header">

    <div class="header-top">

        <div class="brand">

            <div class="brand-icon">
                🛡️
            </div>

            <div>

                <h1>
                    لوحة الإدارة
                </h1>

                <p>
                    المحفظة الذكية — التحكم في النظام
                </p>

            </div>

        </div>

        <a
            href="../logout.php"
            class="logout"
        >
            تسجيل الخروج
        </a>

    </div>


    <div class="stats">

        <div class="stat">

            <div class="stat-value">
                <?= $stats['users'] ?>
            </div>

            <div class="stat-label">
                إجمالي المستخدمين
            </div>

        </div>


        <div class="stat">

            <div class="stat-value">
                <?= $stats['active_users'] ?>
            </div>

            <div class="stat-label">
                المستخدمون النشطون
            </div>

        </div>


        <div class="stat">

            <div class="stat-value">
                <?= $stats['pending_users'] ?>
            </div>

            <div class="stat-label">
                الحسابات المعلقة
            </div>

        </div>

    </div>

</header>


<main>


<!-- شريط الأسماء المتحرك -->

<div class="news-ticker">

    <div class="news-ticker-label">
        📢
    </div>

    <div class="news-ticker-window">

        <div class="news-ticker-track">

            <span>
                إدارة المحفظة الذكية
            </span>

            <span>
                وجدان محمد
            </span>

            <span>
                مستورة مبارك
            </span>

            <span>
                سارة عبدالعظيم
            </span>


            <!-- تكرار المحتوى لضمان استمرار الحركة -->

            <span>
                إدارة المحفظة الذكية
            </span>

            <span>
                وجدان محمد
            </span>

            <span>
                مستورة مبارك
            </span>

            <span>
                سارة عبدالعظيم
            </span>

        </div>

    </div>

</div>


<!-- شبكة الإدارة -->

<section class="admin-grid">

<?php foreach ($cards as $card): ?>

    <a
        href="<?= htmlspecialchars($card['url'], ENT_QUOTES, 'UTF-8') ?>"
        class="admin-card"
    >

        <div class="card-icon">
            <?= htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8') ?>
        </div>

        <div class="card-title">
            <?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?>
        </div>

        <div class="card-description">
            <?= htmlspecialchars($card['description'], ENT_QUOTES, 'UTF-8') ?>
        </div>

    </a>

<?php endforeach; ?>

</section>


</main>


<footer class="footer">

    المحفظة الذكية © <?= date('Y') ?>

</footer>


</div>

</body>

</html>
