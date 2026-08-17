<?php

namespace App\Models;

use App\Core\Database;

/**
 * سفارش‌ها.
 *
 * گردش کار طبق PRD:
 *   در انتظار پرداخت → پرداخت‌شده → در حال آماده‌سازی
 *      → آماده تحویل حضوری / تحویل به پست → تحویل‌شده
 *   (در هر مرحله قبل از تحویل: لغو‌شده یا مرجوعی)
 */
class Order extends Model
{
    protected static string $table = 'orders';

    public const STATUS_LABELS = [
        'pending_payment'  => 'در انتظار پرداخت',
        'paid'             => 'پرداخت‌شده',
        'preparing'        => 'در حال آماده‌سازی',
        'ready_for_pickup' => 'آماده تحویل حضوری',
        'shipped'          => 'تحویل به پست',
        'delivered'        => 'تحویل‌شده',
        'canceled'         => 'لغو‌شده',
        'returned'         => 'مرجوعی',
    ];

    public const SHIPPING_LABELS = [
        'not_required'     => 'تحویل حضوری',
        'awaiting_cost'    => 'در انتظار محاسبه هزینه ارسال',
        'awaiting_payment' => 'در انتظار پرداخت هزینه ارسال',
        'paid'             => 'هزینه ارسال پرداخت شد',
    ];

    /**
     * وضعیت‌هایی که ادمین می‌تواند از هر وضعیت به آن‌ها برود.
     * این جدول جلوی جهش‌های بی‌معنی (مثلاً از «تحویل‌شده» به «در انتظار پرداخت») را می‌گیرد.
     */
    public const ALLOWED_TRANSITIONS = [
        'pending_payment'  => ['canceled'],
        'paid'             => ['preparing', 'canceled', 'returned'],
        'preparing'        => ['ready_for_pickup', 'shipped', 'canceled', 'returned'],
        'ready_for_pickup' => ['delivered', 'canceled', 'returned'],
        'shipped'          => ['delivered', 'returned'],
        'delivered'        => ['returned'],
        'canceled'         => [],
        'returned'         => [],
    ];

    // ==================================================================
    //  ساخت سفارش از روی سبد خرید
    // ==================================================================

    /**
     * ثبت سفارش جدید.
     *
     * همه کار داخل یک تراکنش انجام می‌شود: یا کل سفارش ثبت می‌شود یا هیچ‌چیز.
     *
     * @param  array $items    اقلام سبد (خروجی Cart::items)
     * @param  array $shipping اطلاعات گیرنده و روش ارسال
     * @return int   شناسه سفارش
     * @throws \RuntimeException اگر موجودی کافی نباشد
     */
    public static function createFromCart(int $userId, array $items, array $shipping, int $pickupFee): int
    {
        if ($items === []) {
            throw new \RuntimeException('سبد خرید شما خالی است.');
        }

        $isPickup   = $shipping['delivery_method'] === 'pickup';
        $itemsTotal = 0;

        // بررسی نهایی موجودی — ممکن است از زمان افزودن به سبد تغییر کرده باشد
        foreach ($items as $item) {
            if ((int) $item['is_active'] === 0
                || ($item['variant_id'] !== null && (int) $item['variant_active'] === 0)) {
                throw new \RuntimeException('کالای «' . $item['name'] . '» دیگر در دسترس نیست.');
            }

            if ((int) $item['quantity'] > (int) $item['available_stock']) {
                throw new \RuntimeException(
                    'موجودی کالای «' . $item['name'] . '» کافی نیست. لطفاً سبد خرید را به‌روز کنید.'
                );
            }

            $itemsTotal += (int) $item['unit_price'] * (int) $item['quantity'];
        }

        // تحویل حضوری هزینه ثابت دارد؛ ارسال پستی بعداً توسط ادمین وارد می‌شود
        $shippingCost  = $isPickup ? $pickupFee : null;
        $shippingState = $isPickup ? 'not_required' : 'awaiting_cost';
        $grandTotal    = $itemsTotal + (int) $shippingCost;

        Database::beginTransaction();

        try {
            $orderId = Database::insert('orders', [
                // شماره موقت و یکتا؛ بعد از گرفتن شناسه، شماره نهایی نوشته می‌شود
                'order_number'    => 'TMP-' . bin2hex(random_bytes(8)),
                'user_id'         => $userId,
                'status'          => 'pending_payment',
                'payment_status'  => 'unpaid',
                'delivery_method' => $isPickup ? 'pickup' : 'post',
                'shipping_state'  => $shippingState,
                'items_total'     => $itemsTotal,
                'shipping_cost'   => $shippingCost,
                'grand_total'     => $grandTotal,
                'receiver_name'   => $shipping['receiver_name'] ?? null,
                'receiver_phone'  => $shipping['receiver_phone'] ?? null,
                'province'        => $isPickup ? null : ($shipping['province'] ?? null),
                'city'            => $isPickup ? null : ($shipping['city'] ?? null),
                'postal_code'     => $isPickup ? null : ($shipping['postal_code'] ?? null),
                'address_line'    => $isPickup ? null : ($shipping['address_line'] ?? null),
                'customer_note'   => $shipping['customer_note'] ?? null,
            ]);

            Database::update('orders', [
                'order_number' => self::buildOrderNumber($orderId),
            ], 'id = ?', [$orderId]);

            // کپی اطلاعات کالاها در لحظه خرید تا تغییرات بعدی روی فاکتور اثر نگذارد
            foreach ($items as $item) {
                $unitPrice = (int) $item['unit_price'];
                $quantity  = (int) $item['quantity'];

                Database::insert('order_items', [
                    'order_id'      => $orderId,
                    'product_id'    => (int) $item['product_id'],
                    'variant_id'    => $item['variant_id'] !== null ? (int) $item['variant_id'] : null,
                    'product_name'  => $item['name'],
                    'variant_title' => $item['variant_title'],
                    'sku'           => null,
                    'unit_price'    => $unitPrice,
                    'quantity'      => $quantity,
                    'line_total'    => $unitPrice * $quantity,
                ]);
            }

            self::addHistory($orderId, null, 'pending_payment', 'ثبت سفارش توسط مشتری', $userId);

            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }

        return $orderId;
    }

