<?php
/**
 * تعریف مسیرهای سایت.
 *
 * الگو:
 *      $router->get('/آدرس', 'نام‌کنترلر@نام‌متد');
 *
 * هر چیزی داخل {} یک پارامتر است که به متد کنترلر فرستاده می‌شود.
 *
 * @var App\Core\Router $router
 */

// =====================================================================
//  بخش فروشگاه
// =====================================================================
$router->get('/', 'HomeController@index');

// دسته‌بندی، محصول و جستجو
$router->get('/category/{slug}', 'CategoryController@show');
$router->get('/product/{slug}',  'ProductController@show');
$router->get('/search',          'SearchController@index');
$router->get('/search/suggest',  'SearchController@suggest');

// سبد خرید
$router->get('/cart',         'CartController@index');
$router->post('/cart/add',    'CartController@add');
$router->post('/cart/update', 'CartController@update');
$router->post('/cart/remove', 'CartController@remove');

// --- حساب کاربری مشتری ---
$router->get('/login',      'AuthController@showLogin');
$router->post('/login',     'AuthController@login');
$router->get('/register',   'AuthController@showRegister');
$router->post('/register',  'AuthController@register');
$router->get('/verify',     'AuthController@showVerify');
$router->post('/verify',    'AuthController@verify');
$router->post('/verify/resend', 'AuthController@resend');
$router->post('/logout',    'AuthController@logout');

$router->get('/forgot-password',  'AuthController@showForgot');
$router->post('/forgot-password', 'AuthController@forgot');
$router->get('/reset-password',   'AuthController@showReset');
$router->post('/reset-password',  'AuthController@reset');

// --- پیشخوان حساب کاربری ---
$router->get('/account',                 'AccountController@index');
$router->get('/account/orders',          'AccountController@orders');
$router->get('/order/{id}',              'AccountController@order');
$router->get('/account/profile',         'AccountController@profile');
$router->post('/account/profile',        'AccountController@updateProfile');
$router->post('/account/password',       'AccountController@updatePassword');

// --- دفترچه آدرس ---
$router->get('/account/addresses',                'AddressController@index');
$router->get('/account/addresses/create',         'AddressController@create');
$router->post('/account/addresses',               'AddressController@store');
$router->get('/account/addresses/{id}/edit',      'AddressController@edit');
$router->post('/account/addresses/{id}',          'AddressController@update');
$router->post('/account/addresses/{id}/default',  'AddressController@setDefault');
$router->post('/account/addresses/{id}/delete',   'AddressController@destroy');

// --- علاقه‌مندی‌ها ---
$router->get('/account/wishlist',   'WishlistController@index');
$router->post('/wishlist/toggle',   'WishlistController@toggle');
$router->post('/wishlist/remove',   'WishlistController@remove');

// --- نظرات ---
$router->get('/account/reviews', 'ReviewController@mine');
$router->post('/review',         'ReviewController@store');

// --- تسویه‌حساب ---
$router->get('/checkout',  'CheckoutController@index');
$router->post('/checkout', 'CheckoutController@place');

// --- پرداخت ---
$router->get('/payment/start/{id}', 'PaymentController@start');
$router->get('/payment/callback',   'PaymentController@callback');
$router->post('/payment/callback',  'PaymentController@callback');
// لینک پرداخت تکمیلی هزینه ارسال که برای مشتری ایمیل می‌شود
$router->get('/pay/{token}',       'PaymentController@showToken');
$router->post('/pay/{token}/start', 'PaymentController@startToken');

// بلاگ / مجله آیتکو
$router->get('/blog',         'BlogController@index');
$router->get('/blog/{slug}',  'BlogController@show');

// نرخ لحظه‌ای ارز (دلار/درهم به تومان) — منبع: tgju.org
$router->get('/api/rates',    'RatesController@index');

// صفحهٔ فروش اپل‌آیدی آمریکا (لینک به ربات تلگرام)
$router->get('/appleid',      'AppleIdController@index');

// سئو: نقشه‌ی سایت و robots
$router->get('/sitemap.xml', 'SitemapController@index');
$router->get('/robots.txt',  'SitemapController@robots');

// صفحات ثابت اینماد: قوانین، حریم خصوصی، درباره ما، تماس با ما
$router->get('/page/{slug}', 'PageController@show');


// =====================================================================
//  پنل مدیریت
// =====================================================================

// --- ورود و خروج ---
$router->get('/admin/login',   'Admin\AuthController@showLogin');
$router->post('/admin/login',  'Admin\AuthController@login');
$router->post('/admin/logout', 'Admin\AuthController@logout');

// --- داشبورد ---
$router->get('/admin', 'Admin\DashboardController@index');

