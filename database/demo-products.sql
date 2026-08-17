-- =====================================================================
--  فروشگاه اینترنتی Itecho — محصولات نمونه (اختیاری)
-- ---------------------------------------------------------------------
--  این فایل چند محصول آزمایشی می‌سازد تا بتوانید سایت را قبل از وارد
--  کردن محصولات واقعی ببینید و امتحان کنید.
--
--  روش استفاده: بعد از schema.sql و seed.sql این فایل را Import کنید.
--
--  ⚠️ این فایل برای فروشگاه واقعی لازم نیست. هر وقت خواستید محصولات
--     نمونه را پاک کنید، دستور انتهای همین فایل را اجرا کنید.
--
--  نکته: محصولات نمونه تصویر ندارند و در سایت با کادر «بدون تصویر»
--        نمایش داده می‌شوند. این طبیعی است.
-- =====================================================================

SET NAMES utf8mb4;

-- شناسه دسته‌بندی‌ها و برندها از روی نامک خوانده می‌شود
SET @cat_phone  = (SELECT id FROM categories WHERE slug = 'smartphones');
SET @cat_laptop = (SELECT id FROM categories WHERE slug = 'laptop');
SET @cat_mouse  = (SELECT id FROM categories WHERE slug = 'gaming-mouse');
SET @cat_ps     = (SELECT id FROM categories WHERE slug = 'playstation');

SET @b_samsung  = (SELECT id FROM brands WHERE slug = 'samsung');
SET @b_logitech = (SELECT id FROM brands WHERE slug = 'logitech');
SET @b_asus     = (SELECT id FROM brands WHERE slug = 'asus');
SET @b_sony     = (SELECT id FROM brands WHERE slug = 'sony');


-- ---------------------------------------------------------------------
--  ۱) محصول دارای تنوع (رنگ × حافظه) — برای تست انتخاب Variant
-- ---------------------------------------------------------------------
INSERT INTO products (name, slug, sku, category_id, brand_id, condition_type,
                      price, stock, has_variants, short_description, description,
                      warranty, is_active, is_featured)
VALUES ('گوشی سامسونگ گلکسی S24 (نمونه)', 'demo-galaxy-s24', 'DEMO-S24',
        @cat_phone, @b_samsung, 'new', 0, 0, 1,
        'پرچم‌دار سامسونگ با نمایشگر ۶.۲ اینچ',
        '<p>این یک محصول نمونه برای آزمایش سایت است.</p>',
        '۱۸ ماه گارانتی شرکتی', 1, 1);

SET @p1 = LAST_INSERT_ID();

SET @a_color = (SELECT id FROM attributes WHERE slug = 'color');
SET @a_store = (SELECT id FROM attributes WHERE slug = 'storage');
SET @c_black = (SELECT id FROM attribute_values WHERE slug = 'black');
SET @c_white = (SELECT id FROM attribute_values WHERE slug = 'white');
SET @s_128   = (SELECT id FROM attribute_values WHERE slug = '128gb');
SET @s_256   = (SELECT id FROM attribute_values WHERE slug = '256gb');

-- سه ترکیب: یکی از آن‌ها عمداً موجودی صفر دارد تا حالت «ناموجود» را ببینید
INSERT INTO product_variants (product_id, variant_key, title, sku, price, stock) VALUES
(@p1, CONCAT(@a_color,':',@c_black,'|',@a_store,':',@s_128), 'مشکی / ۱۲۸ گیگابایت', 'DEMO-S24-B128', 42000000, 6),
(@p1, CONCAT(@a_color,':',@c_black,'|',@a_store,':',@s_256), 'مشکی / ۲۵۶ گیگابایت', 'DEMO-S24-B256', 47000000, 3),
(@p1, CONCAT(@a_color,':',@c_white,'|',@a_store,':',@s_128), 'سفید / ۱۲۸ گیگابایت', 'DEMO-S24-W128', 42000000, 0);

INSERT INTO variant_attribute_values (variant_id, attribute_id, attribute_value_id)
SELECT v.id, @a_color, @c_black FROM product_variants v WHERE v.sku IN ('DEMO-S24-B128','DEMO-S24-B256')
UNION ALL
SELECT v.id, @a_color, @c_white FROM product_variants v WHERE v.sku = 'DEMO-S24-W128'
UNION ALL
SELECT v.id, @a_store, @s_128 FROM product_variants v WHERE v.sku IN ('DEMO-S24-B128','DEMO-S24-W128')
UNION ALL
SELECT v.id, @a_store, @s_256 FROM product_variants v WHERE v.sku = 'DEMO-S24-B256';

