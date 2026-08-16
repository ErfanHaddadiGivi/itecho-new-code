<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Paginator;
use App\Models\Product;
use App\Models\Setting;

/**
 * جستجوی داخلی سایت.
 *
 * با حجم ۱۶۰–۱۷۰ محصول، جستجو با LIKE کاملاً سریع است و نیازی به
 * موتور جستجوی جداگانه یا ایندکس FULLTEXT نیست.
 */
class SearchController extends Controller
{
    public function index(): void
    {
        $term = trim((string) $this->input('q'));

        $products  = [];
        $total     = 0;
        $paginator = new Paginator(0);

        if ($term !== '') {
            $filters = ['q' => $term];

            $perPage   = max(4, Setting::getInt('products_per_page', 12));
            $total     = Product::countFiltered($filters);
            $paginator = new Paginator($total, $perPage, $this->intInput('page', 1));

            $products = Product::filter(
                $filters,
                $paginator->limit(),
                $paginator->offset(),
                (string) $this->input('sort', 'newest')
            );
        }

        $this->view('site/search', [
            'title'     => $term !== '' ? 'جستجو: ' . $term : 'جستجو',
            'term'      => $term,
            'products'  => $products,
            'total'     => $total,
            'paginator' => $paginator,
            'sort'      => (string) $this->input('sort', 'newest'),
        ], 'site');
    }

    /**
     * پیشنهاد جستجوی زنده (AJAX) — هنگام تایپ در نوار جستجو
     */
    public function suggest(): void
    {
        $term = trim((string) $this->input('q'));

        if (mb_strlen($term) < 2) {
            $this->json(['items' => []]);
        }

        $items = [];

        foreach (Product::suggest($term) as $product) {
            $items[] = [
                'name'  => $product['name'],
                'url'   => url('product/' . $product['slug']),
                'price' => money((int) $product['price']),
                'image' => $product['main_image']
                    ? url('uploads/products/' . $product['main_image'])
                    : null,
            ];
        }

        $this->json(['items' => $items]);
    }
}
