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
            <textarea id="content" name="content" rows="14"><?= e((string) $value('content')) ?></textarea>
            <span class="field__hint">می‌توانید از تگ‌های ساده HTML مثل &lt;p&gt;، &lt;h2&gt;، &lt;strong&gt; و &lt;ul&gt; استفاده کنید.</span>
        </div>

        <!-- ============ تحلیل سئو (بلادرنگ) ============ -->
        <div class="seo-box" id="seo-box"
             data-blogbase="<?= e(rtrim(url('blog'), '/')) ?>"
             data-hascover="<?= $isEdit && !empty($post['cover_image']) ? '1' : '0' ?>">
            <div class="seo-box__head">
                <h3>تحلیل سئو</h3>
                <div class="seo-score" id="seo-score">
                    <span class="seo-score__num" id="seo-score-num">۰</span>
                    <span class="seo-score__label" id="seo-score-label">—</span>
                </div>
            </div>

            <div class="field">
                <label for="focus_keyword">کلمه کلیدی اصلی</label>
                <input type="text" id="focus_keyword" name="focus_keyword"
                       value="<?= e((string) $value('focus_keyword')) ?>" maxlength="120"
                       placeholder="مثلاً: خرید کنسول ps5">
                <span class="field__hint">عبارتی که می‌خواهید این مطلب با آن در گوگل دیده شود.</span>
            </div>

            <div class="field">
                <label for="meta_title">عنوان سئو (Title)</label>
                <input type="text" id="meta_title" name="meta_title"
                       value="<?= e((string) $value('meta_title')) ?>" maxlength="191"
                       placeholder="خالی بگذارید تا از عنوان مطلب ساخته شود">
                <span class="seo-count" id="meta_title_count"></span>
            </div>

            <div class="field">
                <label for="meta_description">توضیح متا (Description)</label>
                <textarea id="meta_description" name="meta_description" rows="2" maxlength="300"
                          placeholder="خلاصه‌ی جذاب برای نمایش در نتایج گوگل"><?= e((string) $value('meta_description')) ?></textarea>
                <span class="seo-count" id="meta_description_count"></span>
            </div>

            <!-- پیش‌نمایش نتیجه‌ی گوگل -->
            <div class="seo-snippet" aria-hidden="true">
                <span class="seo-snippet__url" id="snippet-url"></span>
                <span class="seo-snippet__title" id="snippet-title"></span>
                <span class="seo-snippet__desc" id="snippet-desc"></span>
            </div>

            <!-- فهرست بررسی‌ها -->
            <ul class="seo-checks" id="seo-checks"></ul>
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

<script src="<?= e(asset('js/seo-analyzer.js')) ?>" defer></script>
