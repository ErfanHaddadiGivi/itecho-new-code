<?php

namespace App\Core;

/**
 * کلاس پایه کنترلرها.
 * همه کنترلرها از این کلاس ارث می‌برند تا به توابع مشترک دسترسی داشته باشند.
 */
abstract class Controller
{
    /**
     * نمایش یک صفحه
     */
    protected function view(string $template, array $data = [], string $layout = 'site'): void
    {
        View::render($template, $data, $layout);
    }

    /**
     * خروجی JSON — برای درخواست‌های AJAX مثل جستجوی زنده
     */
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * خواندن یک مقدار از فرم (POST یا GET) با حذف فاصله‌های اضافی
     */
    protected function input(string $key, mixed $default = ''): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    /**
     * خواندن یک عدد از فرم — ارقام فارسی هم پذیرفته می‌شود
     */
    protected function intInput(string $key, int $default = 0): int
    {
        $value = $this->input($key, (string) $default);
        return (int) en_digits((string) $value);
    }

    /**
     * خواندن یک تیک (checkbox)
     */
    protected function boolInput(string $key): int
    {
        return !empty($_POST[$key]) ? 1 : 0;
    }

    /**
     * همه مقادیر فرم — برای برگرداندن به فرم بعد از خطا
     */
    protected function allInput(): array
    {
        return $_POST;
    }

    /**
     * برگشت به صفحه قبل همراه با خطاها
     */
    protected function backWithErrors(array $errors, string $fallback = ''): never
    {
        Flash::withErrors($errors, $this->allInput());
        $back = $_SERVER['HTTP_REFERER'] ?? '';
        redirect($back !== '' ? $back : $fallback);
    }

    /**
     * محافظت از صفحه‌هایی که نیاز به حساب کاربری دارند.
     *
     * آدرس صفحه فعلی نگه داشته می‌شود تا کاربر بعد از ورود به همان‌جا برگردد.
     */
    protected function requireLogin(
        string $message = 'برای مشاهده این صفحه وارد حساب کاربری خود شوید.'
    ): void {
        if (Auth::check()) {
            return;
        }

        Session::set('intended_url', $_SERVER['REQUEST_URI'] ?? '');
        Flash::info($message);
        redirect('login');
    }

    /**
     * نمایش صفحه ۴۰۴ از داخل کنترلر
     */
    protected function notFound(string $message = 'صفحه مورد نظر پیدا نشد'): never
    {
        http_response_code(404);
        View::render('errors/404', ['title' => 'صفحه پیدا نشد', 'message' => $message], 'site');
        exit;
    }
}
