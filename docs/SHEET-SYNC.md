# همگام‌سازی محصولات از گوگل‌شیت

قیمت و موجودی محصولات (و ساخت محصول ساده‌ی جدید) از یک **گوگل‌شیت خصوصی** و
به‌صورت خودکار، دو بار در روز، بدون ورود به پنل مدیریت به‌روزرسانی می‌شوند.

مدل کار **Push** است: گوگل‌شیت داده را به سرور ما می‌فرستد؛ سرور ما از گوگل چیزی
نمی‌خواند. این‌طوری مشکل شناخته‌شده‌ی ریدایرکت ۳۰۲ در Web App گوگل هم پیش نمی‌آید.

```
Google Sheet (تب database_link)
        │  Apps Script — تریگر زمانی (روزانه ۷ صبح و ۲ بعدازظهر)
        ▼  UrlFetchApp.fetch() → POST (JSON + توکن)
api/sheet-sync.php  →  اعتبارسنجی توکن → پردازش ردیف‌ها → گزارش JSON
```

---

## ۱) راه‌اندازی سمت سرور (یک‌بار)

### الف. ساخت جدول گزارش
اگر دیتابیس را **پیش از افزوده‌شدن این قابلیت** نصب کرده‌اید، یک‌بار فایل
`database/sync-logs.sql` را در phpMyAdmin → Import اجرا کنید. (اجرای آن امن است و
هیچ داده‌ای را پاک نمی‌کند.) در نصب تازه، این جدول از `schema.sql` ساخته می‌شود.

### ب. تعیین توکن مخفی
یک توکن تصادفی و طولانی بسازید:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

سپس آن را در فایل `config/config.local.php` بنویسید (این فایل در گیت نیست):

```php
'sheet_sync_token' => 'همان‌مقداری‌که‌ساختید',
```

> تا وقتی این توکن تنظیم نشود، نقطه پایانی هر درخواستی را با خطای
> «sync endpoint not configured» رد می‌کند.

### ج. آدرس نقطه پایانی
آدرس زیر همان چیزی است که در اسکریپت گوگل قرار می‌دهید (اگر سایت در زیرپوشه است،
همان زیرپوشه در آدرس می‌آید):

```
https://itecho.ir/api/sheet-sync.php
# یا در نصب زیرپوشه‌ای:
https://itecho.ir/newsite/api/sheet-sync.php
```

این آدرس در پنل، صفحه‌ی **پیکربندی → همگام‌سازی گوگل‌شیت** هم نمایش داده می‌شود.

---

## ۲) ساختار شیت

- نام تب باید دقیقاً **`database_link`** باشد.
- ردیف اول سرستون‌هاست، با همین نام‌ها:

| ستون | نگاشت به دیتابیس | توضیح |
|---|---|---|
| `name` | name | برای محصول جدید لازم است |
| `slug` | slug | دستی وارد می‌شود (خودکار ساخته نمی‌شود). برای محصول جدید لازم است و باید یکتا باشد |
| `category_id` | category_id | شناسه‌ی عددی دسته (نه نام). باید در دیتابیس موجود باشد |
| `brand_id` | brand_id | شناسه‌ی عددی برند. اگر پر شود باید موجود باشد |
| `price` | price | تومان. برای محصول جدید لازم است |
| `stock` | stock | موجودی |
| `sku` | sku | **کلید اصلی تطبیق.** برای محصول جدید لازم است |
| `condition_type` | condition_type | `new` یا `used` |
| `compare_at_price` | compare_at_price | قیمت خط‌خورده، اختیاری |
| `id` | (اختیاری) | اگر هم `id` و هم `sku` پر باشند، سرور بررسی می‌کند که به یک محصول اشاره کنند |

---

## ۳) منطق همگام‌سازی

- **تطبیق با `sku`.** اگر `sku` با محصولی بخورد → **UPDATE** و فقط `price` و `stock`
  به‌روزرسانی می‌شوند؛ بقیه‌ی ستون‌ها برای محصول موجود نادیده گرفته می‌شوند.
