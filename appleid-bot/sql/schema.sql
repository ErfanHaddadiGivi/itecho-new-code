-- =====================================================================
--  ربات اپل‌آیدی آیتکو — ساختار دیتابیس (schema)
--  MySQL / MariaDB — utf8mb4 — InnoDB
--
--  نام جدول‌ها و ستون‌ها انگلیسی است. مقدارِ محتوایی (مثل نام ضمانت)
--  می‌تواند فارسی باشد چون داده است، نه کد.
--
--  روش راه‌اندازی: این فایل را در phpMyAdmin → Import اجرا کنید.
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+03:30';

-- ---------------------------------------------------------------------
-- تنظیمات کلیدی/مقداری (شماره کارت، نام صاحب کارت، یوزرنیم ربات و ...)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    `key`      VARCHAR(64) NOT NULL,
    `value`    TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- ادمین‌ها (بر اساس شناسه عددی تلگرام)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    telegram_user_id BIGINT NOT NULL,
    name             VARCHAR(120) NULL,
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admins_tg (telegram_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- انواع ضمانت (قابل مدیریت)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS warranty_types (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    sort_order  INT NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_warranty_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- محصول‌ها (ترکیب ریجن + نوع ضمانت + آیکلود + قیمت)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    region           VARCHAR(8) NOT NULL DEFAULT 'US',
    warranty_type_id INT UNSIGNED NOT NULL,
    icloud_enabled   TINYINT(1) NOT NULL DEFAULT 0,
    price_regular    BIGINT NOT NULL DEFAULT 0,
    price_partner    BIGINT NOT NULL DEFAULT 0,
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    sort_order       INT NOT NULL DEFAULT 0,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_products_active (is_active, sort_order),
    KEY fk_products_warranty (warranty_type_id),
    CONSTRAINT fk_products_warranty FOREIGN KEY (warranty_type_id)
        REFERENCES warranty_types (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- همکارها (partner) — قیمت همکاری + حساب ماهانه
-- balance: بدهی/جاری همکار
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS partners (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    telegram_user_id BIGINT NOT NULL,
    display_name     VARCHAR(120) NULL,
    status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    credit_limit     BIGINT NULL,
    balance          BIGINT NOT NULL DEFAULT 0,
    approved_by      BIGINT NULL,
    approved_at      DATETIME NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_partners_tg (telegram_user_id),
    KEY idx_partners_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- وضعیت ماشین حالت هر کاربر (state machine)
-- context_json: دادهٔ موقتِ در حال جمع‌آوری
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS conversations (
    telegram_user_id BIGINT NOT NULL,
    state            VARCHAR(48) NOT NULL DEFAULT 'START',
    context_json     TEXT NULL,
    active_order_id  BIGINT NULL,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (telegram_user_id),
    KEY idx_conv_updated (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- سفارش‌ها
-- تمام فیلدهای *_enc با AES-256 رمزنگاری‌شده ذخیره می‌شوند.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    channel               ENUM('bot','web') NOT NULL DEFAULT 'bot',
    telegram_user_id      BIGINT NOT NULL DEFAULT 0,
    telegram_username     VARCHAR(64) NULL,
    web_user_id           INT UNSIGNED NULL,
    product_id            INT UNSIGNED NOT NULL,
    price_type            ENUM('regular','partner') NOT NULL DEFAULT 'regular',
    price_amount          BIGINT NOT NULL DEFAULT 0,
    first_name_enc        TEXT NULL,
    last_name_enc         TEXT NULL,
    phone_enc             TEXT NULL,
    email_enc             TEXT NULL,
    birthdate_enc         TEXT NULL,
    payment_method        ENUM('receipt','partner_account') NULL,
    receipt_file_id       VARCHAR(255) NULL,
    verification_code_enc TEXT NULL,
    final_credentials_enc TEXT NULL,
    status                ENUM('draft','pending_payment','pending_approval',
                               'approved_awaiting_code','code_received','completed',
                               'rejected','cancelled') NOT NULL DEFAULT 'draft',
    reject_reason         VARCHAR(255) NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at          DATETIME NULL,
    purged_at             DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_orders_status (status),
    KEY idx_orders_user (telegram_user_id),
    KEY idx_orders_web_user (web_user_id),
    KEY idx_orders_purge (status, completed_at, purged_at),
    KEY fk_orders_product (product_id),
    CONSTRAINT fk_orders_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- کاربران وبِ بخش اپل‌آیدی سایت (ورود با موبایل + رمز)
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- دفتر حساب همکار (هر تراکنش مالی)
-- type: charge (بدهکار شدن)، settlement (تسویه)، adjustment (اصلاح دستی)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS partner_ledger (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    partner_id            INT UNSIGNED NOT NULL,
    order_id              BIGINT UNSIGNED NULL,
    type                  ENUM('charge','settlement','adjustment') NOT NULL,
    amount                BIGINT NOT NULL,
    balance_after         BIGINT NOT NULL,
    admin_telegram_user_id BIGINT NOT NULL,
    note                  VARCHAR(255) NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ledger_partner (partner_id, created_at),
    KEY fk_ledger_order (order_id),
    CONSTRAINT fk_ledger_partner FOREIGN KEY (partner_id)
        REFERENCES partners (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ledger_order FOREIGN KEY (order_id)
        REFERENCES orders (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- گزارش رخدادها (audit) برای هر اقدام مالی/مدیریتی
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_log (
    id                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_telegram_user_id BIGINT NOT NULL,
    action                 VARCHAR(64) NOT NULL,
    entity_type            VARCHAR(48) NOT NULL,
    entity_id              VARCHAR(64) NOT NULL,
    details_json           TEXT NULL,
    created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_admin (admin_telegram_user_id, created_at),
    KEY idx_audit_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- محدودسازی نرخ درخواست هر کاربر (ضد اسپم)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rate_limits (
    id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    telegram_user_id BIGINT NOT NULL,
    window_start     DATETIME NOT NULL,
    request_count    INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rate_user_window (telegram_user_id, window_start),
    KEY idx_rate_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  دادهٔ اولیه (پیش‌فرض‌ها) — با INSERT IGNORE تا دوباره‌اجرا امن باشد
-- =====================================================================

INSERT IGNORE INTO settings (`key`, `value`) VALUES
    ('bot_username', ''),
    ('card_number', ''),
    ('card_holder_name', ''),
    ('sensitive_data_retention_days', '3'),
    ('admin_setup_password_hash', ''),
    ('rate_limit_max_requests', '30'),
    ('rate_limit_window_seconds', '60'),
    ('session_ttl_hours', '24');

INSERT IGNORE INTO warranty_types (id, name, description, is_active, sort_order) VALUES
    (1, 'بدون ضمانت', 'اپل‌آیدی بدون گارانتی جایگزینی', 1, 1),
    (2, 'ضمانت ۷ روزه', 'در صورت مشکل تا ۷ روز جایگزین می‌شود', 1, 2),
    (3, 'ضمانت ۳۰ روزه', 'در صورت مشکل تا ۳۰ روز جایگزین می‌شود', 1, 3);

INSERT IGNORE INTO products (id, region, warranty_type_id, icloud_enabled, price_regular, price_partner, is_active, sort_order) VALUES
    (1, 'US', 1, 0, 200000, 150000, 1, 1),
    (2, 'US', 2, 1, 350000, 280000, 1, 2),
    (3, 'US', 3, 1, 500000, 420000, 1, 3);