-- جدول فیلتر از روی تنوع‌ها پر می‌شود (همان کاری که پنل مدیریت انجام می‌دهد)
INSERT INTO product_attributes (product_id, attribute_id, attribute_value_id)
SELECT DISTINCT v.product_id, vav.attribute_id, vav.attribute_value_id
  FROM product_variants v
  JOIN variant_attribute_values vav ON vav.variant_id = v.id
 WHERE v.product_id = @p1;

-- قیمت = کمترین تنوع فعال، موجودی = مجموع تنوع‌ها
UPDATE products p SET
  p.price = (SELECT MIN(price) FROM product_variants WHERE product_id = p.id AND is_active = 1),
  p.stock = (SELECT SUM(stock)  FROM product_variants WHERE product_id = p.id AND is_active = 1)
WHERE p.id = @p1;

INSERT INTO product_specs (product_id, spec_key, spec_value, sort_order) VALUES
(@p1, 'اندازه صفحه‌نمایش', '۶.۲ اینچ', 1),
(@p1, 'دوربین اصلی',      '۵۰ مگاپیکسل', 2),
(@p1, 'باتری',            '۴۰۰۰ میلی‌آمپر ساعت', 3);


-- ---------------------------------------------------------------------
--  ۲) محصول ساده با تخفیف — برای تست قیمت خط‌خورده
-- ---------------------------------------------------------------------
INSERT INTO products (name, slug, sku, category_id, brand_id, condition_type,
                      price, compare_at_price, stock, short_description, is_active, is_featured)
VALUES ('ماوس گیمینگ لاجیتک G502 (نمونه)', 'demo-logitech-g502', 'DEMO-G502',
        @cat_mouse, @b_logitech, 'new', 3200000, 3900000, 14,
        'ماوس گیمینگ با سنسور ۲۵۶۰۰ DPI', 1, 1);


-- ---------------------------------------------------------------------
--  ۳) کالای کارکرده — برای تست فیلتر «نو / کارکرده»
-- ---------------------------------------------------------------------
INSERT INTO products (name, slug, sku, category_id, brand_id, condition_type,
                      price, stock, short_description, serial_number, is_active)
VALUES ('لپ‌تاپ ایسوس ROG (نمونه، کارکرده)', 'demo-asus-rog', 'DEMO-ROG',
        @cat_laptop, @b_asus, 'used', 58000000, 2,
        'در حد نو، باتری سالم', 'DEMO-SN-99881', 1);


-- ---------------------------------------------------------------------
--  ۴) کالای ناموجود — برای تست نمایش «ناموجود»
-- ---------------------------------------------------------------------
INSERT INTO products (name, slug, sku, category_id, brand_id, condition_type,
                      price, stock, short_description, is_active)
VALUES ('کنسول پلی‌استیشن ۵ (نمونه)', 'demo-ps5', 'DEMO-PS5',
        @cat_ps, @b_sony, 'new', 34000000, 0,
        'نسخه استاندارد با درایو دیسک', 1);


-- ---------------------------------------------------------------------
--  ۵) چند کالای ساده — برای تست صفحه‌بندی و مرتب‌سازی
-- ---------------------------------------------------------------------
INSERT INTO products (name, slug, sku, category_id, brand_id, condition_type, price, stock, is_active) VALUES
('ماوس گیمینگ نمونه A', 'demo-mouse-a', 'DEMO-MS-A', @cat_mouse, @b_logitech, 'new',  1200000, 5, 1),
('ماوس گیمینگ نمونه B', 'demo-mouse-b', 'DEMO-MS-B', @cat_mouse, @b_logitech, 'new',  1500000, 5, 1),
('ماوس گیمینگ نمونه C', 'demo-mouse-c', 'DEMO-MS-C', @cat_mouse, @b_logitech, 'used',  900000, 5, 1),
('ماوس گیمینگ نمونه D', 'demo-mouse-d', 'DEMO-MS-D', @cat_mouse, @b_asus,     'new',  2500000, 0, 1),
('ماوس گیمینگ نمونه E', 'demo-mouse-e', 'DEMO-MS-E', @cat_mouse, @b_asus,     'new',  4200000, 8, 1);


-- =====================================================================
--  پاک کردن محصولات نمونه
-- ---------------------------------------------------------------------
--  هر وقت خواستید همه محصولات نمونه را حذف کنید، خط زیر را از حالت
--  کامنت خارج کرده و اجرا کنید. ردیف‌های وابسته (تنوع، مشخصات، تصاویر)
--  به‌صورت خودکار پاک می‌شوند.
--
--  ⚠️ اگر روی محصول نمونه سفارشی ثبت کرده‌اید، حذف انجام نمی‌شود.
--     ابتدا آن سفارش‌ها را پاک کنید یا محصول را غیرفعال کنید.
-- =====================================================================

-- DELETE FROM products WHERE sku LIKE 'DEMO-%';
