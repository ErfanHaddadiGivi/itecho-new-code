<?php
/**
 * گزارش همگام‌سازی محصولات + آپلود دستی CSV.
 *
 * @var array $logs  فهرست اجراهای اخیر همگام‌سازی
 * @var bool  $ready آیا جدول sync_logs ساخته شده است
 */

use App\Core\Csrf;

$ready = $ready ?? true;

// آدرس کامل نقطه پایانی که در اسکریپت گوگل باید تنظیم شود
$endpoint = rtrim(url('api/sheet-sync.php'), '/');
?>

<?php if (!$ready): ?>
    <div class="panel panel--todo" style="border-inline-start-color: var(--danger);">
        <h2 class="panel__title">یک قدم راه‌اندازی مانده</h2>
        <p class="page-hint">
            جدول <code class="ltr">sync_logs</code> هنوز در دیتابیس ساخته نشده است، برای همین
            این صفحه کار نمی‌کرد. برای فعال شدن همگام‌سازی، یک‌بار فایل زیر را در
            <b>phpMyAdmin → Import</b> اجرا کنید (کاملاً امن است و هیچ داده‌ای را پاک نمی‌کند):
        </p>
        <p><code class="ltr">database/sync-logs.sql</code></p>
        <p class="page-hint">
            پس از ایمپورت، همین صفحه را دوباره باز کنید. توکن مخفی را هم در
            <code class="ltr">config/config.local.php</code> تنظیم کنید (راهنما:
            <code class="ltr">docs/SHEET-SYNC.md</code>).
        </p>
    </div>
<?php else: ?>

<!-- آپلود دستی فایل CSV -->
<div class="panel">
    <h2 class="panel__title">آپلود دستی فایل CSV</h2>
    <p class="page-hint">
        یک فایل CSV با همین ستون‌ها آپلود کنید تا قیمت/موجودی به‌روزرسانی و محصول‌های
        ساده‌ی جدید ساخته شوند. ستون‌ها باید در ردیف اول باشند:
    </p>
    <p><code class="ltr" style="display:block;word-break:break-all;">name, slug, category_id, brand_id, price, stock, sku, condition_type, compare_at_price</code></p>

    <form action="<?= e(url('admin/sync-logs/upload')) ?>" method="post" enctype="multipart/form-data" class="csv-upload">
        <?= Csrf::field() ?>
        <div class="field">
            <label for="csv">فایل CSV</label>
            <input type="file" id="csv" name="csv" accept=".csv,text/csv" required>
        </div>

        <div class="field field--check">
            <label>
                <input type="checkbox" name="deactivate_missing" value="1">
                محصولاتی که در این فایل نیستند غیرفعال شوند
            </label>
            <span class="field__hint">
                ⚠️ فقط وقتی تیک بزنید که این فایل <b>همه‌ی</b> محصولات است. اگر فایل فقط
                بخشی از محصولات را دارد، این گزینه بقیه‌ی محصولات را از سایت پنهان می‌کند.
            </span>
        </div>

        <div class="form-actions">
            <button class="btn btn--primary" type="submit">آپلود و همگام‌سازی</button>
        </div>
    </form>
</div>

<div class="panel panel--todo">
    <h2 class="panel__title">همگام‌سازی خودکار از گوگل‌شیت</h2>
    <p class="page-hint">
        قیمت و موجودی محصولات (و ساخت محصول ساده‌ی جدید) از یک گوگل‌شیت خصوصی و
        به‌صورت خودکار دو بار در روز (حدود ۷ صبح و ۲ بعدازظهر) به‌روزرسانی می‌شوند.
        آدرس نقطه‌ی دریافت که باید در اسکریپت گوگل ثبت شود:
    </p>
    <p><code class="ltr" style="display:inline-block;word-break:break-all;"><?= e($endpoint) ?></code></p>
    <p class="page-hint">
        هر بار که همگام‌سازی اجرا شود (چه از گوگل‌شیت، چه با آپلود CSV)، نتیجه‌اش
        پایین همین صفحه ثبت می‌شود. جزئیات راه‌اندازی در فایل
        <code class="ltr">docs/SHEET-SYNC.md</code> نوشته شده است.
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
                <?php
                    $sourceLabel = match ($log['source']) {
                        'google_sheet' => 'گوگل‌شیت (خودکار)',
                        'csv_upload'   => 'آپلود CSV',
                        default        => $log['source'],
                    };
                ?>
                <div class="sync-card__head">
                    <span class="sync-card__time"><?= e(jdate($log['created_at'], 'datetime')) ?></span>
                    <span class="sync-card__source">منبع: <?= e($sourceLabel) ?></span>
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

<?php endif; /* $ready */ ?>
