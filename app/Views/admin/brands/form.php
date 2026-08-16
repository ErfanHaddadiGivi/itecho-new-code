<?php
/**
 * فرم افزودن / ویرایش برند
 *
 * @var array|null $brand
 * @var array      $errors
 */

use App\Core\Csrf;

$isEdit = $brand !== null;
$action = $isEdit ? url('admin/brands/' . $brand['id']) : url('admin/brands');

$value = static function (string $field, mixed $fallback = '') use ($brand) {
    $old = App\Core\Flash::oldInput($field);
    if ($old !== null) {
        return $old;
    }
    return $brand[$field] ?? $fallback;
};
?>

<div class="panel panel--form">
    <form action="<?= e($action) ?>" method="post" class="form">
        <?= Csrf::field() ?>

        <div class="field">
            <label for="name">نام برند <span class="req">*</span></label>
            <input type="text" id="name" name="name" value="<?= e((string) $value('name')) ?>" required>
            <?php if (isset($errors['name'])): ?>
                <span class="field__error"><?= e($errors['name']) ?></span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="slug">نامک (آدرس)</label>
            <input type="text" id="slug" name="slug" value="<?= e((string) $value('slug')) ?>" dir="ltr">
            <span class="field__hint">خالی بگذارید تا خودکار ساخته شود.</span>
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
                فعال باشد
            </label>
        </div>

        <div class="form-actions">
            <button class="btn btn--primary" type="submit">
                <?= $isEdit ? 'ذخیره تغییرات' : 'افزودن برند' ?>
            </button>
            <a class="btn btn--ghost" href="<?= e(url('admin/brands')) ?>">انصراف</a>
        </div>
    </form>
</div>
