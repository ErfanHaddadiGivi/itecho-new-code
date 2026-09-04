<?php

namespace App\Controllers;

use App\Core\AppleId;
use App\Core\AppleIdAuth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Setting;

/**
 * بخش اپل‌آیدی سایت: صفحهٔ معرفی + ورود/ثبت‌نام (موبایل+رمز) + پروفایل کاربر.
 * سفارش‌گیری در AppleIdOrderController است. داده‌ها در دیتابیس مشترک ربات.
 */
class AppleIdController extends Controller
{
    // ==================================================================
    //  صفحهٔ معرفی
    // ==================================================================
    public function index(): void
    {
        if (!Setting::getBool('appleid_enabled', false)) {
            $this->notFound('این صفحه فعال نیست');
        }

        $botUsername = trim((string) Setting::get('appleid_bot_username', ''));
        $startPrice  = (int) en_digits((string) Setting::get('appleid_start_price', ''));

        // لینک ربات بله (اگر یوزرنیم تنظیم شده)؛ بله از ble.ir استفاده می‌کند
        $baleLink = $botUsername !== ''
            ? 'https://ble.ir/' . rawurlencode($botUsername) . '?start=appleid'
            : '';

        $this->view('site/appleid', [
            'title'           => 'خرید اپل‌آیدی آمریکا | ' . Setting::get('site_name', 'ایتکو'),
            'metaDescription' => 'اپل‌آیدی معتبر ریجن آمریکا روی ایمیل خودت؛ از انتخاب پلن تا تحویل، همه‌چیز آنلاین و با پشتیبانی.',
            'baleLink'        => $baleLink,
            'startPrice'      => $startPrice,
            'webAvailable'    => AppleId::available(),
            'loggedIn'        => AppleIdAuth::check(),
        ], 'site');
    }

    // ==================================================================
    //  ثبت‌نام / ورود / خروج
    // ==================================================================
    public function showRegister(): void
    {
        $this->ensureModule();
        if (AppleIdAuth::check()) { redirect('appleid/account'); }
        $this->view('site/appleid/register', ['title' => 'ثبت‌نام اپل‌آیدی', 'errors' => Flash::errors()], 'site');
    }

    public function register(): void
    {
        $this->ensureModule();
        Csrf::check();

        $phone = \AppleBot\Validator::phone((string) $this->input('phone'));
        $pass  = (string) ($_POST['password'] ?? '');
        $name  = \AppleBot\Validator::name((string) $this->input('name')) ?: null;

        $errors = [];
        if ($phone === null)          { $errors['phone'] = 'شمارهٔ موبایل معتبر وارد کنید (مثل ۰۹۱۲۳۴۵۶۷۸۹).'; }
        if (mb_strlen($pass) < 6)     { $errors['password'] = 'رمز عبور حداقل ۶ کاراکتر باشد.'; }
        if ($phone !== null && AppleIdAuth::phoneExists($phone)) {
            $errors['phone'] = 'این شماره قبلاً ثبت‌نام کرده است. وارد شوید.';
        }
        if ($errors !== []) {
            $this->backWithErrors($errors, 'appleid/register');
        }

        $uid = AppleIdAuth::register($phone, $pass, $name);
        AppleIdAuth::login($uid);
        Flash::success('خوش اومدی! حالا می‌تونی درخواست اپل‌آیدی ثبت کنی.');
        redirect('appleid/account');
    }

    public function showLogin(): void
    {
        $this->ensureModule();
        if (AppleIdAuth::check()) { redirect('appleid/account'); }
        $this->view('site/appleid/login', ['title' => 'ورود اپل‌آیدی', 'errors' => Flash::errors()], 'site');
    }

    public function login(): void
    {
        $this->ensureModule();
        Csrf::check();

        $phone = \AppleBot\Validator::phone((string) $this->input('phone'));
        $pass  = (string) ($_POST['password'] ?? '');

        if ($phone === null || $pass === '' || ($user = AppleIdAuth::attempt($phone, $pass)) === null) {
            $this->backWithErrors(['login' => 'شماره یا رمز عبور درست نیست.'], 'appleid/login');
        }

        AppleIdAuth::login((int) $user['id']);
        redirect('appleid/account');
    }

    public function logout(): void
    {
        Csrf::check();
        AppleIdAuth::logout();
        redirect('appleid');
    }

    // ==================================================================
    //  پروفایل کاربر (لیست سفارش‌ها + وضعیت + تحویل)
    // ==================================================================
    public function account(): void
    {
        $this->ensureModule();
        $uid = $this->requireLoginId();

        $orders = AppleId::orders()->listForWebUser($uid);
        $crypto = AppleId::crypto();

        // کریدنشال نهاییِ سفارش‌های تکمیل‌شده را برای نمایش بازگشایی کن
        foreach ($orders as &$o) {
            $o['final_credentials'] = ($o['status'] === 'completed')
                ? ($crypto->decrypt($o['final_credentials_enc'] ?? null) ?? '')
                : '';
            // تحویل به‌صورت عکس؟ (مقدار با «img:» شروع می‌شود؛ خودِ file_id افشا نمی‌شود)
            $o['final_is_image']   = str_starts_with((string) $o['final_credentials'], 'img:');
        }
        unset($o);

        $this->view('site/appleid/account', [
            'title'  => 'پروفایل اپل‌آیدی',
            'user'   => AppleIdAuth::user(),
            'orders' => $orders,
            'errors' => Flash::errors(),
        ], 'site');
    }

    // ==================================================================
    //  کمک‌کارها
    // ==================================================================
    private function ensureModule(): void
    {
        if (!Setting::getBool('appleid_enabled', false) || !AppleId::available()) {
            $this->notFound('این بخش فعال نیست');
        }
    }

    private function requireLoginId(): int
    {
        if (!AppleIdAuth::check()) {
            Flash::info('برای ادامه وارد حساب اپل‌آیدی خود شوید.');
            redirect('appleid/login');
        }
        return (int) AppleIdAuth::id();
    }
}
