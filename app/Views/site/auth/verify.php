<?php
/** صفحه وارد کردن کد تایید  @var string $email */
use App\Core\Csrf;
?>
<div class="container">
  <div class="auth-card">
    <h1>تایید ایمیل</h1>
    <p class="auth-card__sub">
      کد ۶ رقمی ارسال‌شده به <strong dir="ltr"><?= e($email) ?></strong> را وارد کنید.
    </p>

    <form method="post" action="<?= e(url('verify')) ?>" class="form">
      <?= Csrf::field() ?>

      <div class="field">
        <label for="code">کد تایید</label>
        <input type="text" id="code" name="code" inputmode="numeric" maxlength="6"
               required autofocus autocomplete="one-time-code" dir="ltr" class="code-input">
      </div>

      <button class="btn btn--primary btn--block btn--lg" type="submit">تایید و ورود</button>
    </form>

    <form method="post" action="<?= e(url('verify/resend')) ?>" class="auth-card__foot">
      <?= Csrf::field() ?>
      کد را دریافت نکردید؟
      <button type="submit" class="link-button">ارسال دوباره کد</button>
    </form>
  </div>
</div>