- اگر `sku` با هیچ محصولی نخورد → **INSERT** (محصول ساده‌ی جدید). فیلدهای لازم:
  `name`، `sku`، `price`، `category_id` (و `slug` که ستونش در دیتابیس اجباری است).
  بقیه اختیاری‌اند و به مقدار پیش‌فرض ستون برمی‌گردند.
- محصولی که در دیتابیس هست ولی `sku` آن دیگر در شیت نیست → **`is_active = 0`**
  (غیرفعال، نه حذف). فقط محصولات **ساده** (بدون تنوع) و **دارای SKU** مشمول این
  قانون‌اند؛ محصولات تنوع‌دار هرگز دست نمی‌خورند.
- اگر `category_id` یا `brand_id` در دیتابیس نباشد → آن ردیف رد می‌شود با دلیل
  `invalid category` یا `invalid brand`. دسته/برند خودکار ساخته نمی‌شود.
- هر خطای دیگر (فیلد لازم خالی، داده‌ی نامعتبر، ناسازگاری `id`/`sku`) فقط همان ردیف
  را رد می‌کند و بقیه‌ی شیت ادامه پیدا می‌کند. **یک ردیف خراب کل کار را متوقف نمی‌کند.**
- اگر شیت خالی باشد (هیچ ردیفی)، مرحله‌ی غیرفعال‌سازی اجرا **نمی‌شود** تا یک شیت
  خالی به‌اشتباه کل محصولات را غیرفعال نکند.

### حوزه‌ی خارج از این قابلیت
- **تصویر محصول:** همیشه دستی از پنل، هرگز از شیت.
- **تنوع محصول (`has_variants`):** فعلاً پشتیبانی نمی‌شود؛ فقط محصول ساده.

---

## ۴) پاسخ سرور (نمونه)

```json
{
  "success": true,
  "summary": { "inserted": 3, "updated": 42, "deactivated": 1, "rejected": 2 },
  "rejected_rows": [
    { "sku": "XYZ-123", "reason": "invalid category" },
    { "sku": null, "reason": "missing required field: price" }
  ]
}
```

- توکن نادرست/غایب → `HTTP 401` و `{"success": false, "error": "unauthorized"}`.
- این گزارش در جدول `sync_logs` ذخیره می‌شود و در پنل، صفحه‌ی
  **همگام‌سازی گوگل‌شیت** قابل مشاهده است.

---

## ۵) اسکریپت گوگل (سمت فرستنده)

در گوگل‌شیت: **Extensions → Apps Script**، کد زیر را قرار دهید. دو مقدار بالای فایل
(`ENDPOINT_URL` و `SYNC_TOKEN`) را تنظیم کنید. `SYNC_TOKEN` باید **دقیقاً** برابر
مقدار `sheet_sync_token` سرور باشد.

