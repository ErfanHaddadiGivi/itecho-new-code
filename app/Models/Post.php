<?php

namespace App\Models;

use App\Core\Database;

/**
 * مطالب و مقالات گیمینگ (بلاگ).
 */
class Post extends Model
{
    protected static string $table = 'posts';

    /**
     * مطالب منتشرشده برای نمایش عمومی (صفحه‌بندی‌شده)
     */
    public static function published(int $limit, int $offset): array
    {
        return Database::fetchAll(
            'SELECT id, title, slug, excerpt, cover_image, published_at, views
               FROM posts
              WHERE is_published = 1
              ORDER BY COALESCE(published_at, created_at) DESC
              LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset
        );
    }

    /**
     * شمارش مطالب منتشرشده — برای صفحه‌بندی
     */
    public static function publishedCount(): int
    {
        return (int) Database::fetchValue('SELECT COUNT(*) FROM posts WHERE is_published = 1');
    }

    /**
     * یک مطلب منتشرشده با نامک
     */
    public static function publishedBySlug(string $slug): ?array
    {
        return Database::fetch(
            'SELECT * FROM posts WHERE slug = ? AND is_published = 1 LIMIT 1',
            [$slug]
        );
    }

    /**
     * تازه‌ترین مطالب — برای نمایش در فوتر یا صفحه اصلی
     */
    public static function latest(int $limit = 3): array
    {
        return Database::fetchAll(
            'SELECT id, title, slug, cover_image, published_at
               FROM posts
              WHERE is_published = 1
              ORDER BY COALESCE(published_at, created_at) DESC
              LIMIT ' . (int) $limit
        );
    }

    /**
     * افزایش شمارنده بازدید
     */
    public static function incrementViews(int $id): void
    {
        Database::run('UPDATE posts SET views = views + 1 WHERE id = ?', [$id]);
    }

    /**
     * همه مطالب برای پنل مدیریت (منتشرشده و پیش‌نویس)
     */
    public static function allForAdmin(): array
    {
        return Database::fetchAll(
            'SELECT id, title, slug, cover_image, is_published, published_at, views, created_at
               FROM posts
              ORDER BY created_at DESC'
        );
    }
}
