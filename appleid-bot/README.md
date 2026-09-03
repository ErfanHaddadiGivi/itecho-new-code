# ربات اپل‌آیدی آیتکو

ربات تلگرام فروش اپل‌آیدی ریجن آمریکا + مدیریت کامل داخل تلگرام.
PHP خام + PDO + MySQL/MariaDB — **بدون Composer، بدون فریم‌ورک، بدون SSH**.
مناسب هاست اشتراکی cPanel با Webhook و Cron.

---

## راه‌اندازی گام‌به‌گام

### ۱) ساخت ربات
- در تلگرام به [@BotFather](https://t.me/BotFather) برو → `/newbot` → توکن را بردار.
- شناسهٔ عددی ادمین(ها) را با [@userinfobot](https://t.me/userinfobot) بگیر.

### ۲) دیتابیس
- یک دیتابیس MySQL بساز.
- فایل `sql/schema.sql` را در **phpMyAdmin → Import** اجرا کن.
  (سه محصول و سه نوع ضمانتِ نمونه هم ساخته می‌شود؛ بعداً قیمت/فعال‌بودن را عوض کن.)

### ۳) تنظیمات
```bash
cp config/config.example.php config/config.php
```
سپس `config/config.php` را پر کن:
- `db` → اطلاعات دیتابیس
- `bot_token` → توکن BotFather
- `webhook_secret` → یک رشتهٔ تصادفی:
  ```bash
  php -r "echo bin2hex(random_bytes(24)).PHP_EOL;"
  ```
- `encryption_key` → کلید ۳۲بایتی (یک‌بار بساز، **هرگز عوض نکن**):
  ```bash
  php -r "echo base64_encode(random_bytes(32)).PHP_EOL;"
  ```
- `admin_ids` → شناسهٔ عددی ادمین‌ها، مثل `[123456789]`

### ۴) آپلود روی هاست
- کل پوشهٔ `appleid-bot/` را آپلود کن.
- بهترین حالت: `config/` بیرون از `public_html` باشد. اگر نشد، همین `.htaccess`
  دسترسی وب به `config`, `src`, `cron`, `sql`, `lang`, `logs` را می‌بندد.
- فقط `webhook.php` باید از بیرون در دسترس باشد (HTTPS الزامی).

### ۵) ثبت Webhook
آدرس `webhook.php` را به تلگرام بده (با همان secret):
```bash
curl "https://api.telegram.org/bot<BOT_TOKEN>/setWebhook" \
  -d "url=https://YOUR_DOMAIN/appleid-bot/webhook.php" \
  -d "secret_token=<WEBHOOK_SECRET>"
```
> `secret_token` باید **دقیقاً** برابر `webhook_secret` در config باشد.

### ۶) Cron (پاک‌سازی دادهٔ حساس)
در cPanel → Cron Jobs، هر ۶ ساعت:
```
php /home/USER/appleid-bot/cron/purge_sensitive.php
```

### ۷) تنظیم کارت واریز
در تلگرام به ربات (به‌عنوان ادمین) بفرست:
```
/setcard 6037xxxxxxxxxxxx نام صاحب کارت
```

### ۸) اتصال به سایت آیتکو
- در سایت: **phpMyAdmin → Import** فایل `database/appleid.sql` را اجرا کن.
- در پنل مدیریت سایت → **تنظیمات → اپل‌آیدی آمریکا**:
  «نمایش صفحهٔ اپل‌آیدی» را روشن کن، «یوزرنیم ربات» و «شروع قیمت‌ها» را پر کن.
- صفحهٔ `https://YOUR_SITE/appleid` با دکمهٔ «شروع سفارش در تلگرام»
  (`t.me/<bot>?start=appleid`) فعال می‌شود.

---

## دستورهای ادمین (داخل تلگرام)
| دستور | کار |
|------|-----|
| `/setcard <شماره> <نام>` | تنظیم کارت واریز |
| `/partners` | لیست همکارهای در انتظار تأیید (با دکمهٔ تأیید/رد) |
| `/addpartner <tg_id> <نام>` | افزودن همکار جدید (در انتظار) |
| `/ledger <partner_id>` | خلاصه‌حساب و تراکنش‌های همکار |
| `/settle <partner_id> <مبلغ>` | ثبت تسویهٔ همکار |
| `/admin` | راهنما |

تأیید/رد سفارش، «ثبت روی حساب همکار» و «ثبت اطلاعات نهایی» از روی دکمه‌های
زیرِ هر سفارش انجام می‌شود.

---

## نکات امنیتی
- همهٔ دادهٔ شخصی مشتری + کریدنشال نهایی با **AES-256-GCM** رمز می‌شود؛ کلید
  فقط در `config` است، نه دیتابیس.
- کد تأیید ایمیل **ephemeral** است: بعد از تحویل بلافاصله پاک می‌شود.
- دادهٔ حساسِ سفارش‌های تمام/رد/لغوشده بعد از `sensitive_data_retention_days`
  روز توسط cron پاک می‌شود.
- همهٔ کوئری‌ها Prepared Statement؛ همهٔ ورودی‌ها validate/sanitize.
- وب‌هوک با هدر مخفی محافظت می‌شود؛ فقط POSTِ معتبر پردازش می‌شود.
- هر اقدام مالی/مدیریتی در `audit_log` ثبت می‌شود.

## مدیریت محصول (MVP)
افزودن/ویرایش محصول و نوع ضمانت فعلاً از طریق دیتابیس (جدول‌های `products` و
`warranty_types`) انجام می‌شود. قیمت‌ها بر حسب تومان‌اند و `price_partner`
قیمتی است که به همکارهای تأییدشده نمایش داده می‌شود.
