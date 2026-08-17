<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Mailer;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Setting;
use App\Payment\Gateway;

/**
 * پرداخت آنلاین.
 *
 * دو نوع پرداخت داریم:
 *   purpose = 'order'    → پرداخت مبلغ کالاها
 *   purpose = 'shipping' → پرداخت تکمیلی هزینه ارسال پستی (با لینک ایمیل‌شده)
 *
 * ⚠️ نکته امنیتی: پارامترهایی که درگاه در بازگشت می‌فرستد به‌تنهایی
 * قابل اعتماد نیستند. تا وقتی متد verify درگاه تایید نکرده، هیچ سفارشی
 * «پرداخت‌شده» نمی‌شود.
 */
class PaymentController extends Controller
{
    /**
     * شروع پرداخت مبلغ کالاهای یک سفارش
     */
    public function start(string $id): void
    {
        if (!Auth::check()) {
            Flash::error('برای پرداخت وارد حساب کاربری شوید.');
            redirect('login');
        }

        $order = Order::findForUser((int) $id, (int) Auth::id());

        if ($order === null) {
            $this->notFound('سفارش پیدا نشد');
        }

        if ($order['payment_status'] === 'paid') {
            Flash::info('این سفارش قبلاً پرداخت شده است.');
            redirect('order/' . $order['id']);
        }

        if ($order['status'] === 'canceled') {
            Flash::error('این سفارش لغو شده است.');
            redirect('account/orders');
        }

        $this->send(
            (int) $order['id'],
            'order',
            (int) $order['items_total'],
            'پرداخت سفارش ' . $order['order_number']
        );
    }

    /**
     * صفحه پرداخت تکمیلی هزینه ارسال — با توکن یکتایی که در ایمیل برای مشتری رفته.
     * عمداً نیازی به ورود ندارد تا مشتری با یک کلیک از داخل ایمیل پرداخت کند.
     */
    public function showToken(string $token): void
    {
        $payment = $this->findPaymentByToken($token);
        $order   = Order::find((int) $payment['order_id']);

        $this->view('site/pay-shipping', [
            'title'   => 'پرداخت هزینه ارسال',
            'payment' => $payment,
            'order'   => $order,
        ], 'site');
    }

    /**
     * شروع پرداخت تکمیلی
     */
    public function startToken(string $token): void
    {
        $payment = $this->findPaymentByToken($token);
        $order   = Order::find((int) $payment['order_id']);

        $this->send(
            (int) $order['id'],
            'shipping',
            (int) $payment['amount'],
            'هزینه ارسال سفارش ' . $order['order_number'],
            (int) $payment['id']
        );
    }

    /**
     * بازگشت از درگاه
     */
    public function callback(): void
    {
        // زرین‌پال با Authority و Status برمی‌گردد
        $authority = (string) ($_GET['Authority'] ?? $_GET['authority'] ?? '');
        $status    = (string) ($_GET['Status'] ?? $_GET['status'] ?? '');

        $payment = $authority !== ''
            ? Database::fetch('SELECT * FROM payments WHERE authority = ? LIMIT 1', [$authority])
            : null;

        if ($payment === null) {
            $this->view('site/payment-result', [
                'title'   => 'نتیجه پرداخت',
                'ok'      => false,
                'message' => 'تراکنش مورد نظر پیدا نشد.',
                'order'   => null,
            ], 'site');
            return;
        }

        $order = Order::find((int) $payment['order_id']);

        // اگر قبلاً تایید شده (کاربر صفحه را دوباره باز کرده)
        if ($payment['status'] === 'paid') {
            $this->showResult(true, 'این پرداخت قبلاً با موفقیت انجام شده است.', $order, $payment);
            return;
        }

        // کاربر در درگاه انصراف داده است
        if (strtoupper($status) !== 'OK') {
            Database::update('payments', [
                'status'           => 'canceled',
                'gateway_response' => 'کاربر پرداخت را لغو کرد. Status=' . $status,
            ], 'id = ?', [$payment['id']]);

            $this->showResult(false, 'پرداخت توسط شما لغو شد.', $order, $payment);
            return;
        }

        // --- تایید نهایی با درگاه ---
        $gateway = Gateway::active();
        $result  = $gateway->verify($authority, (int) $payment['amount']);

        if (!$result->ok) {
            Database::update('payments', [
                'status'           => 'failed',
                'gateway_response' => $result->raw !== '' ? $result->raw : $result->message,
            ], 'id = ?', [$payment['id']]);

            $this->showResult(false, $result->message, $order, $payment);
            return;
        }

        Database::update('payments', [
            'status'           => 'paid',
            'ref_id'           => $result->refId,
            'card_pan'         => $result->cardPan,
            'paid_at'          => date('Y-m-d H:i:s'),
            'gateway_response' => $result->raw,
        ], 'id = ?', [$payment['id']]);

        if ($payment['purpose'] === 'shipping') {
            $this->completeShippingPayment($order);
        } else {
            $this->completeOrderPayment($order);
        }

        $order = Order::find((int) $order['id']);
        $this->showResult(true, 'پرداخت با موفقیت انجام شد.', $order,
                          Database::fetch('SELECT * FROM payments WHERE id = ?', [$payment['id']]));
    }

    // ==================================================================

