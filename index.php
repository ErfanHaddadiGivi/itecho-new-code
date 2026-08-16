<?php
/**
 * ایتکو — نقطه ورود سایت (Front Controller)
 *
 * همه درخواست‌ها (به جز فایل‌های واقعی مثل عکس و CSS) توسط .htaccess
 * به همین فایل هدایت می‌شوند.
 */

// ---------------------------------------------------------------------
// مسیرهای پایه
// ---------------------------------------------------------------------
define('ROOT_PATH',   __DIR__);
define('APP_PATH',    ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');

// ---------------------------------------------------------------------
// بارگذاری خودکار کلاس‌ها (جایگزین ساده Composer autoload)
// App\Core\Database  →  app/Core/Database.php
// ---------------------------------------------------------------------
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

// ---------------------------------------------------------------------
// تنظیمات و توابع کمکی
// ---------------------------------------------------------------------
require_once CONFIG_PATH . '/config.php';
require_once APP_PATH . '/Core/helpers.php';

// نمایش خطاها فقط در حالت توسعه
if (config('debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/logs/php-errors.log');
}

date_default_timezone_set('Asia/Tehran');

// ---------------------------------------------------------------------
// شروع نشست (Session)
// ---------------------------------------------------------------------
App\Core\Session::start();

// ---------------------------------------------------------------------
// اجرای مسیرها
// ---------------------------------------------------------------------
$router = new App\Core\Router();
require_once APP_PATH . '/routes.php';

try {
    $router->dispatch();
} catch (Throwable $e) {
    App\Core\ErrorHandler::handle($e);
}
