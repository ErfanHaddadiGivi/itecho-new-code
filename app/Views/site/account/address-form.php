<?php
/** فرم آدرس  @var array|null $address @var array $errors */
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;

$isEdit = $address !== null;
$action = $isEdit ? url('account/addresses/' . $address['id']) : url('account/addresses');
$v = static fn (string $f) => e((string) (Flash::oldInput($f) ?? $address[$f] ?? ''));
?>
<div class="container">
  <h1 class="page-title"><?= $isEdit ? 'ویرایش آدرس' : 'افزودن آدرس' ?></h1>
  <div class="account">
    <?php View::partial('site/account/_nav', ['active' => 'addresses']); ?>

    <div class="account__main">
      <section class="panel-block">
        <form method="post" action="<?= e($action) ?>" class="form">
          <?= Csrf::field() ?>

          <div class="grid-2">
            <div class="field">
              <label for="receiver_name">نام تحویل‌گیرنده <span class="req">*</span></label>
              <input type="text" id="receiver_name" name="receiver_name" value="<?= $v('receiver_name') ?>" required>
              <?php if (isset($errors['receiver_name'])): ?>
                <span class="field__error"><?= e($errors['receiver_name']) ?></span>
              <?php endif; ?>
            </div>
            <div class="field">
              <label for="phone">شماره موبایل <span class="req">*</span></label>
              <input type="tel" id="phone" name="phone" value="<?= $v('phone') ?>" required dir="ltr">
              <?php if (isset($errors['phone'])): ?>
                <span class="field__error"><?= e($errors['phone']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="grid-2">
            <div class="field">
              <label for="province">استان <span class="req">*</span></label>
              <input type="text" id="province" name="province" value="<?= $v('province') ?>" required>
              <?php if (isset($errors['province'])): ?>
                <span class="field__error"><?= e($errors['province']) ?></span>
              <?php endif; ?>
            </div>
            <div class="field">
              <label for="city">شهر <span class="req">*</span></label>
              <input type="text" id="city" name="city" value="<?= $v('city') ?>" required>
              <?php if (isset($errors['city'])): ?>
                <span class="field__error"><?= e($errors['city']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="field">
            <label for="address_line">نشانی کامل <span class="req">*</span></label>
            <textarea id="address_line" name="address_line" rows="3" required><?= $v('address_line') ?></textarea>
            <?php if (isset($errors['address_line'])): ?>
              <span class="field__error"><?= e($errors['address_line']) ?></span>
            <?php endif; ?>
          </div>

          <div class="field field--narrow">
            <label for="postal_code">کد پستی</label>
            <input type="text" id="postal_code" name="postal_code" value="<?= $v('postal_code') ?>"
                   inputmode="numeric" dir="ltr">
            <?php if (isset($errors['postal_code'])): ?>
              <span class="field__error"><?= e($errors['postal_code']) ?></span>
            <?php endif; ?>
          </div>

          <div class="field field--check">
            <label>
              <input type="checkbox" name="is_default" value="1"
                <?= $isEdit && (int) $address['is_default'] === 1 ? 'checked' : '' ?>>
              این آدرس پیش‌فرض باشد
            </label>
          </div>

          <div class="form-actions">
            <button class="btn btn--primary" type="submit">
              <?= $isEdit ? 'ذخیره تغییرات' : 'ثبت آدرس' ?>
            </button>
            <a class="btn btn--ghost" href="<?= e(url('account/addresses')) ?>">انصراف</a>
          </div>
        </form>
      </section>
    </div>
  </div>
</div>
