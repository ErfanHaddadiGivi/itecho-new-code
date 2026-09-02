<?php
/**
 * صفحه جزئیات محصول
 *
 * @var array $product
 * @var array $reviews
 * @var array $related
 * @var array $variantMap
 */

use App\Core\Csrf;
use App\Core\View;

$hasVariants = (int) $product['has_variants'] === 1 && $product['variants'] !== [];

// ویژگی‌های قابل انتخاب: از روی Variantها گروه‌بندی می‌شوند
$options = [];
foreach ($product['variants'] as $variant) {
    foreach ($variant['values'] as $value) {
        $attributeId = (int) $value['attribute_id'];

        if (!isset($options[$attributeId])) {
            $options[$attributeId] = [
                'name'   => $value['attribute_name'],
                'values' => [],
            ];
        }

        $options[$attributeId]['values'][(int) $value['attribute_value_id']] = [
            'label' => $value['value_label'],
            'color' => $value['color_code'],
        ];
    }
}

$hasDiscount = !empty($product['compare_at_price'])
    && (int) $product['compare_at_price'] > (int) $product['price'];

$inStock = (int) $product['stock'] > 0;
?>

<div class="container">
    <nav class="breadcrumb" aria-label="مسیر صفحه">
        <a href="<?= e(url('')) ?>">خانه</a>
        <?php if ($product['category_slug']): ?>
            <span aria-hidden="true">›</span>
            <a href="<?= e(url('category/' . $product['category_slug'])) ?>">
                <?= e($product['category_name']) ?>
            </a>
        <?php endif; ?>
        <span aria-hidden="true">›</span>
        <span><?= e($product['name']) ?></span>
    </nav>

    <div class="product-detail">
        <!-- گالری -->
        <div class="gallery">
            <div class="gallery__main">
                <?php if ($product['main_image']): ?>
                    <img id="gallery-main" src="<?= e(url('uploads/products/' . $product['main_image'])) ?>"
                         alt="<?= e($product['name']) ?>">
                <?php else: ?>
                    <div class="product-card__placeholder">بدون تصویر</div>
                <?php endif; ?>
            </div>

            <?php if ($product['images']): ?>
                <div class="gallery__thumbs">
                    <?php if ($product['main_image']): ?>
                        <button type="button" class="gallery__thumb is-active"
                                data-image="<?= e(url('uploads/products/' . $product['main_image'])) ?>">
                            <img src="<?= e(url('uploads/products/' . $product['main_image'])) ?>" alt="">
                        </button>
                    <?php endif; ?>

                    <?php foreach ($product['images'] as $image): ?>
                        <button type="button" class="gallery__thumb"
                                data-image="<?= e(url('uploads/products/' . $image['image'])) ?>">
                            <img src="<?= e(url('uploads/products/' . $image['image'])) ?>"
                                 alt="<?= e($image['alt_text'] ?? '') ?>">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- اطلاعات و خرید -->
        <div class="product-info">
            <h1><?= e($product['name']) ?></h1>

            <div class="product-info__meta">
                <?php if ($product['brand_name']): ?>
                    <a href="<?= e(url('search?q=' . urlencode($product['brand_name']))) ?>">
                        برند: <?= e($product['brand_name']) ?>
                    </a>
                <?php endif; ?>

                <span class="badge <?= $product['condition_type'] === 'used' ? 'badge--used' : 'badge--new' ?>">
                    <?= $product['condition_type'] === 'used' ? 'کارکرده' : 'نو' ?>
                </span>

                <?php if ((int) $product['rating_count'] > 0): ?>
                    <span class="rating">
                        ★ <?= e(fa_digits(number_format((float) $product['rating_avg'], 1))) ?>
                        <span class="muted">(<?= e(fa_digits((string) (int) $product['rating_count'])) ?> نظر)</span>
                    </span>
                <?php endif; ?>
            </div>

            <form class="buy-box" method="post" action="<?= e(url('cart/add')) ?>" id="buy-form"
                  data-variants="<?= e(json_encode($variantMap, JSON_UNESCAPED_UNICODE)) ?>">
                <?= Csrf::field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="variant_id" id="variant-id" value="">

                <?php if ($hasVariants): ?>
                    <?php foreach ($options as $attributeId => $option): ?>
                        <div class="option-group" data-attribute="<?= (int) $attributeId ?>">
                            <span class="option-group__label"><?= e($option['name']) ?></span>
                            <div class="option-values">
                                <?php foreach ($option['values'] as $valueId => $value): ?>
                                    <label class="option">
                                        <input type="radio" name="option[<?= (int) $attributeId ?>]"
                                               value="<?= (int) $valueId ?>">
                                        <span class="option__box">
                                            <?php if ($value['color']): ?>
                                                <span class="swatch" style="background: <?= e($value['color']) ?>"
                                                      aria-hidden="true"></span>
                                            <?php endif; ?>
                                            <?= e($value['label']) ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="price-box" id="price-box">
                    <?php if ($hasVariants): ?>
                        <span class="price-box__hint" id="price-hint">
                            برای دیدن قیمت، گزینه‌ها را انتخاب کنید
                        </span>
                        <span class="price-box__from">
                            از <?= e(money((int) $product['price'])) ?>
                        </span>
                    <?php else: ?>
                        <?php if ($hasDiscount): ?>
                            <span class="price-box__old"><?= e(money((int) $product['compare_at_price'], false)) ?></span>
                        <?php endif; ?>
                        <span class="price-box__now" id="price-now"><?= e(money((int) $product['price'])) ?></span>
                    <?php endif; ?>
                </div>

                <div class="stock-line" id="stock-line">
                    <?php if (!$hasVariants): ?>
                        <?php if ($inStock): ?>
                            <span class="in-stock">موجود در انبار</span>
                        <?php else: ?>
                            <span class="out-stock">ناموجود</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="buy-actions">
                    <label class="qty">
                        <span>تعداد</span>
                        <input type="number" name="quantity" value="1" min="1" max="20" dir="ltr">
                    </label>

                    <button class="btn btn--primary btn--lg" type="submit" id="add-to-cart"
                        <?= (!$hasVariants && !$inStock) ? 'disabled' : '' ?>>
                        <?= (!$hasVariants && !$inStock) ? 'ناموجود' : 'افزودن به سبد خرید' ?>
                    </button>
                </div>
            </form>

            <!-- علاقه‌مندی: فرم جدا تا با فرم خرید قاطی نشود -->
            <form method="post" action="<?= e(url('wishlist/toggle')) ?>" class="wish-inline">
                <?= Csrf::field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <button type="submit" class="btn btn--ghost btn--block wish-inline__btn<?= $inWishlist ? ' is-on' : '' ?>">
                    <svg viewBox="0 0 24 24" width="17" height="17" aria-hidden="true">
                        <path d="M12 20.5S3.5 15 3.5 9.2A4.7 4.7 0 0 1 12 6.6a4.7 4.7 0 0 1 8.5 2.6c0 5.8-8.5 11.3-8.5 11.3z"
                              fill="<?= $inWishlist ? 'currentColor' : 'none' ?>" stroke="currentColor"
                              stroke-width="1.8" stroke-linejoin="round"/>
                    </svg>
                    <?= $inWishlist ? 'در لیست علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها' ?>
                </button>
            </form>

            <div class="buy-box-tail">

                <?php if ($product['short_description']): ?>
                    <div class="short-desc">
                        <span class="short-desc__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="18" height="18">
                                <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M12 11v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <circle cx="12" cy="7.6" r="1.15" fill="currentColor"/>
                            </svg>
                        </span>
                        <p class="short-desc__text"><?= e($product['short_description']) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($product['warranty']): ?>
                    <p class="buy-note">🛡 <?= e($product['warranty']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- توضیحات و مشخصات -->
    <div class="product-tabs">
        <!-- شورتکات‌های پرش به بخش‌ها (با کلیک، صفحه نرم اسکرول می‌شود) -->
        <nav class="product-nav" aria-label="بخش‌های محصول">
            <?php if ($product['description']): ?>
                <a class="product-nav__link" href="#desc">توضیحات</a>
            <?php endif; ?>
            <?php if ($product['specs']): ?>
                <a class="product-nav__link" href="#specs">مشخصات فنی</a>
            <?php endif; ?>
            <a class="product-nav__link" href="#reviews">نظرات کاربران</a>
            <?php if ($related): ?>
                <a class="product-nav__link" href="#related">محصولات مشابه</a>
            <?php endif; ?>
        </nav>

        <?php if ($product['description']): ?>
            <section class="panel-block" id="desc">
                <h2>توضیحات</h2>
                <div class="rich-text"><?= $product['description'] ?></div>
            </section>
        <?php endif; ?>

        <?php if ($product['specs']): ?>
            <section class="panel-block" id="specs">
                <h2>مشخصات فنی</h2>
                <table class="spec-table">
                    <tbody>
                    <?php foreach ($product['specs'] as $spec): ?>
                        <tr>
                            <th><?= e($spec['spec_key']) ?></th>
                            <td><?= e($spec['spec_value']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>

        <section class="panel-block" id="reviews">
            <h2>نظرات کاربران</h2>

            <?php if ($canReview): ?>
                <form method="post" action="<?= e(url('review')) ?>" class="review-form">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">

                    <div class="field">
                        <span class="option-group__label">امتیاز شما <span class="req">*</span></span>
                        <div class="star-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star-<?= $i ?>" name="rating" value="<?= $i ?>"
                                    <?= $i === 5 ? 'checked' : '' ?> required>
                                <label for="star-<?= $i ?>" title="<?= e(fa_digits((string) $i)) ?> ستاره">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="field">
                        <label for="review-title">عنوان نظر</label>
                        <input type="text" id="review-title" name="title" maxlength="150">
                    </div>

                    <div class="field">
                        <label for="review-comment">متن نظر <span class="req">*</span></label>
                        <textarea id="review-comment" name="comment" rows="4" required
                                  placeholder="تجربه خود را از این محصول بنویسید…"></textarea>
                        <span class="field__hint">حداقل ۱۰ کاراکتر. نظر شما پس از تایید مدیر نمایش داده می‌شود.</span>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn--primary" type="submit">ثبت نظر</button>
                    </div>
                </form>

            <?php elseif ($myReview !== null): ?>
                <p class="review-login-note">
                    <?php if ($myReview['status'] === 'approved'): ?>
                        نظر شما برای این محصول ثبت و تایید شده است.
                    <?php elseif ($myReview['status'] === 'rejected'): ?>
                        نظر شما برای این محصول تایید نشد.
                    <?php else: ?>
                        نظر شما ثبت شده و در انتظار تایید مدیر است.
                    <?php endif; ?>
                </p>

            <?php elseif (!App\Core\Auth::check()): ?>
                <p class="review-login-note">
                    برای ثبت نظر <a href="<?= e(url('login')) ?>">وارد حساب کاربری</a> خود شوید.
                    فقط کسانی که این محصول را خریده‌اند می‌توانند نظر ثبت کنند.
                </p>

            <?php else: ?>
                <p class="review-login-note">
                    ثبت نظر فقط برای خریداران این محصول امکان‌پذیر است.
                </p>
            <?php endif; ?>

            <?php if (!$reviews): ?>
                <p class="empty">هنوز نظری برای این محصول ثبت نشده است.</p>
            <?php else: ?>
                <ul class="review-list">
                    <?php foreach ($reviews as $review): ?>
                        <li class="review">
                            <div class="review__head">
                                <strong><?= e(trim($review['first_name'] . ' ' . $review['last_name'])) ?></strong>
                                <?php if ((int) $review['is_verified_buyer'] === 1): ?>
                                    <span class="badge badge--ok">خریدار تاییدشده</span>
                                <?php endif; ?>
                                <span class="review__stars" aria-label="<?= (int) $review['rating'] ?> از ۵">
                                    <?= str_repeat('★', (int) $review['rating'])
                                      . str_repeat('☆', 5 - (int) $review['rating']) ?>
                                </span>
                                <span class="review__date"><?= e(jdate($review['created_at'])) ?></span>
                            </div>

                            <?php if ($review['title']): ?>
                                <h3 class="review__title"><?= e($review['title']) ?></h3>
                            <?php endif; ?>

                            <p class="review__text"><?= nl2br(e($review['comment'])) ?></p>

                            <?php if ($review['admin_reply']): ?>
                                <div class="review__reply">
                                    <strong>پاسخ فروشگاه:</strong>
                                    <?= nl2br(e($review['admin_reply'])) ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <?php if ($related): ?>
        <section class="section" id="related">
            <div class="section__head"><h2>محصولات مشابه</h2></div>
            <div class="product-grid">
                <?php foreach ($related as $item): ?>
                    <?php View::partial('site/partials/product-card', ['product' => $item]); ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>
