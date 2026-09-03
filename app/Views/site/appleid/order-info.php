<?php
/** مرحله ۲: اطلاعات شخصی اپل‌آیدی — @var array $order @var array $errors */
use App\Core\Csrf;
use App\Core\Flash;

$old = static fn (string $k) => e((string) (Flash::oldInput($k) ?? ''));
?>
<div class="container">
    <div class="appleid-wizard">
        <h1>اطلاعات اپل‌آیدی</h1>
        <p class="muted">این اطلاعات برای ساخت حساب استفاده می‌شود و رمزنگاری‌شده نگهداری می‌گردد.</p>

        <form method="post" action="<?= e(url('appleid/order/' . $order['id'] . '/info')) ?>" class="form">
            <?= Csrf::field() ?>

            <div class="grid-2">
                <div class="field">
                    <label for="first_name">نام (First name)</label>
                    <input type="text" id="first_name" name="first_name" value="<?= $old('first_name') ?>" required>
                    <?php if (isset($errors['first_name'])): ?><span class="field__error"><?= e($errors['first_name']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="last_name">نام خانوادگی (Last name)</label>
                    <input type="text" id="last_name" name="last_name" value="<?= $old('last_name') ?>" required>
                    <?php if (isset($errors['last_name'])): ?><span class="field__error"><?= e($errors['last_name']) ?></span><?php endif; ?>
                </div>
            </div>

            <div class="field">
                <label for="email">ایمیل (اپل‌آیدی روی همین ایمیل ساخته می‌شود)</label>
                <input type="email" id="email" name="email" dir="ltr" value="<?= $old('email') ?>" required>
                <?php if (isset($errors['email'])): ?><span class="field__error"><?= e($errors['email']) ?></span><?php endif; ?>
            </div>

            <div class="grid-2">
                <div class="field">
                    <label for="phone">شمارهٔ موبایل</label>
                    <input type="text" id="phone" name="phone" dir="ltr" inputmode="numeric" placeholder="09123456789" value="<?= $old('phone') ?>" required>
                    <?php if (isset($errors['phone'])): ?><span class="field__error"><?= e($errors['phone']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="birthdate">تاریخ تولد میلادی (YYYY-MM-DD)</label>
                    <input type="text" id="birthdate" name="birthdate" dir="ltr" placeholder="1998-05-21" value="<?= $old('birthdate') ?>" required>
                    <?php if (isset($errors['birthdate'])): ?><span class="field__error"><?= e($errors['birthdate']) ?></span><?php endif; ?>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn--primary btn--block" type="submit">ادامه به تأیید</button>
            </div>
        </form>
    </div>
</div>
