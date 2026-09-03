<?php
/**
 * فایل تشخیص موقت — علت خطای ۵۰۰ را روی صفحه نشان می‌دهد.
 *
 * ⚠️ فقط برای عیب‌یابی. بعد از پیداکردن خطا، این فایل را از هاست پاک کن.
 *
 * چرا لازم شد؟ در حالت production خطاها مخفی‌اند و صفحهٔ ۵۰۰ خالی می‌آید؛
 * این فایل نمایش خطا را روشن می‌کند و بوت سایت را مرحله‌به‌مرحله جلو می‌برد
 * تا دقیقاً معلوم شود کجا و چرا می‌شکند.
 */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// خطاهای مرگبار (parse/fatal) که با try/catch گرفته نمی‌شوند را هم نشان بده
register_shutdown_function(function (): void {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR], true)) {
        echo '<pre style="background:#fdd;color:#900;padding:14px;border:1px solid #900;direction:ltr;text-align:left">'
           . "FATAL:\n" . htmlspecialchars($e['message'])
           . "\nin " . htmlspecialchars($e['file']) . ':' . $e['line']
           . '</pre>';
    }
});

header('Content-Type: text/html; charset=utf-8');
echo '<div style="font-family:Tahoma,sans-serif;direction:rtl;padding:16px">';
echo '<h2>تشخیص بوت سایت</h2>';

function step(string $s): void { echo '<div>▶ ' . htmlspecialchars($s) . ' …</div>'; @ob_flush(); @flush(); }

define('ROOT_PATH', __DIR__);
define('APP_PATH', __DIR__ . '/app');
define('CONFIG_PATH', __DIR__ . '/config');

spl_autoload_register(function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) { return; }
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) { require_once $file; }
});

step('بارگذاری config/config.php');
require CONFIG_PATH . '/config.php';
// جلوگیری از خاموش‌شدن نمایش خطا توسط index (اینجا index اجرا نمی‌شود، ولی محض احتیاط)
ini_set('display_errors', '1');

step('بارگذاری helpers.php');
require APP_PATH . '/Core/helpers.php';
ini_set('display_errors', '1');

step('شروع Session');
App\Core\Session::start();

step('ساخت Router و بارگذاری routes.php');
$router = new App\Core\Router();
require APP_PATH . '/routes.php';

step('اجرای مسیر صفحهٔ اصلی (dispatch)');
try {
    $router->dispatch();
    echo '<div style="color:green">✅ dispatch بدون Exception اجرا شد.</div>';
} catch (\Throwable $e) {
    echo '<pre style="background:#fdd;color:#900;padding:14px;border:1px solid #900;direction:ltr;text-align:left">'
       . 'EXCEPTION: ' . htmlspecialchars(get_class($e)) . "\n"
       . htmlspecialchars($e->getMessage()) . "\n"
       . 'in ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . "\n\n"
       . htmlspecialchars($e->getTraceAsString())
       . '</pre>';
}

echo '<hr><div style="color:#555">پس از خواندن خطا، این فایل (_probe.php) را پاک کن.</div>';
echo '</div>';
