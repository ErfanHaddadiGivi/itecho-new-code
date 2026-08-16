<?php

namespace App\Models;

use App\Core\Database;

/**
 * ویژگی‌ها (رنگ، حافظه داخلی، رم) و مقادیرشان.
 *
 * دو کاربرد دارند:
 *   ۱. ساخت Variant محصول (هر ترکیب قیمت و موجودی مستقل)
 *   ۲. نوار فیلتر صفحه دسته‌بندی
 */
class Attribute extends Model
{
    protected static string $table = 'attributes';

    /**
     * همه ویژگی‌ها به همراه مقادیرشان — برای فرم محصول در پنل
     */
    public static function allWithValues(): array
    {
        $attributes = Database::fetchAll('SELECT * FROM attributes ORDER BY sort_order, name');

        if ($attributes === []) {
            return [];
        }

        $values = Database::fetchAll(
            'SELECT * FROM attribute_values ORDER BY attribute_id, sort_order, value'
        );

        $byAttribute = [];
        foreach ($values as $value) {
            $byAttribute[$value['attribute_id']][] = $value;
        }

        foreach ($attributes as &$attribute) {
            $attribute['values'] = $byAttribute[$attribute['id']] ?? [];
        }

        return $attributes;
    }

    /**
     * ویژگی‌های قابل فیلتر که در یک دسته واقعاً محصول دارند.
     *
     * اینطور در صفحه «موبایل» فیلتر «رنگ» و «حافظه» دیده می‌شود
     * ولی در «صندلی گیمینگ» فیلتر بی‌ربط نمایش داده نمی‌شود.
     */
    public static function filterableForCategories(array $categoryIds): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $ids          = array_map('intval', $categoryIds);

        $rows = Database::fetchAll(
            "SELECT DISTINCT a.id AS attribute_id, a.name AS attribute_name, a.input_type,
                    a.sort_order AS attr_order,
                    av.id AS value_id, av.value, av.slug AS value_slug, av.color_code,
                    av.sort_order AS value_order
               FROM product_attributes pa
               JOIN products p          ON p.id  = pa.product_id AND p.is_active = 1
               JOIN attributes a        ON a.id  = pa.attribute_id AND a.is_filterable = 1
               JOIN attribute_values av ON av.id = pa.attribute_value_id
              WHERE p.category_id IN ({$placeholders})
              ORDER BY a.sort_order, av.sort_order, av.value",
            $ids
        );

        $grouped = [];
        foreach ($rows as $row) {
            $id = $row['attribute_id'];

            if (!isset($grouped[$id])) {
                $grouped[$id] = [
                    'id'         => $id,
                    'name'       => $row['attribute_name'],
                    'input_type' => $row['input_type'],
                    'values'     => [],
                ];
            }

            $grouped[$id]['values'][] = [
                'id'         => $row['value_id'],
                'value'      => $row['value'],
                'slug'       => $row['value_slug'],
                'color_code' => $row['color_code'],
            ];
        }

        return array_values($grouped);
    }

    /**
     * مقادیر یک مجموعه شناسه — برای ساخت عنوان Variant
     *
     * @return array<int, array{id:int, value:string, attribute_id:int, attribute_name:string}>
     */
    public static function valuesByIds(array $valueIds): array
    {
        if ($valueIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($valueIds), '?'));

        $rows = Database::fetchAll(
            "SELECT av.id, av.value, av.attribute_id, a.name AS attribute_name, a.sort_order
               FROM attribute_values av
               JOIN attributes a ON a.id = av.attribute_id
              WHERE av.id IN ({$placeholders})
              ORDER BY a.sort_order, av.sort_order",
            array_map('intval', $valueIds)
        );

        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }

        return $byId;
    }
}
