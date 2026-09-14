<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

requireLogin();

$userId = currentUserId();

/*
 * جلب بيانات المستخدم
 */
$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.full_name,
        u.phone,
        u.email,
        u.status,
        u.created_at,
        w.balance,
        w.currency
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

$fullName = (string) $user['full_name'];
$phone = (string) $user['phone'];
$email = (string) $user['email'];
$status = (string) $user['status'];
$createdAt = (string) $user['created_at'];
$balance = $user['balance'];

/*
 * العملة الأساسية للمحفظة
 */
$currency = 'SDG';

/*
 * حالة الحساب
 */
$statusText = match ($status) {
    'active' => 'نشط',
    'blocked' => 'محظور',
    'pending' => 'قيد المراجعة',
    default => $status
};

/*
 * تنسيق تاريخ إنشاء الحساب
 */
$createdTimestamp = strtotime($createdAt);

if ($createdTimestamp !== false) {
    $createdText = date('Y-m-d', $createdTimestamp)
        . ' — '
        . date('H:i', $createdTimestamp);
} else {
    $createdText = $createdAt;
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

    <title>الملف الشخصي - المحفظة الذكية</title>

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
        }

        /* =========================
           HEADER
        ========================== */

        .header {
            background: #0d2238;
            color: #ffffff;

            padding: 18px 16px 28px;

            border-radius: 0 0 25px 25px;
        }

        .header-top {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .back-button {
            width: 46px;
            height: 46px;

            background: #ffffff;
            color: #0d2238;

            border-radius: 14px;

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

            color: #ffffff;

            font-size: 12px;
            font-weight: 700;
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
            padding: 20px 15px 30px;
        }

        /* =========================
           PROFILE CARD
        ========================== */

        .profile-card {
            background: #ffffff;

            border: 2px solid #e1e5e9;

            border-radius: 21px;

            padding: 22px 18px;

            text-align: center;

            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.06);

            margin-bottom: 16px;
        }

        .profile-avatar {
            width: 88px;
            height: 88px;

            margin: 0 auto 13px;

            border-radius: 50%;

            background: #0d2238;
            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 42px;
        }

        .profile-name {
            font-size: 21px;
            font-weight: 900;

            color: #111111;
        }

        .profile-phone {
            margin-top: 6px;

            font-size: 14px;
            font-weight: 800;

            color: #111111;
        }

        /* =========================
           ACCOUNT DETAILS
        ========================== */

        .section-title {
            font-size: 18px;
            font-weight: 900;

            color: #111111;

            margin: 18px 0 11px;
        }

        .details-card {
            background: #ffffff;

            border: 2px solid #e1e5e9;

            border-radius: 20px;

            padding: 7px 16px;

            box-shadow:
                0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .detail-row {
            min-height: 62px;

            display: flex;
            align-items: center;

            gap: 12px;

            border-bottom: 1px solid #dedede;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-icon {
            width: 42px;
            height: 42px;

            flex-shrink: 0;

            border-radius: 12px;

            background: #eef3f7;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 22px;
        }

        .detail-content {
            flex: 1;
            min-width: 0;
        }

        .detail-label {
            font-size: 12px;
            font-weight: 800;

            color: #111111;
        }

        .detail-value {
            margin-top: 4px;

            font-size: 14px;
            font-weight: 900;

            color: #111111;

            word-break: break-word;
        }

        /* =========================
           BALANCE
        ========================== */

        .balance-card {
            margin-top: 16px;

            background: #0d2238;
            color: #ffffff;

            border-radius: 20px;

            padding: 18px;

            box-shadow:
                0 5px 14px rgba(0, 0, 0, 0.10);
        }

        .balance-label {
            font-size: 13px;
            font-weight: 800;

            color: #ffffff;
        }

        .balance-row {
            display: flex;
            align-items: baseline;

            gap: 9px;

            margin-top: 7px;
        }

        .balance {
            font-size: 30px;
            font-weight: 900;

            color: #ffffff;
        }

        .currency {
            font-size: 17px;
            font-weight: 900;

            color: #ffffff;
        }

        /* =========================
           STATUS
        ========================== */

        .status-card {
            margin-top: 16px;

            background: #ffffff;

            border: 2px solid #e1e5e9;

            border-radius: 20px;

            padding: 17px;

            display: flex;
            align-items: center;

            gap: 12px;
        }

        .status-icon {
            width: 48px;
            height: 48px;

            border-radius: 14px;

            background: #e8f6eb;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 25px;
        }

        .status-text {
            flex: 1;
        }

        .status-label {
            font-size: 12px;
            font-weight: 800;

            color: #111111;
        }

        .status-value {
            margin-top: 3px;

            font-size: 15px;
            font-weight: 900;

            color: #087f23;
        }

        /* =========================
           BUTTONS
        ========================== */

        .button {
            display: block;

            margin-top: 16px;

            padding: 15px;

            border-radius: 16px;

            text-align: center;

            font-size: 15px;
            font-weight: 900;
        }

        .home-button {
            background: #0d2238;
            color: #ffffff;
        }

        .logout-button {
            background: #ffffff;
            color: #111111;

            border: 2px solid #d8dde2;
        }

        .button:active {
            transform: scale(0.98);
        }

        /* =========================
           MOBILE
        ========================== */

        @media (max-width: 380px) {

            .profile-card {
                padding: 19px 14px;
            }

            .profile-avatar {
                width: 78px;
                height: 78px;

                font-size: 36px;
            }

            .profile-name {
                font-size: 19px;
            }

            .detail-value {
                font-size: 13px;
            }

            .balance {
                font-size: 27px;
            }
        }

    </style>

</head>

<body>

<div class="app">

    <!-- HEADER -->

    <header class="header">

        <div class="header-top">

            <a
                href="index.php"
                class="back-button"
                aria-label="العودة"
            >
                →
            </a>

            <div class="header-title">

                <h1>
                    الملف الشخصي
                </h1>

                <p>
                    بيانات حسابك في المحفظة
                </p>

            </div>

            <div class="sdg-logo">
                SDG
            </div>

        </div>

    </header>


    <!-- CONTENT -->

    <main class="content">

        <!-- PROFILE -->

        <section class="profile-card">

            <div class="profile-avatar">
                👤
            </div>

            <div class="profile-name">
                <?= htmlspecialchars(
                    $fullName,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

            <div class="profile-phone" dir="ltr">
                <?= htmlspecialchars(
                    $phone,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </div>

        </section>


        <!-- ACCOUNT DETAILS -->

        <h2 class="section-title">
            بيانات الحساب
        </h2>

        <section class="details-card">

            <!-- الاسم -->

            <div class="detail-row">

                <div class="detail-icon">
                    👤
                </div>

                <div class="detail-content">

                    <div class="detail-label">
                        الاسم الكامل
                    </div>

                    <div class="detail-value">
                        <?= htmlspecialchars(
                            $fullName,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                </div>

            </div>


            <!-- الهاتف -->

            <div class="detail-row">

                <div class="detail-icon">
                    📱
                </div>

                <div class="detail-content">

                    <div class="detail-label">
                        رقم الهاتف
                    </div>

                    <div
                        class="detail-value"
                        dir="ltr"
                    >
                        <?= htmlspecialchars(
                            $phone,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                </div>

            </div>


            <!-- البريد -->

            <div class="detail-row">

                <div class="detail-icon">
                    ✉️
                </div>

                <div class="detail-content">

                    <div class="detail-label">
                        البريد الإلكتروني
                    </div>

                    <div
                        class="detail-value"
                        dir="ltr"
                    >
                        <?= htmlspecialchars(
                            $email,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                </div>

            </div>


            <!-- العملة -->

            <div class="detail-row">

                <div class="detail-icon">
                    💴
                </div>

                <div class="detail-content">

                    <div class="detail-label">
                        العملة
                    </div>

                    <div class="detail-value">
                        SDG — الجنيه السوداني
                    </div>

                </div>

            </div>


            <!-- تاريخ إنشاء الحساب -->

            <div class="detail-row">

                <div class="detail-icon">
                    📅
                </div>

                <div class="detail-content">

                    <div class="detail-label">
                        تاريخ إنشاء الحساب
                    </div>

                    <div class="detail-value">
                        <?= htmlspecialchars(
                            $createdText,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                </div>

            </div>

        </section>


        <!-- BALANCE -->

        <section class="balance-card">

            <div class="balance-label">
                رصيد المحفظة
            </div>

            <div class="balance-row">

                <span class="balance">
                    <?= e(formatMoney($balance)) ?>
                </span>

                <span class="currency">
                    SDG
                </span>

            </div>

        </section>


        <!-- ACCOUNT STATUS -->

        <section class="status-card">

            <div class="status-icon">
                ✓
            </div>

            <div class="status-text">

                <div class="status-label">
                    حالة الحساب
                </div>

                <div class="status-value">
                    <?= htmlspecialchars(
                        $statusText,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>

            </div>

        </section>


        <!-- HOME -->

        <a
            href="index.php"
            class="button home-button"
        >
            🏠 العودة إلى الصفحة الرئيسية
        </a>


        <!-- LOGOUT -->

        <form method="POST" action="logout.php">
            <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
            <button
                type="submit"
                class="button logout-button"
                style="width:100%;font-family:inherit;"
            >
                🚪 تسجيل الخروج
            </button>
        </form>

    </main>

</div>

</body>

</html>
