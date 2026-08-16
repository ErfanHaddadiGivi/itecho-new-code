<?php
/**
 * صفحه ورود به پنل مدیریت
 */

use App\Core\Csrf;
?>
<div class="login-card">
    <div class="login-card__head">
        <span class="logo__mark logo__mark--lg">IT</span>
        <h1>پنل مدیریت ایتکو</h1>
        <p>برای ادامه وارد حساب مدیریت شوید.</p>
    </div>

    <form action="<?= e(url('admin/login')) ?>" method="post" class="form" autocomplete="on">
        <?= Csrf::field() ?>

        <div class="field">
            <label for="email">ایمیل</label>
            <input type="email" id="email" name="email" value="<?= old('email') ?>"
                   required autofocus autocomplete="username" dir="ltr">
        </div>

        <div class="field">
            <label for="password">رمز عبور</label>
            <input type="password" id="password" name="password" required autocomplete="current-password" dir="ltr">
        </div>

        <button class="btn btn--primary btn--block" type="submit">ورود</button>
    </form>

    <p class="login-card__foot">
        <a href="<?= e(url('')) ?>">بازگشت به فروشگاه</a>
    </p>
</div>
