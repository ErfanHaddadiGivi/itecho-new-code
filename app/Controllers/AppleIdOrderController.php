<?php

namespace App\Controllers;

use App\Core\AppleId;
use App\Core\AppleIdAuth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Upload;
use App\Models\Setting;

/**
 * ثبت سفارش اپل‌آیدی از سایت (کاربر واردشده) — همان مراحل ربات:
 * انتخاب ضمانت/آیکلود → اطلاعات → تأیید → پرداخت (فیش) → (تأیید ادمین در بله)
 * → ورود کد → (تحویل توسط ادمین) → دریافت در پروفایل.
 */
class AppleIdOrderController extends Controller
{
    private const RECEIPT_DIR = 'appleid';

    // مرحله ۱: انتخاب ضمانت و آیکلود
    public function create(): void
    {
        $this->guard();
        $this->view('site/appleid/order-new', [
            'title'      => 'درخواست اپل‌آیدی جدید',
            'warranties' => AppleId::activeWarranties(),
            'errors'     => Flash::errors(),
        ], 'site');
    }

    public function store(): void
    {
        $uid = $this->guard();
        Csrf::check();

        $warrantyId = (int) en_digits((string) $this->input('warranty_type_id'));
        $icloud     = ((string) $this->input('icloud')) === '1' ? 1 : 0;

        $product = $warrantyId > 0 ? AppleId::productFor($warrantyId, $icloud) : null;
        if ($product === null) {
            $this->backWithErrors(['combo' => 'این ترکیب فعلاً موجود نیست. ترکیب دیگری را انتخاب کنید.'], 'appleid/order/new');
        }

        $orderId = AppleId::orders()->createWebDraft(
            $uid,
            (int) $product['id'],
            'regular',
            (int) $product['price_regular']
        );
        redirect('appleid/order/' . $orderId . '/info');
    }

    // مرحله ۲: اطلاعات شخصی
    public function info(string $id): void
    {
        $this->guard();
        $order = $this->ownedOrder((int) $id, ['draft']);
        $this->view('site/appleid/order-info', [
            'title'  => 'اطلاعات اپل‌آیدی',
            'order'  => $order,
            'errors' => Flash::errors(),
        ], 'site');
    }

    public function saveInfo(string $id): void
    {
        $uid   = $this->guard();
        Csrf::check();
        $order = $this->ownedOrder((int) $id, ['draft']);

        $first = \AppleBot\Validator::name((string) $this->input('first_name'));
        $last  = \AppleBot\Validator::name((string) $this->input('last_name'));
        $phone = \AppleBot\Validator::phone((string) $this->input('phone'));
        $email = \AppleBot\Validator::email((string) $this->input('email'));
        $birth = \AppleBot\Validator::birthdate((string) $this->input('birthdate'));

        $errors = [];
        if ($first === null) { $errors['first_name'] = 'نام معتبر وارد کنید.'; }
        if ($last === null)  { $errors['last_name']  = 'نام خانوادگی معتبر وارد کنید.'; }
        if ($phone === null) { $errors['phone']      = 'شمارهٔ موبایل معتبر وارد کنید.'; }
        if ($email === null) { $errors['email']      = 'ایمیل معتبر وارد کنید.'; }
        if ($birth === null) { $errors['birthdate']  = 'تاریخ تولد به شکل YYYY-MM-DD وارد کنید.'; }
        if ($errors !== []) {
            $this->backWithErrors($errors, 'appleid/order/' . $order['id'] . '/info');
        }

        $o = AppleId::orders();
        $o->setEncryptedField((int) $order['id'], 'first_name_enc', $first);
        $o->setEncryptedField((int) $order['id'], 'last_name_enc', $last);
        $o->setEncryptedField((int) $order['id'], 'phone_enc', $phone);
        $o->setEncryptedField((int) $order['id'], 'email_enc', $email);
        $o->setEncryptedField((int) $order['id'], 'birthdate_enc', $birth);

        redirect('appleid/order/' . $order['id'] . '/confirm');
    }

    // مرحله ۳: خلاصه و تأیید
    public function confirm(string $id): void
    {
        $this->guard();
        $order   = $this->ownedOrder((int) $id, ['draft']);
        $product = AppleId::productById((int) $order['product_id']);
        $p       = AppleId::orders()->decryptPersonal($order);

        $this->view('site/appleid/order-confirm', [
            'title'    => 'تأیید سفارش',
            'order'    => $order,
            'product'  => $product,
            'personal' => $p,
        ], 'site');
    }

    // تأیید → مرحلهٔ پرداخت
    public function toPayment(string $id): void
    {
        $this->guard();
        Csrf::check();
        $order = $this->ownedOrder((int) $id, ['draft']);
        AppleId::orders()->setStatus((int) $order['id'], 'pending_payment');
        redirect('appleid/order/' . $order['id'] . '/pay');
    }

