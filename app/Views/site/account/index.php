<?php
/** صفحه اصلی حساب کاربری  @var array $user @var array $orders @var int $total */
use App\Core\Csrf;
use App\Core\View;
?>
<div class="container">
  <div class="account-head">
    <div>
      <h1 class="page-title">حساب کاربری</h1>
      <p class="muted"><?= e(trim($user['first_name'] . ' ' . $user['last_name'])) ?>
        · <span class="ltr"><?= e($user['email']) ?></span></p>
    </div>
    <form method="post" action="<?= e(url('logout')) ?>">
      <?= Csrf::field() ?>
      <button class="btn btn--ghost" type="submit">خروج از حساب</button>
    </form>
  </div>

  <section class="panel-block">
    <h2>آخرین سفارش‌ها</h2>

    <?php if (!$orders): ?>
      <p class="empty">هنوز سفارشی ثبت نکرده‌اید.</p>
    <?php else: ?>
      <div class="order-list">
        <?php foreach ($orders as $order): ?>
          <?php View::partial('site/partials/order-card', ['order' => $order]); ?>
        <?php endforeach; ?>
      </div>

      <?php if ($total > count($orders)): ?>
        <p style="margin-top:14px">
          <a href="<?= e(url('account/orders')) ?>">مشاهده همه سفارش‌ها
            (<?= e(fa_digits((string) $total)) ?>)</a>
        </p>
      <?php endif; ?>
    <?php endif; ?>
  </section>

  <section class="panel-block">
    <h2>به‌زودی</h2>
    <p class="muted" style="margin:0">
      دفترچه آدرس، علاقه‌مندی‌ها و ثبت نظر در مرحله بعد اضافه می‌شوند.
    </p>
  </section>
</div>
