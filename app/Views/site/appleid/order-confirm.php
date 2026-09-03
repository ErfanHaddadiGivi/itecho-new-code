<?php
/** مرحله ۳: خلاصه و تأیید — @var array $order @var array $product @var array $personal */
use App\Core\Csrf;
?>
<div class="container">
    <div class="appleid-wizard">
        <h1>تأیید سفارش</h1>

        <div class="appleid-summary">
            <div class="appleid-summary__row"><span>ریجن</span><b>آمریکا (US)</b></div>
            <div class="appleid-summary__row"><span>ضمانت</span><b><?= e($product['warranty_name'] ?? '-') ?></b></div>
            <div class="appleid-summary__row"><span>آیکلود</span><b><?= !empty($product['icloud_enabled']) ? 'فعال' : 'غیرفعال' ?></b></div>
            <div class="appleid-summary__row"><span>نام</span><b><?= e($personal['first_name'] . ' ' . $personal['last_name']) ?></b></div>
            <div class="appleid-summary__row"><span>ایمیل</span><b class="ltr"><?= e($personal['email']) ?></b></div>
            <div class="appleid-summary__row"><span>شماره</span><b class="ltr"><?= e($personal['phone']) ?></b></div>
            <div class="appleid-summary__row"><span>تاریخ تولد</span><b class="ltr"><?= e($personal['birthdate']) ?></b></div>
            <div class="appleid-summary__row appleid-summary__row--total">
                <span>مبلغ قابل پرداخت</span>
                <b><?= e(fa_digits(number_format((int) $order['price_amount']))) ?> تومان</b>
            </div>
        </div>

        <div class="form-actions">
            <form method="post" action="<?= e(url('appleid/order/' . $order['id'] . '/confirm')) ?>">
                <?= Csrf::field() ?>
                <button class="btn btn--primary btn--block" type="submit">تأیید و ادامه به پرداخت</button>
            </form>
            <a class="btn btn--ghost btn--block" href="<?= e(url('appleid/order/' . $order['id'] . '/info')) ?>">اصلاح اطلاعات</a>
        </div>
    </div>
</div>
