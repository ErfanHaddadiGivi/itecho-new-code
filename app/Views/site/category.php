<?php
/**
 * صفحه دسته‌بندی به همراه نوار فیلتر (سبک دیجی‌کالا:
 * بخش‌های بازشونده، جستجوی برند، چیپ فیلترهای فعال و اعمال خودکار)
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

$base = url('category/' . $category['slug']);

/** آیا فیلتری فعال است؟ */
$hasFilters = $active['brand_ids'] || $active['attribute_values'] || $active['condition']
           || $active['min_price'] || $active['max_price'] || $active['in_stock'];

/** ساخت آدرس بر اساس مجموعه‌ای از فیلترها (برای چیپ‌های حذف) */
$buildUrl = static function (array $a) use ($base, $sort): string {
    $q = [];
    if ($sort !== '' && $sort !== 'newest') { $q['sort'] = $sort; }
    if ($a['brand_ids']) { $q['brand'] = array_values($a['brand_ids']); }
    if ($a['attribute_values']) { $q['attr'] = array_values($a['attribute_values']); }
    if ($a['condition']) { $q['condition'] = $a['condition']; }
    if ($a['min_price']) { $q['min_price'] = $a['min_price']; }
    if ($a['max_price']) { $q['max_price'] = $a['max_price']; }
    if ($a['in_stock']) { $q['in_stock'] = 1; }
    return $q ? $base . '?' . http_build_query($q) : $base;
};

/** نام برندها و مقادیر ویژگی برای برچسب چیپ‌ها */
$brandNames = [];
foreach ($brands as $b) { $brandNames[(int) $b['id']] = $b['name']; }
$attrValNames = [];
foreach ($attributes as $at) {
    foreach ($at['values'] as $v) { $attrValNames[(int) $v['id']] = $v['value']; }
}

