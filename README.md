# Itecho — فروشگاه اینترنتی

فروشگاه اینترنتی موبایل، کامپیوتر، کنسول بازی، تجهیزات گیمینگ و لوازم جانبی.

## استک فنی

| مورد | انتخاب |
|---|---|
| بک‌اند | PHP خام + PDO (بدون فریم‌ورک) |
| دیتابیس | MySQL / MariaDB |
| فرانت | HTML + CSS + JavaScript خالص، RTL و ریسپانسیو |
| میزبانی | Shared Hosting (cPanel) — بدون نیاز به SSH، Composer، Node.js یا Docker |
| درگاه پرداخت | زرین‌پال (فعلاً حالت Sandbox) |
| ایمیل | Gmail SMTP با PHPMailer (نسخه بدون Composer) |

## وضعیت پیشرفت

- [x] **۱. طراحی دیتابیس** — `database/schema.sql` + `docs/DATABASE.md`
- [x] **۲. ساختار پایه پروژه** — Router، اتصال دیتابیس، پنل مدیریت (دسته‌بندی، برند، تنظیمات)
- [ ] ۳. صفحات فروشگاه (لیست/جزئیات محصول، سبد خرید)
- [ ] ۴. پرداخت و سفارش
- [ ] ۵. حساب کاربری و نظرات

---

## نصب

### ۱. ساخت دیتابیس
در cPanel → **MySQL Databases** یک دیتابیس و یک کاربر بسازید و کاربر را با دسترسی کامل به دیتابیس اضافه کنید.

سپس در **phpMyAdmin** به ترتیب Import کنید:
1. `database/schema.sql`
2. `database/seed.sql`

### ۲. آپلود فایل‌ها
همه فایل‌های پروژه را در `public_html` (یا زیرپوشه دلخواه) آپلود کنید.
مسیردهی به‌صورت خودکار تشخیص داده می‌شود و اگر پروژه در زیرپوشه باشد هم کار می‌کند.

### ۳. تنظیمات اتصال
از فایل `config/config.local.example.php` یک کپی بگیرید، نامش را به
`config/config.local.php` تغییر دهید و اطلاعات دیتابیس را وارد کنید.

### ۴. ورود به پنل
به آدرس `/admin/login` بروید:

```
ایمیل:     admin@itecho.ir
رمز عبور:  Admin@12345
```

> ⚠️ بلافاصله پس از نصب، رمز عبور مدیر را تغییر دهید.

---

## ساختار پروژه

```
index.php              نقطه ورود — همه درخواست‌ها از اینجا می‌گذرند
.htaccess              مسیردهی و تنظیمات امنیتی آپاچی
│
config/                تنظیمات اتصال دیتابیس (config.local.php در گیت نیست)
│
app/
├── routes.php         تعریف همه آدرس‌های سایت
├── Core/              هسته: Router، Database، Auth، View، Csrf، Flash
├── Models/            کار با جدول‌های دیتابیس
├── Controllers/       منطق هر صفحه
│   └── Admin/         کنترلرهای پنل مدیریت
└── Views/             قالب‌های نمایش
    ├── layouts/       چارچوب کلی صفحات (site / admin / blank)
    ├── site/          صفحات فروشگاه
    ├── admin/         صفحات پنل
    └── errors/        صفحات ۴۰۴ و ۵۰۰
│
assets/                CSS، JavaScript و تصاویر ثابت
uploads/               فایل‌های آپلودی (اجرای PHP در آن غیرفعال است)
database/              فایل‌های SQL
docs/                  مستندات
```

### چطور یک صفحه جدید اضافه کنم؟

۱. یک مسیر در `app/routes.php` اضافه کنید:
```php
$router->get('/my-page', 'MyController@index');
```

۲. کنترلر را در `app/Controllers/MyController.php` بسازید:
```php
class MyController extends Controller
{
    public function index(): void
    {
        $this->view('site/my-page', ['title' => 'عنوان صفحه'], 'site');
    }
}
```

۳. قالب را در `app/Views/site/my-page.php` بنویسید.

---

## نکات امنیتی رعایت‌شده

- همه کوئری‌ها با **Prepared Statement** اجرا می‌شوند (جلوگیری از SQL Injection)
- همه خروجی‌ها با تابع `e()` ایمن‌سازی می‌شوند (جلوگیری از XSS)
- همه فرم‌های POST **توکن CSRF** دارند
- رمزها با `password_hash()` ذخیره می‌شوند
- پس از ۵ تلاش ناموفق ورود، حساب ۲ دقیقه قفل می‌شود
- پوشه‌های `app`، `config` و `database` از دسترسی مستقیم مرورگر مسدود شده‌اند
- اجرای فایل PHP در پوشه `uploads` غیرفعال است

## توسعه محلی

```bash
php -S 127.0.0.1:8000 index.php
```
سپس `config/config.local.php` را با `'debug' => true` بسازید تا جزئیات خطاها نمایش داده شود.
