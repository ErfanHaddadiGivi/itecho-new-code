<?php
/**
 * اسلایدر بالای صفحه اصلی.
 *
 * بدون جاوااسکریپت هم کار می‌کند: همه اسلایدها زیر هم دیده می‌شوند و
 * با اسکریپت به یک اسلایدر چرخشی تبدیل می‌شوند.
 *
 * @var array $banners فهرست اسلایدهای فعال
 */

if (empty($banners)) {
    return;
}
?>
<section class="hero-slider" aria-label="اسلایدر تبلیغاتی" data-slider>
    <div class="hero-slider__track">
        <?php foreach ($banners as $i => $b): ?>
            <?php
                $img       = url('uploads/banners/' . $b['image']);
                $mobileImg = !empty($b['mobile_image']) ? url('uploads/banners/' . $b['mobile_image']) : $img;
                $hasLink   = !empty($b['link_url']);
                $tag       = $hasLink ? 'a' : 'div';
            ?>
            <<?= $tag ?> class="hero-slide" data-slide
                <?= $hasLink ? 'href="' . e($b['link_url']) . '"' : '' ?>
                <?= $i === 0 ? '' : 'aria-hidden="true"' ?>>
                <picture>
                    <source media="(max-width: 640px)" srcset="<?= e($mobileImg) ?>">
                    <img src="<?= e($img) ?>" alt="<?= e((string) ($b['title'] ?? '')) ?>"
                         <?= $i === 0 ? '' : 'loading="lazy"' ?>>
                </picture>
            </<?= $tag ?>>
        <?php endforeach; ?>
    </div>

    <?php if (count($banners) > 1): ?>
        <button class="hero-slider__nav hero-slider__nav--prev" type="button" data-slider-prev
                aria-label="اسلاید قبلی">‹</button>
        <button class="hero-slider__nav hero-slider__nav--next" type="button" data-slider-next
                aria-label="اسلاید بعدی">›</button>

        <div class="hero-slider__dots" role="tablist" aria-label="انتخاب اسلاید">
            <?php foreach ($banners as $i => $b): ?>
                <button class="hero-slider__dot<?= $i === 0 ? ' is-active' : '' ?>" type="button"
                        data-slider-dot="<?= $i ?>" aria-label="اسلاید <?= e(fa_digits((string) ($i + 1))) ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
