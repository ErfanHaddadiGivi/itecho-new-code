<?php
/**
 * داشبورد پنل مدیریت
 *
 * @var array $stats
 * @var array $todo
 * @var int   $revenue
 * @var array $recentOrders
 * @var array $lowStock
 */

/** برچسب فارسی وضعیت سفارش */
$statusLabels = [
    'pending_payment'  => 'در انتظار پرداخت',
    'paid'             => 'پرداخت‌شده',
    'preparing'        => 'در حال آماده‌سازی',
    'ready_for_pickup' => 'آماده تحویل حضوری',
    'shipped'          => 'تحویل به پست',
    'delivered'        => 'تحویل‌شده',
    'canceled'         => 'لغو‌شده',
    'returned'         => 'مرجوعی',
];
?>

<!-- کارهایی که منتظر رسیدگی هستند -->
<?php
$todoItems = [
    ['count' => $todo['new_orders'],             'label' => 'سفارش پرداخت‌شده در انتظار آماده‌سازی',
     'url' => 'admin/orders?status=paid'],
    ['count' => $todo['awaiting_shipping_cost'], 'label' => 'سفارش در انتظار محاسبه هزینه ارسال',
     'url' => 'admin/orders?shipping_state=awaiting_cost'],
    ['count' => $todo['pending_reviews'],        'label' => 'نظر در انتظار تایید',
     'url' => 'admin/reviews?status=pending'],
    ['count' => $todo['unread_messages'],        'label' => 'پیام خوانده‌نشده'],
];
$pending = array_filter($todoItems, static fn ($item) => $item['count'] > 0);
?>

<?php if ($pending): ?>
    <section class="panel panel--todo">
        <h2 class="panel__title">نیازمند رسیدگی</h2>
        <ul class="todo-list">
            <?php foreach ($pending as $item): ?>
                <li>
                    <span class="todo-list__count"><?= e(fa_digits((string) $item['count'])) ?></span>
                    <?php if (!empty($item['url'])): ?>
                        <a href="<?= e(url($item['url'])) ?>"><?= e($item['label']) ?></a>
                    <?php else: ?>
                        <span><?= e($item['label']) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<!-- آمار کلی -->
<section class="stat-grid">
    <div class="stat-card">
        <span class="stat-card__value"><?= e(money($revenue, false)) ?></span>
        <span class="stat-card__label">مجموع فروش پرداخت‌شده (تومان)</span>
    </div>
    <div class="stat-card">
        <span class="stat-card__value"><?= e(fa_digits((string) $stats['orders'])) ?></span>
        <span class="stat-card__label">سفارش</span>
    </div>
    <div class="stat-card">
        <span class="stat-card__value"><?= e(fa_digits((string) $stats['products'])) ?></span>
        <span class="stat-card__label">محصول (<?= e(fa_digits((string) $stats['active_products'])) ?> فعال)</span>
    </div>
    <div class="stat-card">
        <span class="stat-card__value"><?= e(fa_digits((string) $stats['customers'])) ?></span>
        <span class="stat-card__label">مشتری</span>
    </div>
    <div class="stat-card">
        <span class="stat-card__value"><?= e(fa_digits((string) $stats['categories'])) ?></span>
        <span class="stat-card__label">دسته‌بندی</span>
    </div>
    <div class="stat-card">
        <span class="stat-card__value"><?= e(fa_digits((string) $stats['brands'])) ?></span>
        <span class="stat-card__label">برند</span>
    </div>
</section>

<div class="two-col">
    <!-- آخرین سفارش‌ها -->
    <section class="panel">
        <h2 class="panel__title">آخرین سفارش‌ها</h2>

        <?php if (!$recentOrders): ?>
            <p class="empty">هنوز سفارشی ثبت نشده است.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th>شماره</th>
                        <th>مشتری</th>
                        <th>مبلغ</th>
                        <th>وضعیت</th>
                        <th>تاریخ</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td class="ltr"><?= e($order['order_number']) ?></td>
                            <td><?= e($order['customer']) ?></td>
                            <td><?= e(money((int) $order['grand_total'], false)) ?></td>
                            <td>
                                <span class="badge badge--<?= e($order['status']) ?>">
                                    <?= e($statusLabels[$order['status']] ?? $order['status']) ?>
                                </span>
                            </td>
                            <td><?= e(jdate($order['created_at'], 'short')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- موجودی رو به اتمام -->
    <section class="panel">
        <h2 class="panel__title">موجودی رو به اتمام</h2>

        <?php if (!$lowStock): ?>
            <p class="empty">موجودی همه محصولات فعال مناسب است.</p>
        <?php else: ?>
            <ul class="simple-list">
                <?php foreach ($lowStock as $product): ?>
                    <li>
                        <span><?= e($product['name']) ?></span>
                        <span class="stock-pill<?= (int) $product['stock'] === 0 ? ' stock-pill--zero' : '' ?>">
                            <?= e(fa_digits((string) (int) $product['stock'])) ?> عدد
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
