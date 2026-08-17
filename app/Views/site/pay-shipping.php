<?php
/**
 * صفحه پرداخت تکمیلی هزینه ارسال (از طریق لینک ایمیل‌شده)
 *
 * @var array $payment
 * @var array $order
 */

use App\Core\Csrf;
?>
<div class="container">
  <div class="auth-card">
    <h1>پرداخت هزینه ارسال</h1>
    <p class="auth-card__sub">
      سفارش شما بسته‌بندی شده و آماده تحویل به پست است.
    </p>

    <dl class="result-facts">
      <div>
        <dt>شماره سفارش</dt>
        <dd class="ltr"><?= e($order['order_number']) ?></dd>
      </div>
      <div>
        <dt>مبلغ کالاها (پرداخت‌شده)</dt>
        <dd><?= e(money((int) $order['items_total'])) ?></dd>
      </div>
      <div>
        <dt>هزینه ارسال</dt>
        <dd class="amount-highlight"><?= e(money((int) $payment['amount'])) ?></dd>
      </div>
    </dl>

    <form method="post" action="<?= e(url('pay/' . $payment['pay_token'] . '/start')) ?>">
      <?= Csrf::field() ?>
      <button class="btn btn--primary btn--block btn--lg" type="submit">
        پرداخت <?= e(money((int) $payment['amount'])) ?>
      </button>
    </form>

    <p class="checkout__secure">پرداخت از طریق درگاه امن زرین‌پال انجام می‌شود.</p>
  </div>
</div>
