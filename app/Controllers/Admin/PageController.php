<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Page;

/**
 * ویرایش متن صفحات ثابت (درباره ما، تماس، قوانین و ...).
 *
 * صفحه‌ها از قبل وجود دارند؛ اینجا فقط عنوان و متنشان ویرایش می‌شود
 * (نامک ثابت می‌ماند تا لینک‌های داخل سایت نشکنند).
 */
class PageController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $this->view('admin/pages/index', [
            'title' => 'متن صفحات',
            'pages' => Page::allForAdmin(),
        ], 'admin');
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
}
