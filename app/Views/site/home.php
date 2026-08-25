<?php
/**
 * صفحه اصلی فروشگاه.
 *
 * @var array $categories
 * @var array $featured
 * @var array $newest
 * @var array $banners
 */

use App\Models\Setting;

// متن‌های صفحه اصلی از تنظیمات خوانده می‌شوند و از پنل قابل ویرایش‌اند.
$heroTitle    = Setting::get('hero_title', 'تکنولوژی، با خیال راحت');
$heroSubtitle = Setting::get('hero_subtitle', 'موبایل، کامپیوتر، کنسول بازی و تجهیزات گیمینگ — با ضمانت اصالت کالا و ارسال به سراسر ایران.');
$heroCta      = Setting::get('hero_cta', 'شروع خرید');
?>

<?php if (empty($heroVideo)): ?>
    <?php if (!empty($banners)): ?>
        <!-- اسلایدر مدیریت‌شده از پنل؛ اگر اسلایدی نباشد بخش ثابت زیر نمایش داده می‌شود -->
        <div class="container">
            <?php App\Core\View::partial('site/partials/hero-slider', ['banners' => $banners]); ?>
        </div>
    <?php else: ?>
        <section class="hero">
            <div class="container hero__inner">
                <div class="hero__text">
                    <h1><?= e($heroTitle) ?></h1>
                    <?php if ($heroSubtitle !== ''): ?><p><?= e($heroSubtitle) ?></p><?php endif; ?>
                    <?php if ($heroCta !== ''): ?>
                        <a class="btn btn--primary btn--lg" href="#categories"><?= e($heroCta) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
<?php elseif (!empty($banners)): ?>
    <!-- ویدیو بالای صفحه است؛ اسلایدر هم زیر آن (روی محتوا) نمایش داده می‌شود -->
    <div class="container home-slider-under-video">
        <?php App\Core\View::partial('site/partials/hero-slider', ['banners' => $banners]); ?>
    </div>
<?php endif; ?>

<!-- دسته‌بندی‌های اصلی -->
<section class="section" id="categories">
    <div class="container">
        <div class="section__head">
            <h2>دسته‌بندی‌ها</h2>
        </div>

        <?php if (!$categories): ?>
            <p class="empty">هنوز دسته‌بندی‌ای ثبت نشده است.</p>
        <?php else: ?>
            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                    <?php
                        $hasBg   = !empty($category['image']);
                        $tagline = trim((string) ($category['description'] ?? ''));
                    ?>
                    <a class="category-card<?= $hasBg ? ' category-card--bg' : '' ?>"
                       href="<?= e(url('category/' . $category['slug'])) ?>"
                       <?= $hasBg ? 'style="background-image:url(' . e(url('uploads/categories/' . $category['image'])) . ')"' : '' ?>>
                        <span class="category-card__name"><?= e($category['name']) ?></span>
                        <?php if ($tagline !== ''): ?>
                            <span class="category-card__tag"><?= e($tagline) ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- محصولات ویژه -->
<?php if ($featured): ?>
    <section class="section section--alt">
        <div class="container">
            <div class="section__head">
                <h2>پیشنهاد ویژه</h2>
            </div>
            <div class="product-grid">
                <?php foreach ($featured as $product): ?>
                    <?php App\Core\View::partial('site/partials/product-card', ['product' => $product]); ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- جدیدترین‌ها -->
<section class="section">
    <div class="container">
        <div class="section__head">
            <h2>جدیدترین محصولات</h2>
        </div>

        <?php if (!$newest): ?>
            <div class="notice">
                <strong>هنوز محصولی ثبت نشده است.</strong>
                <span>پس از افزودن محصولات از پنل مدیریت، این بخش به‌صورت خودکار پر می‌شود.</span>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($newest as $product): ?>
                    <?php App\Core\View::partial('site/partials/product-card', ['product' => $product]); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- مزیت‌ها -->
<section class="section section--alt">
    <div class="container">
        <div class="feature-grid">
            <div class="feature">
                <h3>ضمانت اصالت کالا</h3>
                <p>همه محصولات اورجینال و دارای گارانتی معتبر هستند.</p>
            </div>
            <div class="feature">
                <h3>پرداخت امن</h3>
                <p>پرداخت از طریق درگاه بانکی معتبر با نماد اعتماد الکترونیکی.</p>
            </div>
            <div class="feature">
                <h3>ارسال سراسری</h3>
                <p>ارسال با پست به سراسر ایران یا تحویل حضوری در فروشگاه.</p>
            </div>
        </div>
    </div>
</section>
