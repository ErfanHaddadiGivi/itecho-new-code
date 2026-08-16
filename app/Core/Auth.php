<?php

namespace App\Core;

/**
 * ورود، خروج و تشخیص کاربر فعلی.
 *
 * طبق PRD فقط یک نقش مدیریتی داریم: مدیر کل (role = 'admin').
 */
class Auth
{
    private const USER_KEY = 'auth_user_id';

    private static ?array $cachedUser = null;

    /**
     * تلاش برای ورود. در صورت موفقیت true برمی‌گرداند.
     */
    public static function attempt(string $email, string $password): bool
    {
        $user = Database::fetch(
            'SELECT * FROM users WHERE email = ? LIMIT 1',
            [mb_strtolower(trim($email))]
        );

        if ($user === null || $user['status'] !== 'active') {
            // برای جلوگیری از حدس زدن ایمیل‌های موجود، پیام خطا همیشه یکسان است
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // اگر الگوریتم هش قدیمی شده بود، هش را به‌روز کن
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            Database::update(
                'users',
                ['password_hash' => password_hash($password, PASSWORD_DEFAULT)],
                'id = ?',
                [$user['id']]
            );
        }

        self::login((int) $user['id']);
        return true;
    }

    public static function login(int $userId): void
    {
        // شناسه نشست را عوض می‌کنیم تا حمله Session Fixation ممکن نباشد
        Session::regenerate();
        Session::set(self::USER_KEY, $userId);
        self::$cachedUser = null;

        Database::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = ?', [$userId]);
    }

    public static function logout(): void
    {
        self::$cachedUser = null;
        Session::destroy();
    }

    public static function check(): bool
    {
        return Session::has(self::USER_KEY);
    }

    /**
     * اطلاعات کاربر واردشده (یا null)
     */
    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $userId = Session::get(self::USER_KEY);
        if ($userId === null) {
            return null;
        }

        $user = Database::fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [$userId]);

        // اگر کاربر حذف یا مسدود شده باشد، نشست را باطل کن
        if ($user === null || $user['status'] !== 'active') {
            self::logout();
            return null;
        }

        return self::$cachedUser = $user;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user !== null ? (int) $user['id'] : null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user !== null && $user['role'] === 'admin';
    }

    /**
     * محافظت از صفحات پنل مدیریت.
     * اگر کاربر مدیر نباشد به صفحه ورود منتقل می‌شود.
     */
    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {
            Session::set('intended_url', $_SERVER['REQUEST_URI'] ?? '');
            Flash::error('برای دسترسی به این بخش باید وارد شوید.');
            redirect('admin/login');
        }
    }
}
