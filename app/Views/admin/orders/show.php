<?php
/**
 * جزئیات سفارش در پنل مدیریت
 *
 * @var array $order
 * @var array $items
 * @var array $payments
 * @var array $history
 * @var ?array $customer
 * @var array $transitions وضعیت‌هایی که می‌توان به آن‌ها رفت
 */

use App\Core\Csrf;
use App\Models\Order;

$isPost = $order['delivery_method'] === 'post';
?>

<div class="order-head">
    <div>
        <h1 class="admin-order-title">سفارش <span class="ltr"><?= e($order['order_number']) ?></span></h1>
        <p class="muted"><?= e(jdate($order['created_at'], 'datetime')) ?></p>
    </div>
    <div class="order-head__badges">
        <span class="badge badge--<?= e($order['status']) ?> badge--lg">
            <?= e(Order::STATUS_LABELS[$order['status']] ?? $order['status']) ?>
        </span>
        <span class="badge <?= $order['payment_status'] === 'paid' ? 'badge--ok' : 'badge--off' ?>">
            <?= $order['payment_status'] === 'paid' ? 'پرداخت‌شده' : 'پرداخت‌نشده' ?>
        </span>
    </div>
</div>

<div class="two-col">
    <div>
        <!-- کالاها -->
        <section class="panel">
            <h2 class="panel__title">کالاها</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr><th>کالا</th><th>تعداد</th><th>قیمت واحد</th><th>جمع</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <?= e($item['product_name']) ?>
                                <?php if ($item['variant_title']): ?>
                                    <span class="block muted"><?= e($item['variant_title']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(fa_digits((string) (int) $item['quantity'])) ?></td>
                            <td><?= e(money((int) $item['unit_price'], false)) ?></td>
                            <td><?= e(money((int) $item['line_total'], false)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="order-totals">
                <div><span>جمع کالاها</span><span><?= e(money((int) $order['items_total'])) ?></span></div>
                <div>
                    <span>هزینه ارسال</span>
                    <span>
                        <?php if ($order['shipping_cost'] === null): ?>
                            <em class="muted">محاسبه نشده</em>
                        <?php else: ?>
                            <?= e(money((int) $order['shipping_cost'])) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="order-totals__grand">
                    <span>مبلغ کل</span><span><?= e(money((int) $order['grand_total'])) ?></span>
                </div>
            </div>
        </section>

        <!-- هزینه ارسال پستی -->
        <?php if ($isPost): ?>
            <section class="panel">
                <h2 class="panel__title">هزینه ارسال پستی</h2>

                <p class="field__hint field__hint--block">
                    وضعیت فعلی:
                    <strong><?= e(Order::SHIPPING_LABELS[$order['shipping_state']] ?? $order['shipping_state']) ?></strong>
                </p>

                <?php if ($order['payment_status'] !== 'paid'): ?>
                    <p class="empty">تا وقتی مبلغ کالاها پرداخت نشده، نمی‌توان هزینه ارسال را ثبت کرد.</p>

                <?php elseif ($order['shipping_state'] === 'paid'): ?>
                    <p class="empty">هزینه ارسال پرداخت شده است.</p>

                <?php else: ?>
                    <form method="post" action="<?= e(url('admin/orders/' . $order['id'] . '/shipping')) ?>"
                          class="form">
                        <?= Csrf::field() ?>

                        <div class="field field--narrow">
                            <label for="shipping_cost">هزینه واقعی ارسال (تومان)</label>
                            <input type="text" id="shipping_cost" name="shipping_cost" inputmode="numeric" dir="ltr"
                                   value="<?= $order['shipping_cost'] !== null ? (int) $order['shipping_cost'] : '' ?>"
                                   required>
                        </div>

                        <p class="field__hint">
                            پس از ثبت، لینک پرداخت به‌صورت خودکار برای مشتری ایمیل می‌شود.
                            <?php if ($order['shipping_state'] === 'awaiting_payment'): ?>
                                <br><strong>توجه:</strong> با ثبت مبلغ جدید، لینک قبلی باطل و لینک تازه ارسال می‌شود.
                            <?php endif; ?>
                        </p>

                        <div class="form-actions">
                            <button class="btn btn--primary" type="submit">
                                ثبت هزینه و ارسال لینک پرداخت
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- تاریخچه -->
        <section class="panel">
            <h2 class="panel__title">تاریخچه وضعیت</h2>
            <ol class="timeline">
                <?php foreach ($history as $row): ?>
                    <li>
                        <span class="timeline__dot" aria-hidden="true"></span>
                        <div>
                            <strong><?= e(Order::STATUS_LABELS[$row['to_status']] ?? $row['to_status']) ?></strong>
                            <?php if ($row['note']): ?>
                                <span class="timeline__note"><?= e($row['note']) ?></span>
                            <?php endif; ?>
                            <span class="timeline__date">
                                <?= e(jdate($row['created_at'], 'datetime')) ?>
                                <?php if ($row['changed_by_name']): ?>
                                    — <?= e(trim($row['changed_by_name'])) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    </div>

    <aside>
        <!-- تغییر وضعیت -->
        <section class="panel">
            <h2 class="panel__title">تغییر وضعیت</h2>

            <?php if (!$transitions): ?>
                <p class="empty">این سفارش در وضعیت نهایی است.</p>
            <?php else: ?>
                <form method="post" action="<?= e(url('admin/orders/' . $order['id'] . '/status')) ?>"
                      class="form" data-confirm="وضعیت سفارش تغییر کند؟">
                    <?= Csrf::field() ?>

                    <div class="field">
                        <label for="status">وضعیت جدید</label>
                        <select id="status" name="status" required>
                            <?php foreach ($transitions as $target): ?>
                                <option value="<?= e($target) ?>">
                                    <?= e(Order::STATUS_LABELS[$target] ?? $target) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="note">یادداشت (اختیاری)</label>
                        <input type="text" id="note" name="note">
                    </div>

                    <p class="field__hint">
                        با انتخاب «لغو‌شده» یا «مرجوعی»، موجودی کالاها به‌صورت خودکار به انبار برمی‌گردد.
                    </p>

                    <div class="form-actions">
                        <button class="btn btn--primary" type="submit">ثبت وضعیت</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <!-- مشتری -->
        <section class="panel">
            <h2 class="panel__title">مشتری</h2>
            <?php if ($customer !== null): ?>
                <p style="margin:0 0 4px">
                    <?= e(trim($customer['first_name'] . ' ' . $customer['last_name'])) ?>
                </p>
                <p class="ltr muted" style="margin:0 0 4px"><?= e($customer['email']) ?></p>
                <?php if ($customer['phone']): ?>
                    <p class="ltr muted" style="margin:0"><?= e($customer['phone']) ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <!-- تحویل -->
        <section class="panel">
            <h2 class="panel__title">اطلاعات تحویل</h2>
            <p class="muted" style="margin:0 0 8px">
                <?= $isPost ? 'ارسال با پست' : 'دریافت حضوری' ?>
            </p>

            <?php if ($order['receiver_name']): ?>
                <p style="margin:0 0 4px"><?= e($order['receiver_name']) ?></p>
            <?php endif; ?>
            <?php if ($order['receiver_phone']): ?>
                <p class="ltr muted" style="margin:0 0 8px"><?= e($order['receiver_phone']) ?></p>
            <?php endif; ?>

            <?php if ($isPost && $order['address_line']): ?>
                <p class="muted" style="margin:0 0 10px">
                    <?= e($order['province']) ?>، <?= e($order['city']) ?><br>
                    <?= e($order['address_line']) ?>
                    <?php if ($order['postal_code']): ?>
                        <br>کد پستی: <span class="ltr"><?= e($order['postal_code']) ?></span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <?php if ($order['customer_note']): ?>
                <p class="order-note">یادداشت مشتری: <?= e($order['customer_note']) ?></p>
            <?php endif; ?>

            <form method="post" action="<?= e(url('admin/orders/' . $order['id'] . '/details')) ?>" class="form">
                <?= Csrf::field() ?>

                <?php if ($isPost): ?>
                    <div class="field">
                        <label for="tracking_code">کد رهگیری پستی</label>
                        <input type="text" id="tracking_code" name="tracking_code" dir="ltr"
                               value="<?= e((string) $order['tracking_code']) ?>">
                    </div>
                <?php endif; ?>

                <div class="field">
                    <label for="admin_note">یادداشت داخلی</label>
                    <textarea id="admin_note" name="admin_note" rows="2"
                    ><?= e((string) $order['admin_note']) ?></textarea>
                </div>

                <div class="form-actions">
                    <button class="btn btn--ghost btn--sm" type="submit">ذخیره</button>
                </div>
            </form>
        </section>

        <!-- تراکنش‌ها -->
        <section class="panel">
            <h2 class="panel__title">تراکنش‌ها</h2>

            <?php if (!$payments): ?>
                <p class="empty">تراکنشی ثبت نشده است.</p>
            <?php else: ?>
                <ul class="payment-list">
                    <?php foreach ($payments as $payment): ?>
                        <li class="payment-row">
                            <div>
                                <strong><?= $payment['purpose'] === 'shipping' ? 'هزینه ارسال' : 'مبلغ کالاها' ?></strong>
                                <span class="block muted"><?= e(money((int) $payment['amount'])) ?></span>
                                <?php if ($payment['ref_id']): ?>
                                    <span class="block ltr muted">کد رهگیری: <?= e($payment['ref_id']) ?></span>
                                <?php endif; ?>
                                <?php if ((int) $payment['is_sandbox'] === 1): ?>
                                    <span class="badge">تست</span>
                                <?php endif; ?>
                            </div>
                            <span class="badge badge--<?= $payment['status'] === 'paid' ? 'ok' : 'off' ?>">
                                <?= $payment['status'] === 'paid' ? 'موفق' : 'ناموفق' ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </aside>
</div>
