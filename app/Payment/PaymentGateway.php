<?php

namespace App\Payment;

/**
 * قرارداد درگاه پرداخت.
 *
 * هدف: اگر روزی درگاه دیگری (مثلاً به‌پرداخت یا آیدی‌پی) اضافه شود،
 * فقط یک کلاس جدید که همین قرارداد را پیاده کند کافی است و هیچ جای
 * دیگری از پروژه لازم نیست تغییر کند.
 *
 * همه مبالغ در این قرارداد به **تومان** هستند. تبدیل به ریال (اگر درگاه
 * ریال بخواهد) وظیفه خود کلاس درگاه است.
 */
interface PaymentGateway
{
    /**
     * نام درگاه برای ذخیره در ستون payments.gateway
     */
    public function name(): string;

    /**
     * آیا درگاه در حالت تست است؟
     */
    public function isSandbox(): bool;

    /**
     * درخواست شروع پرداخت.
     *
     * @param int    $amountToman مبلغ به تومان
     * @param string $callbackUrl آدرسی که درگاه بعد از پرداخت کاربر را به آن برمی‌گرداند
     */
    public function request(int $amountToman, string $callbackUrl, string $description): RequestResult;

    /**
     * تایید پرداخت پس از بازگشت کاربر از درگاه.
     *
     * ⚠️ تا وقتی این متد تایید نکرده، هیچ سفارشی نباید «پرداخت‌شده» شود.
     * پارامترهای بازگشتی درگاه به‌تنهایی قابل اعتماد نیستند.
     */
    public function verify(string $authority, int $amountToman): VerifyResult;
}
