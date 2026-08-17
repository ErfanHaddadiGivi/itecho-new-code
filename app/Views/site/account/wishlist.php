<?php
/** لیست علاقه‌مندی‌ها  @var array $products */
use App\Core\Csrf;
use App\Core\View;
?>
<div class="container">
  <h1 class="page-title">علاقه‌مندی‌ها</h1>
  <div class="account">
    <?php View::partial('site/account/_nav', ['active' => 'wishlist']); ?>

    <div class="account__main">
      <?php if (!$products): ?>
        <div class="notice">
          <strong>لیست علاقه‌مندی‌های شما خالی است.</strong>
          <span>با کلیک روی قلبِ کنار هر محصول، آن را به این لیست اضافه کنید.</span>
          <p style="margin-top:14px"><a class="btn btn--primary" href="<?= e(url('')) ?>">مشاهده محصولات</a></p>
        </div>
      <?php else: ?>
        <div class="wishlist-grid">
          <?php foreach ($products as $product): ?>
            <div class="wishlist-item">
              <?php View::partial('site/partials/product-card', ['product' => $product]); ?>

              <?php if ((int) $product['is_active'] === 0): ?>
                <p class="wishlist-item__note">این محصول در حال حاضر در دسترس نیست.</p>
              <?php elseif ((int) $product['stock'] <= 0): ?>
                <p class="wishlist-item__note">موجودی این محصول تمام شده است.</p>
              <?php endif; ?>

              <form method="post" action="<?= e(url('wishlist/remove')) ?>">
                <?= Csrf::field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <button class="btn btn--ghost btn--sm btn--block" type="submit">حذف از علاقه‌مندی‌ها</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
