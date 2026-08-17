<?php
/** صفحه ثبت‌نام مشتری  @var array $errors */
use App\Core\Csrf;
?>
<div class="container">
  <div class="auth-card">
    <h1>ساخت حساب کاربری</h1>
    <p class="auth-card__sub">پس از ثبت‌نام، یک کد ۶ رقمی به ایمیل شما ارسال می‌شود.</p>

    <form method="post" action="<?= e(url('register')) ?>" class="form">
      <?= Csrf::field() ?>

      <div class="grid-2">
        <div class="field">
          <label for="first_name">نام <span class="req">*</span></label>
          <input type="text" id="first_name" name="first_name" value="<?= old('first_name') ?>" required>
          <?php if (isset($errors['first_name'])): ?>
            <span class="field__error"><?= e($errors['first_name']) ?></span>
          <?php endif; ?>
        </div>

        <div class="field">
          <label for="last_name">نام خانوادگی <span class="req">*</span></label>
          <input type="text" id="last_name" name="last_name" value="<?= old('last_name') ?>" required>
          <?php if (isset($errors['last_name'])): ?>
            <span class="field__error"><?= e($errors['last_name']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="field">
        <label for="email">ایمیل <span class="req">*</span></label>
        <input type="email" id="email" name="email" value="<?= old('email') ?>"
               required autocomplete="username" dir="ltr">
        <?php if (isset($errors['email'])): ?>
          <span class="field__error"><?= e($errors['email']) ?></span>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="phone">شماره موبایل</label>
        <input type="tel" id="phone" name="phone" value="<?= old('phone') ?>"
               placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr">
        <span class="field__hint">اختیاری — برای هماهنگی ارسال سفارش.</span>
        <?php if (isset($errors['phone'])): ?>
          <span class="field__error"><?= e($errors['phone']) ?></span>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="password">رمز عبور <span class="req">*</span></label>
        <input type="password" id="password" name="password" required
               autocomplete="new-password" dir="ltr">
        <span class="field__hint">حداقل ۸ کاراکتر.</span>
        <?php if (isset($errors['password'])): ?>
          <span class="field__error"><?= e($errors['password']) ?></span>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="password_confirm">تکرار رمز عبور <span class="req">*</span></label>
        <input type="password" id="password_confirm" name="password_confirm" required
               autocomplete="new-password" dir="ltr">
        <?php if (isset($errors['password_confirm'])): ?>
          <span class="field__error"><?= e($errors['password_confirm']) ?></span>
        <?php endif; ?>
      </div>

      <button class="btn btn--primary btn--block btn--lg" type="submit">ثبت‌نام</button>
    </form>

    <p class="auth-card__foot">
      قبلاً ثبت‌نام کرده‌اید؟ <a href="<?= e(url('login')) ?>">وارد شوید</a>
    </p>
  </div>
</div>
