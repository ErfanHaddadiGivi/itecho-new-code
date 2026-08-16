<?php

namespace App\Models;

use App\Core\Database;

/**
 * تنظیمات سایت (کلید/مقدار).
 *
 * روش استفاده:
 *      Setting::get('site_name')
 *      Setting::get('pickup_fee', 0)
 *      Setting::set('pickup_fee', 25000)
 */
class Setting
{
    /** همه تنظیمات یک بار از دیتابیس خوانده و در حافظه نگه داشته می‌شوند */
    private static ?array $cache = null;

    /**
     * خواندن همه تنظیمات به صورت آرایه کلید => مقدار
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $rows  = Database::fetchAll('SELECT setting_key, setting_value FROM settings');
        $items = [];

        foreach ($rows as $row) {
            $items[$row['setting_key']] = $row['setting_value'];
        }

        return self::$cache = $items;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::all()[$key] ?? null;
        return ($value === null || $value === '') ? $default : $value;
    }

    /**
     * خواندن یک تنظیم عددی — مثل هزینه تحویل حضوری
     */
    public static function getInt(string $key, int $default = 0): int
    {
        return (int) self::get($key, $default);
    }

    /**
     * خواندن یک تنظیم بله/خیر — مثل حالت تست زرین‌پال
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key);
        return $value === null ? $default : in_array($value, ['1', 1, 'true', true], true);
    }

    /**
     * ذخیره یک تنظیم. اگر کلید وجود نداشته باشد ساخته می‌شود.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        Database::run(
            'INSERT INTO settings (setting_key, setting_value, setting_group)
                  VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            [$key, (string) $value, $group]
        );

        // حافظه موقت را باطل کن تا مقدار جدید خوانده شود
        self::$cache = null;
    }

    /**
     * ذخیره چند تنظیم با هم
     */
    public static function setMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            self::set($key, $value, $group);
        }
    }

    /**
     * تنظیمات یک گروه به همراه عنوان فارسی — برای ساخت فرم پنل مدیریت
     */
    public static function group(string $group): array
    {
        return Database::fetchAll(
            'SELECT setting_key, setting_value, title
               FROM settings
              WHERE setting_group = ?
              ORDER BY sort_order, setting_key',
            [$group]
        );
    }
}
