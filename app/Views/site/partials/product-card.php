<?php
/**
 * کارت محصول — در لیست‌ها و صفحه اصلی استفاده می‌شود.
 *
 * @var array $product
 */

use App\Core\Csrf;
use App\Models\Wishlist;

$hasDiscount = !empty($product['compare_at_price'])
    && (int) $product['compare_at_price'] > (int) $product['price'];

$discountPercent = $hasDiscount
    ? (int) round((1 - $product['price'] / $product['compare_at_price']) * 100)
    : 0;

$inWishlist = in_array((int) $product['id'], Wishlist::currentUserIds(), true);
?>
<div class="product-card-wrap">
    <a class="product-card" href="<?= e(url('product/' . $product['slug'])) ?>">
        <div class="product-card__media">
            <?php if (!empty($product['main_image'])): ?>
                <img src="<?= e(url('uploads/products/' . $product['main_image'])) ?>"
                     alt="<?= e($product['name']) ?>" loading="lazy">
            <?php else: ?>
                <div class="product-card__placeholder" aria-hidden="true">بدون تصویر</div>
            <?php endif; ?>

            <?php if ($hasDiscount): ?>
                <span class="product-card__off"><?= e(fa_digits((string) $discountPercent)) ?>٪</span>
            <?php endif; ?>

            <?php if (($product['condition_type'] ?? 'new') === 'used'): ?>
                <span class="product-card__used">کارکرده</span>
            <?php endif; ?>
        </div>

        <div class="product-card__body">
            <h3 class="product-card__name"><?= e($product['name']) ?></h3>

            <div class="product-card__price">
                <?php if ($hasDiscount): ?>
                    <span class="product-card__old"><?= e(money((int) $product['compare_at_price'], false)) ?></span>
                <?php endif; ?>
                <span class="product-card__now"><?= e(money((int) $product['price'])) ?></span>
            </div>
        </div>
    </a>

    <!-- دکمه علاقه‌مندی بیرون از تگ a است تا کلیک روی آن، کاربر را به صفحه محصول نبرد -->
    <form method="post" action="<?= e(url('wishlist/toggle')) ?>" class="wish-form">
        <?= Csrf::field() ?>
        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <button type="submit" class="wish-btn<?= $inWishlist ? ' is-on' : '' ?>"
                aria-pressed="<?= $inWishlist ? 'true' : 'false' ?>"
                aria-label="<?= $inWishlist ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها' ?>">
            <svg viewBox="0 0 24 24" width="19" height="19" aria-hidden="true">
                <path d="M12 20.5S3.5 15 3.5 9.2A4.7 4.7 0 0 1 12 6.6a4.7 4.7 0 0 1 8.5 2.6c0 5.8-8.5 11.3-8.5 11.3z"
                      fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
            </svg>
        </button>
    </form>
</div>
