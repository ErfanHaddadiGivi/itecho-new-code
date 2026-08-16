<?php

namespace App\Models;

use App\Core\Database;

/**
 * کلاس پایه مدل‌ها — کارهای تکراری دیتابیس یک بار اینجا نوشته شده‌اند.
 *
 * هر مدل فقط کافی است نام جدولش را مشخص کند:
 *      protected static string $table = 'brands';
 */
abstract class Model
{
    protected static string $table = '';

    public static function table(): string
    {
        return static::$table;
    }

    /**
     * پیدا کردن یک ردیف با شناسه
     */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT * FROM `' . static::$table . '` WHERE id = ? LIMIT 1',
            [$id]
        );
    }

    /**
     * پیدا کردن یک ردیف با نامک (slug)
     */
    public static function findBySlug(string $slug): ?array
    {
        return Database::fetch(
            'SELECT * FROM `' . static::$table . '` WHERE slug = ? LIMIT 1',
            [$slug]
        );
    }

    /**
     * همه ردیف‌ها
     */
    public static function all(string $orderBy = 'id DESC'): array
    {
        return Database::fetchAll(
            'SELECT * FROM `' . static::$table . '` ORDER BY ' . $orderBy
        );
    }

    public static function create(array $data): int
    {
        return Database::insert(static::$table, $data);
    }

    public static function updateById(int $id, array $data): int
    {
        return Database::update(static::$table, $data, 'id = ?', [$id]);
    }

    public static function deleteById(int $id): int
    {
        return Database::delete(static::$table, 'id = ?', [$id]);
    }

    /**
     * شمارش ردیف‌ها با شرط دلخواه
     */
    public static function count(string $where = '1', array $params = []): int
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM `' . static::$table . '` WHERE ' . $where,
            $params
        );
    }

    /**
     * بررسی یکتا بودن نامک — هنگام ویرایش، خودِ ردیف نادیده گرفته می‌شود
     */
    public static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM `' . static::$table . '` WHERE slug = ?';
        $params = [$slug];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        return (int) Database::fetchValue($sql, $params) > 0;
    }

    /**
     * ساخت نامک یکتا — اگر تکراری بود عدد به آن اضافه می‌شود
     */
    public static function uniqueSlug(string $desired, ?int $exceptId = null): string
    {
        $slug = slugify($desired);
        $base = $slug;
        $i    = 2;

        while (static::slugExists($slug, $exceptId)) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