    /**
     * ساخت رکورد پرداخت و فرستادن کاربر به درگاه
     */
    private function send(int $orderId, string $purpose, int $amount, string $description, ?int $paymentId = null): never
    {
        if ($amount <= 0) {
            Flash::error('مبلغ پرداخت نامعتبر است.');
            redirect('order/' . $orderId);
        }

        $gateway = Gateway::active();

        // درگاه به آدرس کامل نیاز دارد. اگر base_url در تنظیمات پر شده باشد،
        // url() خودش آدرس مطلق می‌دهد و نباید دوباره دامنه اضافه شود.
        $callbackPath = url('payment/callback');
        $callbackUrl  = str_starts_with($callbackPath, 'http')
            ? $callbackPath
            : self::siteUrl() . $callbackPath;

        $result = $gateway->request($amount, $callbackUrl, $description);

        if (!$result->ok) {
            // تراکنش ناموفق هم ثبت می‌شود تا سابقه بماند
            Database::insert('payments', [
                'order_id'         => $orderId,
                'user_id'          => Auth::id(),
                'purpose'          => $purpose,
                'amount'           => $amount,
                'gateway'          => $gateway->name(),
                'is_sandbox'       => $gateway->isSandbox() ? 1 : 0,
                'status'           => 'failed',
                'gateway_response' => $result->raw !== '' ? $result->raw : $result->message,
            ]);

            Flash::error($result->message);
            redirect($purpose === 'shipping' ? 'order/' . $orderId : 'checkout');
        }

        if ($paymentId !== null) {
            // پرداخت تکمیلی از قبل ساخته شده، فقط authority به آن اضافه می‌شود
            Database::update('payments', [
                'authority'        => $result->authority,
                'status'           => 'pending',
                'gateway'          => $gateway->name(),
                'is_sandbox'       => $gateway->isSandbox() ? 1 : 0,
                'gateway_response' => $result->raw,
            ], 'id = ?', [$paymentId]);
        } else {
            Database::insert('payments', [
                'order_id'         => $orderId,
                'user_id'          => Auth::id(),
                'purpose'          => $purpose,
                'amount'           => $amount,
                'gateway'          => $gateway->name(),
                'is_sandbox'       => $gateway->isSandbox() ? 1 : 0,
                'authority'        => $result->authority,
                'status'           => 'pending',
                'gateway_response' => $result->raw,
            ]);
        }

        redirect($result->redirectUrl);
    }

    /**
     * کارهای پس از پرداخت موفق مبلغ کالاها
     */
    private function completeOrderPayment(array $order): void
    {
        $orderId = (int) $order['id'];

        Database::update('orders', [
            'status'         => 'paid',
            'payment_status' => 'paid',
            'paid_at'        => date('Y-m-d H:i:s'),
        ], 'id = ?', [$orderId]);

        Order::addHistory($orderId, $order['status'], 'paid', 'پرداخت موفق از درگاه', null);

        // کسر موجودی — خودش در برابر اجرای دوباره محافظت شده است
        Order::deductStock($orderId);

        // سبد خرید فقط بعد از پرداخت موفق خالی می‌شود
        Cart::clear();

        $this->sendOrderEmail($orderId);
    }

    /**
     * کارهای پس از پرداخت هزینه ارسال
     */
    private function completeShippingPayment(array $order): void
    {
        $orderId = (int) $order['id'];

        Database::update('orders', ['shipping_state' => 'paid'], 'id = ?', [$orderId]);

        Order::addHistory($orderId, $order['status'], $order['status'],
                          'هزینه ارسال پرداخت شد', null);

        // اگر سفارش هنوز در مرحله «پرداخت‌شده» است، وارد آماده‌سازی می‌شود
        if ($order['status'] === 'paid') {
            Database::update('orders', ['status' => 'preparing'], 'id = ?', [$orderId]);
            Order::addHistory($orderId, 'paid', 'preparing', 'شروع آماده‌سازی پس از پرداخت هزینه ارسال', null);
        }
    }

    private function sendOrderEmail(int $orderId): void
    {
        $order = Order::find($orderId);

        if ($order === null) {
            return;
        }

        $user = Database::fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [$order['user_id']]);

        if ($user === null) {
            return;
        }

        // ارسال ایمیل نباید جریان پرداخت را متوقف کند
        Mailer::sendTemplate(
            $user['email'],
            'سفارش شما ثبت شد — ' . $order['order_number'],
            'order-placed',
            [
                'order' => $order,
                'items' => Order::items($orderId),
                'name'  => $user['first_name'],
            ],
            trim($user['first_name'] . ' ' . $user['last_name'])
        );
    }

    private function showResult(bool $ok, string $message, ?array $order, ?array $payment): void
    {
        $this->view('site/payment-result', [
            'title'   => 'نتیجه پرداخت',
            'ok'      => $ok,
            'message' => $message,
            'order'   => $order,
            'payment' => $payment,
        ], 'site');
    }

    /**
     * پیدا کردن پرداخت تکمیلی با توکن، همراه بررسی‌های لازم
     */
    private function findPaymentByToken(string $token): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            $this->notFound('لینک پرداخت معتبر نیست');
        }

        $payment = Database::fetch(
            "SELECT * FROM payments WHERE pay_token = ? AND purpose = 'shipping' LIMIT 1",
            [$token]
        );

        if ($payment === null) {
            $this->notFound('لینک پرداخت پیدا نشد');
        }

        if ($payment['status'] === 'paid') {
            Flash::info('این هزینه قبلاً پرداخت شده است.');
            redirect('');
        }

        return $payment;
    }

    /**
     * آدرس کامل سایت (برای callback درگاه)
     */
    public static function siteUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        $scheme = $https ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host;
    }
}
