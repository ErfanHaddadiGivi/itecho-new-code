<?php
/**
 * پاک‌سازی دوره‌ای (با Cron Job هر چند ساعت یک‌بار اجرا شود):
 *   ۱) پاک‌کردن دادهٔ حساسِ سفارش‌های تمام‌شده/ردشده/لغوشده بعد از N روز
 *   ۲) تایم‌اوت سشن‌های نیمه‌کاره (قدیمی‌تر از session_ttl_hours)
 *   ۳) پاک‌کردن ردیف‌های قدیمی rate_limits
 *
 * اجرا در cPanel Cron:
 *   php /home/USER/appleid-bot/cron/purge_sensitive.php
 */

declare(strict_types=1);

$services = require __DIR__ . '/../bootstrap.php';

/** @var AppleBot\Db $db */
$db       = $services['db'];
/** @var AppleBot\Settings $settings */
$settings = $services['settings'];
/** @var AppleBot\RateLimiter $rateLimiter */
$rateLimiter = $services['rateLimiter'];

$retentionDays = max(1, $settings->getInt('sensitive_data_retention_days', 3));
$sessionTtlH   = max(1, $settings->getInt('session_ttl_hours', 24));

$now        = time();
$purgeCutoff = date('Y-m-d H:i:s', $now - ($retentionDays * 86400));
$sessCutoff  = date('Y-m-d H:i:s', $now - ($sessionTtlH * 3600));

// ۱) پاک‌سازی دادهٔ حساس
$purged = $db->run(
    "UPDATE orders
        SET first_name_enc = NULL, last_name_enc = NULL, phone_enc = NULL,
            email_enc = NULL, birthdate_enc = NULL,
            verification_code_enc = NULL, final_credentials_enc = NULL,
            purged_at = NOW()
      WHERE status IN ('completed','rejected','cancelled')
        AND purged_at IS NULL
        AND COALESCE(completed_at, updated_at) < ?",
    [$purgeCutoff]
)->rowCount();

// ۲) سشن‌های نیمه‌کاره: سفارش‌های پیش‌نویسِ رهاشده را لغو کن، سپس مکالمه‌ها را پاک کن
$db->run(
    "UPDATE orders o
        JOIN conversations c ON c.active_order_id = o.id
        SET o.status = 'cancelled'
      WHERE o.status IN ('draft','pending_payment')
        AND c.updated_at < ?",
    [$sessCutoff]
);

$expiredSessions = $db->run(
    "DELETE FROM conversations
      WHERE updated_at < ?
        AND state <> 'COMPLETED'",
    [$sessCutoff]
)->rowCount();

// ۳) پاک‌سازی rate_limits قدیمی
$rateCleared = $rateLimiter->purgeOld();

echo sprintf(
    "[%s] purged_orders=%d expired_sessions=%d rate_rows_cleared=%d\n",
    date('Y-m-d H:i:s'),
    $purged,
    $expiredSessions,
    $rateCleared
);
