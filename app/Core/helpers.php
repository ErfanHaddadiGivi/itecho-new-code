<?php
/**
 * توابع کمکی سراسری.
 * این فایل در index.php بارگذاری می‌شود و همه‌جا در دسترس است.
 */

if (!function_exists('config')) {
    /**
     * خواندن یک تنظیم از فایل config با مسیر نقطه‌ای: config('db.host')
     */
    function config(string $key, mixed $default = null): mixed
    {
        $value = $GLOBALS['app_config'] ?? [];
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

if (!function_exists('e')) {
    /**
     * ایمن‌سازی متن برای نمایش در HTML.
     * ⚠️ هر متنی که از کاربر یا دیتابیس می‌آید باید با این تابع چاپ شود.
     */
    function e(?string $text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('base_path_uri')) {
    /**
     * اگر سایت داخل زیرپوشه نصب شده باشد، مسیر آن زیرپوشه را برمی‌گرداند.
     * مثال: نصب در public_html/shop  →  '/shop'
     */
    function base_path_uri(): string
    {
        static $base = null;
        if ($base !== null) {
            return $base;
        }
        $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $base = ($script === '/' || $script === '.') ? '' : rtrim($script, '/');
        return $base;
    }
}

if (!function_exists('url')) {
    /**
     * ساخت آدرس داخلی سایت: url('admin/categories')
     */
    function url(string $path = ''): string
    {
        $configured = trim((string) config('base_url', ''));
        if ($configured !== '') {
            return rtrim($configured, '/') . '/' . ltrim($path, '/');
        }
        return base_path_uri() . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * آدرس فایل‌های ثابت: asset('css/site.css')
     * پارامتر v برای این است که مرورگر بعد از تغییر فایل، نسخه قدیمی را نشان ندهد.
     */
    function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $full = ROOT_PATH . '/assets/' . $path;
        $version = is_file($full) ? filemtime($full) : null;
        return url('assets/' . $path) . ($version ? '?v=' . $version : '');
    }
}

if (!function_exists('redirect')) {
    /**
     * انتقال کاربر به آدرس دیگر و پایان اجرا
     */
    function redirect(string $path): never
    {
        header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
        exit;
    }
}

if (!function_exists('fa_digits')) {
    /**
     * تبدیل ارقام انگلیسی به فارسی: 1234 → ۱۲۳۴
     */
    function fa_digits(string $text): string
    {
        return strtr($text, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴',
                             '5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
    }
}

if (!function_exists('en_digits')) {
    /**
     * تبدیل ارقام فارسی/عربی به انگلیسی — برای ورودی‌های فرم قیمت و تعداد.
     * کاربر ممکن است قیمت را با کیبورد فارسی وارد کند.
     */
    function en_digits(string $text): string
    {
        return strtr($text, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4',
                             '۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
                             '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4',
                             '٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
                             '،'=>'', ','=>'']);
    }
}

if (!function_exists('money')) {
    /**
     * نمایش مبلغ به تومان با ارقام فارسی: 38000000 → «۳۸,۰۰۰,۰۰۰ تومان»
     */
    function money(int|float|null $amount, bool $withUnit = true): string
    {
        $formatted = fa_digits(number_format((float) ($amount ?? 0)));
        return $withUnit ? $formatted . ' تومان' : $formatted;
    }
}

if (!function_exists('gregorian_to_jalali')) {
    /**
     * تبدیل تاریخ میلادی به شمسی
     */
    function gregorian_to_jalali(int $gy, int $gm, int $gd): array
    {
        $gDaysInMonth = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;

        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
              + intdiv($gy2 + 399, 400) + $gd + $gDaysInMonth[$gm - 1];

        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;

        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }
}

if (!function_exists('jdate')) {
    /**
     * نمایش تاریخ شمسی: jdate('2026-08-16 14:30:00') → «۲۵ مرداد ۱۴۰۵»
     * حالت‌ها: 'date' | 'datetime' | 'short'
     */
    function jdate(?string $datetime, string $format = 'date'): string
    {
        if (empty($datetime)) {
            return '—';
        }
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return '—';
        }

        [$jy, $jm, $jd] = gregorian_to_jalali(
            (int) date('Y', $timestamp),
            (int) date('n', $timestamp),
            (int) date('j', $timestamp)
        );

        $months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور',
                   'مهر','آبان','آذر','دی','بهمن','اسفند'];

        return match ($format) {
            'short'    => fa_digits(sprintf('%04d/%02d/%02d', $jy, $jm, $jd)),
            'datetime' => fa_digits("$jd " . $months[$jm - 1] . " $jy، " . date('H:i', $timestamp)),
            default    => fa_digits("$jd " . $months[$jm - 1] . " $jy"),
        };
    }
}

if (!function_exists('slugify')) {
    /**
     * ساخت نامک (slug) از یک متن. حروف فارسی حفظ می‌شوند چون
     * آدرس‌های فارسی در مرورگرهای امروزی مشکلی ندارند.
     */
    function slugify(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/[^\p{L}\p{N}\s\-_]+/u', '', $text) ?? '';
        $text = preg_replace('/[\s_]+/u', '-', $text) ?? '';
        $text = trim($text, '-');
        return $text !== '' ? mb_strtolower($text, 'UTF-8') : (string) time();
    }
}

if (!function_exists('old')) {
    /**
     * برگرداندن مقدار قبلی فیلد فرم بعد از خطای اعتبارسنجی،
     * تا کاربر مجبور نشود دوباره همه‌چیز را پر کند.
     */
    function old(string $field, mixed $default = ''): string
    {
        return e((string) (App\Core\Flash::oldInput($field) ?? $default));
    }
}
