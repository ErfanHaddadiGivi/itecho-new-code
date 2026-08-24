<?php
/**
 * افزودن / ویرایش صفحه‌ی ثابت
 *
 * @var array|null $page  اگر null باشد یعنی حالت افزودن
 * @var array      $errors
 */

use App\Core\Csrf;

$isEdit = $page !== null;
$action = $isEdit ? url('admin/pages/' . $page['id']) : url('admin/pages');

$value = static function (string $field, mixed $fallback = '') use ($page) {
    $old = App\Core\Flash::oldInput($field);
    if ($old !== null) {
        return $old;
    }
    return $page[$field] ?? $fallback;
};
?>

<div class="panel panel--form">
    <form action="<?= e($action) ?>" method="post" class="form">
        <?= Csrf::field() ?>

        <div class="field">
            <label for="title">عنوان صفحه <span class="req">*</span></label>
            <input type="text" id="title" name="title" value="<?= e((string) $value('title')) ?>" required>
            <?php if (isset($errors['title'])): ?>
                <span class="field__error"><?= e($errors['title']) ?></span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="slug">نامک (آدرس صفحه)</label>
            <?php if ($isEdit): ?>
                <input type="text" id="slug" value="<?= e((string) $page['slug']) ?>" dir="ltr" disabled>
                <span class="field__hint">آدرس صفحه ثابت است: <code class="ltr">/page/<?= e($page['slug']) ?></code></span>
            <?php else: ?>
                <input type="text" id="slug" name="slug" value="<?= e((string) $value('slug')) ?>" dir="ltr">
                <span class="field__hint">خالی بگذارید تا خودکار از روی عنوان ساخته شود. بعداً قابل تغییر نیست.</span>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="content">متن صفحه</label>
            <textarea id="content" name="content" rows="16"><?= e((string) $value('content')) ?></textarea>
            <span class="field__hint">می‌توانید از تگ‌های ساده HTML مثل &lt;p&gt;، &lt;h2&gt;، &lt;strong&gt; و &lt;ul&gt; استفاده کنید.</span>
        </div>

        <div class="field">
            <label for="meta_description">توضیح متا (برای گوگل)</label>
            <input type="text" id="meta_description" name="meta_description"
                   value="<?= e((string) $value('meta_description')) ?>" maxlength="300">
        </div>

        <div class="field field--check">
            <label>
                <input type="checkbox" name="is_active" value="1"
                    <?= (int) $value('is_active', 1) === 1 ? 'checked' : '' ?>>
                فعال باشد (در سایت نمایش داده شود)
            </label>
        </div>

        <div class="form-actions">
            <button class="btn btn--primary" type="submit"><?= $isEdit ? 'ذخیره تغییرات' : 'افزودن صفحه' ?></button>
            <a class="btn btn--ghost" href="<?= e(url('admin/pages')) ?>">انصراف</a>
        </div>
    </form>
</div>
