<?php

namespace AppleBot;

/**
 * محدودسازی نرخ درخواست هر کاربر (ضد اسپم) با پنجرهٔ زمانی ثابت.
 * تعداد و طول پنجره از settings خوانده می‌شود.
 */
class RateLimiter
{
    private Db $db;
    private int $max;
    private int $windowSeconds;

    public function __construct(Db $db, Settings $settings)
    {
        $this->db            = $db;
        $this->max           = max(1, $settings->getInt('rate_limit_max_requests', 30));
        $this->windowSeconds = max(1, $settings->getInt('rate_limit_window_seconds', 60));
    }

    /**
     * ثبت یک درخواست و برگرداندن true اگر کاربر مجاز است، false اگر از حد گذشته.
     */
    public function allow(int $telegramUserId): bool
    {
        // شروع پنجرهٔ فعلی (گِرد شده به طول پنجره)
        $windowStart = date('Y-m-d H:i:s', (int) (floor(time() / $this->windowSeconds) * $this->windowSeconds));

        $this->db->run(
            'INSERT INTO rate_limits (telegram_user_id, window_start, request_count)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE request_count = request_count + 1',
            [$telegramUserId, $windowStart]
        );

        $count = (int) $this->db->fetchValue(
            'SELECT request_count FROM rate_limits WHERE telegram_user_id = ? AND window_start = ?',
            [$telegramUserId, $windowStart]
        );

        return $count <= $this->max;
    }

    /** پاک‌سازی پنجره‌های قدیمی (توسط cron صدا زده می‌شود) */
    public function purgeOld(): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - ($this->windowSeconds * 10));
        return $this->db->run('DELETE FROM rate_limits WHERE window_start < ?', [$cutoff])->rowCount();
    }
}
