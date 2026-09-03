-- =====================================================================
--  تنظیمات صفحهٔ «اپل‌آیدی آمریکا» روی سایت (باکس/لندینگ با لینک به ربات تلگرام)
--  در phpMyAdmin → Import اجرا کنید. امن و قابل‌اجرای مجدد است.
-- =====================================================================

INSERT IGNORE INTO settings (setting_key, setting_value, setting_group, title, sort_order) VALUES
    ('appleid_enabled',      '0', 'appleid', 'نمایش صفحهٔ اپل‌آیدی', 1),
    ('appleid_bot_username', '',  'appleid', 'یوزرنیم ربات تلگرام (بدون @)', 2),
    ('appleid_start_price',  '',  'appleid', 'شروع قیمت‌ها (تومان) — فقط عدد', 3);
