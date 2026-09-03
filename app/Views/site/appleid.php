<?php
/**
 * صفحهٔ فروش اپل‌آیدی آمریکا — معرفی + ورود به پورتال وب + (اختیاری) لینک بله.
 *
 * @var string $baleLink     لینک ربات بله (خالی اگر تنظیم نشده)
 * @var int    $startPrice   شروع قیمت‌ها (تومان)
 * @var bool   $webAvailable آیا پورتال وب فعال است
 * @var bool   $loggedIn     آیا کاربر اپل‌آیدی وارد شده
 */
?>

<div class="container">
    <section class="appleid-hero">
        <span class="appleid-hero__badge">سرویس Itecho · ریجن آمریکا</span>
        <h1>اپل‌آیدی آمریکا، آماده و بدون دردسر</h1>
        <p>
            اپل‌آیدی معتبر ریجن آمریکا روی ایمیل خودت ساخته می‌شه؛ از انتخاب پلن تا تحویل،
            همه‌چیز آنلاین و با پشتیبانی تا لحظهٔ ورود به دستگاهت.
        </p>

        <?php if ($startPrice > 0): ?>
            <div class="appleid-hero__price">شروع قیمت‌ها از <b><?= e(fa_digits(number_format($startPrice))) ?></b> تومان</div>
        <?php endif; ?>

        <div class="appleid-hero__actions">
            <?php if ($webAvailable): ?>
                <a class="btn btn--primary btn--lg" href="<?= e(url($loggedIn ? 'appleid/account' : 'appleid/login')) ?>">
                    <?= $loggedIn ? 'ورود به پروفایل و ثبت سفارش' : 'ورود / ثبت‌نام و ثبت سفارش' ?>
                </a>
            <?php endif; ?>
            <?php if ($baleLink !== ''): ?>
                <a class="btn btn--ghost btn--lg" href="<?= e($baleLink) ?>" target="_blank" rel="noopener">سفارش در پیام‌رسان بله</a>
            <?php endif; ?>
        </div>
        <p class="appleid-hero__note">بدون نیاز به کارت یا شمارهٔ خارجی</p>
    </section>

    <!-- سه گام -->
    <section class="appleid-steps">
        <div class="appleid-step">
            <span class="appleid-step__num">۱</span>
            <h3>انتخاب پلن</h3>
            <p>نوع ضمانت و آیکلود دلخواهت رو انتخاب کن.</p>
        </div>
        <div class="appleid-step">
            <span class="appleid-step__num">۲</span>
            <h3>پرداخت امن</h3>
            <p>اطلاعات رو بده و فیش واریزی رو بفرست.</p>
        </div>
        <div class="appleid-step">
            <span class="appleid-step__num">۳</span>
            <h3>تحویل اپل‌آیدی</h3>
            <p>کد ایمیل رو وارد کن و حسابت رو در پروفایل تحویل بگیر.</p>
        </div>
    </section>

    <section class="appleid-features">
        <div class="appleid-feature">🛟 پشتیبانی واقعی</div>
        <div class="appleid-feature">⚡ تحویل سریع</div>
        <div class="appleid-feature">🔒 اطلاعاتت محفوظ می‌مونه</div>
    </section>
</div>
