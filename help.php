<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

requireLogin();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>المساعدة - المحفظة الذكية</title>
<style>
body{margin:0;background:#f5f7fb;font-family:Arial,sans-serif;color:#111827}
.container{max-width:700px;margin:auto;padding:20px}
.header,.faq{background:#fff;border-radius:18px;padding:20px;margin-bottom:15px;box-shadow:0 5px 18px rgba(0,0,0,.07)}
h1{margin:0 0 8px}.back{display:inline-block;margin-top:15px;color:#2563eb;text-decoration:none}
.faq h3{margin-top:0}.faq p{line-height:1.8;color:#475569}
</style>
</head>
<body>
<div class="container">
<div class="header">
<h1>❓ المساعدة</h1>
<p>دليل مختصر لاستخدام المحفظة الذكية.</p>
<a class="back" href="index.php">← العودة إلى المحفظة</a>
</div>

<div class="faq">
<h3>💰 كيف أضيف أموالًا؟</h3>
<p>افتح «إضافة الأموال»، أدخل البيانات المطلوبة وأرسل طلب الإيداع. سيظهر الطلب بحالته حتى تتم مراجعته.</p>
</div>

<div class="faq">
<h3>📤 كيف أرسل أموالًا؟</h3>
<p>اختر «إرسال الأموال»، ثم أدخل بيانات المستلم والمبلغ. قبل التأكيد راجع البيانات جيدًا.</p>
</div>

<div class="faq">
<h3>🛠️ أين أجد الخدمات؟</h3>
<p>من الصفحة الرئيسية اختر «الخدمات» للوصول إلى شحن الهاتف والكهرباء والإنترنت والفواتير والتعليم والخدمات الحكومية.</p>
</div>

<div class="faq">
<h3>📊 كيف أراجع عملياتي؟</h3>
<p>اختر «سجل العمليات» من الصفحة الرئيسية لعرض العمليات المسجلة على محفظتك.</p>
</div>

<div class="faq">
<h3>🔐 كيف أحافظ على حسابي؟</h3>
<p>لا تشارك كلمة المرور أو بيانات الدخول مع أي شخص، وسجّل الخروج عند استخدام جهاز مشترك.</p>
</div>
</div>
</body>
</html>