```javascript
// ===== Itecho — Google Sheet product sync (sender side) =====
// Set the two constants below, then run installTriggers() once to schedule
// the daily syncs. Make sure the project time zone (Project Settings) is
// Asia/Tehran so the hours below are Iran local time.

const ENDPOINT_URL = 'https://itecho.ir/newsite/api/sheet-sync.php'; // your real URL
const SYNC_TOKEN   = 'REPLACE_WITH_THE_SAME_TOKEN_AS_THE_SERVER';
const SHEET_NAME   = 'database_link';

function syncProducts() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(SHEET_NAME);
  if (!sheet) {
    Logger.log('Sheet "' + SHEET_NAME + '" not found');
    return;
  }

  const values = sheet.getDataRange().getValues();
  if (values.length < 2) {
    Logger.log('No data rows to sync');
    return;
  }

  const headers = values[0].map(function (h) { return String(h).trim(); });
  const products = [];

  for (let i = 1; i < values.length; i++) {
    const row = values[i];
    const obj = {};
    let empty = true;

    for (let c = 0; c < headers.length; c++) {
      const key = headers[c];
      if (!key) { continue; }
      const val = row[c];
      if (val !== '' && val !== null && val !== undefined) { empty = false; }
      obj[key] = (val === '' || val === null || val === undefined) ? null : val;
    }

    if (!empty) { products.push(obj); }
  }

  const payload = { token: SYNC_TOKEN, products: products };

  const response = UrlFetchApp.fetch(ENDPOINT_URL, {
    method: 'post',
    contentType: 'application/json',
    payload: JSON.stringify(payload),
    muteHttpExceptions: true
  });

  Logger.log('HTTP ' + response.getResponseCode());
  Logger.log(response.getContentText());
}

// Run this once to create two daily triggers (~07:00 and ~14:00).
function installTriggers() {
  ScriptApp.getProjectTriggers().forEach(function (t) {
    if (t.getHandlerFunction() === 'syncProducts') {
      ScriptApp.deleteTrigger(t);
    }
  });
  ScriptApp.newTrigger('syncProducts').timeBased().atHour(7).everyDays(1).create();
  ScriptApp.newTrigger('syncProducts').timeBased().atHour(14).everyDays(1).create();
  Logger.log('Triggers installed for 07:00 and 14:00');
}
```

### اجرا و زمان‌بندی
1. یک‌بار `syncProducts` را دستی Run کنید تا مجوز دسترسی داده شود و پاسخ در
   **View → Logs** دیده شود.
2. سپس `installTriggers` را یک‌بار Run کنید تا دو تریگر روزانه (۷ و ۱۴) ساخته شوند.
3. مطمئن شوید در **Project Settings** منطقه‌ی زمانی روی `Asia/Tehran` است.

---

## ۶) آپلود دستی CSV (بدون گوگل‌شیت)

اگر بخواهید بدون منتظرماندن برای تریگر روزانه، همین حالا فایلی را دستی اعمال کنید،
از پنل: **پیکربندی → همگام‌سازی محصولات → آپلود دستی فایل CSV**.

- فایل باید همان ستون‌های شیت را داشته باشد (ردیف اول = سرستون‌ها). ستون `id`
  اختیاری است. قیمت می‌تواند با جداکننده‌ی هزارگان و داخل گیومه باشد
  (مثل `"153,913,000"`).
- **منطق اعمال دقیقاً همان منطق گوگل‌شیت است** (تطبیق با SKU، درج/به‌روزرسانی،
  رد ردیف نامعتبر با دلیل). گزارشش هم در همان فهرست پایین صفحه با برچسب «آپلود CSV»
  ثبت می‌شود.
- یک تفاوت مهم و امن: به‌صورت پیش‌فرض، محصولاتی که در فایل نیستند **غیرفعال نمی‌شوند**
  (چون ممکن است فایل فقط بخشی از محصولات باشد). فقط اگر تیک
  «محصولاتی که در این فایل نیستند غیرفعال شوند» را بزنید، رفتار کامل آینه‌ای مثل
  گوگل‌شیت اعمال می‌شود — این تیک را فقط وقتی بزنید که فایل، **همه‌ی** محصولات است.

> نکته: در نمونه‌ی اولیه، ستون‌های `category_id` و `brand_id` خالی بودند؛ چون این‌ها
> برای ساخت محصول جدید لازم‌اند، آن ردیف‌ها با دلیل «missing required field:
> category_id» رد می‌شوند. کافی است شناسه‌ی عددی دسته را در شیت پر کنید.

---

## ۷) امنیت

- تنها روش احراز هویت، یک توکن مخفیِ تصادفی و طولانی است که در بدنه‌ی JSON فرستاده
  و سمت سرور با مقایسه‌ی امن زمان‌ثابت (`hash_equals`) بررسی می‌شود.
- نقطه پایانی فقط `POST` می‌پذیرد و بدون توکن معتبر هیچ پردازشی انجام نمی‌دهد.
- توکن را در گیت یا جای عمومی قرار ندهید؛ فقط در `config.local.php` و در اسکریپت
  گوگل (که خصوصی است).
