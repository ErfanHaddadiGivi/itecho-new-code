<?php
/**
 * بوت‌استرپِ ربات: بارگذاری تنظیمات، اتولودر، و سیم‌کشی وابستگی‌ها.
 * هم webhook.php و هم اسکریپت‌های cron این فایل را require می‌کنند.
 *
 * خروجی: آرایه‌ای از سرویس‌های آماده.
 */

declare(strict_types=1);

define('BOT_BASE', __DIR__);

// --- تنظیمات ---
$configFile = BOT_BASE . '/config/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    error_log('appleid-bot: config/config.php missing');
    exit;
}
$config = require $configFile;

// --- اتولودر ساده برای AppleBot\ ---
spl_autoload_register(static function (string $class): void {
    $prefix = 'AppleBot\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = BOT_BASE . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

// --- خطاها: نمایش نده، لاگ کن ---
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set($config['timezone'] ?? 'Asia/Tehran');

use AppleBot\Db;
use AppleBot\Logger;
use AppleBot\Telegram;
use AppleBot\Crypto;
use AppleBot\Orders;
use AppleBot\PartnerService;
use AppleBot\Settings;
use AppleBot\Lang;
use AppleBot\Conversations;
use AppleBot\BotContext;
use AppleBot\StateMachine;
use AppleBot\AdminActions;
use AppleBot\RateLimiter;

$log      = new Logger(BOT_BASE . '/logs', (bool) ($config['debug'] ?? false));
$db       = new Db($config['db']);
$crypto   = new Crypto((string) ($config['encryption_key'] ?? ''));
$telegram = new Telegram((string) ($config['bot_token'] ?? ''), $log);
$orders   = new Orders($db, $crypto);
$partners = new PartnerService($db);
$settings = new Settings($db);
$lang     = new Lang(BOT_BASE . '/lang/fa.php');
$conv     = new Conversations($db);

$ctx = new BotContext(
    $db, $telegram, $orders, $partners, $settings, $lang, $conv, $log,
    (array) ($config['admin_ids'] ?? [])
);

return [
    'config'       => $config,
    'log'          => $log,
    'db'           => $db,
    'crypto'       => $crypto,
    'telegram'     => $telegram,
    'settings'     => $settings,
    'ctx'          => $ctx,
    'stateMachine' => new StateMachine($ctx),
    'adminActions' => new AdminActions($ctx),
    'rateLimiter'  => new RateLimiter($db, $settings),
];
