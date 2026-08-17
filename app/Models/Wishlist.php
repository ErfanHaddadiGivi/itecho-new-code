<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

/**
 * لیست علاقه‌مندی‌ها.
 *
 * طبق PRD نیازمند حساب کاربری است؛ کاربر مهمان با کلیک روی قلب
 * به صفحه ورود هدایت می‌شود.
 */
class Wishlist
{
    /** شناسه محصولات علاقه‌مندی کاربر فعلی — یک بار خوانده و نگه داشته می‌شود */
    private static ?array $cachedIds = null;

    /**
     * افزودن یا حذف — خروجی می‌گوید الان محصول در لیست هست یا نه.
     */
    public static function toggle(int $userId, int $productId): bool
    {
        self::$cachedIds = null;

        if (self::has($userId, $productId)) {
            Database::delete('wishlist_items', 'user_id = ? AND product_id = ?', [$userId, $productId]);
            return false;
        }

        // INSERT IGNORE تا اگر دو کلیک همزمان رسید، خطای کلید تکراری ندهد
        Database::run(
            'INSERT IGNORE INTO wishlist_items (user_id, product_id) VALUES (?, ?)',
            [$userId, $productId]
        );

        return true;
    }

    public static function remove(int $userId, int $productId): void
    {
        self::$cachedIds = null;
        Database::delete('wishlist_items', 'user_id = ? AND product_id = ?', [$userId, $productId]);
    }

    public static function has(int $userId, int $productId): bool
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM wishlist_items WHERE user_id = ? AND product_id = ?',
            [$userId, $productId]
        ) > 0;
    }

    /**
     * محصولات علاقه‌مندی به همراه اطلاعات لازم برای نمایش کارت
     */
    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT p.id, p.name, p.slug, p.price, p.compare_at_price, p.main_image,
                    p.condition_type, p.stock, p.is_active, w.created_at AS added_at
               FROM wishlist_items w
               JOIN products p ON p.id = w.product_id
              WHERE w.user_id = ?
              ORDER BY w.created_at DESC',
            [$userId]
        );
    }

    public static function countFor(int $userId): int
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM wishlist_items WHERE user_id = ?', [$userId]
        );
    }

    /**
     * شناسه محصولات علاقه‌مندی کاربر واردشده.
     *
     * برای اینکه در لیست محصولات، قلبِ پرشده نمایش داده شود بدون اینکه
     * برای هر کارت یک کوئری جدا زده شود.
     */
    public static function currentUserIds(): array
    {
        if (self::$cachedIds !== null) {
            return self::$cachedIds;
        }

        $userId = Auth::id();

        if ($userId === null) {
            return self::$cachedIds = [];
        }

        $rows = Database::fetchAll(
            'SELECT product_id FROM wishlist_items WHERE user_id = ?', [$userId]
        );

        return self::$cachedIds = array_map('intval', array_column($rows, 'product_id'));
    }
}
