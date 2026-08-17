<?php
/**
 * صفحه تسویه‌حساب
 *
 * @var array  $items
 * @var array  $summary
 * @var ?array $address
 * @var array  $user
 * @var int    $pickupFee
 * @var string $pickupAddr
 * @var string $postNote
 * @var array  $errors
 */

use App\Core\Csrf;
use App\Core\Flash;

/** مقدار فیلد: اول مقدار بازگشتی بعد از خطا، بعد آدرس ذخیره‌شده کاربر */
$value = static function (string $field, string $fallback = '') use ($address) {
    $old = Flash::oldInput($field);
    if ($old !== null) {
        return $old;
    }
    return $address[$field] ?? $fallback;
};

$method = Flash::oldInput('delivery_method') ?? 'pickup';
?>

<div class="container">
    <h1 class="page-title">تسویه‌حساب</h1>

    <form method="post" action="<?= e(url('checkout')) ?>" class="checkout">
        <?= Csrf::field() ?>

        <div class="checkout__main">
            <!-- روش تحویل -->
            <section class="panel-block">
                <h2>روش تحویل</h2>

                <label class="delivery-option">
                    <input type="radio" name="delivery_method" value="pickup"
                        <?= $method === 'pickup' ? 'checked' : '' ?>>
                    <span class="delivery-option__body">
                        <span class="delivery-option__title">دریافت حضوری از فروشگاه</span>
                        <span class="delivery-option__desc">
                            <?php if ($pickupFee > 0): ?>
                                هزینه ثابت <?= e(money($pickupFee)) ?>
                            <?php else: ?>
                                بدون هزینه
                            <?php endif; ?>
                            <?php if ($pickupAddr !== ''): ?>
                                — <?= e($pickupAddr) ?>
                            <?php endif; ?>
                        </span>
                    </span>
                </label>

                <label class="delivery-option">
                    <input type="radio" name="delivery_method" value="post"
                        <?= $method === 'post' ? 'checked' : '' ?>>
                    <span class="delivery-option__body">
                        <span class="delivery-option__title">ارسال با پست</span>
                        <span class="delivery-option__desc">
                            <?= e($postNote !== '' ? $postNote
                                : 'هزینه ارسال پس از بسته‌بندی محاسبه و لینک پرداخت آن برای شما ارسال می‌شود.') ?>
                        </span>
                    </span>
                </label>
            </section>

            <!-- اطلاعات گیرنده -->
            <section class="panel-block">
                <h2>اطلاعات گیرنده</h2>

                <div class="grid-2">
                    <div class="field">
                        <label for="receiver_name">نام تحویل‌گیرنده <span class="req">*</span></label>
                        <input type="text" id="receiver_name" name="receiver_name" required
                               value="<?= e((string) $value('receiver_name',
                                   trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')))) ?>">
                        <?php if (isset($errors['receiver_name'])): ?>
                            <span class="field__error"><?= e($errors['receiver_name']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="receiver_phone">شماره موبایل <span class="req">*</span></label>
                        <input type="tel" id="receiver_phone" name="receiver_phone" required dir="ltr"
                               value="<?= e((string) ($value('phone', (string) ($user['phone'] ?? '')))) ?>">
                        <?php if (isset($errors['receiver_phone'])): ?>
                            <span class="field__error"><?= e($errors['receiver_phone']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- این بخش فقط برای ارسال پستی لازم است -->
                <div id="post-fields"
                     data-items-total="<?= (int) $summary['items_total'] ?>"
                     data-pickup-fee="<?= (int) $pickupFee ?>"
                     <?= $method === 'pickup' ? 'hidden' : '' ?>>
                    <div class="grid-2">
                        <div class="field">
                            <label for="province">استان <span class="req">*</span></label>
                            <input type="text" id="province" name="province"
                                   value="<?= e((string) $value('province')) ?>">
                            <?php if (isset($errors['province'])): ?>
                                <span class="field__error"><?= e($errors['province']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="city">شهر <span class="req">*</span></label>
                            <input type="text" id="city" name="city"
                                   value="<?= e((string) $value('city')) ?>">
                            <?php if (isset($errors['city'])): ?>
                                <span class="field__error"><?= e($errors['city']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="field">
                        <label for="address_line">نشانی کامل <span class="req">*</span></label>
                        <textarea id="address_line" name="address_line" rows="3"
                        ><?= e((string) $value('address_line')) ?></textarea>
                        <?php if (isset($errors['address_line'])): ?>
                            <span class="field__error"><?= e($errors['address_line']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="field field--narrow">
                        <label for="postal_code">کد پستی</label>
                        <input type="text" id="postal_code" name="postal_code" inputmode="numeric" dir="ltr"
                               value="<?= e((string) $value('postal_code')) ?>">
                        <?php if (isset($errors['postal_code'])): ?>
                            <span class="field__error"><?= e($errors['postal_code']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="field field--check">
                        <label>
                            <input type="checkbox" name="save_address" value="1" checked>
                            این آدرس برای خریدهای بعدی ذخیره شود
                        </label>
                    </div>
                </div>

                <div class="field">
                    <label for="customer_note">یادداشت برای فروشگاه</label>
                    <textarea id="customer_note" name="customer_note" rows="2"
                              placeholder="اختیاری"><?= e((string) $value('customer_note')) ?></textarea>
                </div>
            </section>
        </div>

        <!-- خلاصه سفارش -->
        <aside class="checkout__summary">
            <h2>خلاصه سفارش</h2>

            <ul class="checkout-items">
                <?php foreach ($items as $item): ?>
                    <li>
                        <span class="checkout-items__name">
                            <?= e($item['name']) ?>
                            <?php if ($item['variant_title']): ?>
                                <small><?= e($item['variant_title']) ?></small>
                            <?php endif; ?>
                        </span>
                        <span class="checkout-items__qty">×<?= e(fa_digits((string) (int) $item['quantity'])) ?></span>
                        <span class="checkout-items__price">
                            <?= e(money((int) $item['unit_price'] * (int) $item['quantity'], false)) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="cart-summary__row">
                <span>جمع کالاها</span>
                <span><?= e(money($summary['items_total'])) ?></span>
            </div>

            <div class="cart-summary__row" id="row-pickup-fee" <?= $method === 'post' ? 'hidden' : '' ?>>
                <span>هزینه تحویل حضوری</span>
                <span><?= $pickupFee > 0 ? e(money($pickupFee)) : 'رایگان' ?></span>
            </div>

            <div class="cart-summary__row cart-summary__row--total" id="row-total">
                <span>مبلغ قابل پرداخت</span>
                <span id="total-amount"><?= e(money($summary['items_total'] + $pickupFee)) ?></span>
            </div>

            <div class="cart-summary__note" id="post-note" <?= $method === 'pickup' ? 'hidden' : '' ?>>
                الان فقط مبلغ کالاها را می‌پردازید. هزینه ارسال پس از بسته‌بندی
                محاسبه و لینک پرداخت آن برای شما ایمیل می‌شود.
            </div>

            <button class="btn btn--primary btn--block btn--lg" type="submit">
                ثبت سفارش و پرداخت
            </button>

            <p class="checkout__secure">پرداخت از طریق درگاه امن زرین‌پال انجام می‌شود.</p>
        </aside>
    </form>
</div>
