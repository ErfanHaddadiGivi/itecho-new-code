<?php
/**
 * لیست برندها
 *
 * @var array $brands
 */

use App\Core\Csrf;
?>

<div class="page-actions">
    <p class="page-hint">برندها در نوار فیلتر صفحات دسته‌بندی و در جستجو استفاده می‌شوند.</p>
    <a class="btn btn--primary" href="<?= e(url('admin/brands/create')) ?>">افزودن برند</a>
</div>

<?php if (!$brands): ?>
    <div class="panel">
        <p class="empty">هنوز برندی ثبت نشده است.</p>
    </div>
<?php else: ?>
    <div class="panel">
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>نام</th>
                    <th>نامک</th>
                    <th>ترتیب</th>
                    <th>محصولات</th>
                    <th>وضعیت</th>
                    <th class="col-actions">عملیات</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($brands as $brand): ?>
                    <tr>
                        <td><?= e($brand['name']) ?></td>
                        <td class="ltr muted"><?= e($brand['slug']) ?></td>
                        <td><?= e(fa_digits((string) (int) $brand['sort_order'])) ?></td>
                        <td><?= e(fa_digits((string) (int) $brand['product_count'])) ?></td>
                        <td>
                            <?php if ((int) $brand['is_active'] === 1): ?>
                                <span class="badge badge--ok">فعال</span>
                            <?php else: ?>
                                <span class="badge badge--off">غیرفعال</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-actions">
                            <a class="btn btn--ghost btn--sm"
                               href="<?= e(url('admin/brands/' . $brand['id'] . '/edit')) ?>">ویرایش</a>

                            <form action="<?= e(url('admin/brands/' . $brand['id'] . '/delete')) ?>"
                                  method="post" class="inline-form"
                                  data-confirm="آیا از حذف برند «<?= e($brand['name']) ?>» مطمئن هستید؟">
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
<?php endif; ?>
