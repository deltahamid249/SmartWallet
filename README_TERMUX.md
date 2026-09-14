# Smart Wallet - Termux Edition

هذه النسخة معدلة لتعمل محليًا على Termux، وليست نظام دفع حقيقيًا.

## ما تم إصلاحه

- إضافة ملف اتصال PDO المفقود.
- تفعيل `PDO::ERRMODE_EXCEPTION`.
- تعطيل Emulated Prepares.
- إضافة إعدادات جلسة أكثر أمانًا.
- إضافة CSRF Tokens للنماذج.
- إضافة رموز طلب أحادية الاستخدام للعمليات المالية.
- تطبيق Post/Redirect/Get بعد نجاح الإيداع والسحب والتحويل.
- منع القيم السالبة والصيغ العلمية للمبالغ.
- تجنب استخدام Float في الحسابات المالية.
- إضافة قفل محافظ بترتيب ثابت في التحويل.
- إضافة Rate Limiting بسيط لتسجيل الدخول.
- منع كشف أخطاء قاعدة البيانات للمستخدم.
- إضافة قيود وفهارس لقاعدة البيانات.
- إضافة حماية للملفات الحساسة عند استخدام Apache.
- إضافة Router لمنع عرض ملفات SQL عند استخدام PHP built-in server.
- استبعاد النسخة الاحتياطية التي تحتوي على بيانات حساسة من ملف ZIP النهائي.

## تنبيه مالي مهم

الإيداع والسحب في هذه النسخة محليان وتجريبيان:

- الإيداع يزيد الرصيد الداخلي فقط.
- السحب ينقص الرصيد الداخلي فقط.
- لا توجد بوابة دفع.
- لا يوجد تحويل بنكي أو Mobile Money.
- لا توجد Webhooks.
- لا تستخدم هذه النسخة مع أموال حقيقية قبل بناء نظام Ledger ومزود دفع ومراجعة مالية.

## المتطلبات

```bash
pkg update
pkg upgrade
pkg install php mariadb unzip
```

إذا كان اسم حزمة قاعدة البيانات مختلفًا في مستودع Termux لديك، استخدم حزمة MariaDB المتاحة بدلًا منها.

## تشغيل MariaDB

نفذ تهيئة قاعدة البيانات مرة واحدة فقط. قد يكون اسم الأمر في إصدار Termux لديك واحدًا من الآتي:

```bash
mariadb-install-db --datadir=$PREFIX/var/lib/mysql
```

أو:

```bash
mysql_install_db --datadir=$PREFIX/var/lib/mysql
```

ثم شغّل الخادم:

```bash
mariadbd-safe --datadir=$PREFIX/var/lib/mysql &
```

إذا لم يتوفر `mariadbd-safe` استخدم الأمر المتاح في إصدار MariaDB لديك، مثل:

```bash
mysqld_safe --datadir=$PREFIX/var/lib/mysql &
```

## إنشاء قاعدة البيانات

من مجلد المشروع نفذ:

```bash
mariadb -u root < database/database.sql
```

أو إذا كان الأمر المتاح هو `mysql`:

```bash
mysql -u root < database/database.sql
```

إذا كانت قاعدة البيانات محمية بكلمة مرور، اضف الخيار:

```bash
mariadb -u root -p < database/database.sql
```

## إعداد الاتصال

الإعدادات الافتراضية هي:

```text
HOST=127.0.0.1
PORT=3306
DATABASE=smart_wallet
USER=root
PASSWORD=
```

يمكن تغييرها عبر متغيرات البيئة:

```bash
export SMART_WALLET_DB_HOST=127.0.0.1
export SMART_WALLET_DB_PORT=3306
export SMART_WALLET_DB_NAME=smart_wallet
export SMART_WALLET_DB_USER=root
export SMART_WALLET_DB_PASS='your-password'
```

## تشغيل الموقع

من داخل مجلد المشروع:

```bash
php -S 127.0.0.1:8000 router.php
```

ثم افتح في المتصفح:

```text
http://127.0.0.1:8000/register.php
```

أو:

```text
http://127.0.0.1:8000/login.php
```

## حسابات الاختبار

لا تعتمد على الحسابات الموجودة في أي نسخة احتياطية قديمة. أنشئ حسابًا جديدًا من:

```text
/register.php
```

## ملاحظات مهمة

- لا تستخدم `database/smart_wallet_backup.sql` في الإنتاج.
- لا تضع كلمات مرور قاعدة البيانات داخل ملفات عامة.
- لا تستخدم الإيداع والسحب الحاليين لأموال حقيقية.
- يجب استخدام HTTPS عند النشر.
- يجب بناء نظام أدوار وإدارة قبل النشر العام.
- يجب إضافة Ledger وAudit Log قبل أي استخدام مالي.