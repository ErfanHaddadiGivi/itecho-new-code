<?php

namespace App\Core;

/**
 * پل ارتباطی سایت با «ربات اپل‌آیدی» (appleid-bot).
 *
 * سایت و ربات یک دیتابیس مشترک دارند؛ این کلاس با خواندن config ربات
 * (از مسیر appleid_bot_path)، به همان دیتابیس/کلید رمز/کلاینت پیام‌رسان
 * وصل می‌شود تا سفارش‌های سایت هم داخل بله مدیریت شوند.
 *
 * کلاس‌های AppleBot\* از پوشهٔ src ربات بارگذاری می‌شوند (اتولودر جدا).
 */
class AppleId
{
    private static bool $booted = false;
    private static ?array $cfg  = null;
    private static ?\AppleBot\Db $db = null;

    private static function botPath(): string
    {
        return rtrim((string) config('appleid_bot_path', ''), '/');
    }

    /** آیا ماژول اپل‌آیدی در دسترس است؟ (مسیر ربات و config درست تنظیم شده) */
    public static function available(): bool
    {
        return self::config() !== null;
    }

    public static function config(): ?array
    {
        if (self::$booted) {
            return self::$cfg;
        }
        self::$booted = true;

        $base = self::botPath();
        if ($base === '') {
            return self::$cfg = null;
        }
        $cfgFile = $base . '/config/config.php';
        $srcDir  = $base . '/src';
        if (!is_file($cfgFile) || !is_dir($srcDir)) {
            return self::$cfg = null;
        }

        // اتولودر کلاس‌های AppleBot\ از پوشهٔ src ربات
        spl_autoload_register(static function (string $class) use ($srcDir): void {
            if (strncmp($class, 'AppleBot\\', 9) !== 0) {
                return;
            }
            $file = $srcDir . '/' . str_replace('\\', '/', substr($class, 9)) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        });

        $c = require $cfgFile;
        return self::$cfg = (is_array($c) ? $c : null);
    }

    public static function db(): \AppleBot\Db
    {
        if (self::$db === null) {
            self::$db = new \AppleBot\Db(self::config()['db']);
        }
        return self::$db;
    }

    public static function crypto(): \AppleBot\Crypto
    {
        return new \AppleBot\Crypto((string) (self::config()['encryption_key'] ?? ''));
    }

    public static function orders(): \AppleBot\Orders
    {
        return new \AppleBot\Orders(self::db(), self::crypto());
    }

    public static function messenger(): \AppleBot\Messenger
    {
        $c   = self::config();
        $log = new \AppleBot\Logger(self::botPath() . '/logs', false);
        return new \AppleBot\Messenger((string) ($c['bot_token'] ?? ''), (string) ($c['api_base_url'] ?? ''), $log);
    }

    /** شناسهٔ ادمین‌ها (config + جدول admins) */
    public static function adminIds(): array
    {
        $c   = self::config();
        $ids = array_map('intval', $c['admin_ids'] ?? []);
        foreach (self::db()->fetchAll('SELECT telegram_user_id FROM admins WHERE is_active = 1') as $r) {
            $ids[] = (int) $r['telegram_user_id'];
        }
        return array_values(array_unique($ids));
    }

    // --- خواندن محصول/ضمانت/تنظیمات از دیتابیس مشترک ---

    public static function activeWarranties(): array
    {
        return self::db()->fetchAll('SELECT * FROM warranty_types WHERE is_active = 1 ORDER BY sort_order, id');
    }

    public static function productFor(int $warrantyId, int $icloud): ?array
    {
        return self::db()->fetch(
            "SELECT p.*, w.name AS warranty_name
               FROM products p LEFT JOIN warranty_types w ON w.id = p.warranty_type_id
              WHERE p.region = 'US' AND p.warranty_type_id = ? AND p.icloud_enabled = ? AND p.is_active = 1
              ORDER BY p.sort_order, p.id LIMIT 1",
            [$warrantyId, $icloud]
        );
    }

    public static function productById(int $id): ?array
    {
        return self::db()->fetch(
            'SELECT p.*, w.name AS warranty_name
               FROM products p LEFT JOIN warranty_types w ON w.id = p.warranty_type_id
              WHERE p.id = ? LIMIT 1',
            [$id]
        );
    }

    public static function setting(string $key, ?string $default = null): ?string
    {
        $row = self::db()->fetch('SELECT `value` FROM settings WHERE `key` = ? LIMIT 1', [$key]);
        return $row ? $row['value'] : $default;
    }

    /** پیام و کیبورد اقدام ادمین (برای سفارش سایت) — دکمه‌ها با ربات بله هماهنگ‌اند */
    public static function notifyAdminsNewOrder(int $orderId, ?string $receiptPath): void
    {
        $order = self::orders()->find($orderId);
        if ($order === null) {
            return;
        }
        $p       = self::orders()->decryptPersonal($order);
        $product = self::productById((int) $order['product_id']);
        $icloud  = !empty($product['icloud_enabled']) ? 'فعال' : 'غیرفعال';

        $text = "🆕 <b>سفارش سایت #{$orderId}</b>\n"
            . 'محصول: ' . ($product['warranty_name'] ?? '-') . ' / آیکلود ' . $icloud . "\n"
            . 'قیمت: ' . number_format((int) $order['price_amount']) . " تومان\n"
            . 'مشتری (وب): ' . htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) . "\n"
            . "———\n"
            . 'ایمیل: <code>' . htmlspecialchars($p['email']) . "</code>\n"
            . 'شماره: ' . htmlspecialchars($p['phone']) . "\n"
            . 'تولد: ' . htmlspecialchars($p['birthdate']);

        $kb = \AppleBot\Messenger::inlineKeyboard([
            \AppleBot\Messenger::inlineRow([
                ['✅ تأیید و شروع', 'ord:approve:' . $orderId],
                ['❌ رد سفارش', 'ord:reject:' . $orderId],
            ]),
        ]);

        $tg      = self::messenger();
        $fileId  = null;
        foreach (self::adminIds() as $adminId) {
            if ($receiptPath !== null && is_file($receiptPath)) {
                $res = $tg->sendPhotoFile($adminId, $receiptPath, $text, $kb);
                if ($fileId === null && is_array($res) && !empty($res['photo'])) {
                    $photos = $res['photo'];
                    $fileId = (string) (end($photos)['file_id'] ?? '');
                }
            } else {
                $tg->sendMessage($adminId, $text, $kb);
            }
        }

        // اگر فیش به بله آپلود شد، file_id را ذخیره و فایل محلی را پاک کن
        if ($fileId !== null && $fileId !== '') {
            self::db()->update('orders', ['receipt_file_id' => $fileId], 'id = :id', ['id' => $orderId]);
            if ($receiptPath !== null && is_file($receiptPath)) {
                @unlink($receiptPath);
            }
        }
    }

    public static function notifyAdminsCode(int $orderId, string $code): void
    {
        $kb = \AppleBot\Messenger::inlineKeyboard([
            \AppleBot\Messenger::inlineRow([['📥 ثبت اطلاعات نهایی', 'ord:final:' . $orderId]]),
        ]);
        $tg = self::messenger();
        foreach (self::adminIds() as $adminId) {
            $tg->sendMessage($adminId, "🔑 کد تأیید سفارش سایت #{$orderId}:\n<code>" . htmlspecialchars($code) . '</code>', $kb);
        }
    }
}
