<?php
/** صفحه ورود مشتری  @var array $errors */
use App\Core\Csrf;
?>
<div class="container">
  <div class="auth-card">
    <h1>ورود به حساب کاربری</h1>
    <p class="auth-card__sub">برای تکمیل خرید و پیگیری سفارش‌ها وارد شوید.</p>

    <form method="post" action="<?= e(url('login')) ?>" class="form">
      <?= Csrf::field() ?>

      <div class="field">
        <label for="email">ایمیل</label>
        <input type="email" id="email" name="email" value="<?= old('email') ?>"
               required autofocus autocomplete="username" dir="ltr">
      </div>

      <div class="field">
        <label for="password">رمز عبور</label>
        <input type="password" id="password" name="password" required
               autocomplete="current-password" dir="ltr">
      </div>

      <button class="btn btn--primary btn--block btn--lg" type="submit">ورود</button>
    </form>

    <p class="auth-card__foot">
      حساب کاربری ندارید؟ <a href="<?= e(url('register')) ?>">ثبت‌نام کنید</a>
    </p>
  </div>
</div>
