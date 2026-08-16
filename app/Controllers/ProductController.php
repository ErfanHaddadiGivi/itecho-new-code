<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Product;

/**
 * صفحه جزئیات محصول.
 */
class ProductController extends Controller
{
    public function show(string $slug): void
    {
        $product = Product::findFullBySlug($slug);

        if ($product === null) {
            $this->notFound('محصول مورد نظر پیدا نشد');
        }

        $productId = (int) $product['id'];

        // شمارنده بازدید — خطای آن نباید صفحه را خراب کند
        Database::run('UPDATE products SET views = views + 1 WHERE id = ?', [$productId]);

        // نظرات تاییدشده
        $reviews = Database::fetchAll(
            "SELECT r.rating, r.title, r.comment, r.is_verified_buyer, r.admin_reply, r.created_at,
                    u.first_name, u.last_name
               FROM reviews r
               JOIN users u ON u.id = r.user_id
              WHERE r.product_id = ? AND r.status = 'approved'
              ORDER BY r.created_at DESC
              LIMIT 20",
            [$productId]
        );

        $this->view('site/product', [
            'title'    => $product['name'] . ' | ایتکو',
            'product'  => $product,
            'reviews'  => $reviews,
            'related'  => Product::related($productId, $product['category_id'] !== null
                            ? (int) $product['category_id'] : null),
            'variantMap' => $this->buildVariantMap($product['variants']),
            'scripts'    => ['product.js'],
        ], 'site');
    }

    /**
     * ساخت داده‌ای که جاوااسکریپت برای نمایش داینامیک قیمت و موجودی لازم دارد.
     *
     * کلید هر Variant، شناسه مقادیر آن به‌صورت مرتب‌شده و با خط تیره است:
     *      "3-9"  →  {price: ..., stock: ...}
     */
    private function buildVariantMap(array $variants): array
    {
        $map = [];

        foreach ($variants as $variant) {
            $valueIds = array_map(
                static fn ($value) => (int) $value['attribute_value_id'],
                $variant['values']
            );

            sort($valueIds);

            $map[implode('-', $valueIds)] = [
                'id'      => (int) $variant['id'],
                'price'   => (int) $variant['price'],
                'compare' => $variant['compare_at_price'] !== null
                                ? (int) $variant['compare_at_price'] : null,
                'stock'   => (int) $variant['stock'],
                'title'   => $variant['title'],
            ];
        }

        return $map;
    }
}
