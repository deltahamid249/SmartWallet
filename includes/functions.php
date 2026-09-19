<?php
declare(strict_types=1);

/**
 * أدوات عامة للنظام.
 *
 * يحتوي هذا الملف على الدوال المشتركة التي تستخدمها صفحات المستخدم
 * وصفحات الإدارة، مثل الحماية، تنسيق المبالغ، الرسائل المؤقتة، وسجل
 * العمليات الإدارية. لا يحتوي هذا الملف على واجهة مرئية للمستخدم.
 */

/**
 * حساب المسار النسبي للتطبيق داخل DOCUMENT_ROOT.
 *
 * عند وضع المشروع داخل:
 * C:/xampp/htdocs/smart-wallet
 * يجب أن تصبح الروابط:
 * /smart-wallet/index.php
 * بدلًا من:
 * /index.php
 *
 * هذا يجعل النظام يعمل من مجلد فرعي في XAMPP دون تغيير منطق الصفحات.
 */
function appBasePath(): string
{
    static $basePath;

    if ($basePath !== null) {
        return $basePath;
    }

    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $projectRoot = realpath(__DIR__ . '/..');

    if ($documentRoot === false || $projectRoot === false) {
        return $basePath = '';
    }

    $documentRoot = str_replace('\\', '/', $documentRoot);
    $projectRoot = str_replace('\\', '/', $projectRoot);

    $documentRoot = rtrim($documentRoot, '/');

    if (
        $projectRoot !== $documentRoot
        && !str_starts_with($projectRoot, $documentRoot . '/')
    ) {
        return $basePath = '';
    }

    $relativePath = substr($projectRoot, strlen($documentRoot));

    return $basePath = '/' . trim($relativePath, '/');
}

/**
 * إنشاء رابط داخلي يبدأ من مجلد التطبيق.
 *
 * تستقبل الدالة مسارًا يبدأ بشرطة مائلة، ثم تضيف إليه مسار المشروع
 * تلقائيًا إذا كان المشروع يعمل داخل مجلد فرعي في Apache.
 */
function appUrl(string $path = '/'): string
{
    $path = '/' . ltrim($path, '/');
    $basePath = appBasePath();

    return ($basePath === '' ? '' : $basePath) . $path;
}

/**
 * تنفيذ تحويل HTTP داخلي ثم إيقاف الصفحة الحالية.
 *
 * استخدام هذه الدالة يمنع ضياع التحويلات عند تشغيل المشروع داخل
 * C:/xampp/htdocs/smart-wallet بدلًا من جذر الخادم مباشرة.
 */
function redirectTo(string $path): never
{
    header('Location: ' . appUrl($path));
    exit;
}

/**
 * تحويل قيمة إلى HTML آمن.
 *
 * تستخدم الدالة عند عرض بيانات قادمة من قاعدة البيانات أو من المستخدم
 * حتى لا يتم تفسيرها كأكواد HTML أو JavaScript داخل الصفحة.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * إنشاء أو استرجاع رمز حماية النماذج CSRF من الجلسة.
 */
function csrfToken(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_csrf'];
}

/**
 * التحقق من أن رمز CSRF المرسل يطابق الرمز المخزن في الجلسة.
 */
function verifyCsrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['_csrf'])
        && hash_equals((string) $_SESSION['_csrf'], $token);
}

/**
 * إنشاء رمز لمرة واحدة لعملية حساسة مثل الإيداع أو السحب.
 */
function actionToken(string $name): string
{
    $key = '_action_' . $name;

    if (empty($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(24));
    }

    return (string) $_SESSION[$key];
}

/**
 * التحقق من رمز العملية لمرة واحدة ثم حذفه من الجلسة.
 */
function consumeActionToken(string $name, ?string $token): bool
{
    $key = '_action_' . $name;

    if (
        !is_string($token)
        || !isset($_SESSION[$key])
        || !hash_equals((string) $_SESSION[$key], $token)
    ) {
        return false;
    }

    unset($_SESSION[$key]);

    return true;
}

/**
 * تخزين رسالة مؤقتة أو قراءتها من الجلسة.
 *
 * عند تمرير قيمة يتم تخزينها، وعند عدم تمرير قيمة يتم إرجاع الرسالة
 * وحذفها حتى تظهر مرة واحدة فقط.
 */
