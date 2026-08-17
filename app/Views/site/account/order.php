<?php
/**
 * جزئیات سفارش برای مشتری
 *
 * @var array $order
 * @var array $items
 * @var array $history
 * @var array $payments
 */

use App\Models\Order;

$isPost = $order['delivery_method'] === 'post';

// اگر پرداخت تکمیلی هزینه ارسال منتظر است، لینکش را پیدا کن
$shippingPayment = null;
foreach ($payments as $payment) {
    if ($payment['purpose'] === 'shipping' && $payment['status'] !== 'paid') {
        $shippingPayment = $payment;
    }
}
?>

<div class="container">
    <nav class="breadcrumb" aria-label="مسیر صفحه">
        <a href="<?= e(url('account')) ?>">حساب کاربری</a>
        <span aria-hidden="true">›</span>
        <a href="<?= e(url('account/orders')) ?>">سفارش‌های من</a>
        <span aria-hidden="true">›</span>
        <span class="ltr"><?= e($order['order_number']) ?></span>
    </nav>

    <div class="order-head">
        <div>
            <h1 class="page-title">سفارش <span class="ltr"><?= e($order['order_number']) ?></span></h1>
            <p class="muted"><?= e(jdate($order['created_at'], 'datetime')) ?></p>
        </div>
        <span class="badge badge--<?= e($order['status']) ?> badge--lg">
            <?= e(Order::STATUS_LABELS[$order['status']] ?? $order['status']) ?>
        </span>
    </div>

    <!-- اگر پرداخت هزینه ارسال منتظر است، بالای صفحه یادآوری می‌شود -->
    <?php if ($shippingPayment !== null && $order['shipping_state'] === 'awaiting_payment'): ?>
        <div class="alert alert--info">
            هزینه ارسال این سفارش <strong><?= e(money((int) $shippingPayment['amount'])) ?></strong> محاسبه شد.
            <a href="<?= e(url('pay/' . $shippingPayment['pay_token'])) ?>">پرداخت هزینه ارسال</a>
        </div>
    <?php elseif ($order['shipping_state'] === 'awaiting_cost'): ?>
        <div class="alert alert--info">
            سفارش شما در انتظار محاسبه هزینه ارسال است. پس از بسته‌بندی،
            لینک پرداخت هزینه ارسال به ایمیل شما فرستاده می‌شود.
        </div>
    <?php endif; ?>

    <div class="order-detail">
        <div>
            <!-- کالاها -->
            <section class="panel-block">
                <h2>کالاهای سفارش</h2>

                <div class="order-items">
                    <?php foreach ($items as $item): ?>
                        <div class="order-item">
                            <div class="order-item__media">
                                <?php if ($item['main_image']): ?>
                                    <img src="<?= e(url('uploads/products/' . $item['main_image'])) ?>" alt="">
                                <?php else: ?>
                                    <span class="product-card__placeholder">بدون تصویر</span>
                                <?php endif; ?>
                            </div>

                            <div class="order-item__body">
                                <?php if ($item['product_slug']): ?>
                                    <a href="<?= e(url('product/' . $item['product_slug'])) ?>">
                                        <?= e($item['product_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span><?= e($item['product_name']) ?></span>
                                <?php endif; ?>

                                <?php if ($item['variant_title']): ?>
                                    <span class="cart-item__variant"><?= e($item['variant_title']) ?></span>
                                <?php endif; ?>

                                <span class="muted">
                                    <?= e(fa_digits((string) (int) $item['quantity'])) ?> عدد ×
                                    <?= e(money((int) $item['unit_price'], false)) ?>
                                </span>
                            </div>

                            <div class="order-item__total">
                                <?= e(money((int) $item['line_total'], false)) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- پیگیری وضعیت -->
            <section class="panel-block">
                <h2>پیگیری سفارش</h2>

                <ol class="timeline">
                    <?php foreach ($history as $row): ?>
                        <li>
                            <span class="timeline__dot" aria-hidden="true"></span>
                            <div>
                                <strong><?= e(Order::STATUS_LABELS[$row['to_status']] ?? $row['to_status']) ?></strong>
                                <?php if ($row['note']): ?>
                                    <span class="timeline__note"><?= e($row['note']) ?></span>
                                <?php endif; ?>
                                <span class="timeline__date"><?= e(jdate($row['created_at'], 'datetime')) ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>
        </div>

        <!-- اطلاعات جانبی -->
        <aside>
            <section class="panel-block">
                <h2>اطلاعات تحویل</h2>

                <p class="muted" style="margin-bottom:10px">
                    <?= $isPost ? 'ارسال با پست' : 'دریافت حضوری از فروشگاه' ?>
                </p>

                <?php if ($order['receiver_name']): ?>
                    <p style="margin:0 0 4px"><?= e($order['receiver_name']) ?></p>
                <?php endif; ?>

                <?php if ($order['receiver_phone']): ?>
                    <p class="ltr muted" style="margin:0 0 8px"><?= e($order['receiver_phone']) ?></p>
                <?php endif; ?>

                <?php if ($isPost && $order['address_line']): ?>
                    <p class="muted" style="margin:0">
                        <?= e($order['province']) ?>، <?= e($order['city']) ?><br>
                        <?= e($order['address_line']) ?>
                        <?php if ($order['postal_code']): ?>
                            <br>کد پستی: <span class="ltr"><?= e($order['postal_code']) ?></span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php if ($order['tracking_code']): ?>
                    <p style="margin:12px 0 0">
                        کد رهگیری پستی:
                        <strong class="ltr"><?= e($order['tracking_code']) ?></strong>
                    </p>
                <?php endif; ?>
            </section>

            <section class="panel-block">
                <h2>صورتحساب</h2>

                <div class="cart-summary__row">
                    <span>جمع کالاها</span>
                    <span><?= e(money((int) $order['items_total'])) ?></span>
                </div>

                <div class="cart-summary__row">
                    <span>هزینه ارسال</span>
                    <span>
                        <?php if ($order['shipping_cost'] === null): ?>
                            <span class="muted">هنوز محاسبه نشده</span>
                        <?php elseif ((int) $order['shipping_cost'] === 0): ?>
                            رایگان
                        <?php else: ?>
                            <?= e(money((int) $order['shipping_cost'])) ?>
                        <?php endif; ?>
                    </span>
                </div>

                <div class="cart-summary__row cart-summary__row--total">
                    <span>مبلغ کل</span>
                    <span><?= e(money((int) $order['grand_total'])) ?></span>
                </div>

                <?php if ($payments): ?>
                    <h3 class="mini-title">تراکنش‌ها</h3>
                    <ul class="payment-list">
                        <?php foreach ($payments as $payment): ?>
                            <li>
                                <span><?= $payment['purpose'] === 'shipping' ? 'هزینه ارسال' : 'مبلغ کالاها' ?></span>
                                <span><?= e(money((int) $payment['amount'], false)) ?></span>
                                <span class="badge badge--<?= $payment['status'] === 'paid' ? 'ok' : 'off' ?>">
                                    <?= $payment['status'] === 'paid' ? 'پرداخت‌شده' : 'پرداخت‌نشده' ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($order['payment_status'] !== 'paid' && $order['status'] === 'pending_payment'): ?>
                    <a class="btn btn--primary btn--block" style="margin-top:14px"
                       href="<?= e(url('payment/start/' . $order['id'])) ?>">پرداخت سفارش</a>
                <?php endif; ?>
            </section>
        </aside>
    </div>
</div>
