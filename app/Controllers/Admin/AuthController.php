<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Session;

/**
 * ورود و خروج مدیر کل.
 */
class AuthController extends Controller
{
    /** حداکثر تلاش ناموفق قبل از قفل موقت */
    private const MAX_ATTEMPTS = 5;

    /** مدت قفل به ثانیه */
    private const LOCK_SECONDS = 120;

    public function showLogin(): void
    {
        if (Auth::isAdmin()) {
            redirect('admin');
        }

        $this->view('admin/login', [
            'title'  => 'ورود به پنل مدیریت',
            'errors' => Flash::errors(),
        ], 'blank');
    }

    public function login(): void
    {
        Csrf::check();

        // --- جلوگیری از حدس زدن رمز با تلاش پشت‌سرهم ---
        if ($this->isLocked()) {
            $remaining = $this->lockRemaining();
            Flash::error('تعداد تلاش‌های ناموفق زیاد بود. لطفاً ' . fa_digits((string) $remaining) . ' ثانیه دیگر تلاش کنید.');
            redirect('admin/login');
        }

        $email    = (string) $this->input('email');
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            Flash::error('ایمیل و رمز عبور را وارد کنید.');
            redirect('admin/login');
        }

        if (!Auth::attempt($email, $password)) {
            $this->recordFailure();
            // پیام عمداً کلی است تا مشخص نشود کدام‌یک اشتباه بوده
            Flash::error('ایمیل یا رمز عبور نادرست است.');
            redirect('admin/login');
        }

        // فقط مدیر کل اجازه ورود به پنل دارد
        if (!Auth::isAdmin()) {
            Auth::logout();
            $this->recordFailure();
            Flash::error('این حساب اجازه دسترسی به پنل مدیریت را ندارد.');
            redirect('admin/login');
        }

        $this->clearFailures();
        Flash::success('خوش آمدید.');

        // اگر کاربر قبلاً قصد رفتن به صفحه‌ای را داشت، به همان‌جا برگردد
        $intended = Session::get('intended_url');
        Session::forget('intended_url');

        redirect(is_string($intended) && $intended !== '' ? $intended : 'admin');
    }

    public function logout(): void
    {
        Csrf::check();
        Auth::logout();
        redirect('admin/login');
    }

    // ------------------------------------------------------------------
    //  شمارش تلاش‌های ناموفق
    // ------------------------------------------------------------------

    private function isLocked(): bool
    {
        return $this->lockRemaining() > 0;
    }

    private function lockRemaining(): int
    {
        $attempts = (int) Session::get('login_attempts', 0);
        $lastTry  = (int) Session::get('login_last_attempt', 0);

        if ($attempts < self::MAX_ATTEMPTS) {
            return 0;
        }

        $remaining = self::LOCK_SECONDS - (time() - $lastTry);

        if ($remaining <= 0) {
            $this->clearFailures();
            return 0;
        }

        return $remaining;
    }

    private function recordFailure(): void
    {
        Session::set('login_attempts', (int) Session::get('login_attempts', 0) + 1);
        Session::set('login_last_attempt', time());
    }

    private function clearFailures(): void
    {
        Session::forget('login_attempts');
        Session::forget('login_last_attempt');
    }
}
