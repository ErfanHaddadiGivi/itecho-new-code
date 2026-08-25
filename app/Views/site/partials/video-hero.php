<?php
/**
 * پس‌زمینه‌ی ویدیویی استیکی که با اسکرول محو می‌شود.
 * روی هر صفحه‌ای که برایش ویدیو تنظیم شده باشد از داخل لایوت رندر می‌شود.
 *
 * @var string $video        نام فایل ویدیوی دسکتاپ
 * @var string $videoMobile  نام فایل ویدیوی موبایل (اختیاری)
 * @var bool   $full         تمام‌قد بودن (فقط صفحه اصلی)
 * @var string $vtitle       عنوان روی ویدیو (فقط صفحه اصلی)
 * @var string $vsubtitle
 * @var string $vcta
 */

$videoMobile = $videoMobile ?? '';
$full        = $full ?? false;
$vtitle      = $vtitle ?? '';
$vsubtitle   = $vsubtitle ?? '';
$vcta        = $vcta ?? '';
$fade        = $fade ?? 0.9;            // کسری از ارتفاع صفحه برای محوشدن کامل
$bandHeight  = $bandHeight ?? 0;        // ارتفاع بنر صفحه‌های داخلی (vh) — ۰ یعنی پیش‌فرض CSS

// در صفحه‌های داخلی (غیر خانه) اگر ارتفاع سفارشی داده شده، به‌صورت متغیر CSS اعمال می‌شود
// (تا در موبایل هم بتواند مقیاس بخورد).
$style = (!$full && $bandHeight > 0) ? ' style="--vh-band:' . (int) $bandHeight . 'vh"' : '';
?>
<section class="video-hero<?= $full ? ' video-hero--full' : '' ?>" data-video-hero
         data-fade="<?= e((string) $fade) ?>"<?= $style ?>>
    <div class="video-hero__bg">
        <video class="video-hero__video" autoplay muted loop playsinline preload="auto"
               src="<?= e(url('uploads/branding/' . $video)) ?>"
               <?= $videoMobile !== '' ? 'data-src-mobile="' . e(url('uploads/branding/' . $videoMobile)) . '"' : '' ?>></video>
        <span class="video-hero__scrim" aria-hidden="true"></span>
    </div>

    <?php if ($vtitle !== '' || $vsubtitle !== '' || $vcta !== ''): ?>
        <div class="container video-hero__content">
            <?php if ($vtitle !== ''): ?><h1><?= e($vtitle) ?></h1><?php endif; ?>
            <?php if ($vsubtitle !== ''): ?><p><?= e($vsubtitle) ?></p><?php endif; ?>
            <?php if ($vcta !== ''): ?>
                <a class="btn btn--primary btn--lg" href="#main"><?= e($vcta) ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <a class="video-hero__scroll" href="#main" aria-label="پایین"><span></span></a>
</section>
