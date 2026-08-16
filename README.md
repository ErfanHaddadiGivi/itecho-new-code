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
- [ ] ۲. ساختار پایه پروژه (Router، اتصال دیتابیس، پنل ادمین)
- [ ] ۳. صفحات فروشگاه (لیست/جزئیات محصول، سبد خرید)
- [ ] ۴. پرداخت و سفارش
- [ ] ۵. حساب کاربری و نظرات

## نصب دیتابیس

راهنمای کامل در [`docs/DATABASE.md`](docs/DATABASE.md).

به‌طور خلاصه: در phpMyAdmin ابتدا `database/schema.sql` و سپس `database/seed.sql` را Import کنید.
