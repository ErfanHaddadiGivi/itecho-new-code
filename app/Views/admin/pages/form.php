<?php
/**
 * ویرایش متن یک صفحه ثابت
 *
 * @var array $page
 * @var array $errors
 */

use App\Core\Csrf;

$value = static function (string $field, mixed $fallback = '') use ($page) {
    $old = App\Core\Flash::oldInput($field);
    if ($old !== null) {
        return $old;
    }
    return $page[$field] ?? $fallback;
};
?>

<div class="panel panel--form">
    <form action="<?= e(url('admin/pages/' . $page['id'])) ?>" method="post" class="form">
        <?= Csrf::field() ?>

        <div class="field">
            <label for="title">عنوان صفحه <span class="req">*</span></label>
            <input type="text" id="title" name="title" value="<?= e((string) $value('title')) ?>" required>
            <?php if (isset($errors['title'])): ?>
                <span class="field__error"><?= e($errors['title']) ?></span>
            <?php endif; ?>
            <span class="field__hint">آدرس صفحه ثابت است: <code class="ltr">/page/<?= e($page['slug']) ?></code></span>
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
            <button class="btn btn--primary" type="submit">ذخیره تغییرات</button>
            <a class="btn btn--ghost" href="<?= e(url('admin/pages')) ?>">انصراف</a>
        </div>
    </form>
</div>
