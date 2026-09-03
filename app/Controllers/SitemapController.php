<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

/**
 * نقشه‌ی سایت (sitemap.xml) برای موتورهای جستجو.
 *
 * آدرس‌های صفحه اصلی، دسته‌بندی‌ها، محصولات فعال، مطالب منتشرشده و
 * صفحات ثابت را به‌صورت XML استاندارد فهرست می‌کند.
 */
class SitemapController extends Controller
{
    public function index(): void
    {
        $origin = $this->origin();
        $urls   = [];

        $add = function (string $path, ?string $lastmod = null, string $freq = 'weekly', string $priority = '0.6') use (&$urls, $origin) {
            $urls[] = [
                'loc'      => $origin . url($path),
                'lastmod'  => $lastmod ? date('Y-m-d', strtotime($lastmod)) : null,
                'freq'     => $freq,
                'priority' => $priority,
            ];
        };

        // صفحه اصلی و مجله
        $add('', null, 'daily', '1.0');
        $add('blog', null, 'daily', '0.7');

        // دسته‌بندی‌ها
        foreach ($this->rows("SELECT slug, updated_at FROM categories WHERE is_active = 1 ORDER BY sort_order") as $c) {
            $add('category/' . $c['slug'], $c['updated_at'] ?? null, 'weekly', '0.7');
        }

        // محصولات فعال
        foreach ($this->rows("SELECT slug, updated_at FROM products WHERE is_active = 1 ORDER BY updated_at DESC") as $p) {
            $add('product/' . $p['slug'], $p['updated_at'] ?? null, 'weekly', '0.8');
        }

        // مطالب منتشرشده
        foreach ($this->rows("SELECT slug, updated_at FROM posts WHERE is_published = 1 ORDER BY published_at DESC") as $post) {
            $add('blog/' . $post['slug'], $post['updated_at'] ?? null, 'monthly', '0.6');
        }

        // صفحات ثابت
        foreach ($this->rows("SELECT slug, updated_at FROM pages WHERE is_active = 1") as $page) {
            $add('page/' . $page['slug'], $page['updated_at'] ?? null, 'monthly', '0.4');
        }

        header('Content-Type: application/xml; charset=utf-8');

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            if ($u['lastmod']) {
                $xml .= "    <lastmod>" . $u['lastmod'] . "</lastmod>\n";
            }
            $xml .= "    <changefreq>" . $u['freq'] . "</changefreq>\n";
            $xml .= "    <priority>" . $u['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= "</urlset>\n";

        echo $xml;
    }

    /**
     * robots.txt — به موتورهای جستجو اجازه‌ی خزیدن می‌دهد، پنل و سبد را می‌بندد،
     * و آدرس نقشه‌ی سایت را معرفی می‌کند.
     */
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');

        echo "User-agent: *\n";
        echo "Disallow: /admin\n";
        echo "Disallow: /cart\n";
        echo "Disallow: /checkout\n";
        echo "Disallow: /account\n";
        echo "\n";
        echo "Sitemap: " . $this->origin() . url('sitemap.xml') . "\n";
    }

    /**
     * اجرای امن یک کوئری برای نقشه‌ی سایت.
     *
     * اگر جدولی هنوز ساخته نشده باشد (مثلاً مایگریشن مطالب/بلاگ روی هاست
     * اجرا نشده)، به‌جای خطای ۵۰۰ روی /sitemap.xml، آن بخش خالی می‌ماند و
     * بقیه‌ی نقشه‌ی سایت سالم تولید می‌شود.
     */
    private function rows(string $sql): array
    {
        try {
            return Database::fetchAll($sql);
        } catch (\PDOException $e) {
            return [];
        }
    }

    private function origin(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? '';
        return $host !== '' ? $scheme . '://' . $host : '';
    }
}
