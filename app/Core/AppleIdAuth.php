<?php

namespace App\Core;

/**
 * احراز هویتِ بخش اپل‌آیدی سایت (مجزا از حساب فروشگاه).
 * ورود با شمارهٔ موبایل + رمز؛ کاربران در دیتابیس مشترکِ ربات (web_users).
 * نشست با کلید جداگانه تا با حساب فروشگاه قاطی نشود.
 */
class AppleIdAuth
{
    private const KEY = 'appleid_uid';

    public static function check(): bool
    {
        return Session::get(self::KEY) !== null;
    }

    public static function id(): ?int
    {
        $v = Session::get(self::KEY);
        return $v !== null ? (int) $v : null;
    }

    public static function user(): ?array
    {
        $id = self::id();
        if ($id === null) {
            return null;
        }
        return AppleId::db()->fetch(
            'SELECT id, phone, name, created_at, last_login_at FROM web_users WHERE id = ? LIMIT 1',
            [$id]
        );
    }

    public static function login(int $userId): void
    {
        Session::set(self::KEY, $userId);
        AppleId::db()->update('web_users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $userId]);
    }

    public static function logout(): void
    {
        Session::forget(self::KEY);
    }

    public static function attempt(string $phone, string $password): ?array
    {
        $user = AppleId::db()->fetch('SELECT * FROM web_users WHERE phone = ? LIMIT 1', [$phone]);
        if ($user !== null && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return null;
    }

    public static function phoneExists(string $phone): bool
    {
        return (bool) AppleId::db()->fetchValue('SELECT id FROM web_users WHERE phone = ? LIMIT 1', [$phone]);
    }

    public static function register(string $phone, string $password, ?string $name): int
    {
        return AppleId::db()->insert('web_users', [
            'phone'         => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'name'          => $name,
        ]);
    }
}
