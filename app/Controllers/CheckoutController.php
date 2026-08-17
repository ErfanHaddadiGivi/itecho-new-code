<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Setting;

/**
 * تسویه‌حساب.
 *
 * تا این مرحله خرید بدون حساب کاربری آزاد است، ولی برای ثبت سفارش
 * ورود اجباری می‌شود (تصمیم تاییدشده در مرحله طراحی).
 */
class CheckoutController extends Controller
{
    public function index(): void
    {
        $this->requireLogin('برای تکمیل خرید وارد حساب کاربری خود شوید. سبد خرید شما حفظ می‌شود.');

        $items   = Cart::items();
        $summary = Cart::summary($items);

        if ($items === []) {
            Flash::info('سبد خرید شما خالی است.');
            redirect('cart');
        }

        if ($summary['problems'] !== []) {
            Flash::error('برخی کالاهای سبد مشکل دارند. قبل از ادامه آن‌ها را اصلاح کنید.');
            redirect('cart');
        }

        // آخرین آدرس کاربر برای پر کردن خودکار فرم
        $address = Database::fetch(
            'SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1',
            [Auth::id()]
        );

        $this->view('site/checkout', [
            'title'      => 'تسویه‌حساب',
            'items'      => $items,
            'summary'    => $summary,
            'address'    => $address,
            'user'       => Auth::user(),
            'pickupFee'  => Setting::getInt('pickup_fee', 0),
            'pickupAddr' => (string) Setting::get('pickup_address', ''),
            'postNote'   => (string) Setting::get('shipping_note', ''),
            'errors'     => Flash::errors(),
            'scripts'    => ['checkout.js'],
        ], 'site');
    }

    /**
     * ثبت سفارش و رفتن به درگاه پرداخت
     */
    public function place(): void
    {
        $this->requireLogin('برای تکمیل خرید وارد حساب کاربری خود شوید. سبد خرید شما حفظ می‌شود.');
        Csrf::check();

        $items   = Cart::items();
        $summary = Cart::summary($items);

        if ($items === []) {
            Flash::error('سبد خرید شما خالی است.');
            redirect('cart');
        }

        if ($summary['problems'] !== []) {
            Flash::error('برخی کالاهای سبد مشکل دارند.');
            redirect('cart');
        }

        $shipping = $this->readShippingForm();
        $errors   = $this->validateShipping($shipping);

        if ($errors !== []) {
            $this->backWithErrors($errors, 'checkout');
        }

        try {
            $orderId = Order::createFromCart(
                (int) Auth::id(),
                $items,
                $shipping,
                Setting::getInt('pickup_fee', 0)
            );
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
            redirect('cart');
        }

        // ذخیره آدرس برای خریدهای بعدی
        if ($shipping['delivery_method'] === 'post' && !empty($_POST['save_address'])) {
            $this->saveAddress($shipping);
        }

        redirect('payment/start/' . $orderId);
    }

    /**
     * صفحه نتیجه سفارش
     */
    public function success(string $id): void
    {
        $this->requireLogin();

        $order = Order::findForUser((int) $id, (int) Auth::id());

        if ($order === null) {
            $this->notFound('سفارش پیدا نشد');
        }

        $this->view('site/order-success', [
            'title'    => 'سفارش ثبت شد',
            'order'    => $order,
            'items'    => Order::items((int) $order['id']),
            'payments' => Order::payments((int) $order['id']),
        ], 'site');
    }

    // ------------------------------------------------------------------

    private function readShippingForm(): array
    {
        $method = $this->input('delivery_method') === 'post' ? 'post' : 'pickup';

        return [
            'delivery_method' => $method,
            'receiver_name'   => (string) $this->input('receiver_name'),
            'receiver_phone'  => en_digits((string) $this->input('receiver_phone')),
            'province'        => (string) $this->input('province'),
            'city'            => (string) $this->input('city'),
            'postal_code'     => en_digits((string) $this->input('postal_code')),
            'address_line'    => (string) $this->input('address_line'),
            'customer_note'   => (string) $this->input('customer_note'),
        ];
    }

    private function validateShipping(array $data): array
    {
        $errors = [];

        if ($data['receiver_name'] === '') {
            $errors['receiver_name'] = 'نام تحویل‌گیرنده را وارد کنید.';
        }

        if (!preg_match('/^09\d{9}$/', $data['receiver_phone'])) {
            $errors['receiver_phone'] = 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.';
        }

        // برای تحویل حضوری آدرس لازم نیست
        if ($data['delivery_method'] === 'post') {
            if ($data['province'] === '') {
                $errors['province'] = 'استان را وارد کنید.';
            }

            if ($data['city'] === '') {
                $errors['city'] = 'شهر را وارد کنید.';
            }

            if (mb_strlen($data['address_line']) < 10) {
                $errors['address_line'] = 'نشانی کامل را وارد کنید.';
            }

            if ($data['postal_code'] !== '' && !preg_match('/^\d{10}$/', $data['postal_code'])) {
                $errors['postal_code'] = 'کد پستی باید ۱۰ رقم باشد.';
            }
        }

        return $errors;
    }

    private function saveAddress(array $shipping): void
    {
        $userId = (int) Auth::id();

        $exists = Database::fetchValue(
            'SELECT COUNT(*) FROM user_addresses WHERE user_id = ?', [$userId]
        );

        Database::insert('user_addresses', [
            'user_id'       => $userId,
            'receiver_name' => $shipping['receiver_name'],
            'phone'         => $shipping['receiver_phone'],
            'province'      => $shipping['province'],
            'city'          => $shipping['city'],
            'postal_code'   => $shipping['postal_code'] !== '' ? $shipping['postal_code'] : null,
            'address_line'  => $shipping['address_line'],
            'is_default'    => (int) $exists === 0 ? 1 : 0,
        ]);
    }
}
