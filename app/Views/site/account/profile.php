<?php
/** ویرایش اطلاعات حساب  @var array $user @var array $errors */
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\View;

$v = static fn (string $f) => e((string) (Flash::oldInput($f) ?? $user[$f] ?? ''));
?>
<div class="container">
  <h1 class="page-title">اطلاعات حساب</h1>
  <div class="account">
    <?php View::partial('site/account/_nav', ['active' => 'profile']); ?>

    <div class="account__main">
      <section class="panel-block">
        <h2>مشخصات فردی</h2>

        <form method="post" action="<?= e(url('account/profile')) ?>" class="form">
          <?= Csrf::field() ?>

          <div class="grid-2">
            <div class="field">
              <label for="first_name">نام <span class="req">*</span></label>
              <input type="text" id="first_name" name="first_name" value="<?= $v('first_name') ?>" required>
              <?php if (isset($errors['first_name'])): ?>
                <span class="field__error"><?= e($errors['first_name']) ?></span>
              <?php endif; ?>
            </div>
            <div class="field">
              <label for="last_name">نام خانوادگی <span class="req">*</span></label>
              <input type="text" id="last_name" name="last_name" value="<?= $v('last_name') ?>" required>
              <?php if (isset($errors['last_name'])): ?>
                <span class="field__error"><?= e($errors['last_name']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="field">
            <label for="phone">شماره موبایل</label>
            <input type="tel" id="phone" name="phone" value="<?= $v('phone') ?>" dir="ltr">
            <?php if (isset($errors['phone'])): ?>
              <span class="field__error"><?= e($errors['phone']) ?></span>
            <?php endif; ?>
          </div>

          <div class="field">
            <label>ایمیل</label>
            <input type="email" value="<?= e($user['email']) ?>" dir="ltr" disabled>
            <span class="field__hint">
              ایمیل قابل تغییر نیست، چون شناسه ورود شما و مقصد کدهای تایید است.
            </span>
          </div>

          <div class="form-actions">
            <button class="btn btn--primary" type="submit">ذخیره تغییرات</button>
          </div>
        </form>
      </section>

      <section class="panel-block">
        <h2>تغییر رمز عبور</h2>

        <form method="post" action="<?= e(url('account/password')) ?>" class="form">
          <?= Csrf::field() ?>

          <div class="field">
            <label for="current_password">رمز عبور فعلی <span class="req">*</span></label>
            <input type="password" id="current_password" name="current_password" required
                   autocomplete="current-password" dir="ltr">
            <?php if (isset($errors['current_password'])): ?>
              <span class="field__error"><?= e($errors['current_password']) ?></span>
            <?php endif; ?>
          </div>

          <div class="grid-2">
            <div class="field">
              <label for="new_password">رمز عبور جدید <span class="req">*</span></label>
              <input type="password" id="new_password" name="new_password" required
                     autocomplete="new-password" dir="ltr">
              <span class="field__hint">حداقل ۸ کاراکتر.</span>
              <?php if (isset($errors['new_password'])): ?>
                <span class="field__error"><?= e($errors['new_password']) ?></span>
              <?php endif; ?>
            </div>
            <div class="field">
              <label for="new_password_confirm">تکرار رمز جدید <span class="req">*</span></label>
              <input type="password" id="new_password_confirm" name="new_password_confirm" required
                     autocomplete="new-password" dir="ltr">
              <?php if (isset($errors['new_password_confirm'])): ?>
                <span class="field__error"><?= e($errors['new_password_confirm']) ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="form-actions">
            <button class="btn btn--ghost" type="submit">تغییر رمز عبور</button>
          </div>
        </form>
      </section>
    </div>
  </div>
</div>
