<?php
/**
 * چارچوب کلی صفحات فروشگاه.
 *
 * @var string $content محتوای صفحه
 * @var string $title   عنوان صفحه
 */

use App\Core\Auth;
use App\Core\Flash;
use App\Core\Theme;
use App\Models\Category;
use App\Models\Setting;

$menu       = Category::menuTree();
$messages   = Flash::pull();
$siteName   = Setting::get('site_name', 'ایتکو');
$siteLogo   = Setting::get('site_logo', '');
$favicon    = Setting::get('site_favicon', '');
$megamenuBg = Setting::get('megamenu_bg', '');
$themeStyle = Theme::inlineStyle();

// ویدیوی پس‌زمینه‌ی مخصوص همین صفحه (اگر تنظیم شده باشد)
$pageVideo = App\Core\PageVideo::forPath($_SERVER['REQUEST_URI'] ?? '/');

// --- سئو: مقادیر پیش‌فرض که هر صفحه می‌تواند بازنویسی کند ---
$scheme      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host        = $_SERVER['HTTP_HOST'] ?? '';
$origin      = $host !== '' ? $scheme . '://' . $host : '';
$currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

$metaTitle       = $title ?? $siteName;
$metaDescription = $metaDescription ?? Setting::get('site_description', '');
$canonical       = $canonical ?? ($origin !== '' ? $origin . $currentPath : '');
$ogType          = $ogType ?? 'website';
$ogImage         = $ogImage ?? '';
// اگر تصویر سوشال نسبی باشد، آدرس کامل می‌سازیم
if ($ogImage !== '' && !str_starts_with($ogImage, 'http') && $origin !== '') {
    $ogImage = $origin . $ogImage;
}
$jsonLd = $jsonLd ?? '';
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($metaTitle) ?></title>
    <meta name="description" content="<?= e($metaDescription) ?>">
    <?php if ($canonical !== ''): ?><link rel="canonical" href="<?= e($canonical) ?>"><?php endif; ?>

    <!-- Open Graph / شبکه‌های اجتماعی -->
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:title" content="<?= e($metaTitle) ?>">
    <meta property="og:description" content="<?= e($metaDescription) ?>">
    <meta property="og:type" content="<?= e($ogType) ?>">
    <?php if ($canonical !== ''): ?><meta property="og:url" content="<?= e($canonical) ?>"><?php endif; ?>
    <?php if ($ogImage !== ''): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
    <meta name="twitter:card" content="<?= $ogImage !== '' ? 'summary_large_image' : 'summary' ?>">
    <meta name="twitter:title" content="<?= e($metaTitle) ?>">
    <meta name="twitter:description" content="<?= e($metaDescription) ?>">
    <?php if ($ogImage !== ''): ?><meta name="twitter:image" content="<?= e($ogImage) ?>"><?php endif; ?>
    <?php if ($jsonLd !== ''): ?>
    <script type="application/ld+json"><?= $jsonLd ?></script>
    <?php endif; ?>
    <?php if ($favicon): ?>
        <link rel="icon" href="<?= e(url('uploads/branding/' . $favicon)) ?>">
    <?php else: ?>
        <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
    <?php endif; ?>
    <link rel="preload" href="<?= e(url('assets/fonts/Vazirmatn-Variable.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
    <?php if ($themeStyle !== ''): ?>
        <!-- رنگ‌های سفارشی از پنل مدیریت -->
        <style><?= $themeStyle ?></style>
    <?php endif; ?>
</head>
<body>

<a class="skip-link" href="#main">رفتن به محتوای اصلی</a>

