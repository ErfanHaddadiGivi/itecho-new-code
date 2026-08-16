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

// --- محصولات ---
$router->get('/admin/products',             'Admin\ProductController@index');
$router->get('/admin/products/create',      'Admin\ProductController@create');
$router->post('/admin/products',            'Admin\ProductController@store');
$router->get('/admin/products/{id}/edit',   'Admin\ProductController@edit');
$router->post('/admin/products/{id}',       'Admin\ProductController@update');
$router->post('/admin/products/{id}/delete','Admin\ProductController@destroy');

// --- برندها ---
$router->get('/admin/brands',             'Admin\BrandController@index');
$router->get('/admin/brands/create',      'Admin\BrandController@create');
$router->post('/admin/brands',            'Admin\BrandController@store');
$router->get('/admin/brands/{id}/edit',   'Admin\BrandController@edit');
$router->post('/admin/brands/{id}',       'Admin\BrandController@update');
$router->post('/admin/brands/{id}/delete','Admin\BrandController@destroy');

// --- تنظیمات ---
$router->get('/admin/settings',  'Admin\SettingController@index');
$router->post('/admin/settings', 'Admin\SettingController@update');
