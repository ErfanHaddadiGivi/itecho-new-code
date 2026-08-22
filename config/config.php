<?php
/**
 * تنظیمات اصلی برنامه.
 *
 * ⚠️ این فایل را ویرایش نکنید.
 * اطلاعات دیتابیس و رمزها را در فایل config.local.php بنویسید.
 * (از روی config.local.example.php کپی بگیرید و نامش را عوض کنید.)
 */

$config = [
    // حالت توسعه: در هاست نهایی حتماً false باشد تا خطاها به کاربر نمایش داده نشود
    'debug' => false,

    'db' => [
        'host'    => 'localhost',
        'name'    => '',
        'user'    => '',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    // آدرس سایت — خالی بگذارید تا خودکار تشخیص داده شود
    'base_url' => '',

    // توکن مخفی برای همگام‌سازی محصولات از گوگل‌شیت.
    // مقدار واقعی را در config.local.php بنویسید (اینجا خالی می‌ماند).
    // تا وقتی این توکن تنظیم نشود، نقطه پایانی api/sheet-sync.php هر درخواستی را رد می‌کند.
    'sheet_sync_token' => '',
];

// ---------------------------------------------------------------------
// تنظیمات محلی (خارج از گیت) روی تنظیمات بالا نوشته می‌شود
// ---------------------------------------------------------------------
$localFile = __DIR__ . '/config.local.php';

if (is_file($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        // ادغام دو سطحی تا مثلاً فقط db.pass قابل تغییر باشد
        foreach ($local as $key => $value) {
            if (is_array($value) && isset($config[$key]) && is_array($config[$key])) {
                $config[$key] = array_merge($config[$key], $value);
            } else {
                $config[$key] = $value;
            }
        }
    }
} else {
    // راهنمای نصب برای وقتی هنوز فایل تنظیمات ساخته نشده
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html dir="rtl" lang="fa"><meta charset="utf-8">'
       . '<title>نصب ایتکو</title>'
       . '<style>body{font-family:Tahoma,sans-serif;background:#f4f6f5;color:#16211c;'
       . 'display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}'
       . 'div{background:#fff;border:1px solid #dbe4de;border-radius:12px;padding:32px;max-width:560px;line-height:2}'
       . 'h1{margin:0 0 12px;font-size:20px}code{background:#eef3f0;padding:2px 6px;border-radius:4px;'
       . 'direction:ltr;display:inline-block}</style>'
       . '<div><h1>فایل تنظیمات پیدا نشد</h1>'
       . '<p>برای راه‌اندازی سایت، از فایل <code>config/config.local.example.php</code> یک کپی بگیرید '
       . 'و نام آن را به <code>config/config.local.php</code> تغییر دهید، سپس اطلاعات دیتابیس را داخل آن وارد کنید.</p>'
       . '</div></html>';
    exit;
}

// در دسترس قرار دادن برای تابع config()
$GLOBALS['app_config'] = $config;
