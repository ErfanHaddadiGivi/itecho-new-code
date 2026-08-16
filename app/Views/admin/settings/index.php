<?php
/**
 * تنظیمات سایت — فرم از روی ردیف‌های جدول settings ساخته می‌شود.
 *
 * @var array $groups     گروه‌ها به همراه تنظیمات هرکدام
 * @var array $fieldTypes نوع ورودی فیلدهای خاص
 */

use App\Core\Csrf;
?>

<form action="<?= e(url('admin/settings')) ?>" method="post" class="form">
    <?= Csrf::field() ?>

    <?php foreach ($groups as $groupKey => $group): ?>
        <?php if (!$group['settings']) { continue; } ?>

        <section class="panel panel--form">
            <h2 class="panel__title"><?= e($group['label']) ?></h2>

            <?php if ($groupKey === 'payment'): ?>
                <p class="field__hint field__hint--block">
                    تا زمانی که درگاه واقعی تایید نشده، «حالت تست زرین‌پال» را روشن نگه دارید.
                    برای اتصال به درگاه واقعی فقط کافی است کد مرچنت را وارد و این گزینه را خاموش کنید.
                </p>
            <?php elseif ($groupKey === 'mail'): ?>
                <p class="field__hint field__hint--block">
                    برای Gmail باید از «رمز عبور اپلیکیشن» استفاده کنید، نه رمز اصلی حساب.
                </p>
            <?php endif; ?>

            <?php foreach ($group['settings'] as $setting): ?>
                <?php
                $key   = $setting['setting_key'];
                $value = (string) $setting['setting_value'];
                $label = $setting['title'] ?: $key;
                $type  = $fieldTypes[$key] ?? 'text';
                $id    = 'set_' . $key;
                ?>

                <?php if ($type === 'toggle'): ?>
                    <div class="field field--check">
                        <label>
                            <input type="checkbox" id="<?= e($id) ?>" name="settings[<?= e($key) ?>]" value="1"
                                <?= $value === '1' ? 'checked' : '' ?>>
                            <?= e($label) ?>
                        </label>
                    </div>

                <?php elseif ($type === 'textarea'): ?>
                    <div class="field">
                        <label for="<?= e($id) ?>"><?= e($label) ?></label>
                        <textarea id="<?= e($id) ?>" name="settings[<?= e($key) ?>]" rows="3"><?= e($value) ?></textarea>
                    </div>

                <?php elseif (str_starts_with($type, 'select:')): ?>
                    <div class="field field--narrow">
                        <label for="<?= e($id) ?>"><?= e($label) ?></label>
                        <select id="<?= e($id) ?>" name="settings[<?= e($key) ?>]">
                            <?php foreach (explode(',', substr($type, 7)) as $option): ?>
                                <option value="<?= e($option) ?>" <?= $value === $option ? 'selected' : '' ?>>
                                    <?= e($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                <?php elseif ($type === 'password'): ?>
                    <div class="field">
                        <label for="<?= e($id) ?>"><?= e($label) ?></label>
                        <input type="password" id="<?= e($id) ?>" name="settings[<?= e($key) ?>]"
                               value="<?= e($value) ?>" dir="ltr" autocomplete="new-password">
                    </div>

                <?php else: ?>
                    <div class="field">
                        <label for="<?= e($id) ?>"><?= e($label) ?></label>
                        <input type="text" id="<?= e($id) ?>" name="settings[<?= e($key) ?>]"
                               value="<?= e($value) ?>"
                               <?= preg_match('/(email|url|port|_id|username|code|fee|per_page|minutes)/', $key) ? 'dir="ltr"' : '' ?>>
                    </div>
                <?php endif; ?>

            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>

    <div class="form-actions form-actions--sticky">
        <button class="btn btn--primary" type="submit">ذخیره تنظیمات</button>
    </div>
</form>
