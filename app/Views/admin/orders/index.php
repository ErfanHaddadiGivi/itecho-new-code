<?php
/**
 * لیست سفارش‌ها در پنل
 * @var array $orders @var App\Core\Paginator $paginator @var int $total @var array $filters
 */
use App\Core\View;
use App\Models\Order;
?>

<div class="page-actions">
  <p class="page-hint"><?= e(fa_digits((string) $total)) ?> سفارش</p>
</div>

<form class="toolbar" method="get" action="<?= e(url('admin/orders')) ?>">
  <input type="search" name="q" placeholder="شماره سفارش، نام یا ایمیل مشتری…"
         value="<?= e($filters['q']) ?>">

  <select name="status">
    <option value="">همه وضعیت‌ها</option>
    <?php foreach (Order::STATUS_LABELS as $key => $label): ?>
      <option value="<?= e($key) ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>>
        <?= e($label) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="shipping_state">
    <option value="">همه وضعیت‌های ارسال</option>
    <?php foreach (Order::SHIPPING_LABELS as $key => $label): ?>
      <option value="<?= e($key) ?>" <?= $filters['shipping_state'] === $key ? 'selected' : '' ?>>
        <?= e($label) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <button class="btn btn--ghost" type="submit">اعمال</button>
  <?php if ($filters['q'] || $filters['status'] || $filters['shipping_state']): ?>
    <a class="btn btn--ghost" href="<?= e(url('admin/orders')) ?>">حذف فیلتر</a>
  <?php endif; ?>
</form>

<?php if (!$orders): ?>
  <div class="panel"><p class="empty">سفارشی با این مشخصات پیدا نشد.</p></div>
<?php else: ?>
  <div class="panel">
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>شماره</th>
            <th>مشتری</th>
            <th>مبلغ</th>
            <th>تحویل</th>
            <th>وضعیت</th>
            <th>تاریخ</th>
            <th class="col-actions"></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
          <tr>
            <td class="ltr"><?= e($order['order_number']) ?></td>
            <td>
              <?= e($order['customer_name']) ?>
              <span class="ltr muted block"><?= e($order['customer_email']) ?></span>
            </td>
            <td><?= e(money((int) $order['grand_total'], false)) ?></td>
            <td class="muted">
              <?= $order['delivery_method'] === 'post' ? 'پست' : 'حضوری' ?>
              <?php if ($order['shipping_state'] === 'awaiting_cost'): ?>
                <span class="badge badge--pending_payment block">منتظر هزینه ارسال</span>
              <?php elseif ($order['shipping_state'] === 'awaiting_payment'): ?>
                <span class="badge badge--shipped block">منتظر پرداخت ارسال</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge badge--<?= e($order['status']) ?>">
                <?= e(Order::STATUS_LABELS[$order['status']] ?? $order['status']) ?>
              </span>
            </td>
            <td><?= e(jdate($order['created_at'], 'short')) ?></td>
            <td class="col-actions">
              <a class="btn btn--ghost btn--sm"
                 href="<?= e(url('admin/orders/' . $order['id'])) ?>">جزئیات</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php View::partial('site/partials/pagination', ['paginator' => $paginator]); ?>
<?php endif; ?>
