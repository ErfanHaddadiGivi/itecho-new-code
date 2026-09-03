<?php
/** مرحله ۴: پرداخت + آپلود فیش — @var array $order @var string $cardNumber @var string $cardHolder @var array $errors */
use App\Core\Csrf;
?>
<div class="container">
    <div class="appleid-wizard">
        <h1>پرداخت</h1>

        <div class="appleid-pay">
            <p>مبلغ <b><?= e(fa_digits(number_format((int) $order['price_amount']))) ?> تومان</b> را به کارت زیر واریز کن:</p>
            <div class="appleid-pay__card">
                <span class="appleid-pay__num ltr"><?= e($cardNumber) ?></span>
                <span class="appleid-pay__holder">به نام: <b><?= e($cardHolder) ?></b></span>
            </div>
        </div>

        <?php if (isset($errors['receipt'])): ?><p class="field__error"><?= e($errors['receipt']) ?></p><?php endif; ?>

        <form method="post" action="<?= e(url('appleid/order/' . $order['id'] . '/receipt')) ?>" enctype="multipart/form-data" class="form">
            <?= Csrf::field() ?>
            <div class="field">
                <label for="receipt">عکس فیش واریزی</label>
                <input type="file" id="receipt" name="receipt" accept="image/jpeg,image/png,image/webp" required>
                <span class="field__hint">فرمت JPG، PNG یا WebP — حداکثر ۳ مگابایت.</span>
            </div>
            <div class="form-actions">
                <button class="btn btn--primary btn--block" type="submit">ارسال فیش و ثبت سفارش</button>
                <a class="btn btn--ghost btn--block" href="<?= e(url('appleid/account')) ?>">بعداً</a>
            </div>
        </form>
    </div>
</div>
