<?php
/**
 * فهرست مطالب بلاگ
 *
 * @var array $posts
 * @var bool  $ready آیا جدول posts ساخته شده است
 */

use App\Core\Csrf;

$ready = $ready ?? true;
?>

<?php if (!$ready): ?>
    <div class="panel panel--todo" style="border-inline-start-color: var(--danger);">
        <h2 class="panel__title">یک قدم راه‌اندازی مانده</h2>
        <p class="page-hint">
            جدول <code class="ltr">posts</code> هنوز در دیتابیس ساخته نشده است، برای همین
            بخش «مجله آیتکو» کار نمی‌کرد. برای فعال شدن این بخش، یک‌بار فایل زیر را در
            <b>phpMyAdmin → Import</b> اجرا کنید (کاملاً امن است و هیچ داده‌ای را پاک نمی‌کند):
        </p>
        <p><code class="ltr">database/content-update.sql</code></p>
        <p class="page-hint">
            اگر بخش سئوی مطالب هم لازم دارید، فایل
            <code class="ltr">database/seo-fields.sql</code> را نیز ایمپورت کنید.
            پس از ایمپورت، همین صفحه را دوباره باز کنید.
        </p>
    </div>
<?php else: ?>

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

<?php endif; /* $ready */ ?>
