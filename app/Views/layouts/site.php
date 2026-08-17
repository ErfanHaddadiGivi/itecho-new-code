<?php
/**
 * چارچوب کلی صفحات فروشگاه.
 *
 * @var string $content محتوای صفحه
 * @var string $title   عنوان صفحه
 */

use App\Core\Auth;
use App\Core\Flash;
use App\Models\Category;
use App\Models\Setting;

$menu     = Category::menuTree();
$messages = Flash::pull();
$siteName = Setting::get('site_name', 'ایتکو');
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e(Setting::get('site_description', '')) ?>">
    <title><?= e($title ?? $siteName) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
    <link rel="preload" href="<?= e(url('assets/fonts/Vazirmatn-Variable.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
</head>
<body>

<a class="skip-link" href="#main">رفتن به محتوای اصلی</a>

<header class="site-header">
    <!-- نوار بالایی -->
    <div class="topbar">
        <div class="container topbar__inner">
            <span class="topbar__note">ارسال به سراسر ایران · ضمانت اصالت کالا</span>
            <div class="topbar__links">
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
            <span class="logo__mark">IT</span>
            <span class="logo__text"><?= e($siteName) ?></span>
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
    <nav class="mega-nav" id="mega-nav" aria-label="دسته‌بندی محصولات">
        <div class="container">
            <ul class="mega-nav__list">
                <?php foreach ($menu as $item): ?>
                    <li class="mega-nav__item<?= $item['children'] ? ' has-children' : '' ?>">
                        <a href="<?= e(url('category/' . $item['slug'])) ?>"><?= e($item['name']) ?></a>

                        <?php if ($item['children']): ?>
                            <div class="mega-panel">
                                <ul>
                                    <?php foreach ($item['children'] as $child): ?>
                                        <li>
                                            <a href="<?= e(url('category/' . $child['slug'])) ?>">
                                                <?= e($child['name']) ?>
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

<?php if ($messages): ?>
    <div class="container">
        <?php foreach ($messages as $message): ?>
            <div class="alert alert--<?= e($message['type']) ?>"><?= e($message['text']) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main id="main">
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container site-footer__grid">
        <div>
            <div class="logo logo--footer">
                <span class="logo__mark">IT</span>
                <span class="logo__text"><?= e($siteName) ?></span>
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
            <h3>ایتکو</h3>
            <ul>
                <li><a href="<?= e(url('page/about')) ?>">درباره ما</a></li>
                <li><a href="<?= e(url('page/contact')) ?>">تماس با ما</a></li>
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

<script src="<?= e(asset('js/site.js')) ?>" defer></script>
<?php foreach (($scripts ?? []) as $script): ?>
<script src="<?= e(asset('js/' . $script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
<?php Flash::clearOld(); ?>
