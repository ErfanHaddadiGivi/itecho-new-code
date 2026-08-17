<?php
/** دفترچه آدرس  @var array $addresses */
use App\Core\Csrf;
use App\Core\View;
?>
<div class="container">
  <h1 class="page-title">دفترچه آدرس</h1>
  <div class="account">
    <?php View::partial('site/account/_nav', ['active' => 'addresses']); ?>

    <div class="account__main">
      <div class="page-actions">
        <p class="page-hint">آدرس پیش‌فرض هنگام تسویه‌حساب به‌صورت خودکار انتخاب می‌شود.</p>
        <a class="btn btn--primary" href="<?= e(url('account/addresses/create')) ?>">افزودن آدرس</a>
      </div>

      <?php if (!$addresses): ?>
        <div class="notice">
          <strong>هنوز آدرسی ثبت نکرده‌اید.</strong>
          <span>با ثبت آدرس، تسویه‌حساب سریع‌تر انجام می‌شود.</span>
        </div>
      <?php else: ?>
        <div class="address-list">
          <?php foreach ($addresses as $a): ?>
            <div class="address-card<?= (int) $a['is_default'] === 1 ? ' is-default' : '' ?>">
              <div class="address-card__head">
                <strong><?= e($a['receiver_name']) ?></strong>
                <?php if ((int) $a['is_default'] === 1): ?>
                  <span class="badge badge--ok">پیش‌فرض</span>
                <?php endif; ?>
              </div>

              <p class="ltr muted" style="margin:0 0 6px"><?= e($a['phone']) ?></p>
              <p class="muted" style="margin:0 0 12px">
                <?= e($a['province']) ?>، <?= e($a['city']) ?><br>
                <?= e($a['address_line']) ?>
                <?php if ($a['postal_code']): ?>
                  <br>کد پستی: <span class="ltr"><?= e($a['postal_code']) ?></span>
                <?php endif; ?>
              </p>

              <div class="address-card__actions">
                <a class="btn btn--ghost btn--sm"
                   href="<?= e(url('account/addresses/' . $a['id'] . '/edit')) ?>">ویرایش</a>

                <?php if ((int) $a['is_default'] !== 1): ?>
                  <form method="post" action="<?= e(url('account/addresses/' . $a['id'] . '/default')) ?>"
                        class="inline-form">
                    <?= Csrf::field() ?>
                    <button class="btn btn--ghost btn--sm" type="submit">پیش‌فرض کن</button>
                  </form>
                <?php endif; ?>

                <form method="post" action="<?= e(url('account/addresses/' . $a['id'] . '/delete')) ?>"
                      class="inline-form" data-confirm="این آدرس حذف شود؟">
                  <?= Csrf::field() ?>
                  <button class="btn btn--danger btn--sm" type="submit">حذف</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
