<?php

namespace App\Core;

/**
 * محافظت در برابر CSRF.
 *
 * حمله CSRF یعنی سایت مهاجم، مرورگرِ کاربرِ واردشده را وادار کند
 * درخواستی به سایت ما بفرستد (مثلاً حذف یک دسته‌بندی).
 *
 * راه‌حل: هر فرم یک توکن مخفی دارد که فقط سایت خودمان می‌داند.
 *
 * روش استفاده در قالب فرم:
 *      <?= App\Core\Csrf::field() ?>
 * و در کنترلر، قبل از هر تغییر:
 *      Csrf::check();
 */
class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (!Session::has(self::KEY)) {
            Session::set(self::KEY, bin2hex(random_bytes(32)));
        }
        return (string) Session::get(self::KEY);
    }

    /**
     * فیلد مخفی آماده برای گذاشتن داخل هر فرم POST
     */
    public static function field(): string
    {
        return '<input type="hidden" name="' . self::KEY . '" value="' . e(self::token()) . '">';
    }

    public static function isValid(?string $token): bool
    {
        $stored = Session::get(self::KEY);
        if (!is_string($stored) || !is_string($token) || $token === '') {
            return false;
        }
        // hash_equals جلوی حمله زمان‌سنجی را می‌گیرد
        return hash_equals($stored, $token);
    }

    /**
     * بررسی توکن؛ در صورت نامعتبر بودن، اجرا متوقف می‌شود.
     */
    public static function check(): void
    {
        if (!self::isValid($_POST[self::KEY] ?? null)) {
            http_response_code(419);
            header('Content-Type: text/html; charset=utf-8');
            echo '<!doctype html><html dir="rtl" lang="fa"><meta charset="utf-8">'
               . '<title>نشست منقضی شد</title>'
               . '<style>body{font-family:Tahoma,sans-serif;background:#f4f6f5;color:#16211c;display:flex;'
               . 'align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px;text-align:center}'
               . 'div{background:#fff;border:1px solid #dbe4de;border-radius:12px;padding:32px;max-width:460px;line-height:2}'
               . 'a{color:#0b6e4f}</style>'
               . '<div><h1 style="font-size:20px;margin:0 0 10px">نشست شما منقضی شده است</h1>'
               . '<p>لطفاً صفحه قبل را دوباره باز کنید و فرم را مجدداً ارسال کنید.</p>'
               . '<p><a href="' . e(url('')) . '">بازگشت به صفحه اصلی</a></p></div></html>';
            exit;
        }
    }
}
