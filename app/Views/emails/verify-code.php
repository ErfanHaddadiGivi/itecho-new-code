<?php
/**
 * ایمیل کد تایید ۶ رقمی
 *
 * @var string $code
 * @var string $name
 */

use App\Models\Setting;
?>
<p style="margin:0 0 14px;">سلام <?= e($name) ?> عزیز،</p>

<p style="margin:0 0 18px;">
  برای فعال‌سازی حساب کاربری خود، کد زیر را در سایت وارد کنید:
</p>

<div style="margin:0 0 18px;padding:16px;background:#e4f1ea;border-radius:8px;text-align:center;">
  <span style="font-size:30px;font-weight:bold;letter-spacing:8px;color:#0B6E4F;direction:ltr;display:inline-block;">
    <?= e($code) ?>
  </span>
</div>

<p style="margin:0 0 10px;color:#5d6b64;font-size:13px;">
  این کد تا <?= e(fa_digits((string) Setting::getInt('otp_expire_minutes', 10))) ?> دقیقه معتبر است.
</p>

<p style="margin:0;color:#5d6b64;font-size:13px;">
  اگر شما درخواست ساخت حساب نداده‌اید، این ایمیل را نادیده بگیرید.
</p>
