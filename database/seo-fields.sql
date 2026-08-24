-- =====================================================================
--  به‌روزرسانی سئو: افزودن فیلدهای سئو به جدول مطالب (posts)
--
--  این فایل را یک‌بار در phpMyAdmin → Import اجرا کنید.
--  (چون MySQL از ADD COLUMN IF NOT EXISTS پشتیبانی نمی‌کند، اگر ستون‌ها
--   از قبل باشند خطای «Duplicate column» می‌دهد که بی‌خطر است و می‌توانید
--   نادیده بگیرید.)
-- =====================================================================

ALTER TABLE posts
  ADD COLUMN meta_title       VARCHAR(191) DEFAULT NULL COMMENT 'عنوان سئو (تگ title)' AFTER excerpt,
  ADD COLUMN meta_description VARCHAR(300) DEFAULT NULL COMMENT 'توضیح متا برای گوگل'  AFTER meta_title,
  ADD COLUMN focus_keyword    VARCHAR(120) DEFAULT NULL COMMENT 'کلمه کلیدی اصلی مطلب' AFTER meta_description;
