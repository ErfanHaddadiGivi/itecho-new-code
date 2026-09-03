<?php
/** ورود اپل‌آیدی (موبایل + رمز) — @var array $errors */
use App\Core\Csrf;
use App\Core\Flash;
?>
<div class="container">
    <div class="auth-card">
        <h1>ورود اپل‌آیدی</h1>
        <p class="auth-card__hint">با شمارهٔ موبایل و رمزت وارد شو تا سفارش‌هات رو ببینی و درخواست جدید ثبت کنی.</p>

        <?php if (isset($errors['login'])): ?><p class="field__error"><?= e($errors['login']) ?></p><?php endif; ?>

        <form method="post" action="<?= e(url('appleid/login')) ?>" class="form">
            <?= Csrf::field() ?>
            <div class="field">
                <label for="phone">شمارهٔ موبایل</label>
                <input type="text" id="phone" name="phone" dir="ltr" inputmode="numeric"
                       placeholder="09123456789" value="<?= e((string) (Flash::oldInput('phone') ?? '')) ?>" required>
            </div>
            <div class="field">
                <label for="password">رمز عبور</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-actions">
                <button class="btn btn--primary btn--block" type="submit">ورود</button>
            </div>
        </form>

        <p class="auth-card__alt">حساب نداری؟ <a href="<?= e(url('appleid/register')) ?>">ثبت‌نام کن</a></p>
    </div>
</div>
