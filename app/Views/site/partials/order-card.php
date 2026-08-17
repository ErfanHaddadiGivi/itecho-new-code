<?php
/**
 * یک ردیف سفارش در لیست سفارش‌های مشتری
 * @var array $order
 */
use App\Models\Order;
?>
<a class="order-row" href="<?= e(url('order/' . $order['id'])) ?>">
  <span class="order-row__num ltr"><?= e($order['order_number']) ?></span>
  <span class="order-row__date"><?= e(jdate($order['created_at'])) ?></span>
  <span class="badge badge--<?= e($order['status']) ?>">
    <?= e(Order::STATUS_LABELS[$order['status']] ?? $order['status']) ?>
  </span>
  <?php if ($order['shipping_state'] === 'awaiting_payment'): ?>
    <span class="badge badge--pending_payment">در انتظار پرداخت هزینه ارسال</span>
  <?php endif; ?>
  <span class="order-row__total"><?= e(money((int) $order['grand_total'])) ?></span>
</a>
