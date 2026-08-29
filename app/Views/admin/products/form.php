<?php
/**
 * فرم افزودن / ویرایش محصول
 *
 * @var array|null $product
 * @var array $images
 * @var array $specs
 * @var array $variants
 * @var array $categories
 * @var array $brands
 * @var array $attributes
 * @var array $errors
 */

use App\Core\Csrf;
use App\Core\Flash;

$isEdit = $product !== null;
$action = $isEdit ? url('admin/products/' . $product['id']) : url('admin/products');

$value = static function (string $field, mixed $fallback = '') use ($product) {
    $old = Flash::oldInput($field);
    if ($old !== null) {
        return $old;
    }
    return $product[$field] ?? $fallback;
};
?>

<form action="<?= e($action) ?>" method="post" class="form" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
    <?php endif; ?>

    <!-- ================= اطلاعات اصلی ================= -->
    <section class="panel panel--form">
        <h2 class="panel__title">اطلاعات اصلی</h2>

        <div class="field">
            <label for="name">نام محصول <span class="req">*</span></label>
            <input type="text" id="name" name="name" value="<?= e((string) $value('name')) ?>" required>
            <?php if (isset($errors['name'])): ?>
                <span class="field__error"><?= e($errors['name']) ?></span>
            <?php endif; ?>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="category_id">دسته‌بندی <span class="req">*</span></label>
                <select id="category_id" name="category_id" required>
                    <option value="">— انتخاب کنید —</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>"
                            <?= (int) $value('category_id') === (int) $category['id'] ? 'selected' : '' ?>>
                            <?= $category['depth'] === 1 ? '— ' : '' ?><?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['category_id'])): ?>
                    <span class="field__error"><?= e($errors['category_id']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="brand_id">برند</label>
                <select id="brand_id" name="brand_id">
                    <option value="">— بدون برند —</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int) $brand['id'] ?>"
                            <?= (int) $value('brand_id') === (int) $brand['id'] ? 'selected' : '' ?>>
                            <?= e($brand['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="sku">کد کالا (SKU)</label>
                <input type="text" id="sku" name="sku" value="<?= e((string) $value('sku')) ?>" dir="ltr">
                <?php if (isset($errors['sku'])): ?>
                    <span class="field__error"><?= e($errors['sku']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="condition_type">وضعیت کالا</label>
                <select id="condition_type" name="condition_type">
                    <option value="new"  <?= $value('condition_type', 'new') === 'new' ? 'selected' : '' ?>>نو</option>
                    <option value="used" <?= $value('condition_type') === 'used' ? 'selected' : '' ?>>کارکرده</option>
                </select>
            </div>
        </div>

        <div class="field">
            <label for="slug">نامک (آدرس صفحه)</label>
            <input type="text" id="slug" name="slug" value="<?= e((string) $value('slug')) ?>" dir="ltr">
            <span class="field__hint">خالی بگذارید تا خودکار از روی نام ساخته شود.</span>
        </div>

        <div class="field">
            <label for="short_description">توضیح کوتاه</label>
            <textarea id="short_description" name="short_description" rows="2"
            ><?= e((string) $value('short_description')) ?></textarea>
        </div>

        <div class="field">
            <label for="description">توضیحات کامل</label>
            <textarea id="description" name="description" rows="9"
                      data-editor data-upload-url="<?= e(url('admin/media/upload')) ?>"
            ><?= e((string) $value('description')) ?></textarea>
            <span class="field__hint">
                با دکمه‌های بالای این کادر می‌توانید عکس و ویدیو آپلود و در متن درج کنید.
                همچنین می‌توانید از تگ‌های ساده HTML مثل &lt;p&gt; و &lt;ul&gt; استفاده کنید.
            </span>
        </div>
    </section>

    <!-- ================= قیمت و موجودی ================= -->
    <section class="panel panel--form">
        <h2 class="panel__title">قیمت و موجودی</h2>

        <p class="field__hint field__hint--block" id="variant-price-note" hidden>
            این محصول تنوع دارد، پس قیمت و موجودی از روی جدول تنوع‌ها محاسبه می‌شود
            و فیلدهای زیر نادیده گرفته می‌شوند.
        </p>

        <div class="grid-2">
            <div class="field">
                <label for="price">قیمت فروش (تومان)</label>
                <input type="text" id="price" name="price" inputmode="numeric"
                       value="<?= e((string) $value('price', 0)) ?>" dir="ltr">
            </div>

            <div class="field">
                <label for="compare_at_price">قیمت قبل از تخفیف (تومان)</label>
                <input type="text" id="compare_at_price" name="compare_at_price" inputmode="numeric"
                       value="<?= e((string) $value('compare_at_price')) ?>" dir="ltr">
                <span class="field__hint">اگر پر شود، به‌صورت خط‌خورده نمایش داده می‌شود.</span>
                <?php if (isset($errors['compare_at_price'])): ?>
                    <span class="field__error"><?= e($errors['compare_at_price']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid-2">
            <div class="field">
                <label for="stock">موجودی</label>
                <input type="text" id="stock" name="stock" inputmode="numeric"
                       value="<?= e((string) $value('stock', 0)) ?>" dir="ltr">
            </div>

            <div class="field">
                <label for="warranty">گارانتی</label>
                <input type="text" id="warranty" name="warranty"
                       value="<?= e((string) $value('warranty')) ?>"
                       placeholder="مثلاً: ۱۸ ماه گارانتی شرکتی">
            </div>
        </div>

        <div class="field">
            <label for="serial_number">شماره سریال / IMEI</label>
            <input type="text" id="serial_number" name="serial_number"
                   value="<?= e((string) $value('serial_number')) ?>" dir="ltr">
            <span class="field__hint">اختیاری — مخصوص موبایل و کالای کارکرده.</span>
        </div>
    </section>

    <!-- ================= تنوع محصول ================= -->
    <section class="panel panel--form">
        <h2 class="panel__title">تنوع محصول (Variant)</h2>

        <p class="field__hint field__hint--block">
            اگر محصول در چند حالت عرضه می‌شود (مثلاً رنگ یا حافظه متفاوت)، هر ترکیب را
            با قیمت و موجودی مستقل خودش اینجا اضافه کنید. اگر محصول تنوعی ندارد،
            این بخش را خالی بگذارید.
        </p>

        <div class="variants" id="variants"
             data-attributes="<?= e(json_encode(array_map(static fn ($a) => [
                 'id'     => (int) $a['id'],
                 'name'   => $a['name'],
                 'values' => array_map(static fn ($v) => [
                     'id'    => (int) $v['id'],
                     'value' => $v['value'],
                 ], $a['values']),
             ], $attributes), JSON_UNESCAPED_UNICODE)) ?>">

            <table class="table variants__table">
                <thead>
                <tr>
                    <th>ویژگی‌ها</th>
                    <th>قیمت (تومان)</th>
                    <th>موجودی</th>
                    <th>کد کالا</th>
                    <th></th>
                </tr>
                </thead>
                <tbody id="variant-rows">
                <?php foreach ($variants as $variant): ?>
                    <?php
                    $valueIds = array_map(
                        static fn ($v) => (int) $v['attribute_value_id'],
                        $variant['values']
                    );
                    ?>
                    <tr class="variant-row">
                        <td>
                            <input type="hidden" name="variant_id[]" value="<?= (int) $variant['id'] ?>">
                            <input type="hidden" name="variant_values[]"
                                   value="<?= e(implode(',', $valueIds)) ?>">
                            <span class="variant-row__title"><?= e($variant['title']) ?></span>
                        </td>
                        <td>
                            <input type="text" name="variant_price[]" inputmode="numeric"
                                   value="<?= (int) $variant['price'] ?>" dir="ltr">
                        </td>
                        <td>
                            <input type="text" name="variant_stock[]" inputmode="numeric"
                                   value="<?= (int) $variant['stock'] ?>" dir="ltr">
                        </td>
                        <td>
                            <input type="text" name="variant_sku[]"
                                   value="<?= e((string) $variant['sku']) ?>" dir="ltr">
                        </td>
                        <td>
                            <button type="button" class="btn btn--danger btn--sm variant-remove">حذف</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p class="empty" id="variants-empty" <?= $variants ? 'hidden' : '' ?>>
                هنوز ترکیبی اضافه نشده است.
            </p>

            <!-- سازنده ترکیب جدید -->
            <div class="variant-builder" id="variant-builder">
                <div class="variant-builder__selects" id="variant-selects"></div>
                <button type="button" class="btn btn--ghost" id="variant-add">افزودن ترکیب</button>
            </div>
        </div>
    </section>

    <!-- ================= مشخصات فنی ================= -->
    <section class="panel panel--form">
        <h2 class="panel__title">مشخصات فنی</h2>
        <p class="field__hint field__hint--block">
            این مشخصات فقط در تب «مشخصات فنی» صفحه محصول نمایش داده می‌شوند.
        </p>

        <div id="specs">
            <?php foreach ($specs as $spec): ?>
                <div class="spec-row">
                    <input type="text" name="spec_key[]" placeholder="عنوان (مثلاً اندازه صفحه)"
                           value="<?= e($spec['spec_key']) ?>">
                    <input type="text" name="spec_value[]" placeholder="مقدار (مثلاً ۶.۷ اینچ)"
                           value="<?= e($spec['spec_value']) ?>">
                    <button type="button" class="btn btn--danger btn--sm spec-remove">حذف</button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="btn btn--ghost btn--sm" id="spec-add">افزودن مشخصه</button>
    </section>

    <!-- ================= تصاویر ================= -->
    <section class="panel panel--form">
        <h2 class="panel__title">تصاویر</h2>

        <div class="field">
            <label for="main_image">تصویر اصلی</label>
            <?php if ($isEdit && $product['main_image']): ?>
                <div class="current-image">
                    <img src="<?= e(url('uploads/products/' . $product['main_image'])) ?>" alt="">
                    <span class="field__hint">برای تعویض، تصویر جدید انتخاب کنید.</span>
                </div>
            <?php endif; ?>
            <input type="file" id="main_image" name="main_image" accept="image/jpeg,image/png,image/webp">
            <span class="field__hint">فرمت JPG، PNG یا WebP — حداکثر ۳ مگابایت.</span>
            <?php if (isset($errors['main_image'])): ?>
                <span class="field__error"><?= e($errors['main_image']) ?></span>
            <?php endif; ?>
        </div>

        <?php if ($images): ?>
            <div class="field">
                <label>گالری فعلی</label>
                <div class="gallery-manage">
                    <?php foreach ($images as $image): ?>
                        <label class="gallery-manage__item">
                            <img src="<?= e(url('uploads/products/' . $image['image'])) ?>" alt="">
                            <span>
                                <input type="checkbox" name="delete_images[]" value="<?= (int) $image['id'] ?>">
                                حذف
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="field">
            <label for="gallery">افزودن به گالری</label>
            <input type="file" id="gallery" name="gallery[]" multiple
                   accept="image/jpeg,image/png,image/webp">
        </div>
    </section>

    <!-- ================= انتشار ================= -->
    <section class="panel panel--form">
        <h2 class="panel__title">انتشار</h2>

        <div class="field field--check">
            <label>
                <input type="checkbox" name="is_active" value="1"
                    <?= (int) $value('is_active', 1) === 1 ? 'checked' : '' ?>>
                فعال باشد (در سایت نمایش داده شود)
            </label>
        </div>

        <div class="field field--check">
            <label>
                <input type="checkbox" name="is_featured" value="1"
                    <?= (int) $value('is_featured', 0) === 1 ? 'checked' : '' ?>>
                در بخش «پیشنهاد ویژه» صفحه اصلی نمایش داده شود
            </label>
        </div>

        <div class="field">
            <label for="meta_description">توضیح متا (برای گوگل)</label>
            <input type="text" id="meta_description" name="meta_description"
                   value="<?= e((string) $value('meta_description')) ?>">
        </div>
    </section>

    <div class="form-actions form-actions--sticky">
        <button class="btn btn--primary" type="submit">
            <?= $isEdit ? 'ذخیره تغییرات' : 'افزودن محصول' ?>
        </button>
        <a class="btn btn--ghost" href="<?= e(url('admin/products')) ?>">بازگشت به لیست</a>
    </div>
</form>
