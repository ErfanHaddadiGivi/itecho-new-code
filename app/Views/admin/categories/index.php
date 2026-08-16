<?php
/**
 * لیست دسته‌بندی‌ها (نمای درختی)
 *
 * @var array $categories
 */

use App\Core\Csrf;
?>

<div class="page-actions">
    <p class="page-hint">
        مگا منوی سایت از همین دسته‌بندی‌ها ساخته می‌شود. ترتیب نمایش با «ترتیب» مشخص می‌شود
        و هر دسته‌ای که «نمایش در منو» نداشته باشد، در منو دیده نمی‌شود.
    </p>
    <a class="btn btn--primary" href="<?= e(url('admin/categories/create')) ?>">افزودن دسته‌بندی</a>
</div>

<?php if (!$categories): ?>
    <div class="panel">
        <p class="empty">هنوز دسته‌بندی‌ای ثبت نشده است.</p>
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
                    <th>منو</th>
                    <th class="col-actions">عملیات</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr class="<?= $category['depth'] === 1 ? 'row--child' : 'row--parent' ?>">
                        <td>
                            <?php if ($category['depth'] === 1): ?>
                                <span class="tree-branch">└</span>
                            <?php endif; ?>
                            <?= e($category['name']) ?>
                        </td>
                        <td class="ltr muted"><?= e($category['slug']) ?></td>
                        <td><?= e(fa_digits((string) (int) $category['sort_order'])) ?></td>
                        <td><?= e(fa_digits((string) (int) $category['product_count'])) ?></td>
                        <td>
                            <?php if ((int) $category['is_active'] === 1): ?>
                                <span class="badge badge--ok">فعال</span>
                            <?php else: ?>
                                <span class="badge badge--off">غیرفعال</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= (int) $category['show_in_menu'] === 1 ? '✓' : '—' ?>
                        </td>
                        <td class="col-actions">
                            <a class="btn btn--ghost btn--sm"
                               href="<?= e(url('admin/categories/' . $category['id'] . '/edit')) ?>">ویرایش</a>

                            <form action="<?= e(url('admin/categories/' . $category['id'] . '/delete')) ?>"
                                  method="post" class="inline-form"
                                  data-confirm="آیا از حذف دسته‌بندی «<?= e($category['name']) ?>» مطمئن هستید؟">
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