    // مرحله ۴: پرداخت + آپلود فیش
    public function pay(string $id): void
    {
        $this->guard();
        $order = $this->ownedOrder((int) $id, ['pending_payment']);
        $this->view('site/appleid/order-pay', [
            'title'       => 'پرداخت',
            'order'       => $order,
            'cardNumber'  => AppleId::setting('card_number', '—'),
            'cardHolder'  => AppleId::setting('card_holder_name', '—'),
            'errors'      => Flash::errors(),
        ], 'site');
    }

    public function receipt(string $id): void
    {
        $this->guard();
        Csrf::check();
        $order = $this->ownedOrder((int) $id, ['pending_payment']);

        try {
            $name = Upload::image($_FILES['receipt'] ?? [], self::RECEIPT_DIR);
        } catch (\RuntimeException $e) {
            $this->backWithErrors(['receipt' => $e->getMessage()], 'appleid/order/' . $order['id'] . '/pay');
        }
        if ($name === null) {
            $this->backWithErrors(['receipt' => 'عکس فیش را انتخاب کنید.'], 'appleid/order/' . $order['id'] . '/pay');
        }

        // وضعیت → در انتظار تأیید، سپس اطلاع (و آپلود فیش) به ادمین‌ها در بله
        AppleId::orders()->setStatus((int) $order['id'], 'pending_approval', ['payment_method' => 'receipt']);
        AppleId::notifyAdminsNewOrder((int) $order['id'], ROOT_PATH . '/uploads/' . self::RECEIPT_DIR . '/' . $name);

        Flash::success('فیش دریافت شد. سفارش برای بررسی رفت؛ نتیجه را در همین پروفایل می‌بینی.');
        redirect('appleid/account');
    }

    // ورود کد تأیید (بعد از تأیید ادمین)
    public function code(string $id): void
    {
        $this->guard();
        Csrf::check();
        $order = $this->ownedOrder((int) $id, ['approved_awaiting_code']);

        $code = \AppleBot\Validator::clean((string) $this->input('code'));
        if ($code === '' || mb_strlen($code) > 32) {
            $this->backWithErrors(['code' => 'کد معتبر وارد کنید.'], 'appleid/account');
        }

        AppleId::orders()->setVerificationCode((int) $order['id'], $code);
        AppleId::notifyAdminsCode((int) $order['id'], $code);

        Flash::success('کد ثبت شد. چند لحظه صبر کن تا اپل‌آیدی نهایی تحویل بشه.');
        redirect('appleid/account');
    }

    public function cancel(string $id): void
    {
        $this->guard();
        Csrf::check();
        $order = $this->ownedOrder((int) $id, ['draft', 'pending_payment']);
        AppleId::orders()->setStatus((int) $order['id'], 'cancelled');
        Flash::success('سفارش لغو شد.');
        redirect('appleid/account');
    }

    // ==================================================================
    //  کمک‌کارها
    // ==================================================================
    /**
     * تصویرِ تحویل‌شدهٔ اپل‌آیدی را از پیام‌رسان (بله) می‌گیرد و استریم می‌کند.
     * فقط صاحبِ سفارشِ تکمیل‌شده که تحویلش «عکس» است می‌تواند ببیند.
     */
    public function image(string $id): void
    {
        $this->guard();
        $order = $this->ownedOrder((int) $id, ['completed']);

        $creds = AppleId::crypto()->decrypt($order['final_credentials_enc'] ?? null) ?? '';
        if (!str_starts_with($creds, 'img:')) {
            $this->notFound('تصویری برای این سفارش نیست');
        }
        $file = AppleId::messenger()->downloadFile(substr($creds, 4));
        if ($file === null) {
            $this->notFound('دریافت تصویر ناموفق بود');
        }

        header('Content-Type: ' . $file['mime']);
        header('Content-Length: ' . strlen($file['bytes']));
        header('Cache-Control: private, max-age=300');
        header('X-Content-Type-Options: nosniff');
        echo $file['bytes'];
        exit;
    }

    private function guard(): int
    {
        if (!Setting::getBool('appleid_enabled', false) || !AppleId::available()) {
            $this->notFound('این بخش فعال نیست');
        }
        if (!AppleIdAuth::check()) {
            Flash::info('برای ادامه وارد حساب اپل‌آیدی خود شوید.');
            redirect('appleid/login');
        }
        return (int) AppleIdAuth::id();
    }

    /** سفارشِ متعلق به کاربر فعلی با وضعیت مجاز؛ وگرنه ۴۰۴/بازگشت */
    private function ownedOrder(int $orderId, array $allowedStatuses): array
    {
        $uid   = (int) AppleIdAuth::id();
        $order = AppleId::orders()->findForWebUser($orderId, $uid);
        if ($order === null) {
            $this->notFound('سفارش پیدا نشد');
        }
        if (!in_array($order['status'], $allowedStatuses, true)) {
            Flash::error('این مرحله برای سفارش قابل انجام نیست.');
            redirect('appleid/account');
        }
        return $order;
    }
}
