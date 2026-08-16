<?php

namespace App\Core;

/**
 * مدیریت نشست (Session) با تنظیمات امنیتی.
 */
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => base_path_uri() . '/',
            'httponly' => true,   // جاوااسکریپت نتواند کوکی نشست را بخواند
            'samesite' => 'Lax',  // جلوی ارسال کوکی در درخواست‌های بین‌سایتی را می‌گیرد
            'secure'   => $https, // فقط روی HTTPS ارسال شود
        ]);

        // جلوگیری از پذیرفتن شناسه نشست ساختگی
        ini_set('session.use_strict_mode', '1');

        session_name('itecho_session');
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * ساخت شناسه نشست جدید — بعد از ورود موفق انجام می‌شود
     * تا حمله Session Fixation ممکن نباشد.
     */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'],
                      $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