<header class="site-header">
    <!-- نوار بالایی -->
    <div class="topbar">
        <div class="container topbar__inner">
            <span class="topbar__note">ارسال به سراسر ایران · ضمانت اصالت کالا</span>
            <div class="topbar__links">
                <?php if (App\Models\Setting::getBool('appleid_enabled', false)): ?>
                    <a href="<?= e(url('appleid')) ?>">اپل‌آیدی آمریکا</a>
                <?php endif; ?>
                <a href="<?= e(url('blog')) ?>">مجله آیتکو</a>
                <a href="<?= e(url('page/contact')) ?>">تماس با ما</a>
                <a href="<?= e(url('page/about')) ?>">درباره ما</a>
            </div>
        </div>
    </div>

    <!-- بخش اصلی هدر -->
    <div class="container header-main">
        <button class="icon-btn menu-toggle" type="button" aria-label="باز کردن منو" aria-expanded="false"
                aria-controls="mega-nav">
            <span class="burger"></span>
        </button>

        <a class="logo" href="<?= e(url('')) ?>">
            <?php if ($siteLogo): ?>
                <img class="logo__img" src="<?= e(url('uploads/branding/' . $siteLogo)) ?>" alt="<?= e($siteName) ?>">
            <?php else: ?>
                <span class="logo__mark">IT</span>
                <span class="logo__text"><?= e($siteName) ?></span>
            <?php endif; ?>
        </a>

        <form class="search" action="<?= e(url('search')) ?>" method="get" role="search">
            <input type="search" name="q" placeholder="جستجو در محصولات…" aria-label="جستجو"
                   value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
            <button type="submit" aria-label="جستجو">
                <svg viewBox="0 0 24 24" width="19" height="19" aria-hidden="true">
                    <circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/>
                    <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </form>

        <div class="header-actions">
            <a class="icon-btn" href="<?= e(url(Auth::check() ? 'account' : 'login')) ?>"
               aria-label="<?= Auth::check() ? 'حساب کاربری' : 'ورود' ?>">
                <svg viewBox="0 0 24 24" width="21" height="21" aria-hidden="true">
                    <circle cx="12" cy="8" r="3.6" fill="none" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M4.5 20c1.2-3.8 4-5.6 7.5-5.6s6.3 1.8 7.5 5.6" fill="none"
                          stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </a>
            <a class="icon-btn icon-btn--cart" href="<?= e(url('cart')) ?>" aria-label="سبد خرید">
                <svg viewBox="0 0 24 24" width="21" height="21" aria-hidden="true">
                    <path d="M4 5h2l2 10h9l2-7H7" fill="none" stroke="currentColor" stroke-width="1.8"
                          stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="10" cy="19" r="1.4" fill="currentColor"/>
                    <circle cx="17" cy="19" r="1.4" fill="currentColor"/>
                </svg>
                <?php $cartCount = App\Models\Cart::count(); ?>
                <span class="cart-badge" id="cart-badge" <?= $cartCount === 0 ? 'hidden' : '' ?>>
                    <?= e(fa_digits((string) $cartCount)) ?>
                </span>
            </a>
        </div>
    </div>

    <!-- مگا منو: از روی دسته‌بندی‌ها ساخته می‌شود -->
    <nav class="mega-nav<?= $megamenuBg ? ' mega-nav--bg' : '' ?>" id="mega-nav" aria-label="دسته‌بندی محصولات"
         <?= $megamenuBg ? 'style="--megamenu-bg:url(' . e(url('uploads/branding/' . $megamenuBg)) . ')"' : '' ?>>
        <div class="container">
            <ul class="mega-nav__list">
                <?php foreach ($menu as $item): ?>
                    <li class="mega-nav__item<?= $item['children'] ? ' has-children' : '' ?>">
                        <a class="mega-link" href="<?= e(url('category/' . $item['slug'])) ?>">
                            <?php if (!empty($item['icon'])): ?>
                                <img class="mega-icon" src="<?= e(url('uploads/categories/' . $item['icon'])) ?>" alt="" loading="lazy">
                            <?php endif; ?>
                            <span><?= e($item['name']) ?></span>
                        </a>

                        <?php if ($item['children']): ?>
                            <div class="mega-panel">
                                <ul>
                                    <?php foreach ($item['children'] as $child): ?>
                                        <li>
                                            <a class="mega-link" href="<?= e(url('category/' . $child['slug'])) ?>">
                                                <?php if (!empty($child['icon'])): ?>
                                                    <img class="mega-icon" src="<?= e(url('uploads/categories/' . $child['icon'])) ?>" alt="" loading="lazy">
                                                <?php endif; ?>
                                                <span><?= e($child['name']) ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>
</header>