function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return is_string($message) ? $message : null;
}

/**
 * تنظيف مبلغ موجب دون تحويله إلى float.
 *
 * يحافظ ذلك على الدقة المالية، ويتوافق مع DECIMAL(18,2) الذي يسمح
 * بستة عشر رقمًا قبل الفاصلة ورقمين بعدها.
 */
function normalizeAmount(mixed $value): ?string
{
    $value = trim((string) $value);

    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
        return null;
    }

    [$integer, $fraction] = array_pad(
        explode('.', $value, 2),
        2,
        ''
    );

    $integer = ltrim($integer, '0');
    $integer = $integer === '' ? '0' : $integer;
    $fraction = str_pad($fraction, 2, '0');

    if (strlen($integer) > 16) {
        return null;
    }

    if ($integer === '0' && $fraction === '00') {
        return null;
    }

    return $integer . '.' . $fraction;
}

/**
 * تنسيق مبلغ رقمي بفاصلتين عشريتين وفواصل للآلاف.
 */
function formatMoney(mixed $value): string
{
    $value = trim((string) $value);

    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
        return '0.00';
    }

    [$integer, $fraction] = array_pad(
        explode('.', $value, 2),
        2,
        ''
    );

    $integer = ltrim($integer, '0');
    $integer = $integer === '' ? '0' : $integer;
    $fraction = str_pad($fraction, 2, '0');

    return strrev(
        implode(
            ',',
            str_split(strrev($integer), 3)
        )
    ) . '.' . $fraction;
}

/**
 * عرض مبلغ مالي مع عملة النظام.
 *
 * هذه الدالة ترجع نصًا فقط، ولا تضيف HTML،
 * حتى تبقى آمنة للاستخدام داخل الرسائل والتنبيهات
 * والعمليات البرمجية.
 */
function moneyLabel(mixed $value): string
{
    return formatMoney($value) . ' SDG';
}

/**
 * تنظيف ملاحظة نصية وقصر طولها قبل حفظها في قاعدة البيانات.
 */
function cleanNote(mixed $value): ?string
{
    $note = trim((string) $value);

    if ($note === '') {
        return null;
    }

    if (strlen($note) > 255) {
        return null;
    }

    return $note;
}

/**
 * الحصول على عنوان IP للطلب بعد التحقق من أنه عنوان صحيح.
 */
function clientIp(): ?string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP)
        ? $ip
        : null;
}

/**
 * تسجيل إجراء إداري مع المستخدم والهدف وعنوان IP.
 */
function logAdminAction(
    string $action,
    string $description,
    ?string $targetType = null,
    ?int $targetId = null
): void {
    global $pdo;

    $adminId = isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : null;

    $ipAddress = clientIp();

    $stmt = $pdo->prepare("
        INSERT INTO admin_audit_logs
        (
            admin_id,
            action,
            target_type,
            target_id,
            description,
            ip_address
        )
        VALUES
        (
            :admin_id,
            :action,
            :target_type,
            :target_id,
            :description,
            :ip_address
        )
    ");

    $stmt->execute([
        'admin_id' => $adminId ?: null,
        'action' => $action,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'description' => $description,
        'ip_address' => $ipAddress,
    ]);
}

/**
 * إنشاء إشعار يظهر في لوحة إدارة النظام.
 */
function createAdminNotification(
    string $title,
    string $message,
    string $type = 'info',
    ?string $targetType = null,
    ?int $targetId = null
): void {
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO admin_notifications
        (
            title,
            message,
            type,
            target_type,
            target_id
        )
        VALUES
        (
            :title,
            :message,
            :type,
            :target_type,
            :target_id
        )
    ");

    $stmt->execute([
        ':title' => $title,
        ':message' => $message,
        ':type' => $type,
        ':target_type' => $targetType,
        ':target_id' => $targetId,
    ]);
}

/**
 * إرجاع حقل HTML مخفي يحتوي على رمز CSRF للنموذج.
 */
function csrfField(): string
{
    return '<input type="hidden" name="_csrf" value="' .
        htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') .
        '">';
}
