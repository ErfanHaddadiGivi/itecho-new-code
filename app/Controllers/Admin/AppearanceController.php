<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Theme;
use App\Core\Upload;
use App\Models\Setting;

/**
 * شخصی‌سازی ظاهر: رنگ و تم، لوگو و نام سایت.
 *
 * همه مقادیر در جدول settings ذخیره می‌شوند، پس نیازی به تغییر ساختار
 * دیتابیس نیست؛ حتی اگر این ردیف‌ها از قبل نباشند، هنگام ذخیره ساخته می‌شوند.
 */
class AppearanceController extends Controller
{
    /** پوشه ذخیره لوگو و فاوآیکون داخل uploads */
    private const BRAND_DIR = 'branding';

    public function index(): void
    {
        Auth::requireAdmin();

        $this->view('admin/appearance/index', [
            'title'   => 'رنگ و تم، لوگو و نام',
            'primary' => Theme::primary(),
            'accent'  => Theme::accent(),
            'siteName'=> Setting::get('site_name', 'ایتکو'),
            'logo'    => Setting::get('site_logo', ''),
            'favicon' => Setting::get('site_favicon', ''),
            'errors'  => Flash::errors(),
        ], 'admin');
    }

    public function update(): void
    {
        Auth::requireAdmin();
        Csrf::check();

        // --- رنگ‌ها ---
        // مقدار خراب پذیرفته نمی‌شود؛ normalizeHex رنگ فعلی را نگه می‌دارد.
        $primary = Theme::normalizeHex((string) $this->input('theme_primary'), Theme::primary());
        $accent  = Theme::normalizeHex((string) $this->input('theme_accent'), Theme::accent());

        Setting::set('theme_primary', $primary, 'appearance');
        Setting::set('theme_accent', $accent, 'appearance');

        // --- نام سایت ---
        $siteName = trim((string) $this->input('site_name'));
        if ($siteName !== '') {
            Setting::set('site_name', $siteName, 'general');
        }

        // --- لوگو ---
        try {
            $this->handleImage('logo', 'site_logo');
            $this->handleImage('favicon', 'site_favicon');
        } catch (\RuntimeException $e) {
            $this->backWithErrors(['image' => $e->getMessage()], 'admin/appearance');
        }

        Flash::success('ظاهر سایت به‌روزرسانی شد.');
        redirect('admin/appearance');
    }

    // ------------------------------------------------------------------

    /**
     * آپلود (یا حذف) یک تصویر برند و ذخیره نامش در تنظیمات.
     *
     * @param string $field ورودی فایل در فرم
     * @param string $key   کلید تنظیمات
     */
    private function handleImage(string $field, string $key): void
    {
        $current = (string) Setting::get($key, '');

        // درخواست حذف تصویر فعلی
        if (!empty($_POST['remove_' . $field]) && $current !== '') {
            Upload::delete($current, self::BRAND_DIR);
            Setting::set($key, '', 'appearance');
            return;
        }

        $newName = Upload::image($_FILES[$field] ?? [], self::BRAND_DIR);
        if ($newName !== null) {
            Setting::set($key, $newName, 'appearance');
            if ($current !== '') {
                Upload::delete($current, self::BRAND_DIR);
            }
        }
    }
}
