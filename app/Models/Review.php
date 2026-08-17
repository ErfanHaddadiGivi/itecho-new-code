<?php

namespace App\Models;

use App\Core\Database;

/**
 * نظرات و امتیاز محصولات.
 *
 * طبق PRD:
 *   • ثبت نظر فقط پس از خرید امکان‌پذیر است
 *   • نظرات پیش از نمایش عمومی نیاز به تایید ادمین دارند
 *   • نشان «خریدار تاییدشده» برای کسانی که واقعاً محصول را خریده‌اند
 */
class Review extends Model
{
    protected static string $table = 'reviews';

    public const STATUS_LABELS = [
        'pending'  => 'در انتظار تایید',
        'approved' => 'تاییدشده',
        'rejected' => 'رد شده',
    ];

    /**
     * آیا این کاربر این محصول را خریده است؟
     *
     * شرط: سفارشی وجود داشته باشد که پرداخت شده و لغو/مرجوع نشده باشد.
     *
     * @return int|null شناسه سفارش، یا null اگر خریدی نبوده
     */
    public static function purchasedOrderId(int $userId, int $productId): ?int
    {
        $row = Database::fetch(
            "SELECT o.id
               FROM orders o
               JOIN order_items oi ON oi.order_id = o.id
              WHERE o.user_id = ?
                AND oi.product_id = ?
                AND o.payment_status = 'paid'
                AND o.status NOT IN ('canceled', 'returned')
              ORDER BY o.created_at DESC
              LIMIT 1",
            [$userId, $productId]
        );

        return $row !== null ? (int) $row['id'] : null;
    }

    /**
     * نظر قبلی همین کاربر برای همین محصول (اگر ثبت کرده باشد)
     */
    public static function byUserForProduct(int $userId, int $productId): ?array
    {
        return Database::fetch(
            'SELECT * FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1',
            [$userId, $productId]
        );
    }

    /**
     * ثبت نظر جدید.
     *
     * @throws \RuntimeException با پیام فارسی قابل نمایش
     */
    public static function submit(int $userId, int $productId, int $rating, string $title, string $comment): void
    {
        if ($rating < 1 || $rating > 5) {
            throw new \RuntimeException('امتیاز باید بین ۱ تا ۵ ستاره باشد.');
        }

        $comment = trim($comment);

        if (mb_strlen($comment) < 10) {
            throw new \RuntimeException('متن نظر باید حداقل ۱۰ کاراکتر باشد.');
        }

        if (self::byUserForProduct($userId, $productId) !== null) {
            throw new \RuntimeException('شما قبلاً برای این محصول نظر ثبت کرده‌اید.');
        }

        $orderId = self::purchasedOrderId($userId, $productId);

        if ($orderId === null) {
            throw new \RuntimeException('فقط پس از خرید این محصول می‌توانید نظر ثبت کنید.');
        }

        Database::insert('reviews', [
            'product_id'        => $productId,
            'user_id'           => $userId,
            'order_id'          => $orderId,
            'rating'            => $rating,
            'title'             => $title !== '' ? mb_substr($title, 0, 150) : null,
            'comment'           => $comment,
            'is_verified_buyer' => 1,
            'status'            => 'pending',
        ]);
    }

