<?php

namespace App\Core;

use App\Models\Setting;

/**
 * ویدیوی پس‌زمینه‌ی هر صفحه.
 *
 * برای هر «صفحه» (خانه، مجله، هر دسته‌بندی، هر صفحه‌ی ثابت) می‌توان یک ویدیوی
 * دسکتاپ و یک ویدیوی موبایلِ اختیاری تعیین کرد. مقادیر در جدول settings با
 * پیشوند «pv:» ذخیره می‌شوند، پس نیازی به جدول جداگانه نیست.
 *
 *   pv:home            → ویدیوی دسکتاپِ صفحه اصلی
 *   pv:home:m          → ویدیوی موبایلِ صفحه اصلی
 *   pv:category:mobile → ویدیوی دسته با نامک mobile
 *   pv:page:about      → ویدیوی صفحه‌ی ثابت about
 */
class PageVideo
{
    /** گروه تنظیمات */
    private const GROUP = 'pagevideo';

    /**
     * کلید صفحه را از روی مسیر درخواست می‌سازد (یا null اگر صفحه ویدیوپذیر نیست).
     */
    public static function pageKeyForPath(string $path): ?string
    {
        // حذف کوئری‌استرینگ و پیشوند زیرپوشه‌ی نصب
        $path = strtok($path, '?');
        $base = base_path_uri();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = trim(rawurldecode($path), '/');

        if ($path === '') {
            return 'home';
        }

        $seg = explode('/', $path);

        if ($seg[0] === 'blog' && count($seg) === 1) {
            return 'blog';
        }
        if ($seg[0] === 'category' && count($seg) === 2 && $seg[1] !== '') {
            return 'category:' . $seg[1];
        }
        if ($seg[0] === 'page' && count($seg) === 2 && $seg[1] !== '') {
            return 'page:' . $seg[1];
        }

        return null;
    }

    /** کلید تنظیماتِ ویدیوی دسکتاپ برای یک صفحه */
    public static function settingKey(string $pageKey): string
    {
        return 'pv:' . $pageKey;
    }

    /** ویدیوی دسکتاپ یک صفحه (با سازگاری با تنظیم قدیمی hero_video برای خانه) */
    public static function desktop(string $pageKey): string
    {
        $v = (string) Setting::get(self::settingKey($pageKey), '');
        if ($v === '' && $pageKey === 'home') {
            $v = (string) Setting::get('hero_video', '');
        }
        return $v;
    }

    /** ویدیوی موبایل یک صفحه (اختیاری) */
    public static function mobile(string $pageKey): string
    {
        return (string) Setting::get(self::settingKey($pageKey) . ':m', '');
    }

    /**
     * ویدیوی صفحه‌ی جاری بر اساس مسیر — یا null اگر ویدیویی تنظیم نشده باشد.
     *
     * @return array{key:string,desktop:string,mobile:string}|null
     */
    public static function forPath(string $path): ?array
    {
        $key = self::pageKeyForPath($path);
        if ($key === null) {
            return null;
        }

        $desktop = self::desktop($key);
        if ($desktop === '') {
            return null;
        }

        return ['key' => $key, 'desktop' => $desktop, 'mobile' => self::mobile($key)];
    }

    /**
     * ذخیره‌ی نام ویدیوی یک صفحه.
     */
    public static function set(string $pageKey, string $filename, bool $mobile = false): void
    {
        $key = self::settingKey($pageKey) . ($mobile ? ':m' : '');
        Setting::set($key, $filename, self::GROUP);
    }

    /**
     * فهرست همه‌ی صفحه‌های قابل انتخاب برای مدیر: کلید => برچسب فارسی.
     */
    public static function targets(): array
    {
        $out = [
            'home' => 'صفحه اصلی',
            'blog' => 'مجله آیتکو',
        ];

        foreach (Database::fetchAll("SELECT name, slug FROM categories WHERE parent_id IS NULL ORDER BY sort_order, name") as $c) {
            $out['category:' . $c['slug']] = 'دسته: ' . $c['name'];
        }
        foreach (Database::fetchAll("SELECT title, slug FROM pages ORDER BY sort_order, id") as $p) {
            $out['page:' . $p['slug']] = 'صفحه: ' . $p['title'];
        }

        return $out;
    }

    /**
     * صفحه‌هایی که همین حالا ویدیوی دسکتاپ دارند — برای فهرست پنل.
     *
     * @return array<int,array{key:string,label:string,desktop:string,mobile:string}>
     */
    public static function configured(): array
    {
        $rows = [];
        foreach (self::targets() as $key => $label) {
            $desktop = self::desktop($key);
            if ($desktop !== '') {
                $rows[] = [
                    'key'     => $key,
                    'label'   => $label,
                    'desktop' => $desktop,
                    'mobile'  => self::mobile($key),
                ];
            }
        }
        return $rows;
    }
}
