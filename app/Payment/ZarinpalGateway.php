<?php

namespace App\Payment;

use App\Models\Setting;

/**
 * درگاه پرداخت زرین‌پال (نسخه API 4).
 *
 * برای تبدیل حالت تست به حالت واقعی، فقط کافی است در پنل مدیریت:
 *   ۱. «کد مرچنت زرین‌پال» را وارد کنید
 *   ۲. تیک «حالت تست زرین‌پال» را بردارید
 * هیچ تغییری در کد لازم نیست.
 *
 * نکته درباره واحد پول: در این پروژه همه مبالغ به تومان ذخیره می‌شوند،
 * ولی زرین‌پال مبلغ را به ریال می‌گیرد. تبدیل (ضرب در ۱۰) همین‌جا انجام
 * می‌شود تا بقیه پروژه درگیر آن نشود.
 */
class ZarinpalGateway implements PaymentGateway
{
    private const SANDBOX_BASE = 'https://sandbox.zarinpal.com';
    private const LIVE_BASE    = 'https://payment.zarinpal.com';

    private string $merchantId;
    private bool   $sandbox;
    private string $baseUrl;

    public function __construct()
    {
        $this->merchantId = (string) Setting::get('zarinpal_merchant_id', '');
        $this->sandbox    = Setting::getBool('zarinpal_sandbox', true);

        // امکان تغییر آدرس فقط برای توسعه محلی (در config.local.php).
        // در حالت عادی خالی است و آدرس رسمی زرین‌پال استفاده می‌شود.
        $override = (string) config('zarinpal_base_url', '');

        $this->baseUrl = $override !== ''
            ? rtrim($override, '/')
            : ($this->sandbox ? self::SANDBOX_BASE : self::LIVE_BASE);
    }

    public function name(): string
    {
        return 'zarinpal';
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    public function request(int $amountToman, string $callbackUrl, string $description): RequestResult
    {
        if ($this->merchantId === '') {
            return RequestResult::fail('کد مرچنت زرین‌پال در تنظیمات وارد نشده است.');
        }

        if ($amountToman <= 0) {
            return RequestResult::fail('مبلغ پرداخت نامعتبر است.');
        }

        $response = $this->post('/pg/v4/payment/request.json', [
            'merchant_id'  => $this->merchantId,
            'amount'       => $amountToman * 10,   // تومان → ریال
            'currency'     => 'IRR',
            'callback_url' => $callbackUrl,
            'description'  => mb_substr($description, 0, 250),
        ]);

        if ($response === null) {
            return RequestResult::fail('ارتباط با درگاه پرداخت برقرار نشد. لطفاً دوباره تلاش کنید.');
        }

        [$body, $raw] = $response;

        $code      = (int) ($body['data']['code'] ?? 0);
        $authority = (string) ($body['data']['authority'] ?? '');

        if ($code === 100 && $authority !== '') {
            return new RequestResult(
                ok:          true,
                authority:   $authority,
                redirectUrl: $this->baseUrl . '/pg/StartPay/' . $authority,
                raw:         $raw
            );
        }

        return RequestResult::fail($this->errorMessage($body), $raw);
    }

    public function verify(string $authority, int $amountToman): VerifyResult
    {
        if ($authority === '') {
            return VerifyResult::fail('کد تراکنش نامعتبر است.');
        }

        $response = $this->post('/pg/v4/payment/verify.json', [
            'merchant_id' => $this->merchantId,
            'amount'      => $amountToman * 10,   // باید دقیقاً برابر مبلغ درخواست باشد
            'authority'   => $authority,
        ]);

        if ($response === null) {
            return VerifyResult::fail('ارتباط با درگاه پرداخت برقرار نشد.');
        }

        [$body, $raw] = $response;

        $code = (int) ($body['data']['code'] ?? 0);

        // ۱۰۰ = تایید موفق  |  ۱۰۱ = قبلاً تایید شده بود
        if ($code === 100 || $code === 101) {
            return new VerifyResult(
                ok:              true,
                refId:           (string) ($body['data']['ref_id'] ?? ''),
                cardPan:         (string) ($body['data']['card_pan'] ?? ''),
                alreadyVerified: $code === 101,
                message:         $code === 101 ? 'این پرداخت قبلاً تایید شده بود.' : 'پرداخت با موفقیت تایید شد.',
                raw:             $raw
            );
        }

        return VerifyResult::fail($this->errorMessage($body), $raw);
    }

    // ------------------------------------------------------------------

    /**
     * ارسال درخواست JSON به زرین‌پال.
     *
     * @return array{0: array, 1: string}|null  [بدنه پاسخ, پاسخ خام] یا null در صورت خطای ارتباط
     */
    private function post(string $path, array $payload): ?array
    {
        $url  = $this->baseUrl . $path;
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

        $raw = function_exists('curl_init')
            ? $this->postWithCurl($url, $json)
            : $this->postWithStream($url, $json);

        if ($raw === null) {
            return null;
        }

        $body = json_decode($raw, true);

        if (!is_array($body)) {
            error_log('Zarinpal: پاسخ غیرقابل خواندن — ' . mb_substr($raw, 0, 500));
            return null;
        }

        return [$body, mb_substr($raw, 0, 2000)];
    }

    private function postWithCurl(string $url, string $json): ?string
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($json),
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $result = curl_exec($ch);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            error_log('Zarinpal: خطای cURL — ' . $error);
            return null;
        }

