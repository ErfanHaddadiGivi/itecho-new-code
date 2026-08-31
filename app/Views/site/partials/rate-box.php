<?php
/**
 * باکس شناورِ نرخ لحظه‌ای ارز (دلار و درهم به تومان) — گوشهٔ چپ‌پایین صفحه.
 * مقدار اولیه از کش (بدون تماس شبکه) پر می‌شود و rates.js آن را ساعتی تازه می‌کند.
 */

use App\Core\Rates;

$seed  = Rates::peek();
$byKey = [];
foreach (($seed['items'] ?? []) as $it) {
    $byKey[$it['key']] = $it;
}

$defs = ['usd' => 'دلار', 'aed' => 'درهم'];
?>
<aside class="rate-box<?= !empty($seed['stale']) ? ' is-stale' : '' ?>"
       data-rate-box data-endpoint="<?= e(url('api/rates')) ?>"
       aria-label="قیمت لحظه‌ای ارزها">
    <div class="rate-box__head">
        <span class="rate-box__title">قیمت لحظه‌ای ارزها</span>
        <span class="rate-box__live" title="به‌روزرسانی خودکار" aria-hidden="true"></span>
    </div>

    <ul class="rate-box__list">
        <?php foreach ($defs as $key => $label): $it = $byKey[$key] ?? null; ?>
            <li class="rate-row" data-rate="<?= e($key) ?>">
                <span class="rate-row__label"><?= e($label) ?></span>
                <span class="rate-row__value">
                    <span class="rate-row__num" data-rate-val><?= $it ? e(fa_digits(number_format((int) $it['toman']))) : '—' ?></span>
                    <span class="rate-row__unit">تومان</span>
                </span>
                <span class="rate-row__chg rate-row__chg--<?= e($it['dir'] ?? 'none') ?>" data-rate-chg>
                    <?php if ($it && (float) $it['change'] !== 0.0): ?>
                        <?= $it['dir'] === 'down' ? '▼' : '▲' ?> <?= e(fa_digits(number_format(abs((float) $it['change']), 2))) ?>٪
                    <?php endif; ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="rate-box__foot">
        <span class="rate-box__time" data-rate-time></span>
        <span class="rate-box__src">منبع: tgju.org</span>
    </div>
</aside>
