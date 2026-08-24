-- =====================================================================
--  به‌روزرسانی محتوایی سایت
--  - جدول مطالب/بلاگ گیمینگ (posts)
--  - تنظیمات جدید: متن صفحه اصلی، پاپ‌آپ مشاوره، لینک لوکیشن
--
--  اجرای این فایل امن است: جدول با IF NOT EXISTS ساخته می‌شود و ردیف‌های
--  تنظیمات با INSERT IGNORE اضافه می‌شوند، پس هیچ داده‌ای پاک یا بازنویسی نمی‌شود.
-- =====================================================================

-- ---------- بلاگ / مطالب گیمینگ ----------
CREATE TABLE IF NOT EXISTS posts (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title        VARCHAR(191) NOT NULL,
  slug         VARCHAR(191) NOT NULL,
  excerpt      VARCHAR(500) DEFAULT NULL COMMENT 'خلاصه کوتاه برای کارت مطلب',
  content      MEDIUMTEXT DEFAULT NULL COMMENT 'متن کامل مطلب (HTML ساده)',
  cover_image  VARCHAR(255) DEFAULT NULL COMMENT 'تصویر کاور',
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  published_at DATETIME DEFAULT NULL,
  views        INT UNSIGNED NOT NULL DEFAULT 0,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_posts_slug (slug),
  KEY idx_posts_published (is_published, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='مطالب و مقالات گیمینگ (بلاگ)';

-- ---------- تنظیمات جدید ----------
-- متن صفحه اصلی (بخش قهرمان)
INSERT IGNORE INTO settings (setting_key, setting_value, setting_group, title, sort_order) VALUES
('hero_title',    'تکنولوژی، با خیال راحت', 'homepage', 'عنوان بزرگ صفحه اصلی', 10),
('hero_subtitle', 'موبایل، کامپیوتر، کنسول بازی و تجهیزات گیمینگ — با ضمانت اصالت کالا و ارسال به سراسر ایران.', 'homepage', 'متن زیر عنوان صفحه اصلی', 20),
('hero_cta',      'شروع خرید', 'homepage', 'متن دکمه صفحه اصلی', 30);

-- پاپ‌آپ مشاوره (اولین ورود هر کاربر)
INSERT IGNORE INTO settings (setting_key, setting_value, setting_group, title, sort_order) VALUES
('consult_popup_enabled', '1', 'popup', 'نمایش پاپ‌آپ مشاوره', 10),
('consult_popup_title',   'به ایتکو خوش آمدید 🎮', 'popup', 'عنوان پاپ‌آپ', 20),
('consult_popup_text',    'اگر برای انتخاب یا خرید نیاز به راهنمایی و مشاوره دارید، با ما تماس بگیرید:', 'popup', 'متن پاپ‌آپ', 30),
('consult_popup_phone',   '09011020032', 'popup', 'شماره تماس مشاوره', 40);

-- لینک لوکیشن (گوگل مپ) برای ویجت تماس چسبان
INSERT IGNORE INTO settings (setting_key, setting_value, setting_group, title, sort_order) VALUES
('location_url', '', 'general', 'لینک لوکیشن روی نقشه (گوگل مپ)', 105);
