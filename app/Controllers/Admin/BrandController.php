<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Brand;

/**
 * مدیریت برندها.
 */
class BrandController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $this->view('admin/brands/index', [
            'title'  => 'برندها',
            'brands' => Brand::withProductCount(),
        ], 'admin');
    }

    public function create(): void
    {
        Auth::requireAdmin();

        $this->view('admin/brands/form', [
            'title'  => 'افزودن برند',
            'brand'  => null,
            'errors' => Flash::errors(),
        ], 'admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $data   = $this->readForm();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->backWithErrors($errors, 'admin/brands/create');
        }

        $data['slug'] = Brand::uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['name']);

        Brand::create($data);

        Flash::success('برند «' . $data['name'] . '» اضافه شد.');
        redirect('admin/brands');
    }

    public function edit(string $id): void
    {
        Auth::requireAdmin();

        $brand = Brand::find((int) $id);
        if ($brand === null) {
            $this->notFound('برند مورد نظر پیدا نشد');
        }

        $this->view('admin/brands/form', [
            'title'  => 'ویرایش برند',
            'brand'  => $brand,
            'errors' => Flash::errors(),
        ], 'admin');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $brandId = (int) $id;
        $brand   = Brand::find($brandId);

        if ($brand === null) {
            $this->notFound('برند مورد نظر پیدا نشد');
        }

        $data   = $this->readForm();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->backWithErrors($errors, 'admin/brands/' . $brandId . '/edit');
        }

        $data['slug'] = Brand::uniqueSlug(
            $data['slug'] !== '' ? $data['slug'] : $data['name'],
            $brandId
        );

        Brand::updateById($brandId, $data);

        Flash::success('تغییرات ذخیره شد.');
        redirect('admin/brands');
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $brandId = (int) $id;
        $brand   = Brand::find($brandId);

        if ($brand === null) {
            $this->notFound('برند مورد نظر پیدا نشد');
        }

        // محصولات این برند حذف نمی‌شوند، فقط بدون برند می‌مانند.
        // برای جلوگیری از حذف ناخواسته، اول به ادمین هشدار می‌دهیم.
        if (Brand::hasProducts($brandId)) {
            Flash::error('این برند محصول دارد. ابتدا برند محصولات را تغییر دهید.');
            redirect('admin/brands');
        }

        Brand::deleteById($brandId);

        Flash::success('برند «' . $brand['name'] . '» حذف شد.');
        redirect('admin/brands');
    }

    // ------------------------------------------------------------------

    private function readForm(): array
    {
        return [
            'name'       => (string) $this->input('name'),
            'slug'       => (string) $this->input('slug'),
            'sort_order' => $this->intInput('sort_order'),
            'is_active'  => $this->boolInput('is_active'),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors['name'] = 'نام برند را وارد کنید.';
        } elseif (mb_strlen($data['name']) > 120) {
            $errors['name'] = 'نام برند نباید بیشتر از ۱۲۰ کاراکتر باشد.';
        }

        return $errors;
    }
}
