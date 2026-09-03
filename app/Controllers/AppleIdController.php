<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Setting;

/**
 * صفحهٔ فروش اپل‌آیدی آمریکا (باکس/لندینگ) با دکمهٔ شروع سفارش در تلگرام.
 * سفارش‌گیری کامل داخل ربات تلگرام انجام می‌شود؛ این صفحه فقط معرفی + CTA است.
 */
class AppleIdController extends Controller
{
    public function index(): void
    {
        if (!Setting::getBool('appleid_enabled', false)) {
            $this->notFound('این صفحه فعال نیست');
        }

        $botUsername = trim((string) Setting::get('appleid_bot_username', ''));
        $startPrice  = (int) en_digits((string) Setting::get('appleid_start_price', ''));

        // لینک شروع سفارش با deep-link؛ اگر یوزرنیم نبود، دکمه غیرفعال می‌شود
        $telegramLink = $botUsername !== ''
            ? 'https://t.me/' . rawurlencode($botUsername) . '?start=appleid'
            : '';

        $this->view('site/appleid', [
            'title'           => 'خرید اپل‌آیدی آمریکا | ' . Setting::get('site_name', 'ایتکو'),
            'metaDescription' => 'اپل‌آیدی معتبر ریجن آمریکا روی ایمیل خودت؛ از انتخاب پلن تا تحویل، همه‌چیز داخل تلگرام و با پشتیبانی.',
            'telegramLink'    => $telegramLink,
            'startPrice'      => $startPrice,
        ], 'site');
    }
}
