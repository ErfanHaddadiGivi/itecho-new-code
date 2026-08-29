<?php
/**
 * باکس نرخ لحظه‌ای ارز (دلار و درهم به تومان).
 * مقدار اولیه از کش (بدون تماس شبکه) پر می‌شود و rates.js آن را تازه نگه می‌دارد.
 */

use App\Core\Rates;

$seed  = Rates::peek();
$byKey = [];
foreach (($seed['items'] ?? []) as $it) {
    $byKey[$it['key']] = $it;
}

$defs = ['usd' => 'دلار', 'aed' => 'درهم'];
?>
<div class="rate-ticker<?= !empty($seed['stale']) ? ' is-stale' : '' ?>"
     data-rate-box data-endpoint="<?= e(url('api/rates')) ?>"
     title="نرخ لحظه‌ای ارز به تومان — منبع: tgju.org">
    <?php foreach ($defs as $key => $label): $it = $byKey[$key] ?? null; ?>
        <span class="rate-pill" data-rate="<?= e($key) ?>">
            <span class="rate-pill__label"><?= e($label) ?></span>
            <span class="rate-pill__val" data-rate-val><?= $it ? e(fa_digits(number_format((int) $it['toman']))) : '—' ?></span>
            <span class="rate-pill__chg rate-pill__chg--<?= e($it['dir'] ?? 'none') ?>" data-rate-chg>
                <?php if ($it && (float) $it['change'] !== 0.0): ?>
                    <?= $it['dir'] === 'down' ? '▼' : '▲' ?> <?= e(fa_digits(number_format(abs((float) $it['change']), 2))) ?>٪
                <?php endif; ?>
            </span>
        </span>
    <?php endforeach; ?>
    <span class="rate-ticker__unit">تومان</span>
</div>
