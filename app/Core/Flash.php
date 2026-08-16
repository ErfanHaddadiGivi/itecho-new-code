<?php

namespace App\Core;

/**
 * پیام‌های یک‌بارمصرف بین دو صفحه.
 *
 * مثال: بعد از ذخیره یک دسته‌بندی، کاربر به لیست منتقل می‌شود
 * و بالای لیست پیام «با موفقیت ذخیره شد» را می‌بیند.
 * این پیام فقط یک بار نمایش داده می‌شود و بعد پاک می‌شود.
 */
class Flash
{
    private const MESSAGES = '_flash_messages';
    private const ERRORS   = '_flash_errors';
    private const OLD      = '_flash_old';

    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    public static function error(string $message): void
    {
        self::add('error', $message);
    }

    public static function info(string $message): void
    {
        self::add('info', $message);
    }

    private static function add(string $type, string $message): void
    {
        $messages   = Session::get(self::MESSAGES, []);
        $messages[] = ['type' => $type, 'text' => $message];
        Session::set(self::MESSAGES, $messages);
    }

    /**
     * خواندن و پاک کردن پیام‌ها
     */
    public static function pull(): array
    {
        $messages = Session::get(self::MESSAGES, []);
        Session::forget(self::MESSAGES);
        return is_array($messages) ? $messages : [];
    }

    // --- خطاهای اعتبارسنجی فرم ---

    /**
     * ذخیره خطاهای فرم به همراه مقادیر واردشده،
     * تا بعد از بازگشت به فرم، کاربر مجبور نباشد همه‌چیز را دوباره پر کند.
     */
    public static function withErrors(array $errors, array $input = []): void
    {
        Session::set(self::ERRORS, $errors);

        // رمزها هرگز به فرم برنمی‌گردند
        unset($input['password'], $input['password_confirm'], $input['_csrf_token']);
        Session::set(self::OLD, $input);
    }

    public static function errors(): array
    {
        $errors = Session::get(self::ERRORS, []);
        Session::forget(self::ERRORS);
        return is_array($errors) ? $errors : [];
    }

    public static function oldInput(string $field): ?string
    {
        $old = Session::get(self::OLD, []);
        if (!is_array($old) || !array_key_exists($field, $old)) {
            return null;
        }
        return is_scalar($old[$field]) ? (string) $old[$field] : null;
    }

    /**
     * پاک کردن مقادیر قبلی فرم — در انتهای نمایش هر صفحه صدا زده می‌شود
     */
    public static function clearOld(): void
    {
        Session::forget(self::OLD);
    }
}
