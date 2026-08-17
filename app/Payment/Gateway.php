<?php

namespace App\Payment;

/**
 * انتخاب درگاه فعال.
 *
 * الان فقط زرین‌پال داریم. اگر بعداً درگاه دیگری اضافه شد، کافی است
 * کلاس آن ساخته شود و یک شرط به همین متد اضافه شود — بقیه پروژه
 * فقط با قرارداد PaymentGateway کار می‌کند و تغییری لازم ندارد.
 */
class Gateway
{
    public static function active(): PaymentGateway
    {
        return new ZarinpalGateway();
    }
}
