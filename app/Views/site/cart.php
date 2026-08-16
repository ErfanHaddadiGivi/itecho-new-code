<?php
/**
 * سبد خرید
 *
 * @var array $items
 * @var array $summary
 * @var int   $pickupFee
 */

use App\Core\Auth;
use App\Core\Csrf;
?>

<div class="container">
    <h1 class="page-title">سبد خرید</h1>

    <?php if (!$items): ?>
        <div class="notice">
            <strong>سبد خرید شما خالی است.</strong>
            <span>از صفحه محصولات، کالای مورد نظرتان را به سبد اضافه کنید.</span>
            <p style="margin-top:16px">
                <a class="btn btn--primary" href="<?= e(url('')) ?>">مشاهده محصولات</a>
            </p>
        </div>
    <?php else: ?>
        <div class="cart">
            <div class="cart__items">
                <?php foreach ($items as $item): ?>
                    <?php $problem = $summary['problems'][$item['id']] ?? null; ?>

                    <div class="cart-item<?= $problem ? ' has-problem' : '' ?>">
                        <a class="cart-item__media" href="<?= e(url('product/' . $item['slug'])) ?>">
                            <?php if ($item['main_image']): ?>
                                <img src="<?= e(url('uploads/products/' . $item['main_image'])) ?>"
                                     alt="<?= e($item['name']) ?>">
                            <?php else: ?>
                                <span class="product-card__placeholder">بدون تصویر</span>
                            <?php endif; ?>
                        </a>

                        <div class="cart-item__body">
                            <a class="cart-item__name" href="<?= e(url('product/' . $item['slug'])) ?>">
                                <?= e($item['name']) ?>
                            </a>

                            <?php if ($item['variant_title']): ?>
                                <span class="cart-item__variant"><?= e($item['variant_title']) ?></span>
                            <?php endif; ?>

                            <?php if ($problem): ?>
                                <span class="cart-item__problem"><?= e($problem) ?></span>
                            <?php endif; ?>

                            <div class="cart-item__controls">
                                <form method="post" action="<?= e(url('cart/update')) ?>" class="qty-form">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <label class="qty qty--sm">
                                        <span class="sr-only">تعداد</span>
                                        <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>"
                                               min="1" max="20" dir="ltr" onchange="this.form.submit()">
                                    </label>
                                    <noscript><button class="btn btn--ghost btn--sm" type="submit">به‌روزرسانی</button></noscript>
                                </form>

                                <form method="post" action="<?= e(url('cart/remove')) ?>"
                                      data-confirm="این کالا از سبد حذف شود؟">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                    <button class="btn btn--danger btn--sm" type="submit">حذف</button>
                                </form>
                            </div>
                        </div>

                        <div class="cart-item__price">
                            <span class="cart-item__unit"><?= e(money((int) $item['unit_price'])) ?></span>
                            <?php if ((int) $item['quantity'] > 1): ?>
                                <span class="cart-item__line">
                                    جمع: <?= e(money((int) $item['unit_price'] * (int) $item['quantity'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- خلاصه سفارش -->
            <aside class="cart-summary">
                <h2>خلاصه سفارش</h2>

                <div class="cart-summary__row">
                    <span>تعداد کالا</span>
                    <span><?= e(fa_digits((string) $summary['count'])) ?></span>
                </div>

                <div class="cart-summary__row">
                    <span>جمع کالاها</span>
                    <span><?= e(money($summary['items_total'])) ?></span>
                </div>

                <div class="cart-summary__note">
                    هزینه ارسال در مرحله تسویه‌حساب مشخص می‌شود.
                    برای ارسال پستی، هزینه دقیق پس از بسته‌بندی توسط کارشناس محاسبه و
                    لینک پرداخت آن برای شما ارسال می‌شود.
                </div>

                <?php if ($summary['problems']): ?>
                    <div class="alert alert--error">
                        برخی کالاهای سبد مشکل دارند. قبل از ادامه، آن‌ها را اصلاح یا حذف کنید.
                    </div>
                <?php endif; ?>

                <a class="btn btn--primary btn--block btn--lg<?= $summary['problems'] ? ' is-disabled' : '' ?>"
                   href="<?= e(url('checkout')) ?>">
                    ادامه و تسویه‌حساب
                </a>

                <?php if (!Auth::check()): ?>
                    <p class="cart-summary__login">
                        برای تکمیل خرید باید وارد حساب کاربری شوید.
                        سبد خرید شما پس از ورود حفظ می‌شود.
                    </p>
                <?php endif; ?>
            </aside>
        </div>
    <?php endif; ?>
</div>
