<?php
/**
 * لیست محصولات پنل
 *
 * @var array $products
 * @var App\Core\Paginator $paginator
 * @var int   $total
 * @var array $filters
 * @var array $categories
 * @var array $brands
 */

use App\Core\Csrf;
use App\Core\View;
?>

<div class="page-actions">
    <p class="page-hint"><?= e(fa_digits((string) $total)) ?> محصول ثبت شده است.</p>
    <a class="btn btn--primary" href="<?= e(url('admin/products/create')) ?>">افزودن محصول</a>
</div>

<!-- جستجو و فیلتر -->
<form class="toolbar" method="get" action="<?= e(url('admin/products')) ?>">
    <input type="search" name="q" placeholder="جستجو در نام یا کد کالا…"
           value="<?= e($filters['q']) ?>">

    <select name="category_id">
        <option value="">همه دسته‌ها</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?= (int) $category['id'] ?>"
                <?= $filters['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                <?= $category['depth'] === 1 ? '— ' : '' ?><?= e($category['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="brand_id">
        <option value="">همه برندها</option>
        <?php foreach ($brands as $brand): ?>
            <option value="<?= (int) $brand['id'] ?>"
                <?= $filters['brand_id'] === (int) $brand['id'] ? 'selected' : '' ?>>
                <?= e($brand['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="status">
        <option value="">همه وضعیت‌ها</option>
        <option value="active"       <?= $filters['status'] === 'active' ? 'selected' : '' ?>>فعال</option>
        <option value="inactive"     <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>غیرفعال</option>
        <option value="out_of_stock" <?= $filters['status'] === 'out_of_stock' ? 'selected' : '' ?>>ناموجود</option>
    </select>

    <button class="btn btn--ghost" type="submit">اعمال</button>
    <?php if ($filters['q'] || $filters['category_id'] || $filters['brand_id'] || $filters['status']): ?>
        <a class="btn btn--ghost" href="<?= e(url('admin/products')) ?>">حذف فیلتر</a>
    <?php endif; ?>
</form>

<?php if (!$products): ?>
    <div class="panel">
        <p class="empty">محصولی با این مشخصات پیدا نشد.</p>
    </div>
<?php else: ?>
    <div class="panel">
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>تصویر</th>
                    <th>نام</th>
                    <th>دسته / برند</th>
                    <th>قیمت</th>
                    <th>موجودی</th>
                    <th>وضعیت</th>
                    <th class="col-actions">عملیات</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td class="cell-thumb">
                            <?php if ($product['main_image']): ?>
                                <img src="<?= e(url('uploads/products/' . $product['main_image'])) ?>"
                                     alt="" loading="lazy">
                            <?php else: ?>
                                <span class="thumb-empty">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= e($product['name']) ?>
                            <?php if ((int) $product['has_variants'] === 1): ?>
                                <span class="badge">دارای تنوع</span>
                            <?php endif; ?>
                            <?php if ($product['sku']): ?>
                                <span class="ltr muted block"><?= e($product['sku']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="muted">
                            <?= e($product['category_name'] ?? '—') ?>
                            <?php if ($product['brand_name']): ?>
                                <span class="block"><?= e($product['brand_name']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(money((int) $product['price'], false)) ?></td>
                        <td>
                            <span class="stock-pill<?= (int) $product['stock'] === 0 ? ' stock-pill--zero' : '' ?>">
                                <?= e(fa_digits((string) (int) $product['stock'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ((int) $product['is_active'] === 1): ?>
                                <span class="badge badge--ok">فعال</span>
                            <?php else: ?>
                                <span class="badge badge--off">غیرفعال</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-actions">
                            <a class="btn btn--ghost btn--sm"
                               href="<?= e(url('product/' . $product['slug'])) ?>" target="_blank"
                               rel="noopener">نمایش</a>
                            <a class="btn btn--ghost btn--sm"
                               href="<?= e(url('admin/products/' . $product['id'] . '/edit')) ?>">ویرایش</a>

                            <form action="<?= e(url('admin/products/' . $product['id'] . '/delete')) ?>"
                                  method="post" class="inline-form"
                                  data-confirm="آیا از حذف محصول «<?= e($product['name']) ?>» مطمئن هستید؟">
                                <?= Csrf::field() ?>
                                <button class="btn btn--danger btn--sm" type="submit">حذف</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php View::partial('site/partials/pagination', ['paginator' => $paginator]); ?>
<?php endif; ?>
