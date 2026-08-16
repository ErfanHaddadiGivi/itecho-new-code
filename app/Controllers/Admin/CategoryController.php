<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Category;

/**
 * مدیریت دسته‌بندی‌ها.
 *
 * چون مگا منو از همین دسته‌بندی‌ها ساخته می‌شود، هر تغییری اینجا
 * مستقیماً روی منوی سایت اثر می‌گذارد.
 */
class CategoryController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $this->view('admin/categories/index', [
            'title'      => 'دسته‌بندی‌ها',
            'categories' => Category::adminTree(),
        ], 'admin');
    }

    public function create(): void
    {
        Auth::requireAdmin();

        $this->view('admin/categories/form', [
            'title'    => 'افزودن دسته‌بندی',
            'category' => null,
            'parents'  => Category::parentOptions(),
            'errors'   => Flash::errors(),
        ], 'admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $data   = $this->readForm();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->backWithErrors($errors, 'admin/categories/create');
        }

        $data['slug'] = Category::uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['name']);

        Category::create($data);

        Flash::success('دسته‌بندی «' . $data['name'] . '» اضافه شد.');
        redirect('admin/categories');
    }

    public function edit(string $id): void
    {
        Auth::requireAdmin();

        $category = Category::find((int) $id);
        if ($category === null) {
            $this->notFound('دسته‌بندی مورد نظر پیدا نشد');
        }

        $this->view('admin/categories/form', [
            'title'    => 'ویرایش دسته‌بندی',
            'category' => $category,
            'parents'  => Category::parentOptions((int) $id),
            'errors'   => Flash::errors(),
        ], 'admin');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $categoryId = (int) $id;
        $category   = Category::find($categoryId);

        if ($category === null) {
            $this->notFound('دسته‌بندی مورد نظر پیدا نشد');
        }

        $data   = $this->readForm();
        $errors = $this->validate($data, $categoryId);

        if ($errors !== []) {
            $this->backWithErrors($errors, 'admin/categories/' . $categoryId . '/edit');
        }

        $data['slug'] = Category::uniqueSlug(
            $data['slug'] !== '' ? $data['slug'] : $data['name'],
            $categoryId
        );

        Category::updateById($categoryId, $data);

        Flash::success('تغییرات ذخیره شد.');
        redirect('admin/categories');
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $categoryId = (int) $id;
        $category   = Category::find($categoryId);

        if ($category === null) {
            $this->notFound('دسته‌بندی مورد نظر پیدا نشد');
        }

        // جلوی حذف‌های خطرناک را با پیام روشن می‌گیریم
        if (Category::hasChildren($categoryId)) {
            Flash::error('این دسته زیر‌دسته دارد. ابتدا زیر‌دسته‌ها را حذف یا جابه‌جا کنید.');
            redirect('admin/categories');
        }

        if (Category::hasProducts($categoryId)) {
            Flash::error('این دسته محصول دارد. ابتدا محصولات را به دسته دیگری منتقل کنید.');
            redirect('admin/categories');
        }

        Category::deleteById($categoryId);

        Flash::success('دسته‌بندی «' . $category['name'] . '» حذف شد.');
        redirect('admin/categories');
    }

    // ------------------------------------------------------------------

    /**
     * خواندن مقادیر فرم
     */
    private function readForm(): array
    {
        $parentId = $this->intInput('parent_id');

        return [
            'parent_id'    => $parentId > 0 ? $parentId : null,
            'name'         => (string) $this->input('name'),
            'slug'         => (string) $this->input('slug'),
            'description'  => (string) $this->input('description'),
            'sort_order'   => $this->intInput('sort_order'),
            'is_active'    => $this->boolInput('is_active'),
            'show_in_menu' => $this->boolInput('show_in_menu'),
        ];
    }

    /**
     * اعتبارسنجی — پیام‌ها به فارسی و قابل فهم برای کاربر
     */
    private function validate(array $data, ?int $exceptId = null): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors['name'] = 'نام دسته‌بندی را وارد کنید.';
        } elseif (mb_strlen($data['name']) > 120) {
            $errors['name'] = 'نام دسته‌بندی نباید بیشتر از ۱۲۰ کاراکتر باشد.';
        }

        // ساختار دو سطحی است: یک زیر‌دسته نمی‌تواند خودش والد داشته باشد
        if ($data['parent_id'] !== null) {
            $parent = Category::find($data['parent_id']);

            if ($parent === null) {
                $errors['parent_id'] = 'دسته والد انتخاب‌شده معتبر نیست.';
            } elseif ($parent['parent_id'] !== null) {
                $errors['parent_id'] = 'ساختار دسته‌بندی دو سطحی است؛ نمی‌توانید زیر‌دسته را والد قرار دهید.';
            } elseif ($exceptId !== null && (int) $data['parent_id'] === $exceptId) {
                $errors['parent_id'] = 'یک دسته نمی‌تواند والد خودش باشد.';
            }
        }

        // اگر دسته‌ای زیر‌دسته دارد، نباید خودش زیر‌دسته شود
        if ($exceptId !== null && $data['parent_id'] !== null && Category::hasChildren($exceptId)) {
            $errors['parent_id'] = 'این دسته خودش زیر‌دسته دارد، پس نمی‌تواند زیرمجموعه دسته دیگری شود.';
        }

        return $errors;
    }
}
