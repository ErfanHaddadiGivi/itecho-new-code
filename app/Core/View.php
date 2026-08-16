<?php

namespace App\Core;

/**
 * نمایش قالب‌ها.
 *
 * قالب‌ها فایل PHP ساده در پوشه app/Views هستند.
 * محتوای هر قالب داخل یک Layout (چارچوب کلی صفحه) قرار می‌گیرد.
 */
class View
{
    /**
     * @param string $template نام فایل قالب بدون .php  مثل 'admin/categories/index'
     * @param array  $data     متغیرهایی که در قالب در دسترس خواهند بود
     * @param string $layout   نام چارچوب: 'site' | 'admin' | 'blank'
     */
    public static function render(string $template, array $data = [], string $layout = 'site'): void
    {
        $content = self::capture($template, $data);

        // عنوان صفحه برای تگ <title>
        $title = $data['title'] ?? 'ایتکو';

        $layoutFile = APP_PATH . '/Views/layouts/' . $layout . '.php';

        if (!is_file($layoutFile)) {
            echo $content;
            return;
        }

        require $layoutFile;
    }

    /**
     * اجرای یک قالب و گرفتن خروجی آن به صورت رشته
     */
    public static function capture(string $template, array $data = []): string
    {
        $file = APP_PATH . '/Views/' . $template . '.php';

        if (!is_file($file)) {
            throw new \RuntimeException("قالب پیدا نشد: {$template}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    /**
     * قرار دادن یک قالب کوچک داخل قالب دیگر (مثل نوار کناری یا منو)
     */
    public static function partial(string $template, array $data = []): void
    {
        echo self::capture($template, $data);
    }
}
