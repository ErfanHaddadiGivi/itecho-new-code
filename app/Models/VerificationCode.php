<?php

namespace App\Models;

use App\Core\Database;

/**
 * کدهای تایید ۶ رقمی ایمیل.
 *
 * نکات امنیتی:
 *  • کد به‌صورت هش ذخیره می‌شود، پس حتی با دسترسی به دیتابیس قابل استفاده نیست.
 *  • هر کد زمان انقضا دارد.
 *  • شمارنده attempts جلوی حدس زدن کد را می‌گیرد.
 *  • فاصله زمانی بین دو درخواست ارسال کد رعایت می‌شود.
 */
class VerificationCode
{
    /** حداکثر تلاش ناموفق برای یک کد */
    private const MAX_ATTEMPTS = 5;

    /** کمترین فاصله بین دو درخواست ارسال کد (ثانیه) */
    private const RESEND_SECONDS = 90;

    /**
     * ساخت کد جدید و برگرداندن آن به‌صورت خام (فقط برای ارسال در ایمیل).
     *
     * @throws \RuntimeException اگر خیلی زود درخواست دوباره داده شود
     */
    public static function issue(string $email, string $purpose, ?int $userId = null): string
    {
        $email = mb_strtolower(trim($email));

        // جلوگیری از ارسال پشت‌سرهم
        $last = Database::fetch(
            'SELECT created_at FROM verification_codes
              WHERE email = ? AND purpose = ?
              ORDER BY id DESC LIMIT 1',
            [$email, $purpose]
        );

        if ($last !== null) {
            $elapsed = time() - strtotime($last['created_at']);

            if ($elapsed < self::RESEND_SECONDS) {
                throw new \RuntimeException(
                    'برای درخواست کد جدید ' . fa_digits((string) (self::RESEND_SECONDS - $elapsed))
                    . ' ثانیه صبر کنید.'
                );
            }
        }

        // کدهای قبلی همین ایمیل باطل می‌شوند تا فقط آخرین کد معتبر باشد
        Database::delete('verification_codes', 'email = ? AND purpose = ?', [$email, $purpose]);

        $code    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $minutes = Setting::getInt('otp_expire_minutes', 10);

        Database::insert('verification_codes', [
            'user_id'    => $userId,
            'email'      => $email,
            'code_hash'  => password_hash($code, PASSWORD_DEFAULT),
            'purpose'    => $purpose,
            'expires_at' => date('Y-m-d H:i:s', time() + ($minutes * 60)),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        return $code;
    }

    /**
     * بررسی کد واردشده.
     *
     * @throws \RuntimeException با پیام فارسی قابل نمایش
     */
    public static function check(string $email, string $purpose, string $code): void
    {
        $email = mb_strtolower(trim($email));
        $code  = en_digits(trim($code));

        $row = Database::fetch(
            'SELECT * FROM verification_codes
              WHERE email = ? AND purpose = ? AND used_at IS NULL
              ORDER BY id DESC LIMIT 1',
            [$email, $purpose]
        );

        if ($row === null) {
            throw new \RuntimeException('کدی برای این ایمیل ثبت نشده است. لطفاً کد جدید درخواست کنید.');
        }

        if (strtotime($row['expires_at']) < time()) {
            throw new \RuntimeException('این کد منقضی شده است. لطفاً کد جدید درخواست کنید.');
        }

        if ((int) $row['attempts'] >= self::MAX_ATTEMPTS) {
            throw new \RuntimeException('تعداد تلاش‌های ناموفق زیاد بود. لطفاً کد جدید درخواست کنید.');
        }

        if (!password_verify($code, $row['code_hash'])) {
            Database::run(
                'UPDATE verification_codes SET attempts = attempts + 1 WHERE id = ?',
                [$row['id']]
            );

            throw new \RuntimeException('کد واردشده درست نیست.');
        }

        // کد مصرف شد و دیگر قابل استفاده نیست
        Database::update('verification_codes', ['used_at' => date('Y-m-d H:i:s')], 'id = ?', [$row['id']]);
    }
}
