<?php

namespace App\Core;

use App\Models\Setting;

/**
 * شخصی‌سازی ظاهر سایت (رنگ و تم).
 *
 * مدیر فقط دو رنگ اصلی را انتخاب می‌کند: رنگ برند و رنگ تاکیدی.
 * بقیه سایه‌ها (تیره‌تر و روشن‌تر) خودکار از همین دو رنگ ساخته می‌شوند،
 * پس نیازی نیست کاربر با کدهای رنگ ور برود.
 *
 * این کلاس فقط چند خط CSS برای بازنویسی متغیرهای رنگ تولید می‌کند و
 * در <head> صفحه‌های فروشگاه تزریق می‌شود. فایل base.css دست‌نخورده می‌ماند،
 * پس اگر مدیر رنگی انتخاب نکند، دقیقاً همان تم پیش‌فرض نمایش داده می‌شود.
 */
class Theme
{
    /** رنگ‌های پیش‌فرض — باید با مقادیر :root در base.css یکی باشند */
    private const DEFAULT_PRIMARY = '#0B6E4F';
    private const DEFAULT_ACCENT  = '#C2680E';

    /**
     * رنگ برند انتخاب‌شده (یا پیش‌فرض)
     */
    public static function primary(): string
    {
        return self::normalizeHex((string) Setting::get('theme_primary', self::DEFAULT_PRIMARY), self::DEFAULT_PRIMARY);
    }

    /**
     * رنگ تاکیدی انتخاب‌شده (یا پیش‌فرض)
     */
    public static function accent(): string
    {
        return self::normalizeHex((string) Setting::get('theme_accent', self::DEFAULT_ACCENT), self::DEFAULT_ACCENT);
    }

    /**
     * آیا مدیر رنگی غیر از پیش‌فرض انتخاب کرده؟
     * اگر نه، اصلاً نیازی به تزریق CSS نیست.
     */
    public static function isCustom(): bool
    {
        return strcasecmp(self::primary(), self::DEFAULT_PRIMARY) !== 0
            || strcasecmp(self::accent(), self::DEFAULT_ACCENT) !== 0;
    }

    /**
     * خطوط CSS برای بازنویسی متغیرهای رنگ.
     * خالی برمی‌گردد اگر رنگ سفارشی نباشد.
     */
    public static function inlineStyle(): string
    {
        if (!self::isCustom()) {
            return '';
        }

        $primary = self::primary();
        $accent  = self::accent();

        $vars = [
            '--brand'       => $primary,
            '--brand-dark'  => self::darken($primary, 0.12),
            '--brand-soft'  => self::mixWhite($primary, 0.88),
            '--accent'      => $accent,
            '--accent-soft' => self::mixWhite($accent, 0.90),
            '--ok'          => $primary,
            '--ok-soft'     => self::mixWhite($primary, 0.88),
        ];

        $lines = [];
        foreach ($vars as $name => $value) {
            $lines[] = $name . ':' . $value;
        }

        return ':root{' . implode(';', $lines) . '}';
    }

    // ------------------------------------------------------------------
    //  ابزار کار با رنگ
    // ------------------------------------------------------------------

    /**
     * یک رشته رنگ را به شکل #RRGGBB معتبر برمی‌گرداند،
     * وگرنه رنگ جایگزین را می‌دهد (جلوی تزریق مقدار خراب در CSS را می‌گیرد).
     */
    public static function normalizeHex(string $hex, string $fallback): string
    {
        $hex = trim($hex);

        // شکل کوتاه #abc را به #aabbcc باز می‌کنیم
        if (preg_match('/^#?([0-9a-fA-F]{3})$/', $hex, $m)) {
            $c = $m[1];
            return '#' . strtolower($c[0] . $c[0] . $c[1] . $c[1] . $c[2] . $c[2]);
        }

        if (preg_match('/^#?([0-9a-fA-F]{6})$/', $hex, $m)) {
            return '#' . strtolower($m[1]);
        }

        return $fallback;
    }

    /** تیره کردن رنگ به اندازه‌ای بین ۰ تا ۱ */
    private static function darken(string $hex, float $amount): string
    {
        [$r, $g, $b] = self::toRgb($hex);
        $f = max(0.0, 1.0 - $amount);

        return self::toHex([
            (int) round($r * $f),
            (int) round($g * $f),
            (int) round($b * $f),
        ]);
    }

    /** روشن کردن رنگ با ترکیب با سفید ($whiteRatio نسبت سفید، بین ۰ تا ۱) */
    private static function mixWhite(string $hex, float $whiteRatio): string
    {
        [$r, $g, $b] = self::toRgb($hex);
        $w = min(1.0, max(0.0, $whiteRatio));

        return self::toHex([
            (int) round($r + (255 - $r) * $w),
            (int) round($g + (255 - $g) * $w),
            (int) round($b + (255 - $b) * $w),
        ]);
    }

    /** #RRGGBB → [r, g, b] */
    private static function toRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /** [r, g, b] → #RRGGBB */
    private static function toHex(array $rgb): string
    {
        return sprintf('#%02x%02x%02x',
            max(0, min(255, $rgb[0])),
            max(0, min(255, $rgb[1])),
            max(0, min(255, $rgb[2]))
        );
    }
}
