<?php

namespace App\Payment;

/**
 * نتیجه درخواست شروع پرداخت.
 */
class RequestResult
{
    public function __construct(
        public readonly bool    $ok,
        /** کد یکتای تراکنش که درگاه می‌دهد */
        public readonly ?string $authority = null,
        /** آدرسی که کاربر باید به آن فرستاده شود */
        public readonly ?string $redirectUrl = null,
        /** پیام فارسی برای نمایش به کاربر در صورت خطا */
        public readonly string  $message = '',
        /** پاسخ خام درگاه برای ثبت در دیتابیس و عیب‌یابی */
        public readonly string  $raw = ''
    ) {
    }

    public static function fail(string $message, string $raw = ''): self
    {
        return new self(false, null, null, $message, $raw);
    }
}
