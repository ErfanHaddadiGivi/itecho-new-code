<?php

namespace App\Models;

use App\Core\Database;

/**
 * دسته‌بندی دو سطحی.
 *
 * همین جدول منبع مگا منو هم هست:
 *   سطح ۱ (parent_id = NULL) → ستون‌های منو
 *   سطح ۲                    → آیتم‌های داخل هر ستون
 */
class Category extends Model
{
    protected static string $table = 'categories';

    /**
     * دسته‌های اصلی (سطح ۱)
     */
    public static function mainCategories(bool $onlyActive = true): array
    {
        $where = 'parent_id IS NULL' . ($onlyActive ? ' AND is_active = 1' : '');

        return Database::fetchAll(
            "SELECT * FROM categories WHERE {$where} ORDER BY sort_order, name"
        );
    }

    /**
     * زیر‌دسته‌های یک دسته
     */
    public static function children(int $parentId, bool $onlyActive = true): array
    {
        $where = 'parent_id = ?' . ($onlyActive ? ' AND is_active = 1' : '');

        return Database::fetchAll(
            "SELECT * FROM categories WHERE {$where} ORDER BY sort_order, name",
            [$parentId]
        );
    }

    /**
     * ساختار کامل مگا منو با یک کوئری.
     *
     * خروجی: آرایه‌ای از دسته‌های اصلی که هرکدام کلید 'children' دارند.
     */
    public static function menuTree(): array
    {
        $rows = Database::fetchAll(
            'SELECT id, parent_id, name, slug, icon
               FROM categories
              WHERE is_active = 1 AND show_in_menu = 1
              ORDER BY sort_order, name'
        );

        $main     = [];
        $children = [];

        foreach ($rows as $row) {
            if ($row['parent_id'] === null) {
                $row['children'] = [];
                $main[$row['id']] = $row;
            } else {
                $children[$row['parent_id']][] = $row;
            }
        }

        foreach ($children as $parentId => $items) {
            if (isset($main[$parentId])) {
                $main[$parentId]['children'] = $items;
            }
        }

        return array_values($main);
    }

    /**
     * لیست کامل برای پنل مدیریت: دسته‌های اصلی به همراه زیر‌دسته‌هایشان،
     * مرتب‌شده به شکل درختی.
     */
    public static function adminTree(): array
    {
        $rows = Database::fetchAll(
            'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
               FROM categories c
              ORDER BY c.sort_order, c.name'
        );

        $main     = [];
        $children = [];

        foreach ($rows as $row) {
            if ($row['parent_id'] === null) {
                $main[$row['id']] = $row;
            } else {
                $children[$row['parent_id']][] = $row;
            }
        }

        $ordered = [];
        foreach ($main as $id => $category) {
            $category['depth'] = 0;
            $ordered[] = $category;

            foreach ($children[$id] ?? [] as $child) {
                $child['depth'] = 1;
                $ordered[] = $child;
            }
        }

        return $ordered;
    }

    /**
     * گزینه‌های انتخاب «دسته والد» در فرم.
     * فقط دسته‌های اصلی برگردانده می‌شوند چون ساختار دو سطحی است.
     */
    public static function parentOptions(?int $exceptId = null): array
    {
        $sql    = 'SELECT id, name FROM categories WHERE parent_id IS NULL';
        $params = [];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        return Database::fetchAll($sql . ' ORDER BY sort_order, name', $params);
    }

    /**
     * آیا این دسته زیر‌دسته دارد؟ (برای جلوگیری از حذف اشتباهی)
     */
    public static function hasChildren(int $id): bool
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM categories WHERE parent_id = ?', [$id]
        ) > 0;
    }

    /**
     * آیا این دسته محصول دارد؟
     */
    public static function hasProducts(int $id): bool
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM products WHERE category_id = ?', [$id]
        ) > 0;
    }

    /**
     * شناسه یک دسته به همراه شناسه زیر‌دسته‌هایش.
     * برای صفحه دسته‌بندی لازم است: با کلیک روی «موبایل»
     * محصولات «گوشی موبایل» و «تبلت» هم باید دیده شوند.
     */
    public static function idWithChildren(int $id): array
    {
        $ids = [$id];

        $children = Database::fetchAll('SELECT id FROM categories WHERE parent_id = ?', [$id]);
        foreach ($children as $child) {
            $ids[] = (int) $child['id'];
        }

        return $ids;
    }
}