// --- دسته‌بندی‌ها (منبع مگا منو) ---
$router->get('/admin/categories',             'Admin\CategoryController@index');
$router->get('/admin/categories/create',      'Admin\CategoryController@create');
$router->post('/admin/categories',            'Admin\CategoryController@store');
$router->get('/admin/categories/{id}/edit',   'Admin\CategoryController@edit');
$router->post('/admin/categories/{id}',       'Admin\CategoryController@update');
$router->post('/admin/categories/{id}/delete','Admin\CategoryController@destroy');

// --- سفارش‌ها ---
$router->get('/admin/orders',                  'Admin\OrderController@index');
$router->get('/admin/orders/{id}',             'Admin\OrderController@show');
$router->post('/admin/orders/{id}/status',     'Admin\OrderController@updateStatus');
$router->post('/admin/orders/{id}/details',    'Admin\OrderController@updateDetails');
$router->post('/admin/orders/{id}/shipping',   'Admin\OrderController@setShippingCost');

// --- محصولات ---
$router->get('/admin/products',             'Admin\ProductController@index');
$router->get('/admin/products/create',      'Admin\ProductController@create');
$router->post('/admin/products',            'Admin\ProductController@store');
$router->get('/admin/products/{id}/edit',   'Admin\ProductController@edit');
$router->post('/admin/products/{id}',       'Admin\ProductController@update');
$router->post('/admin/products/{id}/delete','Admin\ProductController@destroy');

// --- آپلود رسانه برای ویرایشگر توضیحات (درج عکس/ویدیو در متن) ---
$router->post('/admin/media/upload', 'Admin\MediaController@upload');

// --- برندها ---
$router->get('/admin/brands',             'Admin\BrandController@index');
$router->get('/admin/brands/create',      'Admin\BrandController@create');
$router->post('/admin/brands',            'Admin\BrandController@store');
$router->get('/admin/brands/{id}/edit',   'Admin\BrandController@edit');
$router->post('/admin/brands/{id}',       'Admin\BrandController@update');
$router->post('/admin/brands/{id}/delete','Admin\BrandController@destroy');

// --- نظرات ---
$router->get('/admin/reviews',                'Admin\ReviewController@index');
$router->post('/admin/reviews/{id}/status',   'Admin\ReviewController@updateStatus');
$router->post('/admin/reviews/{id}/delete',   'Admin\ReviewController@destroy');

// --- مجله آیتکو (بلاگ) ---
$router->get('/admin/posts',             'Admin\PostController@index');
$router->get('/admin/posts/create',      'Admin\PostController@create');
$router->post('/admin/posts',            'Admin\PostController@store');
$router->get('/admin/posts/{id}/edit',   'Admin\PostController@edit');
$router->post('/admin/posts/{id}',       'Admin\PostController@update');
$router->post('/admin/posts/{id}/delete','Admin\PostController@destroy');

// --- صفحات ثابت (افزودن، ویرایش، حذف) ---
$router->get('/admin/pages',             'Admin\PageController@index');
$router->get('/admin/pages/create',      'Admin\PageController@create');
$router->post('/admin/pages',            'Admin\PageController@store');
$router->get('/admin/pages/{id}/edit',   'Admin\PageController@edit');
$router->post('/admin/pages/{id}',       'Admin\PageController@update');
$router->post('/admin/pages/{id}/delete','Admin\PageController@destroy');

// --- شخصی‌سازی ظاهر: رنگ و تم، لوگو و نام ---
$router->get('/admin/appearance',  'Admin\AppearanceController@index');
$router->post('/admin/appearance', 'Admin\AppearanceController@update');

// --- بنر تبلیغاتی صفحه اصلی ---
$router->get('/admin/ad-banner',  'Admin\AdBannerController@index');
$router->post('/admin/ad-banner', 'Admin\AdBannerController@update');

// --- ویدیوی پس‌زمینه‌ی صفحه‌ها ---
$router->get('/admin/page-videos',           'Admin\PageVideoController@index');
$router->post('/admin/page-videos',          'Admin\PageVideoController@store');
$router->post('/admin/page-videos/delete',   'Admin\PageVideoController@destroy');
$router->post('/admin/page-videos/settings', 'Admin\PageVideoController@saveSettings');

// --- اسلایدر صفحه اصلی ---
$router->get('/admin/banners',             'Admin\BannerController@index');
$router->get('/admin/banners/create',      'Admin\BannerController@create');
$router->post('/admin/banners',            'Admin\BannerController@store');
$router->get('/admin/banners/{id}/edit',   'Admin\BannerController@edit');
$router->post('/admin/banners/{id}',       'Admin\BannerController@update');
$router->post('/admin/banners/{id}/delete','Admin\BannerController@destroy');

// --- همگام‌سازی محصولات (گزارش‌ها + آپلود دستی CSV) ---
$router->get('/admin/sync-logs',         'Admin\SyncLogController@index');
$router->post('/admin/sync-logs/upload', 'Admin\SyncLogController@upload');

// --- تنظیمات ---
$router->get('/admin/settings',  'Admin\SettingController@index');
$router->post('/admin/settings', 'Admin\SettingController@update');
