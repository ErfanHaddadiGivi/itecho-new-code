<?php
/**
 * فهرست صفحات ثابت — افزودن، ویرایش و حذف
 *
 * @var array $pages
 */

use App\Core\Csrf;
?>

<div class="page-actions">
    <p class="page-hint">صفحه‌های ثابت سایت (درباره ما، تماس با ما، قوانین و ...) را ویرایش کنید یا صفحه‌ی تازه بسازید.</p>
    <a class="btn btn--primary" href="<?= e(url('admin/pages/create')) ?>">افزودن صفحه</a>
</div>

<div class="panel">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>عنوان صفحه</th>
                    <th>آدرس</th>
                    <th>وضعیت</th>
                    <th class="col-actions">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $page): ?>
                    <tr>
                        <td><?= e($page['title']) ?></td>
                        <td class="ltr muted">/page/<?= e($page['slug']) ?></td>
                        <td>
                            <span class="badge <?= (int) $page['is_active'] === 1 ? 'badge--ok' : 'badge--off' ?>">
                                <?= (int) $page['is_active'] === 1 ? 'فعال' : 'غیرفعال' ?>
                            </span>
                        </td>
                        <td class="col-actions">
                            <a class="btn btn--ghost btn--sm" href="<?= e(url('page/' . $page['slug'])) ?>" target="_blank" rel="noopener">مشاهده</a>
                            <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/pages/' . $page['id'] . '/edit')) ?>">ویرایش</a>
                            <form action="<?= e(url('admin/pages/' . $page['id'] . '/delete')) ?>" method="post"
                                  class="inline-form" data-confirm="صفحه‌ی «<?= e($page['title']) ?>» حذف شود؟">
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