<?php if ($pageVideo): ?>
    <?php
        // متن روی ویدیو فقط برای صفحه اصلی از تنظیمات خوانده می‌شود
        $isHomeVideo = $pageVideo['key'] === 'home';
        App\Core\View::partial('site/partials/video-hero', [
            'video'       => $pageVideo['desktop'],
            'videoMobile' => $pageVideo['mobile'],
            'full'        => $isHomeVideo,
            'vtitle'      => $isHomeVideo ? Setting::get('hero_title', 'تکنولوژی، با خیال راحت') : '',
            'vsubtitle'   => $isHomeVideo ? Setting::get('hero_subtitle', '') : '',
            'vcta'        => $isHomeVideo ? Setting::get('hero_cta', 'شروع خرید') : '',
            // شدت محوشدن و ارتفاع بنر از تنظیمات (بخش ویدیوی صفحات)
            'fade'        => max(20, min(200, Setting::getInt('video_fade_speed', 90))) / 100,
            'bandHeight'  => Setting::getInt('video_band_height', 56),
        ]);
    ?>
<?php endif; ?>

<main id="main"<?= $pageVideo ? ' class="main--over-video"' : '' ?>>
    <?php if ($messages): ?>
        <div class="container">
            <?php foreach ($messages as $message): ?>
                <div class="alert alert--<?= e($message['type']) ?>"><?= e($message['text']) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container site-footer__grid">
        <div>
            <div class="logo logo--footer">
                <?php if ($siteLogo): ?>
                    <img class="logo__img" src="<?= e(url('uploads/branding/' . $siteLogo)) ?>" alt="<?= e($siteName) ?>">
                <?php else: ?>
                    <span class="logo__mark">IT</span>
                    <span class="logo__text"><?= e($siteName) ?></span>
                <?php endif; ?>
            </div>
            <p class="site-footer__about">
                فروشگاه اینترنتی موبایل، کامپیوتر، کنسول بازی، تجهیزات گیمینگ و لوازم جانبی.
            </p>
        </div>

        <div>
            <h3>راهنمای خرید</h3>
            <ul>
                <li><a href="<?= e(url('page/faq')) ?>">سوالات متداول</a></li>
                <li><a href="<?= e(url('page/terms')) ?>">قوانین و مقررات</a></li>
                <li><a href="<?= e(url('page/privacy')) ?>">حریم خصوصی</a></li>
            </ul>
        </div>

        <div>
            <h3><?= e($siteName) ?></h3>
            <ul>
                <li><a href="<?= e(url('page/about')) ?>">درباره ما</a></li>
                <li><a href="<?= e(url('page/contact')) ?>">تماس با ما</a></li>
                <li><a href="<?= e(url('blog')) ?>">مجله آیتکو</a></li>
            </ul>
        </div>

        <div class="site-footer__trust">
            <h3>نماد اعتماد</h3>
            <?php $enamad = Setting::get('enamad_code', ''); ?>
            <?php if ($enamad): ?>
                <!-- کد اینماد از پنل مدیریت وارد می‌شود -->
                <?= $enamad ?>
            <?php else: ?>
                <div class="trust-placeholder">نماد اعتماد الکترونیکی</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="container site-footer__bottom">
        <?php [$currentJalaliYear] = gregorian_to_jalali((int) date('Y'), (int) date('n'), (int) date('j')); ?>
        <span>© <?= e(fa_digits((string) $currentJalaliYear)) ?>
              تمام حقوق برای <?= e($siteName) ?> محفوظ است.</span>
        <?php if (Auth::isAdmin()): ?>
            <a href="<?= e(url('admin')) ?>">پنل مدیریت</a>
        <?php endif; ?>
    </div>
</footer>

<?php
// باکس شناور نرخ لحظه‌ای ارز (چپ‌پایین صفحه)
App\Core\View::partial('site/partials/rate-box');
// ویجت تماس چسبان و پاپ‌آپ مشاوره (هر دو از تنظیمات قابل ویرایش‌اند)
App\Core\View::partial('site/partials/contact-widget');
App\Core\View::partial('site/partials/consult-popup');
?>

<script src="<?= e(asset('js/site.js')) ?>" defer></script>
<script src="<?= e(asset('js/site-extras.js')) ?>" defer></script>
<script src="<?= e(asset('js/rates.js')) ?>" defer></script>
<?php foreach (($scripts ?? []) as $script): ?>
<script src="<?= e(asset('js/' . $script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
<?php Flash::clearOld(); ?>
