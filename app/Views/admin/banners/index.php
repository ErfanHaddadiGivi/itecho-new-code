<?php
/**
 * فهرست اسلایدهای صفحه اصلی
 *
 * @var array $banners
 */

use App\Core\Csrf;
?>

<div class="page-actions">
    <p class="page-hint">تصویرهای اسلایدر بالای صفحه اصلی. عدد ترتیب کوچک‌تر جلوتر نمایش داده می‌شود.</p>
    <a class="btn btn--primary" href="<?= e(url('admin/banners/create')) ?>">افزودن اسلاید</a>
</div>

<?php if (!$banners): ?>
    <div class="panel">
        <div class="panel__body">
            <p class="empty">هنوز اسلایدی ثبت نشده است. با دکمه «افزودن اسلاید» اولین تصویر را اضافه کنید.</p>
        </div>
    </div>
<?php else: ?>
    <div class="banner-list">
        <?php foreach ($banners as $b): ?>
            <div class="banner-row<?= (int) $b['is_active'] === 0 ? ' banner-row--off' : '' ?>">
                <div class="banner-row__thumb">
                    <img src="<?= e(url('uploads/banners/' . $b['image'])) ?>" alt="<?= e((string) $b['title']) ?>">
                </div>

                <div class="banner-row__info">
                    <strong><?= e($b['title'] !== null && $b['title'] !== '' ? $b['title'] : 'بدون عنوان') ?></strong>
                    <?php if (!empty($b['link_url'])): ?>
                        <span class="banner-row__link ltr"><?= e($b['link_url']) ?></span>
                    <?php endif; ?>
                    <div class="banner-row__meta">
                        <span>ترتیب: <?= e(fa_digits((string) $b['sort_order'])) ?></span>
                        <span class="badge <?= (int) $b['is_active'] === 1 ? 'badge--ok' : 'badge--off' ?>">
                            <?= (int) $b['is_active'] === 1 ? 'فعال' : 'غیرفعال' ?>
                        </span>
                        <?php if (!empty($b['mobile_image'])): ?>
                            <span class="badge badge--off">تصویر موبایل دارد</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="banner-row__actions">
                    <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/banners/' . $b['id'] . '/edit')) ?>">ویرایش</a>
                    <form action="<?= e(url('admin/banners/' . $b['id'] . '/delete')) ?>" method="post"
                          onsubmit="return confirm('این اسلاید حذف شود؟');">
                        <?= Csrf::field() ?>
                        <button class="btn btn--danger btn--sm" type="submit">حذف</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
