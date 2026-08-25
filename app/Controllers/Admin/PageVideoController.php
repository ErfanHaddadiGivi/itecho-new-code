<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\PageVideo;
use App\Core\Upload;
use App\Models\Setting;

/**
 * مدیریت ویدیوی پس‌زمینه‌ی صفحه‌ها.
 *
 * برای هر صفحه یک ویدیوی دسکتاپ و یک ویدیوی موبایلِ اختیاری قابل تنظیم است.
 */
class PageVideoController extends Controller
{
    private const DIR = 'branding';

    public function index(): void
    {
        Auth::requireAdmin();

        $this->view('admin/page-videos/index', [
            'title'      => 'ویدیوی صفحات',
            'targets'    => PageVideo::targets(),
            'configured' => PageVideo::configured(),
            'fadeSpeed'  => Setting::getInt('video_fade_speed', 90),
            'bandHeight' => Setting::getInt('video_band_height', 56),
        ], 'admin');
    }

    /**
     * ذخیره‌ی تنظیمات نمایش ویدیو: شدت محوشدن و ارتفاع بنر صفحه‌های داخلی.
     */
    public function saveSettings(): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $fade = max(20, min(200, $this->intInput('video_fade_speed', 90)));
        $band = max(25, min(100, $this->intInput('video_band_height', 56)));

        Setting::set('video_fade_speed', (string) $fade, 'pagevideo');
        Setting::set('video_band_height', (string) $band, 'pagevideo');

        Flash::success('تنظیمات نمایش ویدیو ذخیره شد.');
        redirect('admin/page-videos');
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $target  = (string) $this->input('target');
        $targets = PageVideo::targets();

        if (!isset($targets[$target])) {
            Flash::error('صفحه‌ی انتخابی معتبر نیست.');
            redirect('admin/page-videos');
        }

        try {
            $desktop = Upload::video($_FILES['video_desktop'] ?? [], self::DIR);
            $mobile  = Upload::video($_FILES['video_mobile'] ?? [], self::DIR);
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
            redirect('admin/page-videos');
        }

        // برای تنظیم اولیه‌ی یک صفحه، حداقل ویدیوی دسکتاپ لازم است
        if ($desktop === null && PageVideo::desktop($target) === '') {
            Flash::error('برای این صفحه حداقل ویدیوی دسکتاپ را انتخاب کنید.');
            redirect('admin/page-videos');
        }

        if ($desktop !== null) {
            $old = PageVideo::desktop($target);
            PageVideo::set($target, $desktop, false);
            // تنظیم قدیمی خانه هم هم‌راستا شود تا دو مقدار متناقض نماند
            if ($target === 'home') {
                Setting::set('hero_video', $desktop, 'appearance');
            }
            if ($old !== '' && $old !== $desktop) {
                Upload::delete($old, self::DIR);
            }
        }

        if ($mobile !== null) {
            $old = PageVideo::mobile($target);
            PageVideo::set($target, $mobile, true);
            if ($old !== '' && $old !== $mobile) {
                Upload::delete($old, self::DIR);
            }
        }

        Flash::success('ویدیوی «' . $targets[$target] . '» ذخیره شد.');
        redirect('admin/page-videos');
    }

    public function destroy(): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $target = (string) $this->input('target');
        if (!isset(PageVideo::targets()[$target])) {
            Flash::error('صفحه‌ی انتخابی معتبر نیست.');
            redirect('admin/page-videos');
        }

        Upload::delete(PageVideo::desktop($target), self::DIR);
        Upload::delete(PageVideo::mobile($target), self::DIR);

        PageVideo::set($target, '', false);
        PageVideo::set($target, '', true);
        if ($target === 'home') {
            Setting::set('hero_video', '', 'appearance');
        }

        Flash::success('ویدیوی صفحه حذف شد.');
        redirect('admin/page-videos');
    }
}
