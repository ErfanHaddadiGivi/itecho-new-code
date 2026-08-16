<?php

namespace App\Models;

use App\Core\Database;

/**
 * محصولات — خواندن، فیلتر کردن و مدیریت Variantها.
 */
class Product extends Model
{
    protected static string $table = 'products';

    /** گزینه‌های مجاز مرتب‌سازی — ورودی کاربر هرگز مستقیم داخل SQL نمی‌رود */
    private const SORT_OPTIONS = [
        'newest'    => 'p.created_at DESC',
        'cheapest'  => 'p.price ASC',
        'expensive' => 'p.price DESC',
        'popular'   => 'p.sold_count DESC',
        'rating'    => 'p.rating_avg DESC',
    ];

    public const SORT_LABELS = [
        'newest'    => 'جدیدترین',
        'cheapest'  => 'ارزان‌ترین',
        'expensive' => 'گران‌ترین',
        'popular'   => 'پرفروش‌ترین',
        'rating'    => 'بیشترین امتیاز',
    ];

    // ==================================================================
    //  نمایش در فروشگاه
    // ==================================================================

    /**
     * محصول کامل برای صفحه جزئیات
     */
    public static function findFullBySlug(string $slug): ?array
    {
        $product = Database::fetch(
            'SELECT p.*, b.name AS brand_name, b.slug AS brand_slug,
                    c.name AS category_name, c.slug AS category_slug, c.parent_id AS category_parent
               FROM products p
               LEFT JOIN brands b     ON b.id = p.brand_id
               LEFT JOIN categories c ON c.id = p.category_id
              WHERE p.slug = ? AND p.is_active = 1
              LIMIT 1',
            [$slug]
        );

        if ($product === null) {
            return null;
        }

        $id = (int) $product['id'];

        $product['images']   = Database::fetchAll(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$id]
        );
        $product['specs']    = Database::fetchAll(
            'SELECT * FROM product_specs WHERE product_id = ? ORDER BY sort_order, id', [$id]
        );
        $product['variants'] = self::variantsOf($id, true);