    /**
     * شماره سفارش قابل نمایش: IT-۱۴۰۵۰۵۲۵-۰۰۰۱
     */
    private static function buildOrderNumber(int $orderId): string
    {
        [$jy, $jm, $jd] = gregorian_to_jalali((int) date('Y'), (int) date('n'), (int) date('j'));

        return sprintf('IT-%04d%02d%02d-%04d', $jy, $jm, $jd, $orderId);
    }

    // ==================================================================
    //  خواندن سفارش
    // ==================================================================

    public static function findByNumber(string $number): ?array
    {
        return Database::fetch('SELECT * FROM orders WHERE order_number = ? LIMIT 1', [$number]);
    }

    /**
     * سفارش با بررسی مالکیت — کاربر نباید بتواند سفارش دیگری را ببیند
     */
    public static function findForUser(int $orderId, int $userId): ?array
    {
        return Database::fetch(
            'SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1',
            [$orderId, $userId]
        );
    }

    public static function items(int $orderId): array
    {
        return Database::fetchAll(
            'SELECT oi.*, p.slug AS product_slug, p.main_image
               FROM order_items oi
               LEFT JOIN products p ON p.id = oi.product_id
              WHERE oi.order_id = ?
              ORDER BY oi.id',
            [$orderId]
        );
    }

    public static function history(int $orderId): array
    {
        return Database::fetchAll(
            "SELECT h.*, CONCAT(u.first_name, ' ', u.last_name) AS changed_by_name
               FROM order_status_history h
               LEFT JOIN users u ON u.id = h.changed_by
              WHERE h.order_id = ?
              ORDER BY h.created_at, h.id",
            [$orderId]
        );
    }

    public static function payments(int $orderId): array
    {
        return Database::fetchAll(
            'SELECT * FROM payments WHERE order_id = ? ORDER BY created_at, id',
            [$orderId]
        );
    }

