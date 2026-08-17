<?php
/** لیست کامل سفارش‌های مشتری  @var array $orders */
use App\Core\View;
?>
<div class="container">
  <h1 class="page-title">سفارش‌های من</h1>

  <?php if (!$orders): ?>
    <div class="notice">
      <strong>هنوز سفارشی ثبت نکرده‌اید.</strong>
      <p style="margin-top:14px"><a class="btn btn--primary" href="<?= e(url('')) ?>">مشاهده محصولات</a></p>
    </div>
  <?php else: ?>
    <div class="order-list">
      <?php foreach ($orders as $order): ?>
        <?php View::partial('site/partials/order-card', ['order' => $order]); ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
