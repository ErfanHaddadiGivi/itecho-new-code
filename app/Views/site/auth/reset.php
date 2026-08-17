<?php
/** فرم تعیین رمز جدید  @var string $email */
use App\Core\Csrf;
?>
<div class="container">
  <div class="auth-card">
    <h1>تعیین رمز عبور جدید</h1>
    <p class="auth-card__sub">
      کد ارسال‌شده به <strong dir="ltr"><?= e($email) ?></strong> و رمز جدید را وارد کنید.
    </p>

    <form method="post" action="<?= e(url('reset-password')) ?>" class="form">
      <?= Csrf::field() ?>

      <div class="field">
        <label for="code">کد بازیابی</label>
        <input type="text" id="code" name="code" inputmode="numeric" maxlength="6"
               required autofocus autocomplete="one-time-code" dir="ltr" class="code-input">
      </div>

      <div class="field">
        <label for="password">رمز عبور جدید</label>
        <input type="password" id="password" name="password" required
               autocomplete="new-password" dir="ltr">
        <span class="field__hint">حداقل ۸ کاراکتر.</span>
      </div>

      <div class="field">
        <label for="password_confirm">تکرار رمز عبور</label>
        <input type="password" id="password_confirm" name="password_confirm" required
               autocomplete="new-password" dir="ltr">
      </div>

      <button class="btn btn--primary btn--block btn--lg" type="submit">تغییر رمز و ورود</button>
    </form>

    <p class="auth-card__foot">
      <a href="<?= e(url('forgot-password')) ?>">درخواست کد جدید</a>
    </p>
  </div>
</div>
