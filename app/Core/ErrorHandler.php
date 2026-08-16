<?php

namespace App\Core;

use Throwable;

/**
 * مدیریت خطاهای پیش‌بینی‌نشده.
 *
 * در حالت توسعه جزئیات خطا نمایش داده می‌شود،
 * در هاست نهایی فقط یک صفحه ساده به کاربر نشان داده می‌شود
 * و جزئیات در فایل logs/php-errors.log ثبت می‌شود.
 */
class ErrorHandler
{
    public static function handle(Throwable $e): void
    {
        error_log(sprintf(
            "%s: %s in %s:%d\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));

        http_response_code(500);

        if (config('debug')) {
            self::renderDebug($e);
            return;
        }

        try {
            View::render('errors/500', ['title' => 'خطای سرور'], 'site');
        } catch (Throwable) {
            // اگر حتی قالب خطا هم مشکل داشت، یک پیام ساده نمایش بده
            header('Content-Type: text/html; charset=utf-8');
            echo '<!doctype html><html dir="rtl" lang="fa"><meta charset="utf-8">'
               . '<title>خطای سرور</title><body style="font-family:Tahoma,sans-serif;text-align:center;padding:60px">'
               . '<h1>مشکلی پیش آمد</h1><p>لطفاً کمی بعد دوباره تلاش کنید.</p></body></html>';
        }
    }

    private static function renderDebug(Throwable $e): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html dir="rtl" lang="fa"><meta charset="utf-8"><title>خطا</title>';
        echo '<style>body{font-family:Tahoma,sans-serif;background:#fff;padding:28px;line-height:1.9;color:#16211c}'
           . 'h1{color:#b32d2d;font-size:19px;margin:0 0 6px}'
           . 'pre{background:#f4f6f5;padding:16px;border-radius:8px;overflow:auto;direction:ltr;text-align:left;'
           . 'font-size:12.5px;line-height:1.7;border:1px solid #dbe4de}'
           . '.loc{color:#5a6a62;direction:ltr;text-align:left;font-family:monospace;font-size:13px}</style>';
        echo '<h1>' . e(get_class($e)) . '</h1>';
        echo '<p>' . e($e->getMessage()) . '</p>';
        echo '<p class="loc">' . e($e->getFile()) . ':' . (int) $e->getLine() . '</p>';
        echo '<pre>' . e($e->getTraceAsString()) . '</pre>';
        echo '<p style="color:#5a6a62;font-size:13px">این جزئیات فقط چون <code>debug</code> روشن است نمایش داده می‌شود.</p>';
        echo '</html>';
    }
}
