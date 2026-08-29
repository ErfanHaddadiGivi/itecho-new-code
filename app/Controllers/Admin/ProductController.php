<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Paginator;
use App\Core\Upload;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

/**
 * مدیریت محصولات در پنل.
 *
 * این کنترلر سه بخش به‌هم‌پیوسته را با هم ذخیره می‌کند:
 *   ۱. اطلاعات اصلی محصول
 *   ۲. تصاویر (اصلی + گالری)
 *   ۳. مشخصات فنی و Variantها
 */
class ProductController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): void
    {
        Auth::requireAdmin();

        $filters = [
            'q'           => (string) $this->input('q'),
            'category_id' => $this->intInput('category_id'),
            'brand_id'    => $this->intInput('brand_id'),
            'status'      => (string) $this->input('status'),
        ];

        $total     = Product::adminCount($filters);
        $paginator = new Paginator($total, self::PER_PAGE, $this->intInput('page', 1));

        $this->view('admin/products/index', [
            'title'      => 'محصولات',
            'products'   => Product::adminList($filters, $paginator->limit(), $paginator->offset()),
            'paginator'  => $paginator,
            'total'      => $total,
            'filters'    => $filters,
            'categories' => Category::adminTree(),
            'brands'     => Brand::all('sort_order, name'),
        ], 'admin');
    }

    public function create(): void
    {
        Auth::requireAdmin();

        $this->view('admin/products/form', [
            'title'      => 'افزودن محصول',
            'product'    => null,
            'images'     => [],
            'specs'      => [],
            'variants'   => [],
            'categories' => Category::adminTree(),
            'brands'     => Brand::all('sort_order, name'),
            'attributes' => Attribute::allWithValues(),
            'errors'     => Flash::errors(),
            'scripts'    => ['admin-product.js', 'admin-editor.js'],
        ], 'admin');
    }

    public function edit(string $id): void
    {
        Auth::requireAdmin();

        $productId = (int) $id;
        $product   = Product::find($productId);

        if ($product === null) {
            $this->notFound('محصول مورد نظر پیدا نشد');
        }

        $this->view('admin/products/form', [
            'title'      => 'ویرایش محصول',
            'product'    => $product,
            'images'     => Database::fetchAll(
                'SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$productId]
            ),
            'specs'      => Database::fetchAll(
                'SELECT * FROM product_specs WHERE product_id = ? ORDER BY sort_order, id', [$productId]
            ),
            'variants'   => Product::variantsOf($productId),
            'categories' => Category::adminTree(),
            'brands'     => Brand::all('sort_order, name'),
            'attributes' => Attribute::allWithValues(),
            'errors'     => Flash::errors(),
            'scripts'    => ['admin-product.js', 'admin-editor.js'],
        ], 'admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $data   = $this->readForm();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->backWithErrors($errors, 'admin/products/create');
        }

        $data['slug'] = Product::uniqueSlug($data['slug'] !== '' ? $data['slug'] : $data['name']);

        try {
            $mainImage = Upload::image($_FILES['main_image'] ?? []);
        } catch (\RuntimeException $e) {
            $this->backWithErrors(['main_image' => $e->getMessage()], 'admin/products/create');
        }

        if ($mainImage !== null) {
            $data['main_image'] = $mainImage;
        }

        Database::beginTransaction();

        try {
            $productId = Product::create($data);

            $this->saveGallery($productId);
            $this->saveSpecs($productId);
            $this->saveVariants($productId);

            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            Upload::delete($mainImage);
            throw $e;
        }

        Flash::success('محصول «' . $data['name'] . '» اضافه شد.');
        redirect('admin/products/' . $productId . '/edit');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $productId = (int) $id;
        $product   = Product::find($productId);

        if ($product === null) {
            $this->notFound('محصول مورد نظر پیدا نشد');
        }

        $data   = $this->readForm();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->backWithErrors($errors, 'admin/products/' . $productId . '/edit');
        }

        $data['slug'] = Product::uniqueSlug(
            $data['slug'] !== '' ? $data['slug'] : $data['name'],
            $productId
        );

        try {
            $newMainImage = Upload::image($_FILES['main_image'] ?? []);
        } catch (\RuntimeException $e) {
            $this->backWithErrors(['main_image' => $e->getMessage()],
                                  'admin/products/' . $productId . '/edit');
        }

        $oldMainImage = $product['main_image'];

        if ($newMainImage !== null) {
            $data['main_image'] = $newMainImage;
        }

        Database::beginTransaction();

        try {
            Product::updateById($productId, $data);

            $this->deleteSelectedImages($productId);
            $this->saveGallery($productId);
            $this->saveSpecs($productId);
            $this->saveVariants($productId);

            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            Upload::delete($newMainImage);
            throw $e;
        }

        // تصویر اصلی قبلی فقط بعد از موفقیت کامل حذف می‌شود
        if ($newMainImage !== null) {
            Upload::delete($oldMainImage);
        }

        Flash::success('تغییرات ذخیره شد.');
        redirect('admin/products/' . $productId . '/edit');
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $productId = (int) $id;
        $product   = Product::find($productId);

        if ($product === null) {
            $this->notFound('محصول مورد نظر پیدا نشد');
        }

        // محصولی که در سفارشی ثبت شده حذف نمی‌شود تا سابقه فاکتورها سالم بماند
        if (Product::hasOrders($productId)) {
            Flash::error('این محصول در سفارش‌های ثبت‌شده وجود دارد و قابل حذف نیست. '
                       . 'می‌توانید آن را غیرفعال کنید تا در سایت نمایش داده نشود.');
            redirect('admin/products');
        }

        Product::deleteWithFiles($productId);

        Flash::success('محصول «' . $product['name'] . '» حذف شد.');
        redirect('admin/products');
    }

    // ==================================================================
    //  خواندن و اعتبارسنجی فرم
    // ==================================================================

    private function readForm(): array
    {
        $categoryId = $this->intInput('category_id');
        $brandId    = $this->intInput('brand_id');
        $compare    = $this->intInput('compare_at_price');

        return [
            'name'              => (string) $this->input('name'),
            'slug'              => (string) $this->input('slug'),
            'sku'               => ((string) $this->input('sku')) ?: null,
            'category_id'       => $categoryId > 0 ? $categoryId : null,
            'brand_id'          => $brandId > 0 ? $brandId : null,
            'condition_type'    => $this->input('condition_type') === 'used' ? 'used' : 'new',
            'price'             => max(0, $this->intInput('price')),
            'compare_at_price'  => $compare > 0 ? $compare : null,
            'stock'             => max(0, $this->intInput('stock')),
            'short_description' => (string) $this->input('short_description'),
            'description'       => (string) ($_POST['description'] ?? ''),
            'warranty'          => ((string) $this->input('warranty')) ?: null,
            'serial_number'     => ((string) $this->input('serial_number')) ?: null,
            'meta_description'  => (string) $this->input('meta_description'),
            'is_active'         => $this->boolInput('is_active'),
            'is_featured'       => $this->boolInput('is_featured'),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors['name'] = 'نام محصول را وارد کنید.';
        } elseif (mb_strlen($data['name']) > 255) {
            $errors['name'] = 'نام محصول خیلی طولانی است.';
        }

        if ($data['category_id'] === null) {
            $errors['category_id'] = 'دسته‌بندی محصول را انتخاب کنید.';
        }

        if ($data['compare_at_price'] !== null && $data['compare_at_price'] <= $data['price']) {
            $errors['compare_at_price'] = 'قیمت قبل از تخفیف باید بیشتر از قیمت فروش باشد.';
        }

        // SKU باید یکتا باشد
        if ($data['sku'] !== null) {
            $exists = Database::fetchValue(
                'SELECT COUNT(*) FROM products WHERE sku = ? AND id <> ?',
                [$data['sku'], $this->intInput('id')]
            );

            if ((int) $exists > 0) {
                $errors['sku'] = 'این کد کالا قبلاً برای محصول دیگری ثبت شده است.';
            }
        }

        return $errors;
    }

    // ==================================================================
    //  تصاویر
    // ==================================================================

    private function saveGallery(int $productId): void
    {
        if (empty($_FILES['gallery']['name'][0])) {
            return;
        }

        $names = Upload::images($_FILES['gallery']);

        $sortOrder = (int) Database::fetchValue(
            'SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = ?',
            [$productId]
        );

        foreach ($names as $name) {
            Database::insert('product_images', [
                'product_id' => $productId,
                'image'      => $name,
                'sort_order' => ++$sortOrder,
            ]);
        }
    }

    private function deleteSelectedImages(int $productId): void
    {
        $ids = array_filter(array_map('intval', (array) ($_POST['delete_images'] ?? [])));

        foreach ($ids as $imageId) {
            // شرط product_id تضمین می‌کند تصویر محصول دیگری حذف نشود
            $image = Database::fetch(
                'SELECT image FROM product_images WHERE id = ? AND product_id = ? LIMIT 1',
                [$imageId, $productId]
            );

            if ($image === null) {
                continue;
            }

            Database::delete('product_images', 'id = ?', [$imageId]);
            Upload::delete($image['image']);
        }
    }

    // ==================================================================
    //  مشخصات فنی
    // ==================================================================

    private function saveSpecs(int $productId): void
    {
        $keys   = (array) ($_POST['spec_key'] ?? []);
        $values = (array) ($_POST['spec_value'] ?? []);

        // ساده‌ترین راه: همه را پاک کن و از نو بنویس
        Database::delete('product_specs', 'product_id = ?', [$productId]);

        $sortOrder = 0;

        foreach ($keys as $i => $key) {
            $key   = trim((string) $key);
            $value = trim((string) ($values[$i] ?? ''));

            if ($key === '' || $value === '') {
                continue;
            }

            Database::insert('product_specs', [
                'product_id' => $productId,
                'spec_key'   => mb_substr($key, 0, 120),
                'spec_value' => mb_substr($value, 0, 500),
                'sort_order' => ++$sortOrder,
            ]);
        }
    }

    // ==================================================================
    //  Variantها
    // ==================================================================

    /**
     * ذخیره Variantها.
     *
     * فرم برای هر ترکیب یک ردیف می‌فرستد:
     *   variant_id[]      شناسه ردیف موجود (برای ردیف جدید خالی است)
     *   variant_values[]  شناسه مقادیر انتخاب‌شده، جداشده با کاما  مثل «1,9»
     *   variant_price[]، variant_stock[]، variant_sku[]
     */
    private function saveVariants(int $productId): void
    {
        $rows       = (array) ($_POST['variant_values'] ?? []);
        $ids        = (array) ($_POST['variant_id'] ?? []);
        $prices     = (array) ($_POST['variant_price'] ?? []);
        $stocks     = (array) ($_POST['variant_stock'] ?? []);
        $skus       = (array) ($_POST['variant_sku'] ?? []);

        $keptIds = [];

        foreach ($rows as $i => $rawValues) {
            $valueIds = array_filter(array_map('intval', explode(',', (string) $rawValues)));

            if ($valueIds === []) {
                continue;
            }

            $values = Attribute::valuesByIds($valueIds);

            if ($values === []) {
                continue;
            }

            // امضای یکتای ترکیب: «attributeId:valueId» مرتب‌شده
            $pairs = [];
            $labels = [];

            foreach ($values as $value) {
                $pairs[]  = $value['attribute_id'] . ':' . $value['id'];
                $labels[] = $value['value'];
            }

            sort($pairs);

            $payload = [
                'product_id'  => $productId,
                'variant_key' => implode('|', $pairs),
                'title'       => implode(' / ', $labels),
                'sku'         => trim((string) ($skus[$i] ?? '')) ?: null,
                'price'       => max(0, (int) en_digits((string) ($prices[$i] ?? '0'))),
                'stock'       => max(0, (int) en_digits((string) ($stocks[$i] ?? '0'))),
                'is_active'   => 1,
            ];

            $existingId = (int) ($ids[$i] ?? 0);

            if ($existingId > 0) {
                // شرط product_id جلوی ویرایش Variant محصول دیگری را می‌گیرد
                Database::update('product_variants', $payload,
                                 'id = ? AND product_id = ?', [$existingId, $productId]);
                $variantId = $existingId;
            } else {
                $variantId = Database::insert('product_variants', $payload);
            }

            $keptIds[] = $variantId;

            // مقادیر ویژگی این Variant از نو نوشته می‌شود
            Database::delete('variant_attribute_values', 'variant_id = ?', [$variantId]);

            foreach ($values as $value) {
                Database::insert('variant_attribute_values', [
                    'variant_id'         => $variantId,
                    'attribute_id'       => $value['attribute_id'],
                    'attribute_value_id' => $value['id'],
                ]);
            }
        }

        // Variantهایی که ادمین حذف کرده است
        if ($keptIds === []) {
            Database::delete('product_variants', 'product_id = ?', [$productId]);
        } else {
            $placeholders = implode(',', array_fill(0, count($keptIds), '?'));
            Database::run(
                "DELETE FROM product_variants
                  WHERE product_id = ? AND id NOT IN ({$placeholders})",
                array_merge([$productId], $keptIds)
            );
        }

        Product::syncFromVariants($productId);
        Product::rebuildFilterAttributes($productId);
    }
}
