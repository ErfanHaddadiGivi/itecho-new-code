<?php
/** ثبت‌نام اپل‌آیدی (موبایل + رمز) — @var array $errors */
use App\Core\Csrf;
use App\Core\Flash;
?>
<div class="container">
    <div class="auth-card">
        <h1>ثبت‌نام اپل‌آیدی</h1>
        <p class="auth-card__hint">با شمارهٔ موبایل و یک رمز عبور ثبت‌نام کن تا بتونی سفارش اپل‌آیدی ثبت کنی.</p>

        <?php if (isset($errors['phone'])): ?><p class="field__error"><?= e($errors['phone']) ?></p><?php endif; ?>
        <?php if (isset($errors['password'])): ?><p class="field__error"><?= e($errors['password']) ?></p><?php endif; ?>

        <form method="post" action="<?= e(url('appleid/register')) ?>" class="form">
            <?= Csrf::field() ?>
            <div class="field">
                <label for="phone">شمارهٔ موبایل</label>
                <input type="text" id="phone" name="phone" dir="ltr" inputmode="numeric"
                       placeholder="09123456789" value="<?= e((string) (Flash::oldInput('phone') ?? '')) ?>" required>
            </div>
            <div class="field">
                <label for="name">نام (اختیاری)</label>
                <input type="text" id="name" name="name" value="<?= e((string) (Flash::oldInput('name') ?? '')) ?>">
            </div>
            <div class="field">
                <label for="password">رمز عبور</label>
                <input type="password" id="password" name="password" minlength="6" required>
                <span class="field__hint">حداقل ۶ کاراکتر.</span>
            </div>
            <div class="form-actions">
                <button class="btn btn--primary btn--block" type="submit">ثبت‌نام و ادامه</button>
            </div>
        </form>

        <p class="auth-card__alt">حساب داری؟ <a href="<?= e(url('appleid/login')) ?>">وارد شو</a></p>
    </div>
</div>
