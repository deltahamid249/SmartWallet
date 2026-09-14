<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

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
    ':user_id' => $userId
]);

$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    exit('بيانات المستخدم غير موجودة.');
}

$fullName = (string) $user['full_name'];
$balance = (string) $user['balance'];
$currency = 'SDG';

$services = [
    [
        'icon' => '📱',
        'title' => 'شحن الهاتف',
        'description' => 'شحن أرقام زين وسوداني وMTN والخدمات المدعومة.',
        'link' => 'recharge.php',
        'class' => 'mobile',
    ],
    [
        'icon' => '⚡',
        'title' => 'الكهرباء',
        'description' => 'دفع خدمات الكهرباء عند توفر التكامل الرسمي.',
        'link' => 'electricity.php',
        'class' => 'electricity',
    ],
    [
        'icon' => '🌐',
        'title' => 'الإنترنت',
        'description' => 'دفع وتجديد خدمات الإنترنت المدعومة.',
        'link' => 'internet.php',
        'class' => 'internet',
    ],
    [
        'icon' => '🧾',
        'title' => 'الفواتير',
        'description' => 'إدارة ودفع الفواتير والخدمات المختلفة.',
        'link' => 'bills.php',
        'class' => 'bills',
    ],
    [
        'icon' => '🎓',
        'title' => 'التعليم',
        'description' => 'خدمات ورسوم المؤسسات التعليمية المشاركة.',
        'link' => 'education.php',
        'class' => 'education',
    ],
    [
        'icon' => '🏛️',
        'title' => 'الخدمات الحكومية',
        'description' => 'الوصول إلى الخدمات والرسوم الحكومية المدعومة.',
        'link' => 'government.php',
        'class' => 'government',
    ],
];

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>الخدمات - المحفظة الذكية</title>

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

        .container {
            width: min(100% - 24px, 1100px);
            margin: 20px auto 40px;
        }

        .top {
            background: #ffffff;
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .07);
            margin-bottom: 20px;
        }

        .top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .title {
            margin: 0;
            font-size: 28px;
            font-weight: 900;
        }

        .subtitle {
            margin-top: 8px;
            color: #6b7280;
            line-height: 1.7;
        }

        .balance {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 13px 18px;
            min-width: 180px;
        }

        .balance-label {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .balance-value {
            font-size: 21px;
            font-weight: 900;
        }

        .back {
            display: inline-block;
            margin-top: 16px;
            background: #e5e7eb;
            color: #111827;
            padding: 10px 15px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
        }

        .section-title {
            margin: 25px 3px 14px;
            font-size: 21px;
            font-weight: 900;
        }

        .services {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .service-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 22px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .06);
            border: 1px solid #edf0f5;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 9px 24px rgba(0, 0, 0, .09);
        }

        .service-icon {
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            background: #f1f5f9;
            font-size: 28px;
            margin-bottom: 15px;
        }

        .service-card h3 {
            margin: 0 0 9px;
            font-size: 18px;
        }

        .service-card p {
            margin: 0;
            color: #6b7280;
            line-height: 1.7;
            font-size: 14px;
        }

        .notice {
            margin-top: 20px;
            background: #ffffff;
            border-radius: 16px;
            padding: 18px;
            border-right: 4px solid #2563eb;
            color: #374151;
            line-height: 1.8;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .05);
        }

        .notice strong {
            color: #111827;
        }

        @media (max-width: 800px) {
            .services {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 560px) {
            .container {
                width: min(100% - 16px, 1100px);
                margin-top: 10px;
            }

            .top {
                padding: 18px;
            }

            .title {
                font-size: 23px;
            }

            .services {
                grid-template-columns: 1fr;
            }

            .balance {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <section class="top">

        <div class="top-row">

            <div>
                <h1 class="title">🛠️ الخدمات</h1>

                <div class="subtitle">
                    مرحبًا <?= e($fullName) ?>،
                    اختر الخدمة التي تريد استخدامها من مكان واحد.
                </div>
            </div>

            <div class="balance">
                <div class="balance-label">
                    رصيد المحفظة
                </div>

                <div class="balance-value">
                    <?= e(formatMoney($balance)) ?>
                    <?= e($currency) ?>
                </div>
            </div>

        </div>

        <a href="../index.php" class="back">
            ← العودة إلى المحفظة
        </a>

    </section>


    <h2 class="section-title">
        الخدمات المتاحة
    </h2>


    <section class="services">

        <?php foreach ($services as $service): ?>

            <a
                href="<?= e($service['link']) ?>"
                class="service-card <?= e($service['class']) ?>"
            >

                <div class="service-icon">
                    <?= e($service['icon']) ?>
                </div>

                <h3>
                    <?= e($service['title']) ?>
                </h3>

                <p>
                    <?= e($service['description']) ?>
                </p>

            </a>

        <?php endforeach; ?>

    </section>


    <div class="notice">

        <strong>ℹ️ ملاحظة:</strong>

        بعض الخدمات تحتاج إلى تكامل رسمي مع مزود الخدمة قبل تنفيذ العمليات
        بشكل حقيقي. سيتم ربط كل خدمة بمزودها الرسمي عند توفر التكامل والصلاحيات
        المطلوبة.

    </div>

</div>

</body>
</html>