    /**
     * نظرات تاییدشده یک محصول
     */
    public static function approvedForProduct(int $productId, int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT r.*, u.first_name, u.last_name
               FROM reviews r
               JOIN users u ON u.id = r.user_id
              WHERE r.product_id = ? AND r.status = 'approved'
              ORDER BY r.created_at DESC
              LIMIT " . (int) $limit,
            [$productId]
        );
    }

    /**
     * نظرهای یک کاربر (همه وضعیت‌ها — خودش باید ببیند در چه وضعیتی است)
     */
    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT r.*, p.name AS product_name, p.slug AS product_slug, p.main_image
               FROM reviews r
               JOIN products p ON p.id = r.product_id
              WHERE r.user_id = ?
              ORDER BY r.created_at DESC',
            [$userId]
        );
    }

    /**
     * محصولاتی که کاربر خریده ولی هنوز نظر نداده — برای دعوت به ثبت نظر
     */
    public static function awaitingReview(int $userId): array
    {
        return Database::fetchAll(
            "SELECT DISTINCT p.id, p.name, p.slug, p.main_image
               FROM orders o
               JOIN order_items oi ON oi.order_id = o.id
               JOIN products p     ON p.id = oi.product_id
              WHERE o.user_id = ?
                AND o.payment_status = 'paid'
                AND o.status NOT IN ('canceled', 'returned')
                AND NOT EXISTS (
                      SELECT 1 FROM reviews r
                       WHERE r.user_id = o.user_id AND r.product_id = p.id
                )
              ORDER BY o.created_at DESC
              LIMIT 12",
            [$userId]
        );
    }

    // ==================================================================
    //  پنل مدیریت
    // ==================================================================

    public static function adminList(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::adminWhere($filters);

        return Database::fetchAll(
            "SELECT r.*, p.name AS product_name, p.slug AS product_slug,
                    CONCAT(u.first_name, ' ', u.last_name) AS user_name, u.email AS user_email
               FROM reviews r
               JOIN products p ON p.id = r.product_id
               JOIN users u    ON u.id = r.user_id
              WHERE {$where}
              ORDER BY r.created_at DESC
              LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset,
            $params
        );
    }

    public static function adminCount(array $filters): int
    {
        [$where, $params] = self::adminWhere($filters);

        return (int) Database::fetchValue(
            "SELECT COUNT(*) FROM reviews r
               JOIN products p ON p.id = r.product_id
               JOIN users u    ON u.id = r.user_id
              WHERE {$where}",
            $params
        );
    }

    private static function adminWhere(array $filters): array
    {
        $where  = ['1'];
        $params = [];

        if (!empty($filters['status']) && isset(self::STATUS_LABELS[$filters['status']])) {
            $where[]  = 'r.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['q'])) {
            $where[] = "(p.name LIKE ? OR r.comment LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
            $term    = '%' . $filters['q'] . '%';
            $params  = array_merge($params, [$term, $term, $term]);
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * تغییر وضعیت نظر توسط ادمین + به‌روزرسانی امتیاز محصول
     */
    public static function setStatus(int $reviewId, string $status, ?string $adminReply = null): void
    {
        if (!isset(self::STATUS_LABELS[$status])) {
            throw new \RuntimeException('وضعیت نامعتبر است.');
        }

        $review = self::find($reviewId);

        if ($review === null) {
            throw new \RuntimeException('نظر پیدا نشد.');
        }

        $fields = ['status' => $status];

        if ($status === 'approved') {
            $fields['approved_at'] = date('Y-m-d H:i:s');
        }

        if ($adminReply !== null) {
            $fields['admin_reply'] = $adminReply !== '' ? mb_substr($adminReply, 0, 1000) : null;
        }

        Database::update('reviews', $fields, 'id = ?', [$reviewId]);

        self::recalcProductRating((int) $review['product_id']);
    }

    public static function deleteAndRecalc(int $reviewId): void
    {
        $review = self::find($reviewId);

        if ($review === null) {
            return;
        }

        self::deleteById($reviewId);
        self::recalcProductRating((int) $review['product_id']);
    }

    /**
     * محاسبه دوباره میانگین امتیاز محصول — فقط از نظرات تاییدشده
     */
    public static function recalcProductRating(int $productId): void
    {
        $row = Database::fetch(
            "SELECT COUNT(*) AS total, AVG(rating) AS average
               FROM reviews
              WHERE product_id = ? AND status = 'approved'",
            [$productId]
        );

        $count = (int) ($row['total'] ?? 0);

        Database::update('products', [
            'rating_count' => $count,
            'rating_avg'   => $count > 0 ? round((float) $row['average'], 2) : 0,
        ], 'id = ?', [$productId]);
    }
}
