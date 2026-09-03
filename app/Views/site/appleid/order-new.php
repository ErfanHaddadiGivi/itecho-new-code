<?php
/** مرحله ۱: انتخاب ضمانت و آیکلود — @var array $warranties @var array $errors */
use App\Core\Csrf;
?>
<div class="container">
    <div class="appleid-wizard">
        <h1>درخواست اپل‌آیدی جدید</h1>
        <p class="muted">ریجن: آمریکا (US)</p>

        <?php if (isset($errors['combo'])): ?><p class="field__error"><?= e($errors['combo']) ?></p><?php endif; ?>

        <form method="post" action="<?= e(url('appleid/order/new')) ?>" class="form">
            <?= Csrf::field() ?>

            <div class="field">
                <span class="option-group__label">نوع ضمانت</span>
                <div class="wizard-options">
                    <?php foreach ($warranties as $i => $w): ?>
                        <label class="check">
                            <input type="radio" name="warranty_type_id" value="<?= (int) $w['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>>
                            <?= e($w['name']) ?>
                            <?php if (!empty($w['description'])): ?><span class="muted"> — <?= e($w['description']) ?></span><?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="field">
                <span class="option-group__label">آیکلود</span>
                <div class="wizard-options">
                    <label class="check"><input type="radio" name="icloud" value="1" checked> با آیکلود فعال</label>
                    <label class="check"><input type="radio" name="icloud" value="0"> بدون آیکلود</label>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn--primary btn--block" type="submit">ادامه</button>
                <a class="btn btn--ghost btn--block" href="<?= e(url('appleid/account')) ?>">بازگشت</a>
            </div>
        </form>
    </div>
</div>
