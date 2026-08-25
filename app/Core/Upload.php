<?php

namespace App\Core;

/**
 * آپلود امن تصویر.
 *
 * نکات امنیتی که اینجا رعایت شده:
 *  ۱. نوع فایل با getimagesize() بررسی می‌شود، نه با پسوند یا هدر ارسالی مرورگر.
 *     (کسی می‌تواند فایل PHP را با نام image.jpg بفرستد.)
 *  ۲. نام فایل کاملاً تصادفی ساخته می‌شود و نام ارسالی کاربر دور ریخته می‌شود.
 *  ۳. پسوند فقط از نوع واقعی تصویر گرفته می‌شود.
 *  ۴. علاوه بر این، اجرای PHP در پوشه uploads با .htaccess خاموش است.
 *
 * اگر افزونه GD در دسترس باشد، عکس‌های خیلی بزرگ کوچک می‌شوند تا سایت سنگین نشود.
 */
class Upload
{
    /** بیشترین حجم مجاز: ۳ مگابایت */
    private const MAX_BYTES = 3 * 1024 * 1024;

    /** بیشترین عرض تصویر؛ عکس‌های بزرگ‌تر کوچک می‌شوند */
    private const MAX_WIDTH = 1200;

    /** نوع‌های مجاز → پسوند */
    private const ALLOWED = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    /** بیشترین حجم مجاز ویدیو: ۲۰ مگابایت (به محدودیت آپلود هاست هم بستگی دارد) */
    private const MAX_VIDEO_BYTES = 20 * 1024 * 1024;

    /** نوع‌های ویدیوی مجاز → پسوند */
    private const ALLOWED_VIDEO = [
        'video/mp4'       => 'mp4',
        'video/webm'      => 'webm',
        'video/quicktime' => 'mp4',
    ];

