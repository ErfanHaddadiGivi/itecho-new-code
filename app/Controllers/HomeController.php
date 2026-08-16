<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Category;

/**
 * صفحه اصلی فروشگاه.
 *
 * در مرحله ۳ بخش محصولات این صفحه کامل می‌شود.
 */
class HomeController extends Controller
{
    public function index(): void
    {
        $featured = Database::fetchAll(
            'SELECT id, name, slug, price, compare_at_price, main_image, condition_type
               FROM products
              WHERE is_active = 1 AND is_featured = 1
              ORDER BY created_at DESC
              LIMIT 8'
        );

        $newest = Database::fetchAll(
            'SELECT id, name, slug, price, compare_at_price, main_image, condition_type
               FROM products
              WHERE is_active = 1
              ORDER BY created_at DESC
              LIMIT 8'
        );

        $banners = Database::fetchAll(
            "SELECT * FROM banners
              WHERE is_active = 1 AND position = 'slider'
              ORDER BY sort_order
              LIMIT 5"
        );

        $this->view('site/home', [
            'title'      => 'ایتکو | فروشگاه موبایل، کامپیوتر و گیمینگ',
            'categories' => Category::mainCategories(),
            'featured'   => $featured,
            'newest'     => $newest,
            'banners'    => $banners,
        ], 'site');
    }
}
