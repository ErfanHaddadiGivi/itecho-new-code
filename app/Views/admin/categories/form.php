<?php
/**
 * فرم افزودن / ویرایش دسته‌بندی
 *
 * @var array|null $category  اگر null باشد یعنی حالت افزودن
 * @var array      $parents   دسته‌های اصلی برای انتخاب والد
 * @var array      $errors
 */

use App\Core\Csrf;

$isEdit = $category !== null;
$action = $isEdit ? url('admin/categories/' . $category['id']) : url('admin/categories');

/** مقدار فعلی فیلد: اول مقدار بازگشتی بعد از خطا، بعد مقدار دیتابیس */
$value = static function (string $field, mixed $fallback = '') use ($category) {
    $old = App\Core\Flash::oldInput($field);
    if ($old !== null) {
        return $old;
    }
    return $category[$field] ?? $fallback;
};
?>

<div class="panel panel--form">
    <form action="<?= e($action) ?>" method="post" enctype="multipart/form-data" class="form">
        <?= Csrf::field() ?>

        <div class="field">
            <label for="name">نام دسته‌بندی <span class="req">*</span></label>
            <input type="text" id="name" name="name" value="<?= e((string) $value('name')) ?>" required>
            <?php if (isset($errors['name'])): ?>
                <span class="field__error"><?= e($errors['name']) ?></span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="parent_id">دسته والد</label>
            <select id="parent_id" name="parent_id">
                <option value="0">— دسته اصلی (سطح یک) —</option>
                <?php foreach ($parents as $parent): ?>
                    <option value="<?= (int) $parent['id'] ?>"
                        <?= (int) $value('parent_id') === (int) $parent['id'] ? 'selected' : '' ?>>
                        <?= e($parent['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="field__hint">
                دسته اصلی در نوار مگا منو یک ستون می‌سازد و زیر‌دسته‌ها داخل آن ستون نمایش داده می‌شوند.
            </span>
            <?php if (isset($errors['parent_id'])): ?>
                <span class="field__error"><?= e($errors['parent_id']) ?></span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="slug">نامک (آدرس صفحه)</label>
            <input type="text" id="slug" name="slug" value="<?= e((string) $value('slug')) ?>" dir="ltr">
            <span class="field__hint">خالی بگذارید تا خودکار از روی نام ساخته شود.</span>
        </div>

        <div class="field">
            <label for="description">توضیح کوتاه</label>
            <textarea id="description" name="description" rows="3"><?= e((string) $value('description')) ?></textarea>
        </div>

        <div class="field">
            <label>تصویر پس‌زمینه (فقط برای دسته‌های اصلی در صفحه اول)</label>
            <?php if ($isEdit && !empty($category['image'])): ?>
                <div class="brand-upload__preview brand-upload__preview--banner">
                    <img src="<?= e(url('uploads/categories/' . $category['image'])) ?>" alt="تصویر فعلی">
                </div>
                <label class="brand-upload__remove">
                    <input type="checkbox" name="remove_image" value="1"> حذف تصویر
                </label>
            <?php endif; ?>
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp">
            <span class="field__hint">
                این تصویر پشت کارت دسته در صفحه اصلی نمایش داده می‌شود. اندازه پیشنهادی حدود ۶۰۰×۴۰۰ پیکسل.
            </span>
            <?php if (isset($errors['image'])): ?>
                <span class="field__error"><?= e($errors['image']) ?></span>
            <?php endif; ?>
        </div>

        <div class="field field--narrow">
            <label for="sort_order">ترتیب نمایش</label>
            <input type="number" id="sort_order" name="sort_order"
                   value="<?= e((string) $value('sort_order', 0)) ?>" dir="ltr">
            <span class="field__hint">عدد کوچک‌تر، بالاتر نمایش داده می‌شود.</span>
        </div>

        <div class="field field--check">
            <label>
                <input type="checkbox" name="is_active" value="1"
                    <?= (int) $value('is_active', 1) === 1 ? 'checked' : '' ?>>
                فعال باشد
            </label>
        </div>

        <div class="field field--check">
            <label>
                <input type="checkbox" name="show_in_menu" value="1"
                    <?= (int) $value('show_in_menu', 1) === 1 ? 'checked' : '' ?>>
                در مگا منو نمایش داده شود
            </label>
        </div>

        <div class="form-actions">
            <button class="btn btn--primary" type="submit">
                <?= $isEdit ? 'ذخیره تغییرات' : 'افزودن دسته‌بندی' ?>
            </button>
            <a class="btn btn--ghost" href="<?= e(url('admin/categories')) ?>">انصراف</a>
        </div>
    </form>
</div>
