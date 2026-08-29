<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Upload;
use App\Models\Post;

/**
 * مدیریت مطالب بلاگ گیمینگ.
 */
class PostController extends Controller
{
    private const DIR = 'posts';

    public function index(): void
    {
        Auth::requireAdmin();

        $this->view('admin/posts/index', [
            'title' => 'مجله آیتکو',
            'ready' => Post::tableReady(),
            'posts' => Post::allForAdmin(),
        ], 'admin');
    }

    /**
     * اگر جدول مطالب ساخته نشده باشد، به‌جای خطا کاربر را با پیام راهنمایی می‌کنیم.
     */
    private function ensureTable(): void
    {
        if (!Post::tableReady()) {
            Flash::error('ابتدا فایل database/content-update.sql را در دیتابیس ایمپورت کنید تا بخش مجله فعال شود.');
            redirect('admin/posts');
        }
    }

    public function create(): void
    {
        Auth::requireAdmin();
        $this->ensureTable();

        $this->view('admin/posts/form', [
            'title'  => 'افزودن مطلب',
            'post'   => null,
            'errors' => Flash::errors(),
        ], 'admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::check();
        $this->ensureTable();

        $data   = $this->readForm();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->backWithErrors($errors, 'admin/posts/create');
        }

        $data['slug'] = Post::uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['title']);

        try {
            $cover = Upload::image($_FILES['cover_image'] ?? [], self::DIR);
        } catch (\RuntimeException $e) {
            $this->backWithErrors(['cover_image' => $e->getMessage()], 'admin/posts/create');
        }
        if ($cover !== null) {
            $data['cover_image'] = $cover;
        }

        // زمان انتشار: اگر منتشر شده و زمانی ندارد، همین حالا
        if ($data['is_published'] === 1) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        Post::create($data);

        Flash::success('مطلب «' . $data['title'] . '» اضافه شد.');
        redirect('admin/posts');
    }

    public function edit(string $id): void
    {
        Auth::requireAdmin();
        $this->ensureTable();

        $post = Post::find((int) $id);
        if ($post === null) {
            $this->notFound('مطلب مورد نظر پیدا نشد');
        }

        $this->view('admin/posts/form', [
            'title'  => 'ویرایش مطلب',
            'post'   => $post,
            'errors' => Flash::errors(),
        ], 'admin');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();
        $this->ensureTable();

        $postId = (int) $id;
        $post   = Post::find($postId);
        if ($post === null) {
            $this->notFound('مطلب مورد نظر پیدا نشد');
        }

        $data   = $this->readForm();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->backWithErrors($errors, 'admin/posts/' . $postId . '/edit');
        }

        $data['slug'] = Post::uniqueSlug(
            $data['slug'] !== '' ? $data['slug'] : $data['title'],
            $postId
        );

        // تصویر کاور: آپلود جدید یا حذف با تیک
        try {
            $newCover = Upload::image($_FILES['cover_image'] ?? [], self::DIR);
        } catch (\RuntimeException $e) {
            $this->backWithErrors(['cover_image' => $e->getMessage()], 'admin/posts/' . $postId . '/edit');
        }
        if ($newCover !== null) {
            $data['cover_image'] = $newCover;
        } elseif (!empty($_POST['remove_cover'])) {
            $data['cover_image'] = null;
        }

        // اگر تازه منتشر می‌شود و زمان انتشار ندارد، حالا را ثبت کن
        if ($data['is_published'] === 1 && empty($post['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        Post::updateById($postId, $data);

        if (($newCover !== null || !empty($_POST['remove_cover'])) && !empty($post['cover_image'])) {
            Upload::delete($post['cover_image'], self::DIR);
        }

        Flash::success('تغییرات ذخیره شد.');
        redirect('admin/posts');
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $post = Post::find((int) $id);
        if ($post === null) {
            $this->notFound('مطلب مورد نظر پیدا نشد');
        }

        Post::deleteById((int) $id);
        Upload::delete($post['cover_image'] ?? null, self::DIR);

        Flash::success('مطلب حذف شد.');
        redirect('admin/posts');
    }

    // ------------------------------------------------------------------

    private function readForm(): array
    {
        return [
            'title'            => (string) $this->input('title'),
            'slug'             => (string) $this->input('slug'),
            'excerpt'          => (string) $this->input('excerpt'),
            'meta_title'       => (string) $this->input('meta_title'),
            'meta_description' => (string) $this->input('meta_description'),
            'focus_keyword'    => (string) $this->input('focus_keyword'),
            'content'          => (string) ($_POST['content'] ?? ''),
            'is_published'     => $this->boolInput('is_published'),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        if ($data['title'] === '') {
            $errors['title'] = 'عنوان مطلب را وارد کنید.';
        } elseif (mb_strlen($data['title']) > 191) {
            $errors['title'] = 'عنوان نباید بیشتر از ۱۹۱ کاراکتر باشد.';
        }

        if (mb_strlen($data['excerpt']) > 500) {
            $errors['excerpt'] = 'خلاصه نباید بیشتر از ۵۰۰ کاراکتر باشد.';
        }

        return $errors;
    }
}
