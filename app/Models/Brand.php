<?php

namespace App\Models;

use App\Core\Database;

/**
 * برندها
 */
class Brand extends Model
{
    protected static string $table = 'brands';

    /**
     * برندهای فعال — برای نوار فیلتر
     */
    public static function active(): array
    {
        return Database::fetchAll(
            'SELECT * FROM brands WHERE is_active = 1 ORDER BY sort_order, name'
        );
    }

    /**
     * لیست پنل مدیریت به همراه تعداد محصول هر برند
     */
    public static function withProductCount(): array
    {
        return Database::fetchAll(
            'SELECT b.*, (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id) AS product_count
               FROM brands b
              ORDER BY b.sort_order, b.name'
        );
    }

    public static function hasProducts(int $id): bool
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM products WHERE brand_id = ?', [$id]
        ) > 0;
    }
}
