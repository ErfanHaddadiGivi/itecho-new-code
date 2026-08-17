<?php
/**
 * ایمیل لینک پرداخت تکمیلی هزینه ارسال
 *
 * @var array  $order
 * @var int    $cost
 * @var string $link
 * @var string $name
 */
?>
<p style="margin:0 0 14px;">سلام <?= e($name) ?> عزیز،</p>

<p style="margin:0 0 18px;">
  سفارش شما بسته‌بندی شد و هزینه ارسال آن مشخص شده است.
  برای تکمیل فرآیند و تحویل سفارش به پست، لطفاً هزینه ارسال را پرداخت کنید.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="margin:0 0 20px;background:#f4f6f5;border-radius:8px;">
  <tr><td style="padding:14px 16px;font-size:13.5px;line-height:2.1;">
    <strong>شماره سفارش:</strong>
    <span style="direction:ltr;display:inline-block;"><?= e($order['order_number']) ?></span><br>
    <strong>هزینه ارسال:</strong>
    <span style="color:#0B6E4F;font-weight:bold;"><?= e(money($cost)) ?></span>
  </td></tr>
</table>

<div style="text-align:center;margin:0 0 18px;">
  <a href="<?= e($link) ?>"
     style="display:inline-block;background:#0B6E4F;color:#ffffff;text-decoration:none;
            padding:13px 30px;border-radius:8px;font-size:15px;font-weight:bold;">
    پرداخت هزینه ارسال
  </a>
</div>

<p style="margin:0 0 8px;font-size:12px;color:#5d6b64;">
  اگر دکمه بالا کار نکرد، این آدرس را در مرورگر باز کنید:
</p>
<p style="margin:0;font-size:11.5px;color:#5d6b64;direction:ltr;text-align:left;word-break:break-all;">
  <?= e($link) ?>
</p>