    /**
     * سفارش‌های یک مشتری
     */
    public static function forUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC',
            [$userId]
        );
    }

    // ==================================================================
    //  تغییر وضعیت
    // ==================================================================

    public static function addHistory(
        int $orderId,
        ?string $from,
        string $to,
        string $note = '',
        ?int $byUserId = null
    ): void {
        Database::insert('order_status_history', [
            'order_id'    => $orderId,
            'from_status' => $from,
            'to_status'   => $to,
            'note'        => $note !== '' ? mb_substr($note, 0, 300) : null,
            'changed_by'  => $byUserId,
        ]);
    }

    /**
     * تغییر وضعیت سفارش با رعایت جدول انتقال‌های مجاز.
     *
     * @throws \RuntimeException اگر تغییر مجاز نباشد
     */
    public static function changeStatus(int $orderId, string $newStatus, ?int $byUserId, string $note = ''): void
    {
        $order = self::find($orderId);

        if ($order === null) {
            throw new \RuntimeException('سفارش پیدا نشد.');
        }

        $current = $order['status'];

        if ($current === $newStatus) {
            return;
        }

        if (!in_array($newStatus, self::ALLOWED_TRANSITIONS[$current] ?? [], true)) {
            throw new \RuntimeException(
                'تغییر وضعیت از «' . (self::STATUS_LABELS[$current] ?? $current) . '» به «'
                . (self::STATUS_LABELS[$newStatus] ?? $newStatus) . '» ممکن نیست.'
            );
        }

        Database::beginTransaction();

        try {
            $fields = ['status' => $newStatus];

            if ($newStatus === 'delivered') {
                $fields['delivered_at'] = date('Y-m-d H:i:s');
            }

            Database::update('orders', $fields, 'id = ?', [$orderId]);
            self::addHistory($orderId, $current, $newStatus, $note, $byUserId);

            // لغو یا مرجوعی → موجودی به انبار برمی‌گردد
            if ($newStatus === 'canceled' || $newStatus === 'returned') {
                self::restoreStock($orderId, $newStatus === 'canceled' ? 'order_canceled' : 'order_returned', $byUserId);
            }

            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    // ==================================================================
    //  موجودی انبار
    // ==================================================================

    /**
     * کسر موجودی پس از پرداخت موفق.
     *
     * ستون stock_deducted به‌صورت اتمی «قفل» می‌شود تا اگر کاربر صفحه
     * بازگشت از درگاه را دو بار باز کرد، موجودی دو بار کم نشود.
     *
     * @return bool آیا این فراخوانی واقعاً موجودی را کم کرد؟
     */
    public static function deductStock(int $orderId): bool
    {
        // اگر ردیفی تغییر نکرد یعنی قبلاً کسر شده بود
        $claimed = Database::run(
            'UPDATE orders SET stock_deducted = 1 WHERE id = ? AND stock_deducted = 0',
            [$orderId]
        )->rowCount();

        if ($claimed === 0) {
            return false;
        }

        foreach (self::items($orderId) as $item) {
            $quantity  = (int) $item['quantity'];
            $productId = $item['product_id'] !== null ? (int) $item['product_id'] : null;
            $variantId = $item['variant_id'] !== null ? (int) $item['variant_id'] : null;

            if ($variantId !== null) {
                Database::run(
                    'UPDATE product_variants SET stock = GREATEST(stock - ?, 0) WHERE id = ?',
                    [$quantity, $variantId]
                );
            } elseif ($productId !== null) {
                Database::run(
                    'UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?',
                    [$quantity, $productId]
                );
            }

            if ($productId !== null) {
                Database::run(
                    'UPDATE products SET sold_count = sold_count + ? WHERE id = ?',
                    [$quantity, $productId]
                );

                // محصول تنوع‌دار باید دوباره هم‌گام شود
                Product::syncFromVariants($productId);
            }

            Database::insert('inventory_logs', [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'order_id'   => $orderId,
                'change_qty' => -$quantity,
                'reason'     => 'order_paid',
                'note'       => 'کسر بابت پرداخت سفارش',
            ]);
        }

        return true;
    }

    /**
     * بازگشت موجودی هنگام لغو یا مرجوعی
     */
    public static function restoreStock(int $orderId, string $reason, ?int $byUserId = null): bool
    {
        $claimed = Database::run(
            'UPDATE orders SET stock_deducted = 0 WHERE id = ? AND stock_deducted = 1',
            [$orderId]
        )->rowCount();

        if ($claimed === 0) {
            // موجودی اصلاً کسر نشده بود (مثلاً سفارش پرداخت‌نشده لغو شد)
            return false;
        }

        foreach (self::items($orderId) as $item) {
            $quantity  = (int) $item['quantity'];
            $productId = $item['product_id'] !== null ? (int) $item['product_id'] : null;
            $variantId = $item['variant_id'] !== null ? (int) $item['variant_id'] : null;

            if ($variantId !== null) {
                Database::run(
                    'UPDATE product_variants SET stock = stock + ? WHERE id = ?',
                    [$quantity, $variantId]
                );
            } elseif ($productId !== null) {
                Database::run(
                    'UPDATE products SET stock = stock + ? WHERE id = ?',
                    [$quantity, $productId]
                );
            }

            if ($productId !== null) {
                Database::run(
                    'UPDATE products SET sold_count = GREATEST(sold_count - ?, 0) WHERE id = ?',
                    [$quantity, $productId]
                );

                Product::syncFromVariants($productId);
            }

            Database::insert('inventory_logs', [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'order_id'   => $orderId,
                'change_qty' => $quantity,
                'reason'     => $reason,
                'note'       => 'بازگشت موجودی به انبار',
                'created_by' => $byUserId,
            ]);
        }

        return true;
    }

    // ==================================================================
    //  پنل مدیریت
    // ==================================================================

    public static function adminList(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::adminWhere($filters);

        return Database::fetchAll(
            "SELECT o.*, CONCAT(u.first_name, ' ', u.last_name) AS customer_name, u.email AS customer_email
               FROM orders o
               JOIN users u ON u.id = o.user_id
              WHERE {$where}
              ORDER BY o.created_at DESC
              LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset,
            $params
        );
    }

    public static function adminCount(array $filters): int
    {
        [$where, $params] = self::adminWhere($filters);

        return (int) Database::fetchValue(
            "SELECT COUNT(*) FROM orders o JOIN users u ON u.id = o.user_id WHERE {$where}",
            $params
        );
    }

    private static function adminWhere(array $filters): array
    {
        $where  = ['1'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = "(o.order_number LIKE ? OR u.email LIKE ?
                         OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR o.receiver_phone LIKE ?)";
            $term    = '%' . $filters['q'] . '%';
            $params  = array_merge($params, [$term, $term, $term, $term]);
        }

        if (!empty($filters['status']) && isset(self::STATUS_LABELS[$filters['status']])) {
            $where[]  = 'o.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['shipping_state']) && isset(self::SHIPPING_LABELS[$filters['shipping_state']])) {
            $where[]  = 'o.shipping_state = ?';
            $params[] = $filters['shipping_state'];
        }

        return [implode(' AND ', $where), $params];
    }
}
