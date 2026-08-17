<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Session;
use App\Models\Setting;
use App\Models\VerificationCode;

/**
 * ثبت‌نام، ورود و تایید ایمیل مشتریان.
 *
 * طبق تصمیم تاییدشده: خرید بدون ثبت‌نام تا مرحله سبد آزاد است و
 * فقط هنگام تسویه‌حساب ورود اجباری می‌شود.
 */
class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 120;

    // ==================================================================
    //  ورود
    // ==================================================================

    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('account');
        }

        $this->view('site/auth/login', [
            'title'  => 'ورود به حساب کاربری',
            'errors' => Flash::errors(),
        ], 'site');
    }

    public function login(): void
    {
        Csrf::check();

        if ($this->isLocked()) {
            Flash::error('تعداد تلاش‌های ناموفق زیاد بود. لطفاً '
                . fa_digits((string) $this->lockRemaining()) . ' ثانیه دیگر تلاش کنید.');
            redirect('login');
        }

        $email    = mb_strtolower((string) $this->input('email'));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            Flash::error('ایمیل و رمز عبور را وارد کنید.');
            redirect('login');
        }

        $user = Database::fetch('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);

        // اگر ایمیل هنوز تایید نشده، به‌جای ورود، مرحله تایید نشان داده می‌شود
        if ($user !== null
            && $user['email_verified_at'] === null
            && password_verify($password, $user['password_hash'])) {

            $this->startVerification($email, (int) $user['id'], $user['first_name']);
            return;
        }

        if (!Auth::attempt($email, $password)) {
            $this->recordFailure();
            Flash::error('ایمیل یا رمز عبور نادرست است.');
            redirect('login');
        }

        $this->clearFailures();
        Flash::success('خوش آمدید.');

        $intended = Session::get('intended_url');
        Session::forget('intended_url');

        redirect(is_string($intended) && $intended !== '' ? $intended : 'account');
    }

    public function logout(): void
    {
        Csrf::check();
        Auth::logout();
        redirect('');
    }

    // ==================================================================
    //  ثبت‌نام
    // ==================================================================

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect('account');
        }

        $this->view('site/auth/register', [
            'title'  => 'ساخت حساب کاربری',
            'errors' => Flash::errors(),
        ], 'site');
    }

    public function register(): void
    {
        Csrf::check();

        $data = [
            'first_name' => (string) $this->input('first_name'),
            'last_name'  => (string) $this->input('last_name'),
            'email'      => mb_strtolower((string) $this->input('email')),
            'phone'      => en_digits((string) $this->input('phone')),
        ];

        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirm'] ?? '');

        $errors = $this->validateRegistration($data, $password, $confirm);

        if ($errors !== []) {
            $this->backWithErrors($errors, 'register');
        }

        $userId = Database::insert('users', [
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => $data['email'],
            'phone'         => $data['phone'] !== '' ? $data['phone'] : null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => 'customer',
            'status'        => 'active',
        ]);

        $this->startVerification($data['email'], $userId, $data['first_name']);
    }

    private function validateRegistration(array $data, string $password, string $confirm): array
    {
        $errors = [];

        if ($data['first_name'] === '') {
            $errors['first_name'] = 'نام را وارد کنید.';
        }

        if ($data['last_name'] === '') {
            $errors['last_name'] = 'نام خانوادگی را وارد کنید.';
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'ایمیل معتبر وارد کنید.';
        } else {
            $exists = Database::fetchValue('SELECT COUNT(*) FROM users WHERE email = ?', [$data['email']]);

            if ((int) $exists > 0) {
                $errors['email'] = 'این ایمیل قبلاً ثبت شده است. وارد شوید یا رمز عبور را بازیابی کنید.';
            }
        }

        if ($data['phone'] !== '' && !preg_match('/^09\d{9}$/', $data['phone'])) {
            $errors['phone'] = 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.';
        }

        if (mb_strlen($password) < 8) {
            $errors['password'] = 'رمز عبور باید حداقل ۸ کاراکتر باشد.';
        } elseif ($password !== $confirm) {
            $errors['password_confirm'] = 'تکرار رمز عبور با رمز عبور یکسان نیست.';
        }

        return $errors;
    }

    // ==================================================================
    //  تایید ایمیل با کد ۶ رقمی
    // ==================================================================

    /**
     * ساخت کد، ارسال ایمیل و بردن کاربر به صفحه تایید
     */
    private function startVerification(string $email, int $userId, string $firstName): never
    {
        Session::set('pending_email', $email);

        try {
            $code = VerificationCode::issue($email, 'verify_email', $userId);

            $sent = Mailer::sendTemplate(
                $email,
                'کد تایید حساب کاربری ایتکو',
                'verify-code',
                ['code' => $code, 'name' => $firstName],
                $firstName
            );

            if ($sent) {
                Flash::success('کد تایید به ایمیل شما ارسال شد.');
            } else {
                // ایمیل ارسال نشد؛ حساب ساخته شده ولی کاربر باید بداند چه کند
                Flash::error('ارسال ایمیل ناموفق بود. لطفاً دوباره درخواست کد بدهید '
                           . 'یا با پشتیبانی تماس بگیرید.');
            }
        } catch (\RuntimeException $e) {
            Flash::info($e->getMessage());
        }

        redirect('verify');
    }

    public function showVerify(): void
    {
        $email = Session::get('pending_email');

        if (!is_string($email) || $email === '') {
            redirect('login');
        }

        $this->view('site/auth/verify', [
            'title'  => 'تایید ایمیل',
            'email'  => $email,
            'errors' => Flash::errors(),
        ], 'site');
    }

    public function verify(): void
    {
        Csrf::check();

        $email = Session::get('pending_email');

        if (!is_string($email) || $email === '') {
            redirect('login');
        }

        try {
            VerificationCode::check($email, 'verify_email', (string) $this->input('code'));
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
            redirect('verify');
        }

        $user = Database::fetch('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);

        if ($user === null) {
            Flash::error('حساب کاربری پیدا نشد.');
            redirect('register');
        }

        Database::update('users', ['email_verified_at' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

        Session::forget('pending_email');
        Auth::login((int) $user['id']);

        Flash::success('حساب شما با موفقیت فعال شد.');

        $intended = Session::get('intended_url');
        Session::forget('intended_url');

        redirect(is_string($intended) && $intended !== '' ? $intended : 'account');
    }

    /**
     * ارسال دوباره کد
     */
    public function resend(): void
    {
        Csrf::check();

        $email = Session::get('pending_email');

        if (!is_string($email) || $email === '') {
            redirect('login');
        }

        $user = Database::fetch('SELECT id, first_name FROM users WHERE email = ? LIMIT 1', [$email]);

        if ($user === null) {
            redirect('register');
        }

        $this->startVerification($email, (int) $user['id'], (string) $user['first_name']);
    }

    // ==================================================================
    //  شمارش تلاش ناموفق ورود
    // ==================================================================

    private function isLocked(): bool
    {
        return $this->lockRemaining() > 0;
    }

    private function lockRemaining(): int
    {
        $attempts = (int) Session::get('site_login_attempts', 0);
        $lastTry  = (int) Session::get('site_login_last', 0);

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
        Session::set('site_login_attempts', (int) Session::get('site_login_attempts', 0) + 1);
        Session::set('site_login_last', time());
    }

    private function clearFailures(): void
    {
        Session::forget('site_login_attempts');
        Session::forget('site_login_last');
    }
}
