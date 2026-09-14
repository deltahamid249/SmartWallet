<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = currentUserId();

$stmt = $pdo->prepare("
    SELECT
        u.full_name,
        w.balance
    FROM users u
    INNER JOIN wallets w ON w.user_id = u.id
    WHERE u.id = :user_id
    LIMIT 1
");

$stmt->execute([
    'user_id' => $userId
]);

$user = $stmt->fetch();

if (!$user) {
    logoutUser();
    header('Location: login.php');
    exit;
}

$fullName = $user['full_name'];
$balance = $user['balance'];

$currency = 'SDG';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>المحفظة الذكية</title>

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
            text-decoration: none;
            color: inherit;
        }

        .app {
            width: 100%;
            max-width: 560px;
            margin: 0 auto;
            min-height: 100vh;
            background: #f4f6f8;
        }

        /* =========================
           HEADER
        ========================== */

        .header {
            background: #0d2238;
            color: #ffffff;
            padding: 22px 18px 26px;
            border-radius: 0 0 24px 24px;
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
            gap: 10px;
        }

        .logo-symbol {
            width: 52px;
            height: 48px;
            background: #ffffff;
            color: #0d2238;
            border-radius: 14px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 17px;
            font-weight: 900;
            letter-spacing: 0.5px;
        }

        .brand-text h1 {
            font-size: 20px;
            font-weight: 900;
            line-height: 1.3;
        }

        .brand-text p {
            margin-top: 3px;
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
        }

        .user-icon {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: #ffffff;
            color: #0d2238;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 23px;
            font-weight: 900;
        }

        /* =========================
           BALANCE
        ========================== */

        .balance-card {
            margin-top: 22px;
            background: #ffffff;
            color: #111111;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.10);
        }

        .balance-label {
            font-size: 14px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .balance-row {
            display: flex;
            align-items: baseline;
            gap: 9px;
            flex-wrap: wrap;
        }

        .balance-number {
            font-size: 34px;
            font-weight: 900;
            line-height: 1.1;
            color: #111111;
        }

        .balance-currency {
            font-size: 18px;
            font-weight: 900;
            color: #0d2238;
        }

        .balance-user {
            margin-top: 12px;
            font-size: 13px;
            font-weight: 800;
            color: #111111;
        }

        /* =========================
           CONTENT
        ========================== */

        .content {
            padding: 20px 16px 30px;
        }

        .section-title {
            font-size: 19px;
            font-weight: 900;
            color: #111111;
            margin-bottom: 14px;
        }

        /* =========================
           SERVICES GRID
        ========================== */

        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .service-card {
            width: 100%;
            min-width: 0;
            height: 132px;
            background: #ffffff;
            border: 2px solid #e2e6ea;
            border-radius: 18px;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            text-align: center;
            padding: 12px 7px;

            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06);

            transition:
                transform 0.15s ease,
                box-shadow 0.15s ease;
        }

        .service-card:active {
            transform: scale(0.96);
        }

        .service-icon {
            width: 60px;
            height: 60px;
            border-radius: 17px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 30px;
            margin-bottom: 9px;

            background: #eef3f7;
            color: #111111;
        }

        .service-card h3 {
            font-size: 14px;
            font-weight: 900;
            color: #111111;
            line-height: 1.35;
        }

        .service-card.disabled {
            opacity: 0.55;
        }

        .service-card.disabled .service-icon {
            background: #eeeeee;
        }

        /* =========================
           TRANSACTION HISTORY
        ========================== */

        .history-card {
            margin-top: 18px;
            background: #ffffff;
            border: 2px solid #e2e6ea;
            border-radius: 18px;
            padding: 16px;

            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;

            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06);
        }

        .history-left {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .history-icon {
            width: 54px;
            height: 54px;
            border-radius: 15px;
            background: #eef3f7;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 29px;
        }

        .history-text h3 {
            font-size: 16px;
            font-weight: 900;
            color: #111111;
        }

        .history-text p {
            margin-top: 4px;
            font-size: 12px;
            font-weight: 700;
            color: #111111;
        }

        .history-arrow {
            font-size: 25px;
            font-weight: 900;
            color: #111111;
        }

        /* =========================
           LOGOUT
        ========================== */

        .logout {
            display: block;
            margin-top: 18px;

            background: #ffffff;
            border: 2px solid #d8dde2;
            border-radius: 16px;

            padding: 15px;

            text-align: center;

            color: #111111;
            font-size: 15px;
            font-weight: 900;
        }

        .logout:active {
            transform: scale(0.98);
        }

        /* =========================
           MOBILE
        ========================== */

        @media (max-width: 380px) {

            .services-grid {
                gap: 8px;
            }

            .service-card {
            width: 100%;
            min-width: 0;
                height: 120px;
                padding: 9px 5px;
            }

            .service-icon {
                width: 52px;
                height: 52px;
                font-size: 28px;
            }

            .service-card h3 {
                font-size: 12px;
            }

            .balance-number {
                font-size: 30px;
            }
        }
    </style>
</head>

<body>

<div class="app">

    <!-- HEADER -->

    <header class="header">

        <div class="header-top">

            <div class="brand">

                <div class="logo-symbol">
                    SDG
                </div>

                <div class="brand-text">
                    <h1>المحفظة الذكية</h1>
                    <p>الجنيه السوداني</p>
                </div>

            </div>

            <a href="profile.php" class="user-icon" aria-label="الملف الشخصي">
                👤
            </a>

        </div>


        <!-- BALANCE -->

        <div class="balance-card">

            <div class="balance-label">
                الرصيد المتاح
            </div>

            <div class="balance-row">

                <span class="balance-number">
                    <?= e(formatMoney($balance)) ?>
                </span>

                <span class="balance-currency">
                    SDG
                </span>

            </div>

            <div class="balance-user">
                <?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?>
            </div>

        </div>

    </header>


    <!-- MAIN -->

    <main class="content">

        <h2 class="section-title">
            الخدمات
        </h2>


        <div class="services-grid">

            <!-- إضافة الأموال -->

            <a href="deposit.php" class="service-card">

                <div class="service-icon">
                    💵
                </div>

                <h3>
                    إضافة الأموال
                </h3>

            </a>


            <!-- سحب الأموال -->

            <a href="withdraw.php" class="service-card">

                <div class="service-icon">
                    💴
                </div>

                <h3>
                    سحب الأموال
                </h3>

            </a>


            <!-- إرسال الأموال -->

            <a href="transfer.php" class="service-card">

                <div class="service-icon">
                    📤
                </div>

                <h3>
                    إرسال الأموال
                </h3>

            </a>


            <!-- سجل العمليات -->

            <a href="transactions.php" class="service-card">

                <div class="service-icon">
                    📊
                </div>

                <h3>
                    سجل العمليات
                </h3>

            </a>


            <!-- الملف الشخصي -->

            <a href="profile.php" class="service-card">

                <div class="service-icon">
                    👤
                </div>

                <h3>
                    الملف الشخصي
                </h3>

            </a>


            <!-- المدفوعات -->

            <a href="payments.php" class="service-card">

                <div class="service-icon">
                    🧾
                </div>

                <h3>
                    المدفوعات
                </h3>

            </a>


            <?php if (isAdmin()): ?>

            <!-- لوحة الإدارة -->

            <a href="admin/index.php" class="service-card">

                <div class="service-icon">
                    🛡️
                </div>

                <h3>
                    لوحة الإدارة
                </h3>

            </a>

            <?php endif; ?>


            <!-- الإشعارات -->

            <div class="service-card disabled">
                <div class="service-icon">🔔</div>
                <h3>الإشعارات</h3>
            </div>


            <!-- الإعدادات -->

            <div class="service-card disabled">
                <div class="service-icon">⚙️</div>
                <h3>الإعدادات</h3>
            </div>


            <!-- المساعدة -->

            <div class="service-card disabled">
                <div class="service-icon">❓</div>
                <h3>المساعدة</h3>
            </div>


        </div>

        <!-- TRANSACTION HISTORY -->

        <a href="transactions.php" class="history-card">

            <div class="history-left">

                <div class="history-icon">
                    📋
                </div>

                <div class="history-text">

                    <h3>
                        سجل العمليات
                    </h3>

                    <p>
                        عرض جميع عمليات المحفظة
                    </p>

                </div>

            </div>

            <div class="history-arrow">
                ←
            </div>

        </a>


        <!-- LOGOUT -->

        <form method="POST" action="logout.php" class="logout">
            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
            <button
                type="submit"
                style="width:100%;border:0;background:transparent;
                font:inherit;color:inherit;cursor:pointer;"
            >
                🚪 تسجيل الخروج
            </button>
        </form>

    </main>

</div>

</body>
</html>
