<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Upload;

/**
 * آپلود رسانه برای «ویرایشگر توضیحات» — درج عکس و ویدیو داخل متن.
 *
 * این نقطه پایانی با AJAX از فرم محصول صدا زده می‌شود و همیشه JSON برمی‌گرداند
 * (نه ریدایرکت) تا جاوااسکریپت بتواند آدرس فایل را بگیرد و در متن درج کند.
 */
class MediaController extends Controller
{
    private const DIR = 'content';

    public function upload(): void
    {
        // به‌جای redirect (که پاسخ HTML می‌دهد) اینجا JSON خطا می‌دهیم
        if (!Auth::isAdmin()) {
            $this->json(['ok' => false, 'error' => 'دسترسی ندارید. دوباره وارد شوید.'], 403);
        }

        if (!Csrf::isValid($_POST['_csrf_token'] ?? null)) {
            $this->json(['ok' => false, 'error' => 'نشست منقضی شده است. صفحه را دوباره باز کنید.'], 419);
        }

        $file = $_FILES['file'] ?? [];
        $kind = ($_POST['kind'] ?? '') === 'video' ? 'video' : 'image';

        try {
            if ($kind === 'video') {
                $name = Upload::video($file, self::DIR);
            } else {
                $name = Upload::image($file, self::DIR);
            }
        } catch (\RuntimeException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        if ($name === null) {
            $this->json(['ok' => false, 'error' => 'فایلی انتخاب نشده است.'], 422);
        }

        $this->json([
            'ok'   => true,
            'type' => $kind,
            'url'  => url('uploads/' . self::DIR . '/' . $name),
        ]);
    }
}
