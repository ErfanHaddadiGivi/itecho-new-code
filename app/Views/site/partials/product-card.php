<?php
/**
 * کارت محصول — در لیست‌ها و صفحه اصلی استفاده می‌شود.
 *
 * @var array $product
 */

$hasDiscount = !empty($product['compare_at_price'])
    && (int) $product['compare_at_price'] > (int) $product['price'];

$discountPercent = $hasDiscount
    ? (int) round((1 - $product['price'] / $product['compare_at_price']) * 100)
    : 0;
?>
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
