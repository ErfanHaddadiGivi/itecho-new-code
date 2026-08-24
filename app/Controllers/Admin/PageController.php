<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Page;

/**
 * مدیریت صفحات ثابت: افزودن صفحه‌ی جدید، ویرایش متن همه‌ی صفحات و حذف.
 *
 * نامک صفحه فقط هنگام ساخت تعیین می‌شود و بعد ثابت می‌ماند تا لینک‌های
 * داخل سایت (مثل /page/about) نشکنند.
 */
class PageController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $this->view('admin/pages/index', [
            'title' => 'صفحات سایت',
            'pages' => Page::allForAdmin(),
        ], 'admin');
    }

    public function create(): void
    {
        Auth::requireAdmin();

        $this->view('admin/pages/form', [
            'title'  => 'افزودن صفحه',
            'page'   => null,
            'errors' => Flash::errors(),
        ], 'admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $title = trim((string) $this->input('title'));
        if ($title === '') {
            $this->backWithErrors(['title' => 'عنوان صفحه را وارد کنید.'], 'admin/pages/create');
        }

        $desiredSlug = trim((string) $this->input('slug'));
        $slug        = Page::uniqueSlug($desiredSlug !== '' ? $desiredSlug : $title);

        Page::create([
            'slug'             => $slug,
            'title'            => $title,
            'content'          => (string) ($_POST['content'] ?? ''),
            'meta_description' => trim((string) $this->input('meta_description')),
            'is_active'        => $this->boolInput('is_active'),
            'sort_order'       => $this->intInput('sort_order'),
        ]);

        Flash::success('صفحه‌ی «' . $title . '» ساخته شد.');
        redirect('admin/pages');
    }

    public function edit(string $id): void
    {
        Auth::requireAdmin();

        $page = Page::find((int) $id);
        if ($page === null) {
            $this->notFound('صفحه مورد نظر پیدا نشد');
        }

        $this->view('admin/pages/form', [
            'title'  => 'ویرایش صفحه: ' . $page['title'],
            'page'   => $page,
            'errors' => Flash::errors(),
        ], 'admin');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $pageId = (int) $id;
        $page   = Page::find($pageId);
        if ($page === null) {
            $this->notFound('صفحه مورد نظر پیدا نشد');
        }

        $title = trim((string) $this->input('title'));
        if ($title === '') {
            $this->backWithErrors(['title' => 'عنوان صفحه را وارد کنید.'], 'admin/pages/' . $pageId . '/edit');
        }

        Page::updateById($pageId, [
            'title'            => $title,
            'content'          => (string) ($_POST['content'] ?? ''),
            'meta_description' => trim((string) $this->input('meta_description')),
            'is_active'        => $this->boolInput('is_active'),
        ]);

        Flash::success('متن صفحه ذخیره شد.');
        redirect('admin/pages');
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $page = Page::find((int) $id);
        if ($page === null) {
            $this->notFound('صفحه مورد نظر پیدا نشد');
        }

        Page::deleteById((int) $id);

        Flash::success('صفحه‌ی «' . $page['title'] . '» حذف شد.');
        redirect('admin/pages');
    }
}
