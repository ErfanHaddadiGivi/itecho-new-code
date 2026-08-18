<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Upload;
use App\Models\Banner;

/**
 * مدیریت اسلایدر صفحه اصلی.
 *
 * هر اسلاید یک تصویر، عنوان اختیاری و لینک اختیاری دارد.
 * تصویرها در uploads/banners ذخیره می‌شوند.
 */
class BannerController extends Controller
{
    private const DIR = 'banners';

    public function index(): void
    {
        Auth::requireAdmin();

        $this->view('admin/banners/index', [
            'title'   => 'اسلایدر صفحه اصلی',
            'banners' => Banner::allSlides(),
        ], 'admin');
    }

    public function create(): void
    {
        Auth::requireAdmin();

        $this->view('admin/banners/form', [
            'title'  => 'افزودن اسلاید',
            'banner' => null,
            'errors' => Flash::errors(),
        ], 'admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $data = $this->readForm();

        try {
            $image = Upload::image($_FILES['image'] ?? [], self::DIR);
        } catch (\RuntimeException $e) {
            $this->backWithErrors(['image' => $e->getMessage()], 'admin/banners/create');
        }

        // برای اسلاید جدید تصویر اجباری است
        if ($image === null) {
            $this->backWithErrors(['image' => 'برای اسلاید یک تصویر انتخاب کنید.'], 'admin/banners/create');
        }

        $data['image'] = $image;

        try {
            $data['mobile_image'] = Upload::image($_FILES['mobile_image'] ?? [], self::DIR);
        } catch (\RuntimeException $e) {
            Upload::delete($image, self::DIR);
            $this->backWithErrors(['mobile_image' => $e->getMessage()], 'admin/banners/create');
        }

        Banner::create($data);

        Flash::success('اسلاید اضافه شد.');
        redirect('admin/banners');
    }

    public function edit(string $id): void
    {
        Auth::requireAdmin();

        $banner = Banner::find((int) $id);
        if ($banner === null) {
            $this->notFound('اسلاید مورد نظر پیدا نشد');
        }

        $this->view('admin/banners/form', [
            'title'  => 'ویرایش اسلاید',
            'banner' => $banner,
            'errors' => Flash::errors(),
        ], 'admin');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $bannerId = (int) $id;
        $banner   = Banner::find($bannerId);

        if ($banner === null) {
            $this->notFound('اسلاید مورد نظر پیدا نشد');
        }

        $data = $this->readForm();

        // تصویر اصلی: فقط اگر تصویر جدیدی آپلود شد جایگزین می‌شود
        try {
            $newImage = Upload::image($_FILES['image'] ?? [], self::DIR);
        } catch (\RuntimeException $e) {
            $this->backWithErrors(['image' => $e->getMessage()], 'admin/banners/' . $bannerId . '/edit');
        }

        if ($newImage !== null) {
            $data['image'] = $newImage;
        }

        // تصویر موبایل: آپلود جدید یا حذف با تیک
        try {
            $newMobile = Upload::image($_FILES['mobile_image'] ?? [], self::DIR);
        } catch (\RuntimeException $e) {
            if (isset($newImage)) {
                Upload::delete($newImage, self::DIR);
            }
            $this->backWithErrors(['mobile_image' => $e->getMessage()], 'admin/banners/' . $bannerId . '/edit');
        }

        if ($newMobile !== null) {
            $data['mobile_image'] = $newMobile;
        } elseif (!empty($_POST['remove_mobile_image'])) {
            $data['mobile_image'] = null;
        }

        Banner::updateById($bannerId, $data);

        // تصویرهای قدیمی که جایگزین یا حذف شدند از دیسک پاک می‌شوند
        if (isset($newImage) && $newImage !== null && !empty($banner['image'])) {
            Upload::delete($banner['image'], self::DIR);
        }
        if ((isset($newMobile) && $newMobile !== null || !empty($_POST['remove_mobile_image']))
            && !empty($banner['mobile_image'])) {
            Upload::delete($banner['mobile_image'], self::DIR);
        }

        Flash::success('تغییرات اسلاید ذخیره شد.');
        redirect('admin/banners');
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $banner = Banner::find((int) $id);
        if ($banner === null) {
            $this->notFound('اسلاید مورد نظر پیدا نشد');
        }

        Banner::deleteById((int) $id);
        Upload::delete($banner['image'] ?? null, self::DIR);
        Upload::delete($banner['mobile_image'] ?? null, self::DIR);

        Flash::success('اسلاید حذف شد.');
        redirect('admin/banners');
    }

    // ------------------------------------------------------------------

    private function readForm(): array
    {
        $title = trim((string) $this->input('title'));
        $link  = trim((string) $this->input('link_url'));

        return [
            'title'      => $title !== '' ? $title : null,
            'link_url'   => $link !== '' ? $link : null,
            'position'   => 'slider',
            'sort_order' => $this->intInput('sort_order'),
            'is_active'  => $this->boolInput('is_active'),
        ];
    }
}
