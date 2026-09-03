-- =====================================================================
--  مهاجرت: افزودن پورتال وبِ سایت به دیتابیسِ ربات (برای نصب‌های موجود)
--  اگر schema.sql تازه ایمپورت شده، نیازی به این فایل نیست.
--  در phpMyAdmin → Import اجرا کنید. امن و قابل‌اجرای مجدد.
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS web_users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    phone         VARCHAR(15) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name          VARCHAR(120) NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_web_users_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- افزودن ستون‌های کانال و کاربر وب به orders (در صورت نبودن)
-- توجه: اگر ستون از قبل هست، این دستور خطای بی‌ضرر می‌دهد؛ نادیده بگیرید.
ALTER TABLE orders
    ADD COLUMN channel ENUM('bot','web') NOT NULL DEFAULT 'bot' AFTER id;
ALTER TABLE orders
    ADD COLUMN web_user_id INT UNSIGNED NULL AFTER telegram_username;
ALTER TABLE orders
    ADD KEY idx_orders_web_user (web_user_id);
ALTER TABLE orders
    MODIFY telegram_user_id BIGINT NOT NULL DEFAULT 0;
