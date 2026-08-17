<?php
/**
 * مدیریت نظرات در پنل
 *
 * @var array $reviews
 * @var App\Core\Paginator $paginator
 * @var int   $total
 * @var array $filters
 * @var array $counts
 */

use App\Core\Csrf;
use App\Core\View;
use App\Models\Review;

$tabs = [
    'pending'  => 'در انتظار تایید',
    'approved' => 'تاییدشده',
    'rejected' => 'رد شده',
    ''         => 'همه',
];
?>

<div class="page-actions">
    <p class="page-hint">
        هیچ نظری تا وقتی تایید نشود در سایت نمایش داده نمی‌شود.
        با تایید یا رد نظر، امتیاز محصول به‌صورت خودکار دوباره محاسبه می‌شود.
    </p>
</div>

<!-- زبانه‌های وضعیت -->
<div class="review-tabs">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="review-tab<?= $filters['status'] === $key ? ' is-active' : '' ?>"
           href="<?= e(url('admin/reviews' . ($key !== '' ? '?status=' . $key : '?status='))) ?>">
            <?= e($label) ?>
            <?php if (isset($counts[$key])): ?>
                <span class="review-tab__count"><?= e(fa_digits((string) $counts[$key])) ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (!$reviews): ?>
    <div class="panel"><p class="empty">نظری در این بخش وجود ندارد.</p></div>
<?php else: ?>
    <div class="review-admin-list">
        <?php foreach ($reviews as $r): ?>
            <article class="panel review-admin">
                <div class="review-admin__head">
                    <div>
                        <a href="<?= e(url('product/' . $r['product_slug'])) ?>" target="_blank" rel="noopener">
                            <strong><?= e($r['product_name']) ?></strong>
                        </a>
                        <span class="review__stars">
                            <?= str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']) ?>
                        </span>
                        <?php if ((int) $r['is_verified_buyer'] === 1): ?>
                            <span class="badge badge--ok">خریدار تاییدشده</span>
                        <?php endif; ?>
                    </div>

                    <span class="badge badge--<?= $r['status'] === 'approved' ? 'ok'
                        : ($r['status'] === 'rejected' ? 'canceled' : 'pending_payment') ?>">
                        <?= e(Review::STATUS_LABELS[$r['status']] ?? $r['status']) ?>
                    </span>
                </div>

                <p class="review-admin__meta muted">
                    <?= e($r['user_name']) ?> ·
                    <span class="ltr"><?= e($r['user_email']) ?></span> ·
                    <?= e(jdate($r['created_at'], 'datetime')) ?>
                </p>

                <?php if ($r['title']): ?>
                    <h3 class="review__title"><?= e($r['title']) ?></h3>
                <?php endif; ?>

                <p class="review-admin__text"><?= nl2br(e($r['comment'])) ?></p>

                <form method="post" action="<?= e(url('admin/reviews/' . $r['id'] . '/status')) ?>"
                      class="review-admin__form">
                    <?= Csrf::field() ?>

                    <div class="field">
                        <label for="reply-<?= (int) $r['id'] ?>">پاسخ فروشگاه (اختیاری)</label>
                        <textarea id="reply-<?= (int) $r['id'] ?>" name="admin_reply" rows="2"
                        ><?= e((string) $r['admin_reply']) ?></textarea>
                    </div>

                    <div class="review-admin__actions">
                        <?php if ($r['status'] !== 'approved'): ?>
                            <button class="btn btn--primary btn--sm" type="submit"
                                    name="status" value="approved">تایید و نمایش</button>
                        <?php endif; ?>

                        <?php if ($r['status'] !== 'rejected'): ?>
                            <button class="btn btn--ghost btn--sm" type="submit"
                                    name="status" value="rejected">رد کردن</button>
                        <?php endif; ?>

                        <?php if ($r['status'] !== 'pending'): ?>
                            <button class="btn btn--ghost btn--sm" type="submit"
                                    name="status" value="pending">بازگشت به انتظار</button>
                        <?php endif; ?>
                    </div>
                </form>

                <form method="post" action="<?= e(url('admin/reviews/' . $r['id'] . '/delete')) ?>"
                      class="review-admin__delete" data-confirm="این نظر برای همیشه حذف شود؟">
                    <?= Csrf::field() ?>
                    <button class="btn btn--danger btn--sm" type="submit">حذف نظر</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>

    <?php View::partial('site/partials/pagination', ['paginator' => $paginator]); ?>
<?php endif; ?>
