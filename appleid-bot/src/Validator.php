<?php

namespace AppleBot;

/**
 * اعتبارسنجی و پاک‌سازی ورودی‌های کاربر (ایمیل، شماره، تاریخ، نام).
 * همه‌ی متدها ایستا هستند.
 */
class Validator
{
    /** تبدیل ارقام فارسی/عربی به انگلیسی */
    public static function enDigits(string $text): string
    {
        return strtr($text, [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
    }

    /** حذف فاصله‌های اضافه و کاراکترهای کنترلی */
    public static function clean(string $text): string
    {
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);
        return trim(preg_replace('/\s+/u', ' ', (string) $text));
    }

    /** نام: ۱ تا ۶۰ کاراکتر، بدون کاراکتر کنترلی */
    public static function name(string $text): ?string
    {
        $text = self::clean($text);
        $len  = mb_strlen($text);
        return ($len >= 1 && $len <= 60) ? $text : null;
    }

    /** ایمیل معتبر (کوچک‌شده) */
    public static function email(string $text): ?string
    {
        $text = strtolower(self::clean($text));
        if (mb_strlen($text) > 120) {
            return null;
        }
        return filter_var($text, FILTER_VALIDATE_EMAIL) ? $text : null;
    }

    /** شمارهٔ موبایل ایران: 09xxxxxxxxx (ارقام فارسی هم پذیرفته می‌شود) */
    public static function phone(string $text): ?string
    {
        $text = self::enDigits(self::clean($text));
        $text = preg_replace('/[\s\-()]/', '', $text);
        // +98 یا 0098 → 0
        $text = preg_replace('/^(\+98|0098)/', '0', (string) $text);
        if (preg_match('/^9\d{9}$/', $text)) {
            $text = '0' . $text;
        }
        return preg_match('/^09\d{9}$/', $text) ? $text : null;
    }

    /**
     * تاریخ تولد میلادی به فرمت YYYY-MM-DD (ارقام فارسی و جداکنندهٔ / هم پذیرفته می‌شود).
     * باید تاریخ واقعی و منطقی باشد (سن ۱۰ تا ۱۰۰ سال).
     */
    public static function birthdate(string $text): ?string
    {
        $text = self::enDigits(self::clean($text));
        $text = str_replace(['/', '.'], '-', $text);
        if (!preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $text, $m)) {
            return null;
        }
        [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        if (!checkdate($mo, $d, $y)) {
            return null;
        }
        $year = (int) date('Y');
        if ($y < $year - 100 || $y > $year - 10) {
            return null;
        }
        return sprintf('%04d-%02d-%02d', $y, $mo, $d);
    }
}
