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

    /**
     * اسلایدهای فعال صفحه اصلی، به ترتیب نمایش
     */
    public static function activeSlides(int $limit = 6): array
    {
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
        return Database::fetchAll(
            "SELECT * FROM banners
              WHERE position = 'slider'
              ORDER BY sort_order, id"
        );
    }
}
