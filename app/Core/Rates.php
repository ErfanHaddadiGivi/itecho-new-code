<?php

namespace App\Core;

/**
 * نرخ لحظه‌ای ارز از tgju.org، همراه با کش فایلی.
 *
 *  - قیمت‌ها از فیلدهای «ریالی» tgju خوانده و به «تومان» تبدیل می‌شوند (÷۱۰).
 *  - برای کم‌کردن فشار روی منبع و سرعت بیشتر، نتیجه چند دقیقه کش می‌شود.
 *  - اگر دریافت از منبع شکست بخورد، آخرین دادهٔ کش‌شده (با نشان «قدیمی»)
 *    برگردانده می‌شود تا باکس هیچ‌وقت خالی نماند.
 *
 * اگر روزی آدرس یا ساختار tgju عوض شد، فقط ثابت‌های زیر را تنظیم کنید.
 */
class Rates
{
    /** منبع داده — اگر tgju آدرسش را عوض کرد، همین‌جا تغییر دهید */
    private const SOURCE = 'https://call.tgju.org/ajax.json';

    /** مدت اعتبار کش بر حسب ثانیه (اینجا ۵ دقیقه) */
    private const TTL = 300;

    /**
     * هر ۱۰ ریال = ۱ تومان. فیلدهای دلار/درهم در tgju ریالی هستند.
     * اگر روزی اعداد ۱۰ برابر یا یک‌دهم به‌نظر رسید، این عدد را ۱ کنید.
     */
    private const RIAL_TO_TOMAN = 10;

    /** ارزهای موردنظر: کلیدِ ما => [کلید tgju، برچسب فارسی] */
    private const SYMBOLS = [
        'usd' => ['price_dollar_rl', 'دلار'],
        'aed' => ['price_aed',       'درهم'],
    ];

    /** فقط از کش می‌خواند (بدون تماس شبکه) — برای رندر سریع اولیهٔ صفحه */
    public static function peek(): array
    {
        return self::get(false);
    }

    /**
     * دریافت نرخ‌ها.
     *
     * @param bool $allowFetch اگر true و کش منقضی باشد، از منبع می‌گیرد.
     * @return array{ok:bool, items:array, updated_at:int, stale:bool}
     */
    public static function get(bool $allowFetch = true): array
    {
        $cache = self::readCache();
        $fresh = $cache !== null && (time() - (int) $cache['updated_at']) < self::TTL;

        // کش تازه است
        if ($fresh) {
            $cache['stale'] = false;
            return $cache;
        }

        // اجازهٔ دریافت نداریم → کش قدیمی یا خالی
        if (!$allowFetch) {
            if ($cache !== null) {
                $cache['stale'] = true;
                return $cache;
            }
            return self::emptyResult();
        }

        // دریافت تازه از منبع
        $fetched = self::fetchAndParse();
        if ($fetched !== null) {
            $fetched['updated_at'] = time();
            $fetched['stale'] = false;
            self::writeCache($fetched);
            return $fetched;
        }

        // دریافت شکست خورد → آخرین کش (قدیمی) یا خالی
        if ($cache !== null) {
            $cache['stale'] = true;
            return $cache;
        }
        return self::emptyResult();
    }

    // ------------------------------------------------------------------

    private static function fetchAndParse(): ?array
    {
        $raw = self::httpGet(self::SOURCE);
        if ($raw === null) {
            return null;
        }
        return self::parse($raw);
    }

    /**
     * تبدیل پاسخ JSON منبع به ساختار تمیز و تومانی.
     * (public برای این‌که بشود مستقل تست کرد.)
     */
    public static function parse(string $raw): ?array
    {
        $json    = json_decode($raw, true);
        $current = is_array($json) ? ($json['current'] ?? null) : null;
        if (!is_array($current)) {
            return null;
        }

        $items = [];
        foreach (self::SYMBOLS as $key => [$src, $label]) {
            $row = $current[$src] ?? null;
            if (!is_array($row) || !isset($row['p'])) {
                continue;
            }

            $rial = (int) preg_replace('/[^0-9]/', '', (string) $row['p']);
            if ($rial <= 0) {
                continue;
            }

            $dt = (string) ($row['dt'] ?? '');

            $items[] = [
                'key'    => $key,
                'label'  => $label,
                'toman'  => intdiv($rial, self::RIAL_TO_TOMAN),
                'change' => isset($row['dp']) ? (float) str_replace(',', '', (string) $row['dp']) : 0.0,
                'dir'    => $dt === 'low' ? 'down' : ($dt === 'high' ? 'up' : 'none'),
                'time'   => (string) ($row['t'] ?? ''),
            ];
        }

        return $items === [] ? null : ['ok' => true, 'items' => $items];
    }

    private static function httpGet(string $url): ?string
    {
        // روش ارجح روی هاست اشتراکی: cURL
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; ItechoBot/1.0)',
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return (is_string($body) && $body !== '' && $code >= 200 && $code < 300) ? $body : null;
        }

        // جایگزین: file_get_contents
        if (ini_get('allow_url_fopen')) {
            $ctx = stream_context_create(['http' => [
                'method'  => 'GET',
                'timeout' => 8,
                'header'  => "User-Agent: Mozilla/5.0 (compatible; ItechoBot/1.0)\r\nAccept: application/json\r\n",
            ]]);
            $body = @file_get_contents($url, false, $ctx);
            return (is_string($body) && $body !== '') ? $body : null;
        }

        return null;
    }

    private static function cacheFile(): string
    {
        return ROOT_PATH . '/tmp/rates.json';
    }

    private static function readCache(): ?array
    {
        $file = self::cacheFile();
        if (!is_file($file)) {
            return null;
        }
        $data = json_decode((string) @file_get_contents($file), true);
        return (is_array($data) && isset($data['updated_at'])) ? $data : null;
    }

    private static function writeCache(array $data): void
    {
        $file = self::cacheFile();
        $dir  = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    private static function emptyResult(): array
    {
        return ['ok' => false, 'items' => [], 'updated_at' => 0, 'stale' => true];
    }
}
