<?php
/** نظرهای من  @var array $reviews @var array $toReview */
use App\Core\View;
use App\Models\Review;
?>
<div class="container">
  <h1 class="page-title">نظرهای من</h1>
  <div class="account">
    <?php View::partial('site/account/_nav', ['active' => 'reviews']); ?>

    <div class="account__main">
      <?php if ($toReview): ?>
        <section class="panel-block">
          <h2>منتظر نظر شما</h2>
          <p class="muted" style="margin-bottom:12px">
            این کالاها را خریده‌اید و هنوز نظری ثبت نکرده‌اید:
          </p>
          <ul class="to-review">
            <?php foreach ($toReview as $item): ?>
              <li>
                <a href="<?= e(url('product/' . $item['slug'] . '#reviews')) ?>">
                  <?= e($item['name']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </section>
      <?php endif; ?>

      <section class="panel-block">
        <h2>نظرهای ثبت‌شده</h2>

        <?php if (!$reviews): ?>
          <p class="empty">هنوز نظری ثبت نکرده‌اید.</p>
        <?php else: ?>
          <ul class="review-list">
            <?php foreach ($reviews as $r): ?>
              <li class="review">
                <div class="review__head">
                  <a href="<?= e(url('product/' . $r['product_slug'])) ?>">
                    <strong><?= e($r['product_name']) ?></strong>
                  </a>
                  <span class="review__stars">
                    <?= str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']) ?>
                  </span>
                  <span class="badge badge--<?= $r['status'] === 'approved' ? 'ok' : ($r['status'] === 'rejected' ? 'canceled' : 'pending_payment') ?>">
                    <?= e(Review::STATUS_LABELS[$r['status']] ?? $r['status']) ?>
                  </span>
                  <span class="review__date"><?= e(jdate($r['created_at'])) ?></span>
                </div>

                <?php if ($r['title']): ?>
                  <h3 class="review__title"><?= e($r['title']) ?></h3>
                <?php endif; ?>

                <p class="review__text"><?= nl2br(e($r['comment'])) ?></p>

                <?php if ($r['status'] === 'pending'): ?>
                  <p class="review__pending">این نظر پس از تایید مدیر در صفحه محصول نمایش داده می‌شود.</p>
                <?php endif; ?>

                <?php if ($r['admin_reply']): ?>
                  <div class="review__reply">
                    <strong>پاسخ فروشگاه:</strong> <?= nl2br(e($r['admin_reply'])) ?>
                  </div>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    </div>
  </div>
</div>
