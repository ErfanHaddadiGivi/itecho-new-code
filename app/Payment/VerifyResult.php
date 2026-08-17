<?php

namespace App\Payment;

/**
 * نتیجه تایید پرداخت.
 */
class VerifyResult
{
    public function __construct(
        public readonly bool    $ok,
        /** کد رهگیری پرداخت که به مشتری نشان داده می‌شود */
        public readonly ?string $refId = null,
        /** شماره کارت ماسک‌شده */
        public readonly ?string $cardPan = null,
        /**
         * درگاه می‌گوید این تراکنش قبلاً تایید شده بود.
         * یعنی کاربر صفحه بازگشت را دوباره باز کرده — نباید دوباره
         * موجودی کسر یا ایمیل ارسال شود.
         */
        public readonly bool    $alreadyVerified = false,
        public readonly string  $message = '',
        public readonly string  $raw = ''
    ) {
    }

    public static function fail(string $message, string $raw = ''): self
    {
        return new self(false, null, null, false, $message, $raw);
    }
}
