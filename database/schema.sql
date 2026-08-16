-- =====================================================================
--  فروشگاه اینترنتی Itecho — ساختار پایگاه داده
--  نسخه: 1.0
--  موتور: InnoDB  |  کدگذاری: utf8mb4  |  سازگار با MySQL 5.7+ و MariaDB 10.3+
-- ---------------------------------------------------------------------
--  نکات مهم:
--   • همه مبالغ به «تومان» و به صورت عدد صحیح (BIGINT) ذخیره می‌شوند؛
--     در ایران واحد اعشاری نداریم و این کار از خطاهای اعشاری جلوگیری می‌کند.
--   • طول ستون‌های متنی که ایندکس یکتا دارند 191 است تا روی هاست‌های
--     قدیمی (MySQL 5.7 با utf8mb4) هم به محدودیت 767 بایت نخوریم.
--   • برای اجرا روی cPanel: phpMyAdmin → دیتابیس مورد نظر → Import → این فایل.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';


-- =====================================================================
--  بخش ۱ — تنظیمات و محتوای ثابت سایت
-- =====================================================================

-- تنظیمات کلی سایت (کلید/مقدار) — نام سایت، کلید زرین‌پال، اطلاعات SMTP و ...
-- به جای ساختن ده‌ها ستون، هر تنظیم یک ردیف است تا افزودن تنظیم جدید آسان باشد.
CREATE TABLE settings (
  setting_key    VARCHAR(100) NOT NULL COMMENT 'کلید یکتای تنظیم، مثل site_name',
  setting_value  TEXT DEFAULT NULL     COMMENT 'مقدار تنظیم',
  setting_group  VARCHAR(50)  NOT NULL DEFAULT 'general' COMMENT 'گروه‌بندی برای نمایش در پنل: general/payment/mail/shipping',
  title          VARCHAR(150) DEFAULT NULL COMMENT 'عنوان فارسی برای نمایش در فرم پنل',
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key),
  KEY idx_settings_group (setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='تنظیمات سایت به صورت کلید/مقدار';


-- صفحات ثابت مورد نیاز اینماد: قوانین و مقررات، حریم خصوصی، درباره ما، تماس با ما
CREATE TABLE pages (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug        VARCHAR(191) NOT NULL COMMENT 'آدرس صفحه، مثل terms یا privacy',
  title       VARCHAR(191) NOT NULL,
  content     MEDIUMTEXT DEFAULT NULL COMMENT 'محتوای HTML صفحه',
  meta_description VARCHAR(300) DEFAULT NULL,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  sort_order  INT NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pages_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='صفحات ثابت سایت';


-- پیام‌های فرم «تماس با ما»
CREATE TABLE contact_messages (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name  VARCHAR(120) NOT NULL,
  email      VARCHAR(191) DEFAULT NULL,
  phone      VARCHAR(20)  DEFAULT NULL,
  subject    VARCHAR(191) DEFAULT NULL,
  message    TEXT NOT NULL,
  is_read    TINYINT(1) NOT NULL DEFAULT 0,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_contact_is_read (is_read, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='پیام‌های ارسالی از فرم تماس با ما';


-- بنرها و اسلایدر صفحه اصلی (قابل مدیریت از پنل)
CREATE TABLE banners (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title       VARCHAR(191) DEFAULT NULL,
  image       VARCHAR(255) NOT NULL COMMENT 'مسیر فایل تصویر',
  mobile_image VARCHAR(255) DEFAULT NULL COMMENT 'تصویر مخصوص موبایل (اختیاری)',
  link_url    VARCHAR(255) DEFAULT NULL,
  position    ENUM('slider','top','middle','sidebar') NOT NULL DEFAULT 'slider',
  sort_order  INT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_banners_position (position, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='بنرهای تبلیغاتی و اسلایدر صفحه اصلی';


-- =====================================================================
--  بخش ۲ — کاربران و احراز هویت
-- =====================================================================

-- کاربران سایت. مدیر کل هم در همین جدول است و فقط role = admin دارد.
-- طبق PRD فقط یک نقش مدیریتی داریم، پس جدول جداگانه نقش‌ها لازم نیست.
CREATE TABLE users (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  first_name        VARCHAR(60) NOT NULL DEFAULT '',
  last_name         VARCHAR(60) NOT NULL DEFAULT '',
  email             VARCHAR(191) NOT NULL,
  phone             VARCHAR(20) DEFAULT NULL,
  password_hash     VARCHAR(255) NOT NULL COMMENT 'خروجی password_hash() — هرگز رمز خام ذخیره نشود',
  role              ENUM('customer','admin') NOT NULL DEFAULT 'customer',
  status            ENUM('active','blocked') NOT NULL DEFAULT 'active',
  email_verified_at DATETIME DEFAULT NULL COMMENT 'زمان تایید ایمیل با کد ۶ رقمی',
  last_login_at     DATETIME DEFAULT NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_phone (phone),
  KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='کاربران سایت (مشتری و مدیر کل)';


-- دفترچه آدرس کاربر — برای ارسال پستی
CREATE TABLE user_addresses (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED NOT NULL,
  receiver_name VARCHAR(120) NOT NULL COMMENT 'نام تحویل‌گیرنده',
  phone         VARCHAR(20)  NOT NULL,
  province      VARCHAR(60)  NOT NULL COMMENT 'استان',
  city          VARCHAR(60)  NOT NULL COMMENT 'شهر',
  postal_code   VARCHAR(12)  DEFAULT NULL,
  address_line  VARCHAR(500) NOT NULL COMMENT 'نشانی کامل',
  is_default    TINYINT(1) NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_addresses_user (user_id),
  CONSTRAINT fk_addresses_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='آدرس‌های ذخیره‌شده کاربران';


-- کدهای تایید ۶ رقمی ایمیل: تایید ثبت‌نام و بازیابی رمز عبور.
-- کد به صورت هش ذخیره می‌شود تا در صورت نشت دیتابیس قابل استفاده نباشد.
-- بعداً برای OTP پیامکی فقط کافی است ستون email با mobile جایگزین/تکمیل شود.
CREATE TABLE verification_codes (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED DEFAULT NULL COMMENT 'ممکن است هنگام ثبت‌نام هنوز کاربر ساخته نشده باشد',
  email       VARCHAR(191) NOT NULL,
  code_hash   VARCHAR(255) NOT NULL COMMENT 'هش کد ۶ رقمی',
  purpose     ENUM('verify_email','reset_password') NOT NULL,
  attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'تعداد تلاش ناموفق برای جلوگیری از حدس زدن',
  expires_at  DATETIME NOT NULL,
  used_at     DATETIME DEFAULT NULL,
  ip_address  VARCHAR(45) DEFAULT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_codes_lookup (email, purpose, expires_at),
  KEY idx_codes_user (user_id),
  CONSTRAINT fk_codes_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='کدهای تایید ایمیل و بازیابی رمز';


-- =====================================================================
--  بخش ۳ — دسته‌بندی، برند و ویژگی‌ها
-- =====================================================================

-- دسته‌بندی دو سطحی. همین جدول مگا منو را هم می‌سازد:
-- سطح ۱ (parent_id = NULL) ستون‌های منو، سطح ۲ آیتم‌های زیر هر ستون.
CREATE TABLE categories (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  parent_id    INT UNSIGNED DEFAULT NULL COMMENT 'NULL یعنی دسته اصلی',
  name         VARCHAR(120) NOT NULL,
  slug         VARCHAR(191) NOT NULL,
  description  TEXT DEFAULT NULL,
  image        VARCHAR(255) DEFAULT NULL COMMENT 'آیکون یا تصویر دسته',
  sort_order   INT NOT NULL DEFAULT 0 COMMENT 'ترتیب نمایش در منو و لیست‌ها',
  is_active    TINYINT(1) NOT NULL DEFAULT 1,
  show_in_menu TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'نمایش در مگا منو',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug),
  KEY idx_categories_parent (parent_id, is_active, sort_order),
  KEY idx_categories_menu (show_in_menu, is_active, sort_order),
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='دسته‌بندی دو سطحی محصولات + منبع مگا منو';


-- برندها — برای فیلتر صفحات دسته‌بندی و جستجو
CREATE TABLE brands (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name       VARCHAR(120) NOT NULL,
  slug       VARCHAR(191) NOT NULL,
  logo       VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_brands_slug (slug),
  KEY idx_brands_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='برندهای محصولات';


-- ویژگی‌ها (رنگ، حافظه داخلی، رم و ...).
-- دو کاربرد دارند: ۱) ساخت Variant  ۲) فیلتر در صفحه دسته‌بندی
CREATE TABLE attributes (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(80) NOT NULL COMMENT 'مثل: رنگ، حافظه داخلی',
  slug          VARCHAR(191) NOT NULL,
  input_type    ENUM('select','color') NOT NULL DEFAULT 'select' COMMENT 'color یعنی در سایت به شکل دایره رنگی نمایش داده شود',
  is_filterable TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'در نوار فیلتر دسته‌بندی نمایش داده شود؟',
  sort_order    INT NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_attributes_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='ویژگی‌های محصول (پایه Variant و فیلتر)';


-- مقادیر هر ویژگی: رنگ → مشکی/سفید ، حافظه داخلی → 128GB/256GB
CREATE TABLE attribute_values (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  attribute_id INT UNSIGNED NOT NULL,
  value        VARCHAR(80) NOT NULL,
  slug         VARCHAR(191) NOT NULL,
  color_code   VARCHAR(7) DEFAULT NULL COMMENT 'کد رنگ مثل #000000 برای ویژگی از نوع color',
  sort_order   INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_attr_value (attribute_id, slug),
  KEY idx_attr_values_attr (attribute_id, sort_order),
  CONSTRAINT fk_attr_values_attribute FOREIGN KEY (attribute_id) REFERENCES attributes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='مقادیر مجاز هر ویژگی';


-- =====================================================================
--  بخش ۴ — محصولات
-- =====================================================================

-- محصولات.
-- قاعده مهم درباره قیمت و موجودی:
--   • اگر محصول Variant ندارد (has_variants = 0) → price و stock همین جدول ملاک است.
--   • اگر Variant دارد (has_variants = 1) → قیمت و موجودی واقعی در product_variants است
--     و price اینجا برابر «کمترین قیمت Variantهای فعال» و stock برابر «مجموع موجودی»
--     نگه داشته می‌شود. این کار را یک تابع در کد انجام می‌دهد تا مرتب‌سازی و
--     فیلتر قیمت در صفحه دسته‌بندی با یک کوئری ساده انجام شود.
CREATE TABLE products (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name              VARCHAR(255) NOT NULL,
  slug              VARCHAR(191) NOT NULL,
  sku               VARCHAR(64) DEFAULT NULL COMMENT 'کد انبارداری محصول',
  category_id       INT UNSIGNED DEFAULT NULL,
  brand_id          INT UNSIGNED DEFAULT NULL,
  condition_type    ENUM('new','used') NOT NULL DEFAULT 'new' COMMENT 'نو / کارکرده',
  price             BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'قیمت فروش به تومان',
  compare_at_price  BIGINT UNSIGNED DEFAULT NULL COMMENT 'قیمت قبل از تخفیف (خط‌خورده)',
  stock             INT NOT NULL DEFAULT 0 COMMENT 'موجودی؛ برای محصول Variant‌دار مجموع موجودی‌ها',
  has_variants      TINYINT(1) NOT NULL DEFAULT 0,
  main_image        VARCHAR(255) DEFAULT NULL COMMENT 'تصویر اصلی',
  short_description VARCHAR(500) DEFAULT NULL,
  description       MEDIUMTEXT DEFAULT NULL COMMENT 'توضیحات کامل (HTML)',
  serial_number     VARCHAR(100) DEFAULT NULL COMMENT 'IMEI یا سریال — اختیاری، مخصوص موبایل و کالای کارکرده',
  warranty          VARCHAR(120) DEFAULT NULL COMMENT 'گارانتی، مثل: ۱۸ ماه گارانتی شرکتی',
  is_active         TINYINT(1) NOT NULL DEFAULT 1,
  is_featured       TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'نمایش در بخش ویژه صفحه اصلی',
  views             INT UNSIGNED NOT NULL DEFAULT 0,
  rating_avg        DECIMAL(3,2) NOT NULL DEFAULT 0.00 COMMENT 'میانگین امتیاز — از نظرات تاییدشده محاسبه می‌شود',
  rating_count      INT UNSIGNED NOT NULL DEFAULT 0,
  sold_count        INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'تعداد فروش — برای مرتب‌سازی پرفروش‌ترین',
  meta_description  VARCHAR(300) DEFAULT NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_slug (slug),
  UNIQUE KEY uq_products_sku (sku),
  KEY idx_products_category (category_id, is_active),
  KEY idx_products_brand (brand_id, is_active),
  KEY idx_products_price (is_active, price),
  KEY idx_products_condition (condition_type, is_active),
  KEY idx_products_featured (is_featured, is_active),
  KEY idx_products_name (name),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE RESTRICT,
  CONSTRAINT fk_products_brand    FOREIGN KEY (brand_id)    REFERENCES brands (id)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='محصولات فروشگاه';


-- گالری تصاویر محصول (علاوه بر تصویر اصلی)
CREATE TABLE product_images (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  image      VARCHAR(255) NOT NULL,
  alt_text   VARCHAR(191) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_images_product (product_id, sort_order),
  CONSTRAINT fk_images_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='گالری تصاویر محصول';


-- مشخصات فنی محصول — متن آزاد کلید/مقدار فقط برای «نمایش» در تب مشخصات.
-- (برای فیلتر کردن از جدول product_attributes استفاده می‌شود، نه از این جدول.)
CREATE TABLE product_specs (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id INT UNSIGNED NOT NULL,
  spec_key   VARCHAR(120) NOT NULL COMMENT 'مثل: اندازه صفحه‌نمایش',
  spec_value VARCHAR(500) NOT NULL COMMENT 'مثل: ۶.۷ اینچ',
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_specs_product (product_id, sort_order),
  CONSTRAINT fk_specs_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='مشخصات فنی محصول برای نمایش';


-- ویژگی‌های قابل فیلتر هر محصول (رنگ‌ها، حافظه‌ها و ...).
-- برای محصولات Variant‌دار هنگام ذخیره Variantها به صورت خودکار پر می‌شود
-- تا کوئری فیلتر ساده و سریع بماند.
CREATE TABLE product_attributes (
  product_id         INT UNSIGNED NOT NULL,
  attribute_id       INT UNSIGNED NOT NULL,
  attribute_value_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (product_id, attribute_value_id),
  KEY idx_prod_attr_filter (attribute_id, attribute_value_id),
  CONSTRAINT fk_prod_attr_product   FOREIGN KEY (product_id)         REFERENCES products (id)         ON DELETE CASCADE,
  CONSTRAINT fk_prod_attr_attribute FOREIGN KEY (attribute_id)       REFERENCES attributes (id)       ON DELETE CASCADE,
  CONSTRAINT fk_prod_attr_value     FOREIGN KEY (attribute_value_id) REFERENCES attribute_values (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='ویژگی‌های محصول برای فیلتر کردن';


-- Variantها: هر ترکیب ویژگی، قیمت و موجودی مستقل خود را دارد.
-- variant_key امضای یکتای ترکیب است (مثل "1:3|2:7") تا ترکیب تکراری ثبت نشود.
CREATE TABLE product_variants (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id       INT UNSIGNED NOT NULL,
  variant_key      VARCHAR(191) NOT NULL COMMENT 'امضای ترکیب ویژگی‌ها برای جلوگیری از تکرار',
  title            VARCHAR(191) NOT NULL COMMENT 'عنوان خوانا مثل: مشکی / ۲۵۶ گیگابایت',
  sku              VARCHAR(64) DEFAULT NULL,
  price            BIGINT UNSIGNED NOT NULL DEFAULT 0,
  compare_at_price BIGINT UNSIGNED DEFAULT NULL,
  stock            INT NOT NULL DEFAULT 0,
  image            VARCHAR(255) DEFAULT NULL COMMENT 'تصویر مخصوص این Variant (اختیاری)',
  is_active        TINYINT(1) NOT NULL DEFAULT 1,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_variant_combo (product_id, variant_key),
  UNIQUE KEY uq_variant_sku (sku),
  KEY idx_variants_product (product_id, is_active),
  CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='ترکیب‌های محصول با قیمت و موجودی مستقل';


-- اینکه هر Variant از چه مقادیری ساخته شده است
CREATE TABLE variant_attribute_values (
  variant_id         INT UNSIGNED NOT NULL,
  attribute_id       INT UNSIGNED NOT NULL,
  attribute_value_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (variant_id, attribute_id),
  KEY idx_variant_value (attribute_value_id),
  CONSTRAINT fk_vav_variant   FOREIGN KEY (variant_id)         REFERENCES product_variants (id)  ON DELETE CASCADE,
  CONSTRAINT fk_vav_attribute FOREIGN KEY (attribute_id)       REFERENCES attributes (id)        ON DELETE CASCADE,
  CONSTRAINT fk_vav_value     FOREIGN KEY (attribute_value_id) REFERENCES attribute_values (id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='مقادیر ویژگی هر Variant';


-- =====================================================================
--  بخش ۵ — سبد خرید و علاقه‌مندی‌ها
-- =====================================================================

-- سبد خرید در دیتابیس ذخیره می‌شود تا کاربر بعد از ورود دوباره سبدش را داشته باشد.
-- برای مهمان‌ها با session_token و پس از ورود با user_id شناسایی می‌شود.
CREATE TABLE carts (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED DEFAULT NULL,
  session_token CHAR(40) DEFAULT NULL COMMENT 'شناسه سبد مهمان (کوکی)',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_carts_user (user_id),
  UNIQUE KEY uq_carts_session (session_token),
  KEY idx_carts_updated (updated_at) COMMENT 'برای پاک‌سازی سبدهای رهاشده قدیمی',
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='سبد خرید کاربران و مهمان‌ها';


-- اقلام سبد خرید. قیمت اینجا ذخیره نمی‌شود و همیشه لحظه‌ای از محصول/Variant خوانده
-- می‌شود تا مشتری قیمت قدیمی نبیند.
CREATE TABLE cart_items (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cart_id    INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  variant_id INT UNSIGNED DEFAULT NULL COMMENT 'NULL یعنی محصول Variant ندارد',
  -- ستون کمکی: در ایندکس یکتا مقدار NULL با NULL برابر شمرده نمی‌شود، پس بدون این ستون
  -- یک محصول بدون Variant می‌توانست دو بار به صورت دو ردیف جدا وارد سبد شود.
  variant_ref INT UNSIGNED AS (IFNULL(variant_id, 0)) STORED,
  quantity   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cart_line (cart_id, product_id, variant_ref),
  KEY idx_cart_items_product (product_id),
  CONSTRAINT fk_cart_items_cart    FOREIGN KEY (cart_id)    REFERENCES carts (id)            ON DELETE CASCADE,
  CONSTRAINT fk_cart_items_product FOREIGN KEY (product_id) REFERENCES products (id)         ON DELETE CASCADE,
  CONSTRAINT fk_cart_items_variant FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='اقلام داخل سبد خرید';


-- لیست علاقه‌مندی‌ها (نیازمند حساب کاربری)
CREATE TABLE wishlist_items (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_wishlist (user_id, product_id),
  KEY idx_wishlist_product (product_id),
  CONSTRAINT fk_wishlist_user    FOREIGN KEY (user_id)    REFERENCES users (id)    ON DELETE CASCADE,
  CONSTRAINT fk_wishlist_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='لیست علاقه‌مندی کاربران';


-- =====================================================================
--  بخش ۶ — سفارش‌ها، پرداخت و انبار
-- =====================================================================

-- سفارش‌ها.
-- سه ستون وضعیت داریم تا گردش کار روشن بماند:
--   status          → مرحله اصلی سفارش (طبق نمودار PRD)
--   payment_status  → وضعیت پرداخت مبلغ کالاها
--   shipping_state  → وضعیت هزینه ارسال پستی (محاسبه‌نشده / منتظر پرداخت / پرداخت‌شده)
CREATE TABLE orders (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_number    VARCHAR(20) NOT NULL COMMENT 'شماره سفارش قابل نمایش به مشتری',
  user_id         INT UNSIGNED NOT NULL,
  status          ENUM(
                    'pending_payment',   -- در انتظار پرداخت
                    'paid',              -- پرداخت‌شده
                    'preparing',         -- در حال آماده‌سازی
                    'ready_for_pickup',  -- آماده تحویل حضوری
                    'shipped',           -- تحویل به پست
                    'delivered',         -- تحویل‌شده
                    'canceled',          -- لغو‌شده
                    'returned'           -- مرجوعی
                  ) NOT NULL DEFAULT 'pending_payment',
  payment_status  ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  delivery_method ENUM('pickup','post') NOT NULL DEFAULT 'pickup' COMMENT 'دریافت حضوری / ارسال با پست',
  shipping_state  ENUM(
                    'not_required',    -- تحویل حضوری، هزینه ثابت
                    'awaiting_cost',   -- در انتظار محاسبه هزینه ارسال توسط ادمین
                    'awaiting_payment',-- هزینه وارد شده، منتظر پرداخت تکمیلی مشتری
                    'paid'             -- هزینه ارسال پرداخت شد
                  ) NOT NULL DEFAULT 'not_required',

  -- مبالغ (تومان)
  items_total     BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'جمع کالاها',
  shipping_cost   BIGINT UNSIGNED DEFAULT NULL COMMENT 'هزینه ارسال؛ برای پست تا وارد نشدن توسط ادمین NULL است',
  grand_total     BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'مبلغ نهایی = items_total + shipping_cost',

  -- کپی اطلاعات گیرنده در لحظه ثبت سفارش (اگر بعداً آدرس کاربر تغییر کرد، سابقه سفارش دست‌نخورده بماند)
  receiver_name   VARCHAR(120) DEFAULT NULL,
  receiver_phone  VARCHAR(20)  DEFAULT NULL,
  province        VARCHAR(60)  DEFAULT NULL,
  city            VARCHAR(60)  DEFAULT NULL,
  postal_code     VARCHAR(12)  DEFAULT NULL,
  address_line    VARCHAR(500) DEFAULT NULL,

  tracking_code   VARCHAR(60) DEFAULT NULL COMMENT 'کد رهگیری پستی',
  customer_note   VARCHAR(500) DEFAULT NULL COMMENT 'یادداشت مشتری',
  admin_note      VARCHAR(500) DEFAULT NULL COMMENT 'یادداشت داخلی ادمین',
  stock_deducted  TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'محافظ: تضمین می‌کند موجودی فقط یک بار کسر شود',
  paid_at         DATETIME DEFAULT NULL,
  delivered_at    DATETIME DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_orders_number (order_number),
  KEY idx_orders_user (user_id, created_at),
  KEY idx_orders_status (status, created_at),
  KEY idx_orders_shipping_state (shipping_state),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='سفارش‌های ثبت‌شده';


-- اقلام سفارش. نام و قیمت در لحظه خرید کپی می‌شوند تا تغییرات بعدی محصول
-- روی فاکتورهای قدیمی اثر نگذارد.
CREATE TABLE order_items (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id      INT UNSIGNED NOT NULL,
  product_id    INT UNSIGNED DEFAULT NULL COMMENT 'اگر محصول حذف شود NULL می‌شود ولی سابقه می‌ماند',
  variant_id    INT UNSIGNED DEFAULT NULL,
  product_name  VARCHAR(255) NOT NULL COMMENT 'کپی نام محصول در لحظه خرید',
  variant_title VARCHAR(191) DEFAULT NULL COMMENT 'کپی عنوان Variant در لحظه خرید',
  sku           VARCHAR(64) DEFAULT NULL,
  unit_price    BIGINT UNSIGNED NOT NULL COMMENT 'قیمت واحد در لحظه خرید',
  quantity      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  line_total    BIGINT UNSIGNED NOT NULL COMMENT 'unit_price × quantity',
  PRIMARY KEY (id),
  KEY idx_order_items_order (order_id),
  KEY idx_order_items_product (product_id),
  CONSTRAINT fk_order_items_order   FOREIGN KEY (order_id)   REFERENCES orders (id)           ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id)         ON DELETE SET NULL,
  CONSTRAINT fk_order_items_variant FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='اقلام هر سفارش';


-- تاریخچه تغییر وضعیت سفارش — برای پیگیری و نمایش به مشتری
CREATE TABLE order_status_history (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id    INT UNSIGNED NOT NULL,
  from_status VARCHAR(30) DEFAULT NULL,
  to_status   VARCHAR(30) NOT NULL,
  note        VARCHAR(300) DEFAULT NULL,
  changed_by  INT UNSIGNED DEFAULT NULL COMMENT 'کاربر تغییردهنده؛ NULL یعنی سیستم',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_history_order (order_id, created_at),
  CONSTRAINT fk_history_order FOREIGN KEY (order_id)   REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_history_user  FOREIGN KEY (changed_by) REFERENCES users (id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='تاریخچه وضعیت سفارش';


-- تراکنش‌های پرداخت. هر سفارش می‌تواند بیش از یک پرداخت داشته باشد:
--   purpose = 'order'    → پرداخت مبلغ کالاها
--   purpose = 'shipping' → پرداخت تکمیلی هزینه ارسال (لینکی که برای مشتری ارسال می‌شود)
-- ستون gateway از الان وجود دارد تا اگر بعداً درگاه دیگری اضافه شد، ساختار تغییر نکند.
CREATE TABLE payments (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id         INT UNSIGNED NOT NULL,
  user_id          INT UNSIGNED DEFAULT NULL,
  purpose          ENUM('order','shipping') NOT NULL DEFAULT 'order',
  amount           BIGINT UNSIGNED NOT NULL COMMENT 'مبلغ این تراکنش به تومان',
  gateway          VARCHAR(30) NOT NULL DEFAULT 'zarinpal',
  is_sandbox       TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'در حالت تست زرین‌پال ۱ است',
  pay_token        CHAR(32) DEFAULT NULL COMMENT 'توکن یکتای لینک پرداخت تکمیلی که برای مشتری ایمیل می‌شود',
  authority        VARCHAR(100) DEFAULT NULL COMMENT 'Authority دریافتی از زرین‌پال',
  ref_id           VARCHAR(60) DEFAULT NULL COMMENT 'کد رهگیری پرداخت موفق',
  card_pan         VARCHAR(30) DEFAULT NULL COMMENT 'شماره کارت ماسک‌شده',
  status           ENUM('pending','paid','failed','canceled','refunded') NOT NULL DEFAULT 'pending',
  gateway_response TEXT DEFAULT NULL COMMENT 'پاسخ خام درگاه برای عیب‌یابی',
  paid_at          DATETIME DEFAULT NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payments_authority (authority),
  UNIQUE KEY uq_payments_token (pay_token),
  KEY idx_payments_order (order_id, status),
  CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_payments_user  FOREIGN KEY (user_id)  REFERENCES users (id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='تراکنش‌های درگاه پرداخت';


-- گزارش تغییرات موجودی — هر کسر یا برگشت موجودی اینجا ثبت می‌شود.
-- کمک می‌کند اگر موجودی اشتباه شد، دلیلش قابل پیگیری باشد.
CREATE TABLE inventory_logs (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id  INT UNSIGNED DEFAULT NULL,
  variant_id  INT UNSIGNED DEFAULT NULL,
  order_id    INT UNSIGNED DEFAULT NULL,
  change_qty  INT NOT NULL COMMENT 'منفی = کسر، مثبت = برگشت',
  reason      ENUM('order_paid','order_canceled','order_returned','manual_edit') NOT NULL,
  note        VARCHAR(300) DEFAULT NULL,
  created_by  INT UNSIGNED DEFAULT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_inv_product (product_id, created_at),
  KEY idx_inv_order (order_id),
  CONSTRAINT fk_inv_product FOREIGN KEY (product_id) REFERENCES products (id)         ON DELETE SET NULL,
  CONSTRAINT fk_inv_variant FOREIGN KEY (variant_id) REFERENCES product_variants (id) ON DELETE SET NULL,
  CONSTRAINT fk_inv_order   FOREIGN KEY (order_id)   REFERENCES orders (id)           ON DELETE SET NULL,
  CONSTRAINT fk_inv_user    FOREIGN KEY (created_by) REFERENCES users (id)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='گزارش تغییرات موجودی انبار';


-- =====================================================================
--  بخش ۷ — نظرات و امتیازدهی
-- =====================================================================

-- نظرات: پس از تایید ادمین نمایش داده می‌شوند.
-- اگر order_id پر باشد یعنی کاربر واقعاً محصول را خریده → نشان «خریدار تایید‌شده».
CREATE TABLE reviews (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id        INT UNSIGNED NOT NULL,
  user_id           INT UNSIGNED NOT NULL,
  order_id          INT UNSIGNED DEFAULT NULL,
  rating            TINYINT UNSIGNED NOT NULL COMMENT 'امتیاز ۱ تا ۵',
  title             VARCHAR(150) DEFAULT NULL,
  comment           TEXT NOT NULL,
  is_verified_buyer TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'نشان خریدار تاییدشده',
  status            ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  admin_reply       VARCHAR(1000) DEFAULT NULL COMMENT 'پاسخ فروشگاه به نظر',
  approved_at       DATETIME DEFAULT NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_review_user_product (user_id, product_id) COMMENT 'هر کاربر برای هر محصول یک نظر',
  KEY idx_reviews_product (product_id, status, created_at),
  KEY idx_reviews_status (status, created_at),
  CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_user    FOREIGN KEY (user_id)    REFERENCES users (id)    ON DELETE CASCADE,
  CONSTRAINT fk_reviews_order   FOREIGN KEY (order_id)   REFERENCES orders (id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='نظرات و امتیاز محصولات';


SET FOREIGN_KEY_CHECKS = 1;
