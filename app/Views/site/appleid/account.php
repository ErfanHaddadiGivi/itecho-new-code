<?php
/**
 * پروفایل کاربر اپل‌آیدی: لیست سفارش‌ها + اقدام بر اساس وضعیت + تحویل.
 *
 * @var array      $user
 * @var array      $orders
 * @var array      $errors
 */
use App\Core\Csrf;

$cancelForm = static function (int $id): string {
    return '<form method="post" action="' . e(url('appleid/order/' . $id . '/cancel')) . '" class="inline-form" data-confirm="این سفارش لغو شود؟">'
        . Csrf::field()
        . '<button class="btn btn--ghost btn--sm" type="submit">لغو</button></form>';
};

$statusLabels = [
    'draft'                  => ['ناتمام', 'badge--off'],
    'pending_payment'        => ['در انتظار پرداخت', 'badge--warn'],
    'pending_approval'       => ['در حال بررسی', 'badge--warn'],
    'approved_awaiting_code' => ['منتظر کد ایمیل', 'badge--brand'],
    'code_received'          => ['در حال آماده‌سازی', 'badge--brand'],
    'completed'              => ['تحویل شد', 'badge--ok'],
    'rejected'               => ['رد شد', 'badge--danger'],
    'cancelled'              => ['لغو شد', 'badge--off'],
];
?>
<div class="container">
    <div class="appleid-account">
        <div class="appleid-account__head">
            <div>
                <h1>پروفایل اپل‌آیدی</h1>
                <span class="muted"><?= e($user['name'] ?: $user['phone']) ?></span>
            </div>
            <div class="appleid-account__actions">
                <a class="btn btn--primary" href="<?= e(url('appleid/order/new')) ?>">+ درخواست جدید</a>
                <form method="post" action="<?= e(url('appleid/logout')) ?>" class="inline-form">
                    <?= Csrf::field() ?>
                    <button class="btn btn--ghost btn--sm" type="submit">خروج</button>
                </form>
            </div>
        </div>

        <?php if (isset($errors['code'])): ?><p class="field__error"><?= e($errors['code']) ?></p><?php endif; ?>

        <?php if (!$orders): ?>
            <div class="notice">
                <strong>هنوز سفارشی نداری.</strong>
                <span>با دکمهٔ «درخواست جدید» اولین اپل‌آیدی‌ات رو سفارش بده.</span>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $o): ?>
                <?php [$label, $badge] = $statusLabels[$o['status']] ?? ['—', 'badge--off']; ?>
                <div class="appleid-order">
                    <div class="appleid-order__top">
                        <div>
                            <span class="appleid-order__id">سفارش #<?= (int) $o['id'] ?></span>
                            <span class="badge <?= e($badge) ?>"><?= e($label) ?></span>
                        </div>
                        <span class="muted"><?= e(jdate($o['created_at'])) ?></span>
                    </div>

                    <div class="appleid-order__meta">
                        ضمانت: <b><?= e($o['warranty_name'] ?? '-') ?></b>
                        · آیکلود: <b><?= !empty($o['icloud_enabled']) ? 'فعال' : 'غیرفعال' ?></b>
                        · مبلغ: <b><?= e(fa_digits(number_format((int) $o['price_amount']))) ?></b> تومان
                    </div>

                    <?php if ($o['status'] === 'draft'): ?>
                        <div class="appleid-order__do">
                            <a class="btn btn--primary btn--sm" href="<?= e(url('appleid/order/' . $o['id'] . '/info')) ?>">ادامهٔ ثبت سفارش</a>
                            <?= $cancelForm((int) $o["id"]) ?>
                        </div>

                    <?php elseif ($o['status'] === 'pending_payment'): ?>
                        <div class="appleid-order__do">
                            <a class="btn btn--primary btn--sm" href="<?= e(url('appleid/order/' . $o['id'] . '/pay')) ?>">پرداخت و آپلود فیش</a>
                            <?= $cancelForm((int) $o["id"]) ?>
                        </div>

                    <?php elseif ($o['status'] === 'pending_approval'): ?>
                        <p class="appleid-order__hint">فیش دریافت شد؛ سفارش در حال بررسی توسط پشتیبانی است.</p>

                    <?php elseif ($o['status'] === 'approved_awaiting_code'): ?>
                        <div class="appleid-order__code">
                            <p class="appleid-order__hint">✅ سفارش تأیید شد. کدی که از اپل به ایمیلت می‌رسه رو اینجا وارد کن:</p>
                            <form method="post" action="<?= e(url('appleid/order/' . $o['id'] . '/code')) ?>" class="code-form">
                                <?= Csrf::field() ?>
                                <input type="text" name="code" inputmode="numeric" placeholder="کد تأیید" dir="ltr" required>
                                <button class="btn btn--primary btn--sm" type="submit">ثبت کد</button>
                            </form>
                        </div>

                    <?php elseif ($o['status'] === 'code_received'): ?>
                        <p class="appleid-order__hint">کد ثبت شد؛ اپل‌آیدی‌ات به‌زودی همین‌جا تحویل داده می‌شه.</p>

                    <?php elseif ($o['status'] === 'completed'): ?>
                        <div class="appleid-order__delivered">
                            <h3>🎉 اپل‌آیدی شما</h3>
                            <pre class="appleid-creds"><?= e($o['final_credentials']) ?></pre>
                        </div>

                    <?php elseif ($o['status'] === 'rejected'): ?>
                        <p class="appleid-order__hint appleid-order__hint--danger">
                            علت رد: <?= e($o['reject_reason'] ?? '—') ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

