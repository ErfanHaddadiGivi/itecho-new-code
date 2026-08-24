<?php
/**
 * فهرست مطالب بلاگ
 *
 * @var array $posts
 */

use App\Core\Csrf;
?>

<div class="page-actions">
    <p class="page-hint">مطالب و مقالات گیمینگ که در بخش «مطالب» سایت نمایش داده می‌شوند.</p>
    <a class="btn btn--primary" href="<?= e(url('admin/posts/create')) ?>">افزودن مطلب</a>
</div>

<?php if (!$posts): ?>
    <div class="panel">
        <p class="empty">هنوز مطلبی ثبت نشده است. با دکمه «افزودن مطلب» اولین مقاله را بنویسید.</p>
    </div>
<?php else: ?>
    <div class="panel">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>عنوان</th>
                        <th>وضعیت</th>
                        <th>بازدید</th>
                        <th>تاریخ</th>
                        <th class="col-actions">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                        <tr>
                            <td>
                                <div class="cell-title">
                                    <?php if (!empty($post['cover_image'])): ?>
                                        <img class="cell-thumb" src="<?= e(url('uploads/posts/' . $post['cover_image'])) ?>" alt="">
                                    <?php endif; ?>
                                    <span><?= e($post['title']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= (int) $post['is_published'] === 1 ? 'badge--ok' : 'badge--off' ?>">
                                    <?= (int) $post['is_published'] === 1 ? 'منتشر شده' : 'پیش‌نویس' ?>
                                </span>
                            </td>
                            <td><?= e(fa_digits((string) $post['views'])) ?></td>
                            <td class="muted"><?= e(jdate($post['published_at'] ?? $post['created_at'])) ?></td>
                            <td class="col-actions">
                                <a class="btn btn--ghost btn--sm" href="<?= e(url('blog/' . $post['slug'])) ?>" target="_blank" rel="noopener">مشاهده</a>
                                <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/posts/' . $post['id'] . '/edit')) ?>">ویرایش</a>
                                <form action="<?= e(url('admin/posts/' . $post['id'] . '/delete')) ?>" method="post"
                                      class="inline-form" data-confirm="آیا این مطلب حذف شود؟">
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
