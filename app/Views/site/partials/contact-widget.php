<?php
/**
 * ویجت تماس چسبان (کنار صفحه).
 *
 * همه‌ی مقادیر از تنظیمات خوانده می‌شوند و از پنل (تنظیمات → اطلاعات کلی سایت)
 * قابل ویرایش‌اند. فقط آیکونی که مقدارش پر شده باشد نمایش داده می‌شود.
 */

use App\Models\Setting;

$phone     = trim((string) Setting::get('site_phone', ''));
$telegram  = trim((string) Setting::get('telegram_url', ''));
$whatsapp  = trim((string) Setting::get('whatsapp_number', ''));
$location  = trim((string) Setting::get('location_url', ''));

// نرمال‌سازی لینک‌ها
$telHref = $phone !== '' ? 'tel:' . preg_replace('/[^\d+]/', '', $phone) : '';

$telegramHref = '';
if ($telegram !== '') {
    if (str_starts_with($telegram, 'http')) {
        $telegramHref = $telegram;
    } else {
        $telegramHref = 'https://t.me/' . ltrim($telegram, '@');
    }
}

$whatsappHref = '';
if ($whatsapp !== '') {
    $digits = preg_replace('/\D/', '', $whatsapp);
    // شماره ایرانی که با ۰ شروع می‌شود را به فرمت بین‌المللی تبدیل می‌کنیم
    if (str_starts_with($digits, '0')) {
        $digits = '98' . substr($digits, 1);
    }
    $whatsappHref = $digits !== '' ? 'https://wa.me/' . $digits : '';
}

// اگر هیچ راه ارتباطی تنظیم نشده، ویجت را اصلاً نشان نده
if ($telHref === '' && $telegramHref === '' && $whatsappHref === '' && $location === '') {
    return;
}
?>
<div class="contact-fab" aria-label="راه‌های ارتباط">
    <button class="contact-fab__toggle" type="button" aria-expanded="false" aria-controls="contact-fab-list"
            aria-label="راه‌های ارتباط با ما">
        <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true">
            <path d="M4 5c0 8.3 6.7 15 15 15 .7 0 1-.6 1-1.2v-3c0-.5-.3-.9-.8-1l-3.1-.7c-.4-.1-.9 0-1.2.4l-1 1.2A12 12 0 0 1 8.3 10l1.2-1c.3-.3.5-.8.4-1.2L9.2 4.8C9 4.3 8.7 4 8.2 4h-3C4.6 4 4 4.3 4 5Z"
                  fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
        </svg>
    </button>

    <ul class="contact-fab__list" id="contact-fab-list">
        <?php if ($telHref !== ''): ?>
            <li><a class="contact-fab__item contact-fab__item--phone" href="<?= e($telHref) ?>" aria-label="تماس تلفنی">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M4 5c0 8.3 6.7 15 15 15 .7 0 1-.6 1-1.2v-3c0-.5-.3-.9-.8-1l-3.1-.7c-.4-.1-.9 0-1.2.4l-1 1.2A12 12 0 0 1 8.3 10l1.2-1c.3-.3.5-.8.4-1.2L9.2 4.8C9 4.3 8.7 4 8.2 4h-3C4.6 4 4 4.3 4 5Z" fill="currentColor"/></svg>
                <span>تماس</span>
            </a></li>
        <?php endif; ?>

        <?php if ($whatsappHref !== ''): ?>
            <li><a class="contact-fab__item contact-fab__item--whatsapp" href="<?= e($whatsappHref) ?>"
                   target="_blank" rel="noopener" aria-label="واتساپ">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.5 15.3L2 22l4.8-1.4A10 10 0 1 0 12 2Zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .3-3.4-.7-2.9-1.2-4.7-4.2-4.9-4.4-.1-.2-1.1-1.5-1.1-2.8s.7-2 .9-2.2c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.4.5c-.2.2-.3.3-.1.6.2.3.9 1.4 1.9 2.3 1.3 1.1 2.3 1.4 2.6 1.6.3.1.4.1.6-.1l.7-.9c.2-.3.4-.2.6-.1l1.9.9c.3.1.4.2.5.3.1.2.1.7-.1 1.3Z" fill="currentColor"/></svg>
                <span>واتساپ</span>
            </a></li>
        <?php endif; ?>

        <?php if ($telegramHref !== ''): ?>
            <li><a class="contact-fab__item contact-fab__item--telegram" href="<?= e($telegramHref) ?>"
                   target="_blank" rel="noopener" aria-label="تلگرام">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M21.9 4.3 18.7 19c-.2 1-.9 1.3-1.7.8l-4.6-3.4-2.2 2.1c-.3.3-.5.5-.9.5l.3-4.6L18.3 6c.4-.3-.1-.5-.6-.2L7.4 12.5l-4.5-1.4c-1-.3-1-.9.2-1.4l17.4-6.7c.8-.3 1.5.2 1.4 1.3Z" fill="currentColor"/></svg>
                <span>تلگرام</span>
            </a></li>
        <?php endif; ?>

        <?php if ($location !== ''): ?>
            <li><a class="contact-fab__item contact-fab__item--map" href="<?= e($location) ?>"
                   target="_blank" rel="noopener" aria-label="موقعیت روی نقشه">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" fill="currentColor"/></svg>
                <span>موقعیت</span>
            </a></li>
        <?php endif; ?>
    </ul>
</div>
