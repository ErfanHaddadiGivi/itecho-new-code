<?php
/**
 * بنر تبلیغاتی صفحه اصلی (زیر بخش بالایی).
 * تصویر دسکتاپ/موبایل با <picture> به‌صورت خودکار سوییچ می‌شود (بدون جاوااسکریپت).
 */

use App\Models\Setting;

if (!Setting::getBool('ad_banner_enabled', false)) {
    return;
}

$image = (string) Setting::get('ad_banner_image', '');
if ($image === '') {
    return;
}

$mobile = (string) Setting::get('ad_banner_image_mobile', '');
$link   = trim((string) Setting::get('ad_banner_link', ''));

$src       = url('uploads/banners/' . $image);
$srcMobile = $mobile !== '' ? url('uploads/banners/' . $mobile) : '';

$tag  = $link !== '' ? 'a' : 'div';
$attr = $link !== '' ? ' href="' . e($link) . '" rel="noopener"' : '';
?>
<div class="container">
    <<?= $tag ?> class="ad-banner"<?= $attr ?> aria-label="بنر تبلیغاتی">
        <picture>
            <?php if ($srcMobile !== ''): ?>
                <source media="(max-width: 600px)" srcset="<?= e($srcMobile) ?>">
            <?php endif; ?>
            <img class="ad-banner__img" src="<?= e($src) ?>" alt="بنر تبلیغاتی" loading="lazy">
        </picture>
    </<?= $tag ?>>
</div>
