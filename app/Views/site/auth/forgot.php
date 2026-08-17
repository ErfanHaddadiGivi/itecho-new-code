<?php
/** فرم درخواست بازیابی رمز */
use App\Core\Csrf;
?>
<div class="container">
  <div class="auth-card">
    <h1>بازیابی رمز عبور</h1>
    <p class="auth-card__sub">
      ایمیل حساب خود را وارد کنید تا کد بازیابی برایتان ارسال شود.
    </p>

    <form method="post" action="<?= e(url('forgot-password')) ?>" class="form">
      <?= Csrf::field() ?>
      <div class="field">
        <label for="email">ایمیل</label>
        <input type="email" id="email" name="email" value="<?= old('email') ?>"
               required autofocus autocomplete="username" dir="ltr">
      </div>
      <button class="btn btn--primary btn--block btn--lg" type="submit">ارسال کد بازیابی</button>
    </form>

    <p class="auth-card__foot"><a href="<?= e(url('login')) ?>">بازگشت به صفحه ورود</a></p>
  </div>
</div>
