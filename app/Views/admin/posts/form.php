<?php
/**
 * فرم افزودن / ویرایش مطلب
 *
 * @var array|null $post
 * @var array      $errors
 */

use App\Core\Csrf;

$isEdit = $post !== null;
$action = $isEdit ? url('admin/posts/' . $post['id']) : url('admin/posts');

$value = static function (string $field, mixed $fallback = '') use ($post) {
    $old = App\Core\Flash::oldInput($field);
    if ($old !== null) {
        return $old;
    }
    return $post[$field] ?? $fallback;
};
?>

<div class="panel panel--form">
    <form action="<?= e($action) ?>" method="post" enctype="multipart/form-data" class="form">
        <?= Csrf::field() ?>

        <div class="field">
            <label for="title">عنوان مطلب <span class="req">*</span></label>
            <input type="text" id="title" name="title" value="<?= e((string) $value('title')) ?>" required maxlength="191">
            <?php if (isset($errors['title'])): ?>
                <span class="field__error"><?= e($errors['title']) ?></span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="slug">نامک (آدرس مطلب)</label>
            <input type="text" id="slug" name="slug" value="<?= e((string) $value('slug')) ?>" dir="ltr">
            <span class="field__hint">خالی بگذارید تا خودکار از روی عنوان ساخته شود.</span>
        </div>

        <div class="field">
            <label>تصویر کاور</label>
            <?php if ($isEdit && !empty($post['cover_image'])): ?>
                <div class="brand-upload__preview brand-upload__preview--banner">
                    <img src="<?= e(url('uploads/posts/' . $post['cover_image'])) ?>" alt="کاور فعلی">
                </div>
                <label class="brand-upload__remove">
                    <input type="checkbox" name="remove_cover" value="1"> حذف تصویر کاور
                </label>
            <?php endif; ?>
            <input type="file" name="cover_image" accept="image/png,image/jpeg,image/webp">
            <?php if (isset($errors['cover_image'])): ?>
                <span class="field__error"><?= e($errors['cover_image']) ?></span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="excerpt">خلاصه کوتاه</label>
            <textarea id="excerpt" name="excerpt" rows="2" maxlength="500"><?= e((string) $value('excerpt')) ?></textarea>
            <span class="field__hint">در کارت مطلب و نتایج نمایش داده می‌شود.</span>
            <?php if (isset($errors['excerpt'])): ?>
                <span class="field__error"><?= e($errors['excerpt']) ?></span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="content">متن کامل مطلب</label>
            <textarea id="content" name="content" rows="14" class="mono-ltr-off"><?= e((string) $value('content')) ?></textarea>
            <span class="field__hint">می‌توانید از تگ‌های ساده HTML مثل &lt;p&gt;، &lt;h2&gt;، &lt;strong&gt; و &lt;ul&gt; استفاده کنید.</span>
        </div>

        <div class="field field--check">
            <label>
                <input type="checkbox" name="is_published" value="1"
                    <?= (int) $value('is_published', 1) === 1 ? 'checked' : '' ?>>
                منتشر شود (در سایت نمایش داده شود)
            </label>
        </div>

        <div class="form-actions">
            <button class="btn btn--primary" type="submit">
                <?= $isEdit ? 'ذخیره تغییرات' : 'افزودن مطلب' ?>
            </button>
            <a class="btn btn--ghost" href="<?= e(url('admin/posts')) ?>">انصراف</a>
        </div>
    </form>
</div>
