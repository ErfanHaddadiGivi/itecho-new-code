<?php

namespace AppleBot;

/**
 * رمزنگاری/بازگشایی دادهٔ حساس با AES-256-GCM.
 *
 *  - کلید ۳۲بایتی از config می‌آید (base64) و هرگز داخل دیتابیس نیست.
 *  - GCM هم محرمانگی و هم اصالت (تشخیص دستکاری) را تضمین می‌کند.
 *  - خروجی: base64( iv(12) . tag(16) . ciphertext )
 */
class Crypto
{
    private const CIPHER = 'aes-256-gcm';

    private string $key;

    public function __construct(string $base64Key)
    {
        $key = base64_decode($base64Key, true);
        if ($key === false || strlen($key) !== 32) {
            throw new \RuntimeException('کلید رمزنگاری نامعتبر است؛ باید base64 از ۳۲ بایت باشد.');
        }
        $this->key = $key;
    }

    /**
     * رمزنگاری یک رشته. برای مقدار خالی/نال، نال برمی‌گرداند.
     */
    public function encrypt(?string $plain): ?string
    {
        if ($plain === null || $plain === '') {
            return null;
        }

        $iv  = random_bytes(12);
        $tag = '';
        $ct  = openssl_encrypt($plain, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ct === false) {
            throw new \RuntimeException('رمزنگاری ناموفق بود.');
        }

        return base64_encode($iv . $tag . $ct);
    }

    /**
     * بازگشایی. اگر مقدار نال/خراب باشد، نال برمی‌گرداند (بدون پرتاب خطا).
     */
    public function decrypt(?string $encoded): ?string
    {
        if ($encoded === null || $encoded === '') {
            return null;
        }

        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 29) {
            return null;
        }

        $iv  = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ct  = substr($raw, 28);

        $plain = openssl_decrypt($ct, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        return $plain === false ? null : $plain;
    }
}
