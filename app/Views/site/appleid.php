<?php
/**
 * صفحهٔ فروش اپل‌آیدی آمریکا — معرفی + دکمهٔ شروع سفارش در تلگرام.
 *
 * @var string $telegramLink لینک deep-link ربات (خالی اگر یوزرنیم تنظیم نشده)
 * @var int    $startPrice   شروع قیمت‌ها (تومان)
 */
?>

<div class="container">
    <section class="appleid-hero">
        <span class="appleid-hero__badge">سرویس Itecho · ریجن آمریکا</span>
        <h1>اپل‌آیدی آمریکا، آماده و بدون دردسر</h1>
        <p>
            اپل‌آیدی معتبر ریجن آمریکا روی ایمیل خودت ساخته می‌شه؛ از انتخاب پلن تا تحویل،
            همه‌چیز داخل تلگرام و با پشتیبانی تا لحظهٔ ورود به دستگاهت.
        </p>

        <?php if ($startPrice > 0): ?>
            <div class="appleid-hero__price">شروع قیمت‌ها از <b><?= e(fa_digits(number_format($startPrice))) ?></b> تومان</div>
        <?php endif; ?>

        <?php if ($telegramLink !== ''): ?>
            <a class="btn btn--primary btn--lg appleid-cta" href="<?= e($telegramLink) ?>" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                    <path d="M21.9 4.3 18.7 19c-.2 1-.9 1.3-1.8.8l-4.9-3.6-2.4 2.3c-.3.3-.5.5-1 .5l.3-4.9L18 5.6c.4-.4-.1-.5-.6-.2L6.9 12.1 2.2 10.6c-1-.3-1-.9.2-1.4L20.6 3c.8-.3 1.5.2 1.3 1.3z" fill="currentColor"/>
                </svg>
                شروع سفارش در تلگرام
            </a>
            <p class="appleid-hero__note">بدون نیاز به کارت یا شمارهٔ خارجی</p>
        <?php else: ?>
            <p class="appleid-hero__note">به‌زودی فعال می‌شود.</p>
        <?php endif; ?>
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
            <p>کد ایمیل رو بخون و حسابت رو تحویل بگیر.</p>
        </div>
    </section>

    <!-- مزیت‌ها -->
    <section class="appleid-features">
        <div class="appleid-feature">🛟 پشتیبانی واقعی</div>
        <div class="appleid-feature">⚡ تحویل سریع</div>
        <div class="appleid-feature">🔒 اطلاعاتت محفوظ می‌مونه</div>
    </section>

    <?php if ($telegramLink !== ''): ?>
        <div class="appleid-cta-bottom">
            <a class="btn btn--primary btn--lg" href="<?= e($telegramLink) ?>" target="_blank" rel="noopener">
                شروع سفارش در تلگرام
            </a>
        </div>
    <?php endif; ?>
</div>
