<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Upload;
use App\Models\Setting;

/**
 * بنر تبلیغاتی صفحه اصلی.
 *
 * یک بنر تصویری با لینک اختیاری که در صفحه اصلی (زیر بخش بالایی) نمایش داده
 * می‌شود. تصویر دسکتاپ و موبایل جدا قابل تنظیم است. مقادیر در جدول settings
 * ذخیره می‌شوند، پس نیازی به تغییر ساختار دیتابیس نیست.
 */
class AdBannerController extends Controller
{
    /** پوشه ذخیره تصویر بنر داخل uploads */
    private const DIR = 'banners';

    public function index(): void
    {
        Auth::requireAdmin();

        $this->view('admin/ad-banner/index', [
            'title'   => 'بنر تبلیغاتی',
            'enabled' => Setting::getBool('ad_banner_enabled', false),
            'image'   => Setting::get('ad_banner_image', ''),
            'mobile'  => Setting::get('ad_banner_image_mobile', ''),
            'link'    => Setting::get('ad_banner_link', ''),
            'errors'  => Flash::errors(),
        ], 'admin');
    }

    public function update(): void
    {
        Auth::requireAdmin();
        Csrf::check();

        Setting::set('ad_banner_enabled', $this->boolInput('ad_banner_enabled') ? '1' : '0', 'banner');
        Setting::set('ad_banner_link', trim((string) $this->input('ad_banner_link')), 'banner');

        try {
            $this->handleImage('ad_image', 'ad_banner_image');
            $this->handleImage('ad_image_mobile', 'ad_banner_image_mobile');
        } catch (\RuntimeException $e) {
            $this->backWithErrors(['image' => $e->getMessage()], 'admin/ad-banner');
        }

        Flash::success('بنر تبلیغاتی ذخیره شد.');
        redirect('admin/ad-banner');
    }

    // ------------------------------------------------------------------

    /**
     * آپلود یا حذف یک تصویر بنر و ذخیره نامش در تنظیمات.
     */
    private function handleImage(string $field, string $key): void
    {
        $current = (string) Setting::get($key, '');

        if (!empty($_POST['remove_' . $field]) && $current !== '') {
            Upload::delete($current, self::DIR);
            Setting::set($key, '', 'banner');
            return;
        }

        $newName = Upload::image($_FILES[$field] ?? [], self::DIR);
        if ($newName !== null) {
            Setting::set($key, $newName, 'banner');
            if ($current !== '') {
                Upload::delete($current, self::DIR);
            }
        }
    }
}
