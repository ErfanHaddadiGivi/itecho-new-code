<?php
/**
 * شخصی‌سازی ظاهر: رنگ و تم، لوگو و نام سایت.
 *
 * @var string $primary
 * @var string $accent
 * @var string $siteName
 * @var string $logo
 * @var string $favicon
 * @var string $megamenuBg
 * @var array  $errors
 */

use App\Core\Csrf;

/** رنگ‌های آماده برای انتخاب سریع (نام → [برند، تاکید]) */
$presets = [
    'سبز ایتکو'  => ['#0B6E4F', '#C2680E'],
    'آبی'        => ['#1D4ED8', '#F59E0B'],
    'بنفش'       => ['#6D28D9', '#DB2777'],
    'قرمز'       => ['#B91C1C', '#0E7490'],
    'مشکی طلایی' => ['#1F2937', '#B7791F'],
    'صورتی'      => ['#BE185D', '#0F766E'],
];
?>

<form action="<?= e(url('admin/appearance')) ?>" method="post" enctype="multipart/form-data" class="form appearance">
    <?= Csrf::field() ?>

    <?php if (isset($errors['image'])): ?>
        <div class="alert alert--error"><?= e($errors['image']) ?></div>
    <?php endif; ?>

    <!-- ============ رنگ و تم ============ -->
    <div class="panel">
        <h2 class="panel__title">رنگ و تم</h2>
        <div>
            <p class="muted">
                رنگ برند رنگ اصلی دکمه‌ها و لینک‌هاست و رنگ تاکیدی برای برچسب تخفیف و جزئیات به کار می‌رود.
                سایه‌های روشن و تیره خودکار ساخته می‌شوند.
            </p>

            <div class="preset-row">
                <span class="preset-row__label">تم آماده:</span>
                <?php foreach ($presets as $name => [$p, $a]): ?>
                    <button type="button" class="preset-chip js-preset"
                            data-primary="<?= e($p) ?>" data-accent="<?= e($a) ?>"
                            style="--c1: <?= e($p) ?>; --c2: <?= e($a) ?>;">
                        <span class="preset-chip__dots"></span><?= e($name) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="color-grid">
                <div class="field">
                    <label for="theme_primary">رنگ برند</label>
                    <div class="color-input">
                        <input type="color" id="theme_primary" name="theme_primary"
                               value="<?= e($primary) ?>" class="js-color">
                        <input type="text" class="color-hex js-hex" data-for="theme_primary"
                               value="<?= e($primary) ?>" dir="ltr" maxlength="7">
                    </div>
                </div>

                <div class="field">
                    <label for="theme_accent">رنگ تاکیدی</label>
                    <div class="color-input">
                        <input type="color" id="theme_accent" name="theme_accent"
                               value="<?= e($accent) ?>" class="js-color">
                        <input type="text" class="color-hex js-hex" data-for="theme_accent"
                               value="<?= e($accent) ?>" dir="ltr" maxlength="7">
                    </div>
                </div>
            </div>

            <!-- پیش‌نمایش زنده -->
            <div class="theme-preview" id="theme-preview">
                <span class="theme-preview__label">پیش‌نمایش:</span>
                <button type="button" class="btn btn--primary tp-primary">دکمه اصلی</button>
                <span class="tp-badge">۲۰٪ تخفیف</span>
                <a href="#" class="tp-link" onclick="return false;">یک لینک نمونه</a>
            </div>
        </div>
    </div>

    <!-- ============ نام و لوگو ============ -->
    <div class="panel">
        <h2 class="panel__title">نام و لوگو</h2>
        <div>
            <div class="field">
                <label for="site_name">نام سایت</label>
                <input type="text" id="site_name" name="site_name" value="<?= e($siteName) ?>" maxlength="60">
                <span class="field__hint">در هدر، فوتر و عنوان صفحه‌ها نمایش داده می‌شود.</span>
            </div>

            <div class="brand-uploads">
                <!-- لوگو -->
                <div class="field brand-upload">
                    <label>لوگوی سایت</label>
                    <?php if ($logo): ?>
                        <div class="brand-upload__preview brand-upload__preview--logo">
                            <img src="<?= e(url('uploads/branding/' . $logo)) ?>" alt="لوگوی فعلی">
                        </div>
                        <label class="brand-upload__remove">
                            <input type="checkbox" name="remove_logo" value="1"> حذف لوگو
                        </label>
                    <?php endif; ?>
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/webp">
                    <span class="field__hint">
                        اگر لوگو آپلود شود جای متن «IT + نام» را می‌گیرد. ترجیحاً PNG با پس‌زمینه شفاف.
                    </span>
                </div>

                <!-- فاوآیکون -->
                <div class="field brand-upload">
                    <label>فاوآیکون (آیکون تب مرورگر)</label>
                    <?php if ($favicon): ?>
                        <div class="brand-upload__preview brand-upload__preview--favicon">
                            <img src="<?= e(url('uploads/branding/' . $favicon)) ?>" alt="فاوآیکون فعلی">
                        </div>
                        <label class="brand-upload__remove">
                            <input type="checkbox" name="remove_favicon" value="1"> حذف فاوآیکون
                        </label>
                    <?php endif; ?>
                    <input type="file" name="favicon" accept="image/png,image/jpeg,image/webp">
                    <span class="field__hint">تصویر مربعی کوچک، مثلاً ۶۴×۶۴ پیکسل.</span>
                </div>

                <!-- پس‌زمینه مگا منو -->
                <div class="field brand-upload">
                    <label>پس‌زمینه مگا منو</label>
                    <?php if ($megamenuBg): ?>
                        <div class="brand-upload__preview brand-upload__preview--banner">
                            <img src="<?= e(url('uploads/branding/' . $megamenuBg)) ?>" alt="پس‌زمینه فعلی مگا منو">
                        </div>
                        <label class="brand-upload__remove">
                            <input type="checkbox" name="remove_megamenu_bg" value="1"> حذف پس‌زمینه مگا منو
                        </label>
                    <?php endif; ?>
                    <input type="file" name="megamenu_bg" accept="image/png,image/jpeg,image/webp">
                    <span class="field__hint">
                        تصویری که پشت پنل‌های بازشوی مگا منو نمایش داده می‌شود (روی آن یک لایه‌ی تیره برای خوانایی می‌افتد).
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions form-actions--sticky">
        <button class="btn btn--primary" type="submit">ذخیره تغییرات</button>
        <a class="btn btn--ghost" href="<?= e(url('')) ?>" target="_blank" rel="noopener">مشاهده سایت ↗</a>
    </div>
</form>

<script src="<?= e(asset('js/appearance.js')) ?>" defer></script>