        return (string) $result;
    }

    /**
     * روش جایگزین برای هاست‌هایی که cURL ندارند
     */
    private function postWithStream(string $url, string $json): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content'       => $json,
                'timeout'       => 20,
                'ignore_errors' => true,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            error_log('Zarinpal: ارسال درخواست با file_get_contents ناموفق بود');
            return null;
        }

        return $result;
    }

    /**
     * تبدیل کد خطای زرین‌پال به پیام فارسی قابل فهم برای مشتری
     */
    private function errorMessage(array $body): string
    {
        $code    = $body['errors']['code'] ?? ($body['data']['code'] ?? null);
        $message = $body['errors']['message'] ?? '';

        $known = [
            -9   => 'اطلاعات ارسالی به درگاه ناقص است.',
            -10  => 'کد مرچنت یا آدرس سایت با تنظیمات زرین‌پال هم‌خوانی ندارد.',
            -11  => 'درخواست مورد نظر یافت نشد.',
            -12  => 'تعداد تلاش‌ها بیش از حد مجاز بود. کمی بعد دوباره تلاش کنید.',
            -15  => 'درگاه پرداخت غیرفعال است. با پشتیبانی تماس بگیرید.',
            -16  => 'سطح تایید حساب پذیرنده پایین‌تر از حد مجاز است.',
            -30  => 'امکان پرداخت با این مبلغ وجود ندارد.',
            -33  => 'مبلغ پرداخت با مبلغ سفارش یکسان نیست.',
            -34  => 'مبلغ تراکنش از سقف مجاز بیشتر است.',
            -50  => 'مبلغ پرداخت‌شده با مبلغ سفارش برابر نیست.',
            -51  => 'پرداخت ناموفق بود.',
            -53  => 'این پرداخت متعلق به این فروشگاه نیست.',
            -54  => 'کد تراکنش نامعتبر است.',
            101  => 'این پرداخت قبلاً تایید شده است.',
        ];

        if ($code !== null && isset($known[(int) $code])) {
            return $known[(int) $code];
        }

        error_log('Zarinpal: خطای ناشناخته — کد ' . var_export($code, true) . ' | ' . $message);

        return 'پرداخت انجام نشد. اگر مبلغی از حساب شما کم شده، طی ۷۲ ساعت برمی‌گردد.';
    }
}
