<?php
/**
 * چارچوب پنل مدیریت.
 *
 * @var string $content
 * @var string $title
 */

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Setting;

$messages = Flash::pull();
$user     = Auth::user();
$path     = $_SERVER['REQUEST_URI'] ?? '';

/** برای مشخص کردن منوی فعال */
$isActive = static function (string $segment) use ($path): string {
    if ($segment === 'admin') {
        return preg_match('#/admin/?($|\?)#', $path) ? ' is-active' : '';
    }
    return str_contains($path, '/admin/' . $segment) ? ' is-active' : '';
};
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'پنل مدیریت') ?> | پنل ایتکو</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
    <link rel="preload" href="<?= e(url('assets/fonts/Vazirmatn-Variable.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin">

<div class="admin-shell">

    <!-- نوار کناری -->
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-sidebar__head">
            <a class="logo logo--admin" href="<?= e(url('admin')) ?>">
                <span class="logo__mark">IT</span>
                <span class="logo__text"><?= e(Setting::get('site_name', 'ایتکو')) ?></span>
            </a>
        </div>

        <nav class="admin-nav">
            <span class="admin-nav__label">فروشگاه</span>
            <a class="admin-nav__link<?= $isActive('admin') ?>" href="<?= e(url('admin')) ?>">داشبورد</a>
            <a class="admin-nav__link<?= $isActive('products') ?>" href="<?= e(url('admin/products')) ?>">محصولات</a>
            <a class="admin-nav__link<?= $isActive('categories') ?>" href="<?= e(url('admin/categories')) ?>">دسته‌بندی‌ها</a>
            <a class="admin-nav__link<?= $isActive('orders') ?>" href="<?= e(url('admin/orders')) ?>">سفارش‌ها</a>
            <a class="admin-nav__link<?= $isActive('brands') ?>" href="<?= e(url('admin/brands')) ?>">برندها</a>
            <a class="admin-nav__link<?= $isActive('reviews') ?>" href="<?= e(url('admin/reviews')) ?>">نظرات</a>

            <span class="admin-nav__label">پیکربندی</span>
            <a class="admin-nav__link<?= $isActive('settings') ?>" href="<?= e(url('admin/settings')) ?>">تنظیمات</a>

        </nav>

        <div class="admin-sidebar__foot">
            <a href="<?= e(url('')) ?>" target="_blank" rel="noopener">مشاهده سایت ↗</a>
        </div>
    </aside>

    <!-- بخش اصلی -->
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="icon-btn sidebar-toggle" type="button" aria-label="منو" aria-controls="admin-sidebar"
                    aria-expanded="false">
                <span class="burger"></span>
            </button>

            <h1 class="admin-topbar__title"><?= e($title ?? 'پنل مدیریت') ?></h1>

            <div class="admin-topbar__user">
                <span class="admin-topbar__name">
                    <?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?>
                </span>
                <form action="<?= e(url('admin/logout')) ?>" method="post">
                    <?= Csrf::field() ?>
                    <button class="btn btn--ghost btn--sm" type="submit">خروج</button>
                </form>
            </div>
        </header>

        <div class="admin-content">
            <?php foreach ($messages as $message): ?>
                <div class="alert alert--<?= e($message['type']) ?>"><?= e($message['text']) ?></div>
            <?php endforeach; ?>

            <?= $content ?>
        </div>
    </div>
</div>

<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
<?php foreach (($scripts ?? []) as $script): ?>
<script src="<?= e(asset('js/' . $script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
<?php Flash::clearOld(); ?>
