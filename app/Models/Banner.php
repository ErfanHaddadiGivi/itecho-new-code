<?php

namespace App\Models;

use App\Core\Database;

/**
 * بنرهای اسلایدر صفحه اصلی.
 *
 * جدول banners از قبل در schema.sql وجود دارد. اینجا فقط متدهای
 * ساده برای خواندن و مدیریت آن اضافه شده است.
 */
class Banner extends Model
{
    protected static string $table = 'banners';

    /** آیا جدول banners ساخته شده؟ (تا نبودِ مایگریشن به‌جای خطای ۵۰۰ روی صفحه اصلی، حالت خالی نشان دهد) */
    private static ?bool $ready = null;

    public static function tableReady(): bool
    {
        if (self::$ready !== null) {
            return self::$ready;
        }
        try {
            Database::fetchValue('SELECT 1 FROM banners LIMIT 1');
            return self::$ready = true;
        } catch (\PDOException $e) {
            return self::$ready = false;
        }
    }

    /**
     * اسلایدهای فعال صفحه اصلی، به ترتیب نمایش
     */
    public static function activeSlides(int $limit = 6): array
    {
        if (!self::tableReady()) {
            return [];
        }
        return Database::fetchAll(
            "SELECT * FROM banners
              WHERE is_active = 1 AND position = 'slider'
              ORDER BY sort_order, id
              LIMIT " . (int) $limit
        );
    }

    /**
     * همه اسلایدها برای پنل مدیریت (فعال و غیرفعال)
     */
    public static function allSlides(): array
    {
        if (!self::tableReady()) {
            return [];
        }
        return Database::fetchAll(
            "SELECT * FROM banners
              WHERE position = 'slider'
              ORDER BY sort_order, id"
        );
    }
}
