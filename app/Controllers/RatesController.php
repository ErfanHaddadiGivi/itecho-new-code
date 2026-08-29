<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Rates;

/**
 * نقطهٔ پایانی JSON برای باکس نرخ لحظه‌ای ارز (دلار/درهم به تومان).
 * جاوااسکریپت سمت کاربر هر چند دقیقه این آدرس را صدا می‌زند.
 */
class RatesController extends Controller
{
    public function index(): void
    {
        // اجازهٔ کش کوتاه در مرورگر/پراکسی تا درخواست‌ها کم شود
        header('Cache-Control: public, max-age=120');
        $this->json(Rates::get(true));
    }
}
