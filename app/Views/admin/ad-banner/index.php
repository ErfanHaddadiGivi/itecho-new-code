<?php
/**
 * تنظیمات بنر تبلیغاتی صفحه اصلی
 *
 * @var bool   $enabled
 * @var string $image   نام فایل تصویر دسکتاپ
 * @var string $mobile  نام فایل تصویر موبایل
 * @var string $link    آدرس مقصد بنر
 * @var array  $errors
 */

use App\Core\Csrf;
?>

<div class="page-actions">
    <p class="page-hint">
        یک بنر تبلیغاتی تصویری که در <b>صفحه اصلی، زیر بخش بالایی (اسلایدر/ویدیو)</b>
        نمایش داده می‌شود. می‌توانید برای موبایل تصویر جداگانه بگذارید و یک لینک مقصد تعیین کنید.
    </p>
</div>

<?php if (isset($errors['image'])): ?>
    <div class="panel panel--todo" style="border-inline-start-color: var(--danger);">
        <p><?= e($errors['image']) ?></p>
    </div>
<?php endif; ?>

<form action="<?= e(url('admin/ad-banner')) ?>" method="post" enctype="multipart/form-data" class="form">
    <?= Csrf::field() ?>

    <section class="panel panel--form">
        <div class="field field--check">
            <label>
                <input type="checkbox" name="ad_banner_enabled" value="1" <?= $enabled ? 'checked' : '' ?>>
                بنر تبلیغاتی نمایش داده شود
            </label>
        </div>

        <div class="field">
            <label for="ad_banner_link">لینک مقصد (اختیاری)</label>
            <input type="text" id="ad_banner_link" name="ad_banner_link" dir="ltr"
                   value="<?= e((string) $link) ?>" placeholder="https://...">
            <span class="field__hint">اگر پر شود، با کلیک روی بنر کاربر به این آدرس می‌رود. خالی بگذارید تا بنر لینک نداشته باشد.</span>
        </div>

        <div class="brand-uploads">
            <!-- تصویر دسکتاپ -->
            <div class="field brand-upload">
                <label>تصویر بنر (دسکتاپ)</label>
                <?php if ($image): ?>
                    <div class="brand-upload__preview brand-upload__preview--banner">
                        <img src="<?= e(url('uploads/banners/' . $image)) ?>" alt="بنر فعلی">
                    </div>
                    <label class="brand-upload__remove">
                        <input type="checkbox" name="remove_ad_image" value="1"> حذف تصویر دسکتاپ
                    </label>
                <?php endif; ?>
                <input type="file" name="ad_image" accept="image/png,image/jpeg,image/webp">
                <span class="field__hint">تصویر پهن (مثلاً ۱۲۰۰×۳۰۰ پیکسل). فرمت JPG، PNG یا WebP.</span>
            </div>

            <!-- تصویر موبایل -->
            <div class="field brand-upload">
                <label>تصویر بنر (موبایل) — اختیاری</label>
                <?php if ($mobile): ?>
                    <div class="brand-upload__preview brand-upload__preview--banner">
                        <img src="<?= e(url('uploads/banners/' . $mobile)) ?>" alt="بنر موبایل فعلی">
                    </div>
                    <label class="brand-upload__remove">
                        <input type="checkbox" name="remove_ad_image_mobile" value="1"> حذف تصویر موبایل
                    </label>
                <?php endif; ?>
                <input type="file" name="ad_image_mobile" accept="image/png,image/jpeg,image/webp">
                <span class="field__hint">اگر خالی بماند، همان تصویر دسکتاپ روی موبایل هم نشان داده می‌شود. برای نمایش بهتر، تصویر مربعی‌تر بگذارید.</span>
            </div>
        </div>

        <div class="form-actions form-actions--sticky">
            <button class="btn btn--primary" type="submit">ذخیره بنر</button>
        </div>
    </section>
</form>