        return $product;
    }

    /**
     * Variantهای یک محصول به همراه مقادیر ویژگی‌ها
     */
    public static function variantsOf(int $productId, bool $onlyActive = false): array
    {
        $where = 'v.product_id = ?' . ($onlyActive ? ' AND v.is_active = 1' : '');

        $variants = Database::fetchAll(
            "SELECT v.* FROM product_variants v WHERE {$where} ORDER BY v.price, v.id",
            [$productId]
        );

        if ($variants === []) {
            return [];
        }

        // مقادیر همه Variantها با یک کوئری خوانده می‌شود تا داخل حلقه کوئری نزنیم
        $ids          = array_column($variants, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $values = Database::fetchAll(
            "SELECT vav.variant_id, vav.attribute_id, vav.attribute_value_id,
                    a.name AS attribute_name, av.value AS value_label, av.color_code
               FROM variant_attribute_values vav
               JOIN attributes a        ON a.id  = vav.attribute_id
               JOIN attribute_values av ON av.id = vav.attribute_value_id
              WHERE vav.variant_id IN ({$placeholders})
              ORDER BY a.sort_order, av.sort_order",
            $ids
        );

        $byVariant = [];
        foreach ($values as $value) {
            $byVariant[$value['variant_id']][] = $value;
        }

        foreach ($variants as &$variant) {
            $variant['values'] = $byVariant[$variant['id']] ?? [];
        }

        return $variants;
    }

    /**
     * محصولات مرتبط — از همان دسته‌بندی
     */
    public static function related(int $productId, ?int $categoryId, int $limit = 6): array
    {
        if ($categoryId === null) {
            return [];
        }

        return Database::fetchAll(
            'SELECT id, name, slug, price, compare_at_price, main_image, condition_type
               FROM products
              WHERE is_active = 1 AND category_id = ? AND id <> ?
              ORDER BY sold_count DESC, created_at DESC
              LIMIT ' . (int) $limit,
            [$categoryId, $productId]
        );
    }

    // ==================================================================
    //  فیلتر و جستجو
    // ==================================================================

    /**
     * ساخت شرط‌های فیلتر از روی ورودی کاربر.
     *
     * @return array{where:string, params:array, joins:string}
     */
    private static function buildFilter(array $filters): array
    {
        $where  = ['p.is_active = 1'];
        $params = [];
        $joins  = '';

        // دسته‌بندی (به همراه زیر‌دسته‌ها)
        if (!empty($filters['category_ids'])) {
            $ids = array_map('intval', (array) $filters['category_ids']);
            $where[] = 'p.category_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            $params  = array_merge($params, $ids);
        }

        // برند (چند انتخابی)
        if (!empty($filters['brand_ids'])) {
            $ids = array_map('intval', (array) $filters['brand_ids']);
            $where[] = 'p.brand_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            $params  = array_merge($params, $ids);
        }

        // وضعیت نو / کارکرده
        if (!empty($filters['condition']) && in_array($filters['condition'], ['new', 'used'], true)) {
            $where[]  = 'p.condition_type = ?';
            $params[] = $filters['condition'];
        }

        // بازه قیمت
        if (!empty($filters['min_price'])) {
            $where[]  = 'p.price >= ?';
            $params[] = (int) $filters['min_price'];
        }
        if (!empty($filters['max_price'])) {
            $where[]  = 'p.price <= ?';
            $params[] = (int) $filters['max_price'];
        }

        // فقط کالاهای موجود
        if (!empty($filters['in_stock'])) {
            $where[] = 'p.stock > 0';
        }

        // جستجوی متنی روی نام، برند و کد کالا.
        // نامک برند هم بررسی می‌شود چون کاربر ایرانی ممکن است نام برند را
        // انگلیسی تایپ کند («samsung» به‌جای «سامسونگ»).
        if (!empty($filters['q'])) {
            $joins  .= ' LEFT JOIN brands sb ON sb.id = p.brand_id';
            $where[] = '(p.name LIKE ? OR sb.name LIKE ? OR sb.slug LIKE ? OR p.sku LIKE ?)';
            $term    = '%' . $filters['q'] . '%';
            $params  = array_merge($params, [$term, $term, $term, $term]);
        }

        // ویژگی‌ها (رنگ، حافظه و ...) — محصول باید همه گروه‌های انتخاب‌شده را داشته باشد
        if (!empty($filters['attribute_values'])) {
            $ids = array_map('intval', (array) $filters['attribute_values']);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $where[] = "(SELECT COUNT(DISTINCT pa.attribute_id)
                           FROM product_attributes pa
                          WHERE pa.product_id = p.id
                            AND pa.attribute_value_id IN ({$placeholders}))
                        = (SELECT COUNT(DISTINCT av.attribute_id)
                             FROM attribute_values av
                            WHERE av.id IN ({$placeholders}))";
            $params = array_merge($params, $ids, $ids);
        }

        return [
            'where'  => implode(' AND ', $where),
            'params' => $params,
            'joins'  => $joins,
        ];
    }

    /**
     * شمارش محصولات مطابق فیلتر (برای صفحه‌بندی)
     */
    public static function countFiltered(array $filters): int
    {
        $f = self::buildFilter($filters);

        return (int) Database::fetchValue(
            "SELECT COUNT(*) FROM products p{$f['joins']} WHERE {$f['where']}",
            $f['params']
        );
    }

    /**
     * گرفتن محصولات مطابق فیلتر
     */
    public static function filter(array $filters, int $limit, int $offset, string $sort = 'newest'): array
    {
        $f       = self::buildFilter($filters);
        $orderBy = self::SORT_OPTIONS[$sort] ?? self::SORT_OPTIONS['newest'];

        return Database::fetchAll(
            "SELECT p.id, p.name, p.slug, p.price, p.compare_at_price, p.main_image,
                    p.condition_type, p.stock, p.rating_avg, p.has_variants
               FROM products p{$f['joins']}
              WHERE {$f['where']}
              ORDER BY {$orderBy}
              LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset,
            $f['params']
        );
    }

    /**
     * کمترین و بیشترین قیمت در نتیجه فعلی — برای نمایش بازه در نوار فیلتر
     */
    public static function priceRange(array $filters): array
    {
        $f = self::buildFilter($filters);

        $row = Database::fetch(
            "SELECT MIN(p.price) AS min_price, MAX(p.price) AS max_price
               FROM products p{$f['joins']} WHERE {$f['where']}",
            $f['params']
        );

        return [
            'min' => (int) ($row['min_price'] ?? 0),
            'max' => (int) ($row['max_price'] ?? 0),
        ];
    }

    /**
     * برندهای موجود در یک دسته — برای اینکه نوار فیلتر فقط برندهای مرتبط را نشان دهد
     */
    public static function brandsInCategories(array $categoryIds): array
    {
        if ($categoryIds === []) {
            return Brand::active();
        }

        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));

        return Database::fetchAll(
            "SELECT DISTINCT b.id, b.name, b.slug
               FROM brands b
               JOIN products p ON p.brand_id = b.id
              WHERE p.is_active = 1 AND p.category_id IN ({$placeholders})
              ORDER BY b.sort_order, b.name",
            array_map('intval', $categoryIds)
        );
    }

    /**
     * پیشنهاد جستجوی زنده
     */
    public static function suggest(string $term, int $limit = 8): array
    {
        $like = '%' . $term . '%';

        return Database::fetchAll(
            'SELECT p.id, p.name, p.slug, p.price, p.main_image
               FROM products p
               LEFT JOIN brands b ON b.id = p.brand_id
              WHERE p.is_active = 1
                AND (p.name LIKE ? OR b.name LIKE ? OR b.slug LIKE ? OR p.sku LIKE ?)
              ORDER BY p.sold_count DESC, p.created_at DESC
              LIMIT ' . (int) $limit,
            [$like, $like, $like, $like]
        );
    }

    // ==================================================================
    //  پنل مدیریت
    // ==================================================================

    /**
     * لیست محصولات پنل با جستجو و فیلتر
     */
    public static function adminList(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::adminWhere($filters);

        return Database::fetchAll(
            "SELECT p.*, b.name AS brand_name, c.name AS category_name
               FROM products p
               LEFT JOIN brands b     ON b.id = p.brand_id
               LEFT JOIN categories c ON c.id = p.category_id
              WHERE {$where}
              ORDER BY p.created_at DESC
              LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset,
            $params
        );
    }

    public static function adminCount(array $filters): int
    {
        [$where, $params] = self::adminWhere($filters);

        return (int) Database::fetchValue(
            "SELECT COUNT(*) FROM products p WHERE {$where}",
            $params
        );
    }

    private static function adminWhere(array $filters): array
    {
        $where  = ['1'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
            $term    = '%' . $filters['q'] . '%';
            $params  = array_merge($params, [$term, $term]);
        }

        if (!empty($filters['category_id'])) {
            $where[]  = 'p.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }

        if (!empty($filters['brand_id'])) {
            $where[]  = 'p.brand_id = ?';
            $params[] = (int) $filters['brand_id'];
        }

        if (($filters['status'] ?? '') === 'inactive') {
            $where[] = 'p.is_active = 0';
        } elseif (($filters['status'] ?? '') === 'active') {
            $where[] = 'p.is_active = 1';
        } elseif (($filters['status'] ?? '') === 'out_of_stock') {
            $where[] = 'p.stock <= 0';
        }

        return [implode(' AND ', $where), $params];
    }

    // ==================================================================
    //  هم‌گام‌سازی قیمت و موجودی
    // ==================================================================

    /**
     * برای محصول Variant‌دار، قیمت و موجودی جدول products را از روی Variantها
     * به‌روز می‌کند:
     *      price = کمترین قیمت Variantهای فعال
     *      stock = مجموع موجودی Variantهای فعال
     *
     * دلیلش در docs/DATABASE.md توضیح داده شده: فیلتر و مرتب‌سازی قیمت
     * در صفحه دسته‌بندی با یک کوئری ساده انجام شود.
     */
    public static function syncFromVariants(int $productId): void
    {
        $row = Database::fetch(
            'SELECT MIN(price) AS min_price, SUM(stock) AS total_stock, COUNT(*) AS variant_count
               FROM product_variants
              WHERE product_id = ? AND is_active = 1',
            [$productId]
        );

        $count = (int) ($row['variant_count'] ?? 0);

        if ($count === 0) {
            // دیگر Variant فعالی ندارد — محصول به حالت ساده برمی‌گردد
            Database::update('products', ['has_variants' => 0], 'id = ?', [$productId]);
            return;
        }

        Database::update('products', [
            'has_variants' => 1,
            'price'        => (int) $row['min_price'],
            'stock'        => (int) $row['total_stock'],
        ], 'id = ?', [$productId]);
    }

    /**
     * ساخت دوباره جدول product_attributes از روی Variantها.
     * این جدول فقط برای فیلتر کردن استفاده می‌شود.
     */
    public static function rebuildFilterAttributes(int $productId): void
    {
        Database::delete('product_attributes', 'product_id = ?', [$productId]);

        Database::run(
            'INSERT IGNORE INTO product_attributes (product_id, attribute_id, attribute_value_id)
             SELECT DISTINCT v.product_id, vav.attribute_id, vav.attribute_value_id
               FROM product_variants v
               JOIN variant_attribute_values vav ON vav.variant_id = v.id
              WHERE v.product_id = ?',
            [$productId]
        );
    }

    /**
     * حذف کامل محصول به همراه تصاویرش از روی دیسک
     */
    public static function deleteWithFiles(int $productId): void
    {
        $product = self::find($productId);
        if ($product === null) {
            return;
        }

        $images = Database::fetchAll(
            'SELECT image FROM product_images WHERE product_id = ?', [$productId]
        );

        // ردیف‌های وابسته با ON DELETE CASCADE خودکار پاک می‌شوند
        self::deleteById($productId);

        \App\Core\Upload::delete($product['main_image']);
        foreach ($images as $image) {
            \App\Core\Upload::delete($image['image']);
        }
    }

    /**
     * آیا این محصول در سفارشی ثبت شده است؟
     * (برای هشدار دادن قبل از حذف)
     */
    public static function hasOrders(int $productId): bool
    {
        return (int) Database::fetchValue(
            'SELECT COUNT(*) FROM order_items WHERE product_id = ?', [$productId]
        ) > 0;
    }
}
