<?php
/**
 * فرم افزودن / ویرایش اسلاید
 *
 * @var array|null $banner  اگر null باشد یعنی حالت افزودن
 * @var array      $errors
 */

use App\Core\Csrf;

$isEdit = $banner !== null;
$action = $isEdit ? url('admin/banners/' . $banner['id']) : url('admin/banners');

$value = static function (string $field, mixed $fallback = '') use ($banner) {
    $old = App\Core\Flash::oldInput($field);
    if ($old !== null) {
        return $old;
    }
    return $banner[$field] ?? $fallback;
};
?>

<div class="panel panel--form">
    <form action="<?= e($action) ?>" method="post" enctype="multipart/form-data" class="form">
        <?= Csrf::field() ?>

        <!-- تصویر اصلی -->
        <div class="field">
            <label>تصویر اسلاید <?= $isEdit ? '' : '<span class="req">*</span>' ?></label>
            <?php if ($isEdit && !empty($banner['image'])): ?>
                <div class="brand-upload__preview brand-upload__preview--banner">
                    <img src="<?= e(url('uploads/banners/' . $banner['image'])) ?>" alt="تصویر فعلی">
                </div>
            <?php endif; ?>
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
                   <?= $isEdit ? '' : 'required' ?>>
            <span class="field__hint">
                اندازه پیشنهادی حدود ۱۲۰۰×۴۰۰ پیکسل. فرمت JPG، PNG یا WebP.
                <?= $isEdit ? 'برای نگه‌داشتن تصویر فعلی، این فیلد را خالی بگذارید.' : '' ?>
            </span>
            <?php if (isset($errors['image'])): ?>
                <span class="field__error"><?= e($errors['image']) ?></span>
            <?php endif; ?>
        </div>

        <!-- تصویر موبایل (اختیاری) -->
        <div class="field">
            <label>تصویر مخصوص موبایل (اختیاری)</label>
            <?php if ($isEdit && !empty($banner['mobile_image'])): ?>
                <div class="brand-upload__preview brand-upload__preview--banner">
                    <img src="<?= e(url('uploads/banners/' . $banner['mobile_image'])) ?>" alt="تصویر موبایل فعلی">
                </div>
                <label class="brand-upload__remove">
                    <input type="checkbox" name="remove_mobile_image" value="1"> حذف تصویر موبایل
                </label>
            <?php endif; ?>
            <input type="file" name="mobile_image" accept="image/png,image/jpeg,image/webp">
            <span class="field__hint">اگر خالی باشد، همان تصویر اصلی روی موبایل هم نمایش داده می‌شود.</span>
            <?php if (isset($errors['mobile_image'])): ?>
                <span class="field__error"><?= e($errors['mobile_image']) ?></span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="title">عنوان (اختیاری)</label>
            <input type="text" id="title" name="title" value="<?= e((string) $value('title')) ?>" maxlength="191">
            <span class="field__hint">فقط برای شناسایی در همین فهرست است و روی سایت نمایش داده نمی‌شود.</span>
        </div>

        <div class="field">
            <label for="link_url">لینک مقصد (اختیاری)</label>
            <input type="text" id="link_url" name="link_url" value="<?= e((string) $value('link_url')) ?>" dir="ltr"
                   placeholder="مثلاً /category/mobile یا آدرس کامل">
            <span class="field__hint">با کلیک روی اسلاید کاربر به این آدرس می‌رود.</span>
        </div>

        <div class="field field--narrow">
            <label for="sort_order">ترتیب نمایش</label>
            <input type="number" id="sort_order" name="sort_order"
                   value="<?= e((string) $value('sort_order', 0)) ?>" dir="ltr">
        </div>

        <div class="field field--check">
            <label>
                <input type="checkbox" name="is_active" value="1"
                    <?= (int) $value('is_active', 1) === 1 ? 'checked' : '' ?>>
                فعال باشد (روی سایت نمایش داده شود)
            </label>
        </div>

        <div class="form-actions">
            <button class="btn btn--primary" type="submit">
                <?= $isEdit ? 'ذخیره تغییرات' : 'افزودن اسلاید' ?>
            </button>
            <a class="btn btn--ghost" href="<?= e(url('admin/banners')) ?>">انصراف</a>
        </div>
    </form>
</div>
