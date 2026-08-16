<?php
/**
 * صفحه ۴۰۴
 *
 * @var string|null $message
 */
?>
<section class="section">
    <div class="container">
        <div class="error-page">
            <span class="error-page__code">۴۰۴</span>
            <h1><?= e($message ?? 'صفحه مورد نظر پیدا نشد') ?></h1>
            <p>ممکن است آدرس را اشتباه وارد کرده باشید یا این صفحه حذف شده باشد.</p>
            <a class="btn btn--primary" href="<?= e(url('')) ?>">بازگشت به صفحه اصلی</a>
        </div>
    </div>
</section>
