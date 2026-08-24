<?php
/**
 * پاپ‌آپ مشاوره — در اولین ورود هر کاربر (هر نشست) یک‌بار نمایش داده می‌شود.
 *
 * متن، عنوان، شماره و روشن/خاموش بودنش از تنظیمات (پاپ‌آپ مشاوره) قابل ویرایش است.
 */

use App\Models\Setting;

if (!Setting::getBool('consult_popup_enabled', true)) {
    return;
}

$title = trim((string) Setting::get('consult_popup_title', 'به ایتکو خوش آمدید'));
$text  = trim((string) Setting::get('consult_popup_text', 'برای مشاوره با ما تماس بگیرید:'));
$phone = trim((string) Setting::get('consult_popup_phone', ''));

if ($phone === '') {
    return;
}

$telHref = 'tel:' . preg_replace('/[^\d+]/', '', $phone);
?>
<div class="consult-popup" id="consult-popup" hidden>
    <div class="consult-popup__backdrop" data-consult-close></div>
    <div class="consult-popup__box" role="dialog" aria-modal="true" aria-labelledby="consult-popup-title">
        <button class="consult-popup__close" type="button" data-consult-close aria-label="بستن">×</button>
        <div class="consult-popup__icon" aria-hidden="true">🎮</div>
        <h2 class="consult-popup__title" id="consult-popup-title"><?= e($title) ?></h2>
        <p class="consult-popup__text"><?= e($text) ?></p>
        <a class="consult-popup__phone" href="<?= e($telHref) ?>" dir="ltr"><?= e(fa_digits($phone)) ?></a>
        <a class="btn btn--primary consult-popup__cta" href="<?= e($telHref) ?>">تماس می‌گیرم</a>
    </div>
</div>
