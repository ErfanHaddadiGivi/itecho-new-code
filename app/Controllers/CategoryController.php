<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Paginator;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;

/**
 * صفحه دسته‌بندی به همراه نوار فیلتر.
 */
class CategoryController extends Controller
{
    public function show(string $slug): void
    {
        $category = Category::findBySlug($slug);

        if ($category === null || (int) $category['is_active'] === 0) {
            $this->notFound('دسته‌بندی مورد نظر پیدا نشد');
        }

        $categoryId  = (int) $category['id'];
        $categoryIds = Category::idWithChildren($categoryId);

        $filters = $this->readFilters($categoryIds);
        $sort    = (string) $this->input('sort', 'newest');

        $perPage   = max(4, Setting::getInt('products_per_page', 12));
        $total     = Product::countFiltered($filters);
        $paginator = new Paginator($total, $perPage, $this->intInput('page', 1));

        $products = Product::filter($filters, $paginator->limit(), $paginator->offset(), $sort);

        // دسته والد برای مسیر راهنما (breadcrumb)
        $parent = $category['parent_id'] !== null
            ? Category::find((int) $category['parent_id'])
            : null;

        $this->view('site/category', [
            'title'      => $category['name'] . ' | ایتکو',
            'category'   => $category,
            'parent'     => $parent,
            'children'   => Category::children($categoryId),
            'products'   => $products,
            'paginator'  => $paginator,
            'total'      => $total,
            'sort'       => $sort,
            'brands'     => Product::brandsInCategories($categoryIds),
            'attributes' => Attribute::filterableForCategories($categoryIds),
            'priceRange' => Product::priceRange(['category_ids' => $categoryIds]),
            'active'     => $filters,
        ], 'site');
    }

    /**
     * خواندن فیلترها از آدرس صفحه.
     * همه مقادیر به عدد تبدیل می‌شوند تا چیزی جز عدد وارد کوئری نشود.
     */
    private function readFilters(array $categoryIds): array
    {
        return [
            'category_ids'     => $categoryIds,
            'brand_ids'        => array_filter(array_map('intval', (array) ($_GET['brand'] ?? []))),
            'attribute_values' => array_filter(array_map('intval', (array) ($_GET['attr'] ?? []))),
            'condition'        => in_array($_GET['condition'] ?? '', ['new', 'used'], true)
                                    ? $_GET['condition'] : '',
            'min_price'        => (int) en_digits((string) ($_GET['min_price'] ?? '')),
            'max_price'        => (int) en_digits((string) ($_GET['max_price'] ?? '')),
            'in_stock'         => !empty($_GET['in_stock']) ? 1 : 0,
        ];
    }
}
