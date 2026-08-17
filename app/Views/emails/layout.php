<?php
/**
 * چارچوب کلی ایمیل‌ها.
 *
 * در ایمیل نمی‌توان به فایل CSS خارجی لینک داد و بسیاری از ایمیل‌خوان‌ها
 * تگ <style> را حذف می‌کنند، پس استایل به‌صورت inline نوشته شده است.
 *
 * @var string $subject
 * @var string $content
 */

use App\Models\Setting;

$siteName = Setting::get('site_name', 'ایتکو');
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><title><?= e($subject) ?></title></head>
<body style="margin:0;padding:24px 12px;background:#f4f6f5;font-family:Tahoma,Arial,sans-serif;color:#16211c;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
             style="max-width:560px;background:#ffffff;border:1px solid #dfe6e1;border-radius:10px;overflow:hidden;">

        <tr><td style="background:#0B6E4F;padding:18px 24px;">
          <span style="color:#ffffff;font-size:17px;font-weight:bold;"><?= e($siteName) ?></span>
        </td></tr>

        <tr><td style="padding:24px;font-size:14px;line-height:2;" dir="rtl">
          <?= $content ?>
        </td></tr>

        <tr><td style="padding:14px 24px;background:#f4f6f5;border-top:1px solid #dfe6e1;
                       font-size:11.5px;color:#5d6b64;line-height:1.9;">
          این ایمیل به‌صورت خودکار از <?= e($siteName) ?> ارسال شده است؛ لطفاً به آن پاسخ ندهید.
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