/** فهرست چیپ‌های فیلتر فعال: [برچسب، آدرسِ حذف] */
$chips = [];
foreach ($active['brand_ids'] as $id) {
    $a = $active; $a['brand_ids'] = array_values(array_diff($a['brand_ids'], [$id]));
    $chips[] = [$brandNames[$id] ?? 'برند', $buildUrl($a)];
}
foreach ($active['attribute_values'] as $id) {
    $a = $active; $a['attribute_values'] = array_values(array_diff($a['attribute_values'], [$id]));
    $chips[] = [$attrValNames[$id] ?? 'ویژگی', $buildUrl($a)];
}
if ($active['condition']) {
    $a = $active; $a['condition'] = '';
    $chips[] = [$active['condition'] === 'new' ? 'نو' : 'کارکرده', $buildUrl($a)];
}
if ($active['min_price'] || $active['max_price']) {
    $a = $active; $a['min_price'] = 0; $a['max_price'] = 0;
    $label = 'قیمت: ';
    $label .= $active['min_price'] ? 'از ' . money($active['min_price'], false) : '';
    $label .= $active['max_price'] ? ' تا ' . money($active['max_price'], false) : '';
    $chips[] = [$label, $buildUrl($a)];
}
if ($active['in_stock']) {
    $a = $active; $a['in_stock'] = 0;
    $chips[] = ['فقط موجود', $buildUrl($a)];
}
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

            <form method="get" action="<?= e($base) ?>" id="filter-form" data-autofilter>
                <!-- ترتیب فعلی حفظ شود -->
                <input type="hidden" name="sort" value="<?= e($sort) ?>">

                <?php if ($children): ?>
                    <details class="filter-group" open>
                        <summary><h3>زیر‌دسته‌ها</h3></summary>
                        <div class="filter-group__body">
                            <ul class="filter-links">
                                <?php foreach ($children as $child): ?>
                                    <li><a href="<?= e(url('category/' . $child['slug'])) ?>"><?= e($child['name']) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </details>
                <?php endif; ?>

                <details class="filter-group" open>
                    <summary><h3>وضعیت کالا</h3></summary>
                    <div class="filter-group__body">
                        <label class="check"><input type="radio" name="condition" value=""
                            <?= $active['condition'] === '' ? 'checked' : '' ?>> همه</label>
                        <label class="check"><input type="radio" name="condition" value="new"
                            <?= $active['condition'] === 'new' ? 'checked' : '' ?>> نو</label>
                        <label class="check"><input type="radio" name="condition" value="used"
                            <?= $active['condition'] === 'used' ? 'checked' : '' ?>> کارکرده</label>
                    </div>
                </details>

                <?php if ($brands): ?>
                    <details class="filter-group" open>
                        <summary><h3>برند</h3></summary>
                        <div class="filter-group__body">
                            <?php if (count($brands) > 6): ?>
                                <input type="text" class="filter-search" placeholder="جستجوی برند…"
                                       data-filter-search aria-label="جستجوی برند">
                            <?php endif; ?>
                            <div class="filter-scroll" data-filter-list>
                                <?php foreach ($brands as $brand): ?>
                                    <label class="check" data-name="<?= e($brand['name']) ?>">
                                        <input type="checkbox" name="brand[]" value="<?= (int) $brand['id'] ?>"
                                            <?= in_array((int) $brand['id'], $active['brand_ids'], true) ? 'checked' : '' ?>>
                                        <?= e($brand['name']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </details>
                <?php endif; ?>

                <?php foreach ($attributes as $attribute): ?>
                    <details class="filter-group" open>
                        <summary><h3><?= e($attribute['name']) ?></h3></summary>
                        <div class="filter-group__body">
                            <div class="filter-scroll">
                                <?php foreach ($attribute['values'] as $value): ?>
                                    <label class="check">
                                        <input type="checkbox" name="attr[]" value="<?= (int) $value['id'] ?>"
                                            <?= in_array((int) $value['id'], $active['attribute_values'], true) ? 'checked' : '' ?>>
                                        <?php if ($attribute['input_type'] === 'color' && $value['color_code']): ?>
                                            <span class="swatch" style="background: <?= e($value['color_code']) ?>" aria-hidden="true"></span>
                                        <?php endif; ?>
                                        <?= e($value['value']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </details>
                <?php endforeach; ?>

                <?php
                    $pMin   = (int) floor($priceRange['min'] ?? 0);
                    $pMax   = (int) ceil($priceRange['max'] ?? 0);
                    $curMin = $active['min_price'] ?: $pMin;
                    $curMax = $active['max_price'] ?: $pMax;
                ?>
                <details class="filter-group" open>
                    <summary><h3>محدوده قیمت (تومان)</h3></summary>
                    <div class="filter-group__body">
                        <?php if ($pMax > $pMin): ?>
                            <!-- اسلایدر دو‌سرِ کشویی قیمت (جاوااسکریپت خالص) -->
                            <div class="price-slider" data-price-slider
                                 data-min="<?= $pMin ?>" data-max="<?= $pMax ?>">
                                <div class="price-slider__track">
                                    <div class="price-slider__fill"></div>
                                </div>
                                <input type="range" class="price-slider__range price-slider__range--min"
                                       min="<?= $pMin ?>" max="<?= $pMax ?>" value="<?= (int) $curMin ?>"
                                       aria-label="کمترین قیمت">
                                <input type="range" class="price-slider__range price-slider__range--max"
                                       min="<?= $pMin ?>" max="<?= $pMax ?>" value="<?= (int) $curMax ?>"
                                       aria-label="بیشترین قیمت">
                            </div>
                            <div class="price-slider__values">
                                <span data-price-label-min><?= e(money($curMin, false)) ?></span>
                                <span>تا</span>
                                <span data-price-label-max><?= e(money($curMax, false)) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="price-inputs">
                            <input type="text" name="min_price" inputmode="numeric" placeholder="از"
                                   value="<?= $active['min_price'] ? e((string) $active['min_price']) : '' ?>"
                                   data-price-input-min>
                            <input type="text" name="max_price" inputmode="numeric" placeholder="تا"
                                   value="<?= $active['max_price'] ? e((string) $active['max_price']) : '' ?>"
                                   data-price-input-max>
                        </div>
                        <?php if ($priceRange['max'] > 0): ?>
                            <span class="filter-hint">
                                از <?= e(money($priceRange['min'], false)) ?> تا <?= e(money($priceRange['max'], false)) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </details>

                <div class="filter-group filter-group--plain">
                    <label class="check check--switch">
                        <input type="checkbox" name="in_stock" value="1" <?= $active['in_stock'] ? 'checked' : '' ?>>
                        فقط کالاهای موجود
                    </label>
                </div>

                <div class="filter-actions">
                    <button class="btn btn--primary btn--block" type="submit">اعمال فیلتر</button>
                    <?php if ($hasFilters): ?>
                        <a class="btn btn--ghost btn--block" href="<?= e($base) ?>">حذف همه فیلترها</a>
                    <?php endif; ?>
                </div>
            </form>
        </aside>

        <!-- نتایج -->
        <section class="catalog__main">
            <div class="catalog__head">
                <div>
                    <h1><?= e($category['name']) ?></h1>
                    <span class="catalog__count"><?= e(fa_digits((string) $total)) ?> کالا</span>
                </div>

                <div class="catalog__tools">
                    <button class="btn btn--ghost btn--sm filters-toggle" type="button">
                        فیلترها<?= $hasFilters ? ' <span class="filters-toggle__dot"></span>' : '' ?>
                    </button>

                    <label class="sort">
                        <span>مرتب‌سازی:</span>
                        <select onchange="this.form.submit()" name="sort" form="sort-form">
                            <?php foreach (Product::SORT_LABELS as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </div>

            <!-- چیپ فیلترهای فعال -->
            <?php if ($chips): ?>
                <div class="filter-chips">
                    <?php foreach ($chips as [$label, $removeUrl]): ?>
                        <a class="filter-chip" href="<?= e($removeUrl) ?>">
                            <span><?= e($label) ?></span>
                            <span class="filter-chip__x" aria-hidden="true">✕</span>
                        </a>
                    <?php endforeach; ?>
                    <a class="filter-chips__clear" href="<?= e($base) ?>">حذف همه</a>
                </div>
            <?php endif; ?>

            <!-- فرم مرتب‌سازی: فیلترهای فعلی را هم با خود می‌برد -->
            <form id="sort-form" method="get" action="<?= e($base) ?>" hidden>
                <?php foreach ($active['brand_ids'] as $id): ?>
                    <input type="hidden" name="brand[]" value="<?= (int) $id ?>">
                <?php endforeach; ?>
                <?php foreach ($active['attribute_values'] as $id): ?>
                    <input type="hidden" name="attr[]" value="<?= (int) $id ?>">
                <?php endforeach; ?>
                <?php if ($active['condition']): ?><input type="hidden" name="condition" value="<?= e($active['condition']) ?>"><?php endif; ?>
                <?php if ($active['min_price']): ?><input type="hidden" name="min_price" value="<?= (int) $active['min_price'] ?>"><?php endif; ?>
                <?php if ($active['max_price']): ?><input type="hidden" name="max_price" value="<?= (int) $active['max_price'] ?>"><?php endif; ?>
                <?php if ($active['in_stock']): ?><input type="hidden" name="in_stock" value="1"><?php endif; ?>
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