    /**
     * ذخیره یک ویدیوی آپلودشده (مثلاً ویدیوی پس‌زمینه‌ی صفحه اصلی).
     *
     * @param  array  $file یک عضو از $_FILES
     * @param  string $dir  پوشه مقصد داخل uploads
     * @return string|null  نام فایل ذخیره‌شده، یا null اگر فایلی انتخاب نشده بود
     * @throws \RuntimeException با پیام فارسی قابل نمایش
     */
    public static function video(array $file, string $dir = 'branding'): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        self::assertNoUploadError($file['error']);

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('فایل ارسالی معتبر نیست.');
        }

        if ($file['size'] > self::MAX_VIDEO_BYTES) {
            throw new \RuntimeException('حجم ویدیو نباید بیشتر از ۲۰ مگابایت باشد.');
        }

        // نوع واقعی فایل با finfo بررسی می‌شود، نه با پسوند یا هدر مرورگر
        $mime = false;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $mime = mime_content_type($file['tmp_name']);
        }

        if ($mime === false || !isset(self::ALLOWED_VIDEO[$mime])) {
            throw new \RuntimeException('فقط ویدیو با فرمت MP4 یا WebM مجاز است.');
        }

        $extension = self::ALLOWED_VIDEO[$mime];
        $folder    = ROOT_PATH . '/uploads/' . $dir;

        if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
            throw new \RuntimeException('پوشه آپلود قابل ساخت نیست. دسترسی پوشه uploads را بررسی کنید.');
        }

        $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $path = $folder . '/' . $name;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new \RuntimeException('ذخیره ویدیو ناموفق بود. دسترسی نوشتن پوشه uploads را بررسی کنید.');
        }

        return $name;
    }

    /**
     * ذخیره یک تصویر آپلودشده.
     *
     * @param  array  $file یک عضو از $_FILES
     * @param  string $dir  پوشه مقصد داخل uploads، مثل 'products'
     * @return string|null  نام فایل ذخیره‌شده، یا null اگر فایلی انتخاب نشده بود
     * @throws \RuntimeException با پیام فارسی قابل نمایش به کاربر
     */
    public static function image(array $file, string $dir = 'products'): ?string
    {
        // کاربر فایلی انتخاب نکرده — خطا نیست
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        self::assertNoUploadError($file['error']);

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \RuntimeException('فایل ارسالی معتبر نیست.');
        }

        if ($file['size'] > self::MAX_BYTES) {
            throw new \RuntimeException('حجم تصویر نباید بیشتر از ۳ مگابایت باشد.');
        }

        // بررسی واقعی بودن تصویر
        $info = @getimagesize($file['tmp_name']);
        if ($info === false || !isset(self::ALLOWED[$info[2]])) {
            throw new \RuntimeException('فقط تصویر با فرمت JPG، PNG یا WebP مجاز است.');
        }

        $extension = self::ALLOWED[$info[2]];
        $folder    = ROOT_PATH . '/uploads/' . $dir;

        if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
            throw new \RuntimeException('پوشه آپلود قابل ساخت نیست. دسترسی پوشه uploads را بررسی کنید.');
        }

        // نام تصادفی — نام اصلی فایل کاربر هرگز استفاده نمی‌شود
        $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $path = $folder . '/' . $name;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new \RuntimeException('ذخیره تصویر ناموفق بود. دسترسی نوشتن پوشه uploads را بررسی کنید.');
        }

        self::shrinkIfTooWide($path, $info[2]);

        return $name;
    }

    /**
     * آپلود چند تصویر (گالری). خروجی: آرایه نام فایل‌ها.
     *
     * ساختار $_FILES برای input چندتایی متفاوت است، اینجا به شکل ساده برمی‌گردد.
     */
    public static function images(array $files, string $dir = 'products'): array
    {
        $saved = [];

        $count = is_array($files['name'] ?? null) ? count($files['name']) : 0;

        for ($i = 0; $i < $count; $i++) {
            $single = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];

            $name = self::image($single, $dir);
            if ($name !== null) {
                $saved[] = $name;
            }
        }

        return $saved;
    }

    /**
     * حذف یک تصویر از روی دیسک
     */
    public static function delete(?string $name, string $dir = 'products'): void
    {
        if (empty($name)) {
            return;
        }

        // فقط نام فایل پذیرفته می‌شود تا کسی نتواند با ../ فایل دیگری را حذف کند
        $name = basename($name);
        $path = ROOT_PATH . '/uploads/' . $dir . '/' . $name;

        if (is_file($path)) {
            @unlink($path);
        }
    }

    // ------------------------------------------------------------------

    private static function assertNoUploadError(int $code): void
    {
        $message = match ($code) {
            UPLOAD_ERR_OK        => null,
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE => 'حجم فایل بیشتر از حد مجاز سرور است.',
            UPLOAD_ERR_PARTIAL   => 'آپلود فایل کامل نشد. دوباره تلاش کنید.',
            UPLOAD_ERR_NO_TMP_DIR,
            UPLOAD_ERR_CANT_WRITE => 'سرور نتوانست فایل را ذخیره کند.',
            UPLOAD_ERR_EXTENSION => 'آپلود توسط تنظیمات سرور متوقف شد.',
            default              => 'خطای نامشخص در آپلود فایل.',
        };

        if ($message !== null) {
            throw new \RuntimeException($message);
        }
    }

    /**
     * کوچک کردن تصویر اگر از حد عریض‌تر باشد.
     * اگر افزونه GD نبود، تصویر دست‌نخورده می‌ماند (سایت از کار نمی‌افتد).
     */
    private static function shrinkIfTooWide(string $path, int $imageType): void
    {
        if (!function_exists('imagecreatetruecolor')) {
            return;
        }

        $info = @getimagesize($path);
        if ($info === false || $info[0] <= self::MAX_WIDTH) {
            return;
        }

        [$width, $height] = $info;
        $newWidth  = self::MAX_WIDTH;
        $newHeight = (int) round($height * ($newWidth / $width));

        $source = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default        => false,
        };

        if ($source === false) {
            return;
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // حفظ شفافیت برای PNG و WebP
        if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_WEBP) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        match ($imageType) {
            IMAGETYPE_JPEG => imagejpeg($resized, $path, 82),
            IMAGETYPE_PNG  => imagepng($resized, $path, 6),
            IMAGETYPE_WEBP => imagewebp($resized, $path, 82),
            default        => null,
        };

        imagedestroy($source);
        imagedestroy($resized);
    }
}
