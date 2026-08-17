<?php
/**
 * صفحه نتیجه پرداخت (بازگشت از درگاه)
 *
 * @var bool   $ok
 * @var string $message
 * @var ?array $order
 * @var ?array $payment
 */
?>
<div class="container">
  <div class="result-card <?= $ok ? 'result-card--ok' : 'result-card--fail' ?>">
    <div class="result-card__icon" aria-hidden="true"><?= $ok ? '✓' : '✕' ?></div>

    <h1><?= $ok ? 'پرداخت موفق' : 'پرداخت انجام نشد' ?></h1>
    <p class="result-card__msg"><?= e($message) ?></p>

    <?php if ($order !== null): ?>
      <dl class="result-facts">
        <div>
          <dt>شماره سفارش</dt>
          <dd class="ltr"><?= e($order['order_number']) ?></dd>
        </div>

        <?php if ($ok && !empty($payment['ref_id'])): ?>
          <div>
            <dt>کد رهگیری پرداخت</dt>
            <dd class="ltr"><?= e($payment['ref_id']) ?></dd>
          </div>
        <?php endif; ?>

        <?php if (!empty($payment['card_pan'])): ?>
          <div>
            <dt>شماره کارت</dt>
            <dd class="ltr"><?= e($payment['card_pan']) ?></dd>
          </div>
        <?php endif; ?>

        <div>
          <dt>مبلغ</dt>
          <dd><?= e(money((int) ($payment['amount'] ?? $order['grand_total']))) ?></dd>
        </div>
      </dl>

      <?php if ($ok && $order['delivery_method'] === 'post' && $order['shipping_state'] === 'awaiting_cost'): ?>
        <div class="notice notice--inline">
          هزینه ارسال پس از بسته‌بندی توسط کارشناس محاسبه می‌شود و
          <strong>لینک پرداخت آن به ایمیل شما ارسال خواهد شد.</strong>
        </div>
      <?php endif; ?>

      <div class="result-card__actions">
        <a class="btn btn--primary" href="<?= e(url('order/' . $order['id'])) ?>">مشاهده سفارش</a>

        <?php if (!$ok): ?>
          <a class="btn btn--ghost" href="<?= e(url('payment/start/' . $order['id'])) ?>">تلاش دوباره</a>
        <?php endif; ?>

        <a class="btn btn--ghost" href="<?= e(url('')) ?>">بازگشت به فروشگاه</a>
      </div>
    <?php else: ?>
      <div class="result-card__actions">
        <a class="btn btn--primary" href="<?= e(url('')) ?>">بازگشت به فروشگاه</a>
      </div>
    <?php endif; ?>
  </div>
</div>
