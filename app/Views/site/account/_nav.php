<?php
/**
 * منوی کناری بخش حساب کاربری
 * @var string $active کلید صفحه فعال
 */
use App\Core\Csrf;

$items = [
    ''          => ['label' => 'پیشخوان',        'url' => 'account'],
    'orders'    => ['label' => 'سفارش‌های من',   'url' => 'account/orders'],
    'wishlist'  => ['label' => 'علاقه‌مندی‌ها',  'url' => 'account/wishlist'],
    'reviews'   => ['label' => 'نظرهای من',      'url' => 'account/reviews'],
    'addresses' => ['label' => 'دفترچه آدرس',    'url' => 'account/addresses'],
    'profile'   => ['label' => 'اطلاعات حساب',   'url' => 'account/profile'],
];
?>
<nav class="account-nav" aria-label="منوی حساب کاربری">
  <?php foreach ($items as $key => $item): ?>
    <a class="account-nav__link<?= ($active ?? '') === $key ? ' is-active' : '' ?>"
       href="<?= e(url($item['url'])) ?>"><?= e($item['label']) ?></a>
  <?php endforeach; ?>

  <form method="post" action="<?= e(url('logout')) ?>" class="account-nav__logout">
    <?= Csrf::field() ?>
    <button type="submit" class="account-nav__link account-nav__link--exit">خروج از حساب</button>
  </form>
</nav>
