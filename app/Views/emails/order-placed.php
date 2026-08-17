<?php
/**
 * ایمیل تایید ثبت و پرداخت سفارش
 *
 * @var array  $order
 * @var array  $items
 * @var string $name
 */

use App\Models\Order;

$isPost = $order['delivery_method'] === 'post';
?>
<p style="margin:0 0 14px;">سلام <?= e($name) ?> عزیز،</p>

<p style="margin:0 0 18px;">
  پرداخت شما با موفقیت انجام شد و سفارش‌تان ثبت شد.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="margin:0 0 18px;background:#f4f6f5;border-radius:8px;">
  <tr><td style="padding:14px 16px;font-size:13.5px;line-height:2.1;">
    <strong>شماره سفارش:</strong>
    <span style="direction:ltr;display:inline-block;"><?= e($order['order_number']) ?></span><br>
    <strong>تاریخ:</strong> <?= e(jdate($order['created_at'], 'datetime')) ?><br>
    <strong>روش تحویل:</strong> <?= $isPost ? 'ارسال با پست' : 'دریافت حضوری' ?>
  </td></tr>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="margin:0 0 18px;border-collapse:collapse;font-size:13px;">
  <tr style="background:#f4f6f5;">
    <th align="right" style="padding:9px 12px;border-bottom:1px solid #dfe6e1;">کالا</th>
    <th align="center" style="padding:9px 12px;border-bottom:1px solid #dfe6e1;">تعداد</th>
    <th align="left" style="padding:9px 12px;border-bottom:1px solid #dfe6e1;">مبلغ</th>
  </tr>
  <?php foreach ($items as $item): ?>
    <tr>
      <td align="right" style="padding:9px 12px;border-bottom:1px solid #edf2ef;">
        <?= e($item['product_name']) ?>
        <?php if ($item['variant_title']): ?>
          <br><span style="color:#5d6b64;font-size:11.5px;"><?= e($item['variant_title']) ?></span>
        <?php endif; ?>
      </td>
      <td align="center" style="padding:9px 12px;border-bottom:1px solid #edf2ef;">
        <?= e(fa_digits((string) (int) $item['quantity'])) ?>
      </td>
      <td align="left" style="padding:9px 12px;border-bottom:1px solid #edf2ef;white-space:nowrap;">
        <?= e(money((int) $item['line_total'], false)) ?>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:13.5px;">
  <tr>
    <td align="right" style="padding:5px 0;">جمع کالاها</td>
    <td align="left" style="padding:5px 0;"><?= e(money((int) $order['items_total'])) ?></td>
  </tr>
  <?php if (!$isPost): ?>
    <tr>
      <td align="right" style="padding:5px 0;">هزینه تحویل حضوری</td>
      <td align="left" style="padding:5px 0;"><?= e(money((int) $order['shipping_cost'])) ?></td>
    </tr>
  <?php endif; ?>
  <tr>
    <td align="right" style="padding:9px 0;border-top:1px solid #dfe6e1;font-weight:bold;">مبلغ پرداخت‌شده</td>
    <td align="left" style="padding:9px 0;border-top:1px solid #dfe6e1;font-weight:bold;">
      <?= e(money((int) $order['items_total'])) ?>
    </td>
  </tr>
</table>

<?php if ($isPost): ?>
  <div style="margin:18px 0 0;padding:14px 16px;background:#fbf0e2;border-radius:8px;
              font-size:13px;line-height:2;color:#9a6206;">
    <strong>درباره هزینه ارسال</strong><br>
    هزینه دقیق ارسال پستی پس از بسته‌بندی توسط کارشناس محاسبه می‌شود و
    لینک پرداخت آن در ایمیلی جداگانه برای شما ارسال خواهد شد.
  </div>
<?php else: ?>
  <p style="margin:18px 0 0;font-size:13px;color:#5d6b64;">
    پس از آماده شدن سفارش، برای دریافت حضوری با شما تماس گرفته می‌شود.
  </p>
<?php endif; ?>
