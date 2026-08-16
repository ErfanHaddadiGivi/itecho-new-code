<?php
/**
 * صفحه دسته‌بندی به همراه نوار فیلتر
 *
 * @var array $category
 * @var array|null $parent
 * @var array $children
 * @var array $products
 * @var App\Core\Paginator $paginator
 * @var int   $total
 * @var string $sort
 * @var array $brands
 * @var array $attributes
 * @var array $priceRange
 * @var array $active
 */

use App\Core\View;
use App\Models\Product;

/** آیا فیلتری فعال است؟ (برای نمایش دکمه «حذف فیلترها») */
$hasFilters = $active['brand_ids'] || $active['attribute_values'] || $active['condition']
           || $active['min_price'] || $active['max_price'] || $active['in_stock'];
?>

<div class="container">
    <!-- مسیر راهنما -->
    <nav class="breadcrumb" aria-label="مسیر صفحه">
        <a href="<?= e(url('')) ?>">خانه</a>
        <?php if ($parent !== null): ?>
            <span aria-hidden="true">›</span>
            <a href="<?= e(url('category/' . $parent['slug'])) ?>"><?= e($parent['name']) ?></a>
        <?php endif; ?>
        <span aria-hidden="true">›</span>
        <span><?= e($category['name']) ?></span>
    </nav>

    <div class="catalog">
        <!-- نوار فیلتر -->
        <aside class="filters" id="filters">
            <div class="filters__head">
                <h2>فیلترها</h2>
                <button class="filters__close" type="button" aria-label="بستن فیلترها">✕</button>
            </div>

            <form method="get" action="<?= e(url('category/' . $category['slug'])) ?>" id="filter-form">
                <!-- ترتیب فعلی حفظ شود -->
                <input type="hidden" name="sort" value="<?= e($sort) ?>">

                <?php if ($children): ?>
                    <div class="filter-group">
                        <h3>زیر‌دسته‌ها</h3>
                        <ul class="filter-links">
                            <?php foreach ($children as $child): ?>
                                <li>
                                    <a href="<?= e(url('category/' . $child['slug'])) ?>">
                                        <?= e($child['name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="filter-group">
                    <h3>وضعیت کالا</h3>
                    <label class="check">
                        <input type="radio" name="condition" value=""
                            <?= $active['condition'] === '' ? 'checked' : '' ?>> همه
                    </label>
                    <label class="check">
                        <input type="radio" name="condition" value="new"
                            <?= $active['condition'] === 'new' ? 'checked' : '' ?>> نو
                    </label>
                    <label class="check">
                        <input type="radio" name="condition" value="used"
                            <?= $active['condition'] === 'used' ? 'checked' : '' ?>> کارکرده
                    </label>
                </div>

                <?php if ($brands): ?>
                    <div class="filter-group">
                        <h3>برند</h3>
                        <div class="filter-scroll">
                            <?php foreach ($brands as $brand): ?>
                                <label class="check">
                                    <input type="checkbox" name="brand[]" value="<?= (int) $brand['id'] ?>"
                                        <?= in_array((int) $brand['id'], $active['brand_ids'], true) ? 'checked' : '' ?>>
                                    <?= e($brand['name']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($attributes as $attribute): ?>
                    <div class="filter-group">
                        <h3><?= e($attribute['name']) ?></h3>
                        <div class="filter-scroll">
                            <?php foreach ($attribute['values'] as $value): ?>
                                <label class="check">
                                    <input type="checkbox" name="attr[]" value="<?= (int) $value['id'] ?>"
                                        <?= in_array((int) $value['id'], $active['attribute_values'], true) ? 'checked' : '' ?>>
                                    <?php if ($attribute['input_type'] === 'color' && $value['color_code']): ?>
                                        <span class="swatch" style="background: <?= e($value['color_code']) ?>"
                                              aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <?= e($value['value']) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="filter-group">
                    <h3>محدوده قیمت (تومان)</h3>
                    <div class="price-inputs">
                        <input type="text" name="min_price" inputmode="numeric" placeholder="از"
                               value="<?= $active['min_price'] ? e((string) $active['min_price']) : '' ?>">
                        <input type="text" name="max_price" inputmode="numeric" placeholder="تا"
                               value="<?= $active['max_price'] ? e((string) $active['max_price']) : '' ?>">
                    </div>
                    <?php if ($priceRange['max'] > 0): ?>
                        <span class="filter-hint">
                            از <?= e(money($priceRange['min'], false)) ?>
                            تا <?= e(money($priceRange['max'], false)) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="filter-group">
                    <label class="check">
                        <input type="checkbox" name="in_stock" value="1"
                            <?= $active['in_stock'] ? 'checked' : '' ?>>
                        فقط کالاهای موجود
                    </label>
                </div>

                <div class="filter-actions">
                    <button class="btn btn--primary btn--block" type="submit">اعمال فیلتر</button>
                    <?php if ($hasFilters): ?>
                        <a class="btn btn--ghost btn--block"
                           href="<?= e(url('category/' . $category['slug'])) ?>">حذف فیلترها</a>
                    <?php endif; ?>
                </div>
            </form>
        </aside>

        <!-- نتایج -->
        <section class="catalog__main">
            <div class="catalog__head">
                <div>
                    <h1><?= e($category['name']) ?></h1>
                    <span class="catalog__count">
                        <?= e(fa_digits((string) $total)) ?> کالا
                    </span>
                </div>

                <div class="catalog__tools">
                    <button class="btn btn--ghost btn--sm filters-toggle" type="button">فیلترها</button>

                    <label class="sort">
                        <span>مرتب‌سازی:</span>
                        <select onchange="this.form.submit()" name="sort" form="sort-form">
                            <?php foreach (Product::SORT_LABELS as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </div>

            <!-- فرم مرتب‌سازی: فیلترهای فعلی را هم با خود می‌برد -->
            <form id="sort-form" method="get" action="<?= e(url('category/' . $category['slug'])) ?>" hidden>
                <?php foreach ($active['brand_ids'] as $id): ?>
                    <input type="hidden" name="brand[]" value="<?= (int) $id ?>">
                <?php endforeach; ?>
                <?php foreach ($active['attribute_values'] as $id): ?>
                    <input type="hidden" name="attr[]" value="<?= (int) $id ?>">
                <?php endforeach; ?>
                <?php if ($active['condition']): ?>
                    <input type="hidden" name="condition" value="<?= e($active['condition']) ?>">
                <?php endif; ?>
                <?php if ($active['min_price']): ?>
                    <input type="hidden" name="min_price" value="<?= (int) $active['min_price'] ?>">
                <?php endif; ?>
                <?php if ($active['max_price']): ?>
                    <input type="hidden" name="max_price" value="<?= (int) $active['max_price'] ?>">
                <?php endif; ?>
                <?php if ($active['in_stock']): ?>
                    <input type="hidden" name="in_stock" value="1">
                <?php endif; ?>
            </form>

            <?php if (!$products): ?>
                <div class="notice">
                    <strong>کالایی با این مشخصات پیدا نشد.</strong>
                    <span>فیلترها را تغییر دهید یا حذف کنید.</span>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <?php View::partial('site/partials/product-card', ['product' => $product]); ?>
                    <?php endforeach; ?>
                </div>

                <?php View::partial('site/partials/pagination', ['paginator' => $paginator]); ?>
            <?php endif; ?>
        </section>
    </div>
</div>
