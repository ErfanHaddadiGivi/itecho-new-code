<?php
/**
 * پیشخوان حساب کاربری
 * @var array $user @var array $orders @var int $orderCount
 * @var int $addressCount @var int $wishlistCount @var array $toReview
 */
use App\Core\View;
?>
<div class="container">
  <h1 class="page-title">حساب کاربری</h1>

  <div class="account">
    <?php View::partial('site/account/_nav', ['active' => '']); ?>

    <div class="account__main">
      <section class="panel-block">
        <h2>خوش آمدید، <?= e($user['first_name']) ?></h2>
        <p class="muted" style="margin:0">
          <span class="ltr"><?= e($user['email']) ?></span>
          <?php if ($user['phone']): ?> · <span class="ltr"><?= e($user['phone']) ?></span><?php endif; ?>
        </p>
      </section>

      <div class="account-stats">
        <a class="account-stat" href="<?= e(url('account/orders')) ?>">
          <span class="account-stat__value"><?= e(fa_digits((string) $orderCount)) ?></span>
          <span class="account-stat__label">سفارش</span>
        </a>
        <a class="account-stat" href="<?= e(url('account/wishlist')) ?>">
          <span class="account-stat__value"><?= e(fa_digits((string) $wishlistCount)) ?></span>
          <span class="account-stat__label">علاقه‌مندی</span>
        </a>
        <a class="account-stat" href="<?= e(url('account/addresses')) ?>">
          <span class="account-stat__value"><?= e(fa_digits((string) $addressCount)) ?></span>
          <span class="account-stat__label">آدرس</span>
        </a>
      </div>

      <?php if ($toReview): ?>
        <section class="panel-block">
          <h2>نظرتان را ثبت کنید</h2>
          <p class="muted" style="margin-bottom:12px">
            این کالاها را خریده‌اید و هنوز نظری ثبت نکرده‌اید:
          </p>
          <ul class="to-review">
            <?php foreach (array_slice($toReview, 0, 4) as $item): ?>
              <li>
                <a href="<?= e(url('product/' . $item['slug'] . '#reviews')) ?>">
                  <?= e($item['name']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

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
          <?php if ($orderCount > count($orders)): ?>
            <p style="margin-top:14px">
              <a href="<?= e(url('account/orders')) ?>">مشاهده همه سفارش‌ها</a>
            </p>
          <?php endif; ?>
        <?php endif; ?>
      </section>
    </div>
  </div>
</div>
