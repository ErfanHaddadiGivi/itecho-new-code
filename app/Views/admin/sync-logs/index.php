<?php
/**
 * گزارش همگام‌سازی محصولات از گوگل‌شیت.
 *
 * @var array $logs فهرست اجراهای اخیر همگام‌سازی
 */

// آدرس کامل نقطه پایانی که در اسکریپت گوگل باید تنظیم شود
$endpoint = rtrim(url('api/sheet-sync.php'), '/');
?>

<div class="panel panel--todo">
    <h2 class="panel__title">راهنما</h2>
    <p class="page-hint">
        قیمت و موجودی محصولات (و ساخت محصول ساده‌ی جدید) از یک گوگل‌شیت خصوصی و
        به‌صورت خودکار دو بار در روز (حدود ۷ صبح و ۲ بعدازظهر) به‌روزرسانی می‌شوند.
        آدرس نقطه‌ی دریافت که باید در اسکریپت گوگل ثبت شود:
    </p>
    <p><code class="ltr" style="display:inline-block;word-break:break-all;"><?= e($endpoint) ?></code></p>
    <p class="page-hint">
        هر بار که همگام‌سازی اجرا شود، نتیجه‌اش این‌جا ثبت می‌شود. جزئیات راه‌اندازی
        در فایل <code class="ltr">docs/SHEET-SYNC.md</code> نوشته شده است.
    </p>
</div>

<?php if (!$logs): ?>
    <div class="panel">
        <p class="empty">هنوز هیچ همگام‌سازی‌ای ثبت نشده است. پس از اولین اجرای اسکریپت گوگل، گزارش‌ها این‌جا نمایش داده می‌شوند.</p>
    </div>
<?php else: ?>
    <div class="sync-list">
        <?php foreach ($logs as $log): ?>
            <?php
                $rejected = [];
                if (!empty($log['rejected_rows'])) {
                    $decoded = json_decode((string) $log['rejected_rows'], true);
                    if (is_array($decoded)) {
                        $rejected = $decoded;
                    }
                }
                $hasRejected = (int) $log['rejected'] > 0;
            ?>
            <div class="sync-card<?= $hasRejected ? ' sync-card--warn' : '' ?>">
                <div class="sync-card__head">
                    <span class="sync-card__time"><?= e(jdate($log['created_at'], 'datetime')) ?></span>
                    <span class="sync-card__source">منبع: <?= e($log['source']) ?></span>
                </div>

                <div class="sync-stats">
                    <span class="sync-stat">
                        <span class="sync-stat__num"><?= e(fa_digits((string) $log['received'])) ?></span>
                        <span class="sync-stat__label">دریافت‌شده</span>
                    </span>
                    <span class="sync-stat sync-stat--ok">
                        <span class="sync-stat__num"><?= e(fa_digits((string) $log['inserted'])) ?></span>
                        <span class="sync-stat__label">افزوده</span>
                    </span>
                    <span class="sync-stat sync-stat--brand">
                        <span class="sync-stat__num"><?= e(fa_digits((string) $log['updated'])) ?></span>
                        <span class="sync-stat__label">به‌روزرسانی</span>
                    </span>
                    <span class="sync-stat sync-stat--muted">
                        <span class="sync-stat__num"><?= e(fa_digits((string) $log['deactivated'])) ?></span>
                        <span class="sync-stat__label">غیرفعال‌شده</span>
                    </span>
                    <span class="sync-stat <?= $hasRejected ? 'sync-stat--danger' : 'sync-stat--muted' ?>">
                        <span class="sync-stat__num"><?= e(fa_digits((string) $log['rejected'])) ?></span>
                        <span class="sync-stat__label">ردشده</span>
                    </span>
                </div>

                <?php if ($rejected): ?>
                    <details class="sync-rejected">
                        <summary>مشاهده ردیف‌های ردشده (<?= e(fa_digits((string) count($rejected))) ?>)</summary>
                        <table class="sync-rejected__table">
                            <thead>
                                <tr><th>SKU</th><th>دلیل</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rejected as $r): ?>
                                    <tr>
                                        <td class="ltr"><?= e((string) ($r['sku'] ?? '—')) ?: '—' ?></td>
                                        <td class="ltr"><?= e((string) ($r['reason'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </details>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
