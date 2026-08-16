<?php
/**
 * صفحه نتایج جستجو
 *
 * @var string $term
 * @var array  $products
 * @var int    $total
 * @var App\Core\Paginator $paginator
 * @var string $sort
 */

use App\Core\View;
use App\Models\Product;
?>

<div class="container">
    <div class="catalog__head">
        <div>
            <h1 class="page-title">
                <?php if ($term !== ''): ?>
                    نتایج جستجو برای «<?= e($term) ?>»
                <?php else: ?>
                    جستجو
                <?php endif; ?>
            </h1>
            <?php if ($term !== ''): ?>
                <span class="catalog__count"><?= e(fa_digits((string) $total)) ?> کالا پیدا شد</span>
            <?php endif; ?>
        </div>

        <?php if ($products): ?>
            <div class="catalog__tools">
                <label class="sort">
                    <span>مرتب‌سازی:</span>
                    <select name="sort" form="search-sort" onchange="this.form.submit()">
                        <?php foreach (Product::SORT_LABELS as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
        <?php endif; ?>
    </div>

    <form id="search-sort" method="get" action="<?= e(url('search')) ?>" hidden>
        <input type="hidden" name="q" value="<?= e($term) ?>">
    </form>

    <?php if ($term === ''): ?>
        <div class="notice">
            <strong>عبارتی برای جستجو وارد کنید.</strong>
            <span>می‌توانید نام محصول، برند یا کد کالا را جستجو کنید.</span>
        </div>
    <?php elseif (!$products): ?>
        <div class="notice">
            <strong>کالایی پیدا نشد.</strong>
            <span>املای عبارت را بررسی کنید یا با کلمه کوتاه‌تری جستجو کنید.</span>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <?php View::partial('site/partials/product-card', ['product' => $product]); ?>
            <?php endforeach; ?>
        </div>

        <?php View::partial('site/partials/pagination', ['paginator' => $paginator]); ?>
    <?php endif; ?>
</div>
