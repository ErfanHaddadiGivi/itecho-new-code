<?php
/**
 * مدیریت ویدیوی پس‌زمینه‌ی صفحه‌ها.
 *
 * @var array $targets     همه‌ی صفحه‌های قابل انتخاب (کلید => برچسب)
 * @var array $configured  صفحه‌هایی که همین حالا ویدیو دارند
 */

use App\Core\Csrf;
?>

<div class="panel panel--todo">
    <h2 class="panel__title">راهنما</h2>
    <p class="page-hint">
        برای هر صفحه می‌توانید یک ویدیوی پس‌زمینه بگذارید که بالای آن صفحه نمایش داده
        می‌شود و با اسکرول محو می‌شود. می‌توانید یک نسخه‌ی جدا برای موبایل هم آپلود کنید
        (ترجیحاً عمودی) تا روی گوشی بهم‌ریخته نشود؛ اگر نسخه‌ی موبایل نگذارید، همان ویدیوی
        دسکتاپ روی موبایل هم پخش می‌شود و به‌صورت خودکار به اندازه‌ی صفحه برش می‌خورد.
    </p>
</div>

<!-- افزودن / به‌روزرسانی ویدیوی یک صفحه -->
<div class="panel panel--form">
    <h2 class="panel__title">افزودن یا تغییر ویدیوی صفحه</h2>
    <form action="<?= e(url('admin/page-videos')) ?>" method="post" enctype="multipart/form-data" class="form">
        <?= Csrf::field() ?>

        <div class="field">
            <label for="target">صفحه</label>
            <select id="target" name="target" required>
                <?php foreach ($targets as $key => $label): ?>
                    <option value="<?= e($key) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="video_desktop">ویدیوی دسکتاپ</label>
            <input type="file" id="video_desktop" name="video_desktop" accept="video/mp4,video/webm">
            <span class="field__hint">MP4 یا WebM، حداکثر ۲۰ مگابایت. اندازه‌ی افقی (مثلاً ۱۶:۹).</span>
        </div>

        <div class="field">
            <label for="video_mobile">ویدیوی موبایل (اختیاری)</label>
            <input type="file" id="video_mobile" name="video_mobile" accept="video/mp4,video/webm">
            <span class="field__hint">ترجیحاً عمودی (مثلاً ۹:۱۶). اگر خالی بماند، همان ویدیوی دسکتاپ استفاده می‌شود.</span>
        </div>

        <div class="form-actions">
            <button class="btn btn--primary" type="submit">ذخیره ویدیوی صفحه</button>
        </div>
    </form>
</div>

<!-- فهرست ویدیوهای تنظیم‌شده -->
<div class="panel">
    <h2 class="panel__title">ویدیوهای تنظیم‌شده</h2>

    <?php if (!$configured): ?>
        <p class="empty">هنوز برای هیچ صفحه‌ای ویدیو تنظیم نشده است.</p>
    <?php else: ?>
        <div class="banner-list">
            <?php foreach ($configured as $item): ?>
                <div class="banner-row">
                    <div class="banner-row__thumb">
                        <video src="<?= e(url('uploads/branding/' . $item['desktop'])) ?>" muted loop playsinline
                               style="width:100%;height:100%;object-fit:cover;"></video>
                    </div>
                    <div class="banner-row__info">
                        <strong><?= e($item['label']) ?></strong>
                        <div class="banner-row__meta">
                            <span class="badge badge--ok">دسکتاپ</span>
                            <?php if ($item['mobile'] !== ''): ?>
                                <span class="badge badge--ok">موبایل جدا</span>
                            <?php else: ?>
                                <span class="badge badge--off">موبایل: همان دسکتاپ</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="banner-row__actions">
                        <form action="<?= e(url('admin/page-videos/delete')) ?>" method="post"
                              class="inline-form" data-confirm="ویدیوی «<?= e($item['label']) ?>» حذف شود؟">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="target" value="<?= e($item['key']) ?>">
                            <button class="btn btn--danger btn--sm" type="submit">حذف</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
