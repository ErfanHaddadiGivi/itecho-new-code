<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Paginator;
use App\Models\Order;

/**
 * مدیریت سفارش‌ها در پنل.
 */
class OrderController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): void
    {
        Auth::requireAdmin();

        $filters = [
            'q'              => (string) $this->input('q'),
            'status'         => (string) $this->input('status'),
            'shipping_state' => (string) $this->input('shipping_state'),
        ];

        $total     = Order::adminCount($filters);
        $paginator = new Paginator($total, self::PER_PAGE, $this->intInput('page', 1));

        $this->view('admin/orders/index', [
            'title'     => 'سفارش‌ها',
            'orders'    => Order::adminList($filters, $paginator->limit(), $paginator->offset()),
            'paginator' => $paginator,
            'total'     => $total,
            'filters'   => $filters,
        ], 'admin');
    }

    public function show(string $id): void
    {
        Auth::requireAdmin();

        $orderId = (int) $id;
        $order   = Order::find($orderId);

        if ($order === null) {
            $this->notFound('سفارش پیدا نشد');
        }

        $customer = Database::fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [$order['user_id']]);

        $this->view('admin/orders/show', [
            'title'       => 'سفارش ' . $order['order_number'],
            'order'       => $order,
            'items'       => Order::items($orderId),
            'payments'    => Order::payments($orderId),
            'history'     => Order::history($orderId),
            'customer'    => $customer,
            'transitions' => Order::ALLOWED_TRANSITIONS[$order['status']] ?? [],
        ], 'admin');
    }

    /**
     * تغییر وضعیت سفارش
     */
    public function updateStatus(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $orderId = (int) $id;

        try {
            Order::changeStatus(
                $orderId,
                (string) $this->input('status'),
                Auth::id(),
                (string) $this->input('note')
            );

            Flash::success('وضعیت سفارش به‌روزرسانی شد.');
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
        }

        redirect('admin/orders/' . $orderId);
    }

    /**
     * ثبت کد رهگیری پستی و یادداشت داخلی
     */
    public function updateDetails(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $orderId = (int) $id;

        if (Order::find($orderId) === null) {
            $this->notFound('سفارش پیدا نشد');
        }

        Database::update('orders', [
            'tracking_code' => ((string) $this->input('tracking_code')) ?: null,
            'admin_note'    => ((string) $this->input('admin_note')) ?: null,
        ], 'id = ?', [$orderId]);

        Flash::success('اطلاعات سفارش ذخیره شد.');
        redirect('admin/orders/' . $orderId);
    }

    /**
     * وارد کردن هزینه واقعی ارسال پستی.
     *
     * این متد قلب سناریوی «در انتظار محاسبه هزینه ارسال» است:
     *   ۱. هزینه در سفارش ثبت و مبلغ نهایی به‌روز می‌شود
     *   ۲. یک تراکنش تکمیلی با توکن یکتا ساخته می‌شود
     *   ۳. لینک پرداخت برای مشتری ایمیل می‌شود
     */
    public function setShippingCost(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $orderId = (int) $id;
        $order   = Order::find($orderId);

        if ($order === null) {
            $this->notFound('سفارش پیدا نشد');
        }

        if ($order['delivery_method'] !== 'post') {
            Flash::error('این سفارش تحویل حضوری است و هزینه ارسال ندارد.');
            redirect('admin/orders/' . $orderId);
        }

        if ($order['payment_status'] !== 'paid') {
            Flash::error('تا وقتی مبلغ کالاها پرداخت نشده، نمی‌توان هزینه ارسال را ثبت کرد.');
            redirect('admin/orders/' . $orderId);
        }

        $cost = max(0, $this->intInput('shipping_cost'));

        if ($cost === 0) {
            Flash::error('هزینه ارسال را وارد کنید.');
            redirect('admin/orders/' . $orderId);
        }

        $token = bin2hex(random_bytes(16));

        Database::beginTransaction();

        try {
            Database::update('orders', [
                'shipping_cost'  => $cost,
                'grand_total'    => (int) $order['items_total'] + $cost,
                'shipping_state' => 'awaiting_payment',
            ], 'id = ?', [$orderId]);

            // اگر قبلاً تراکنش ارسالِ پرداخت‌نشده‌ای وجود دارد، جایگزین می‌شود
            Database::delete('payments', "order_id = ? AND purpose = 'shipping' AND status <> 'paid'", [$orderId]);

            Database::insert('payments', [
                'order_id'   => $orderId,
                'user_id'    => $order['user_id'],
                'purpose'    => 'shipping',
                'amount'     => $cost,
                'gateway'    => 'zarinpal',
                'pay_token'  => $token,
                'status'     => 'pending',
            ]);

            Order::addHistory($orderId, $order['status'], $order['status'],
                              'هزینه ارسال ثبت شد: ' . money($cost), Auth::id());

            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }

        $emailed = $this->sendShippingLink($orderId, $token, $cost);

        Flash::success($emailed
            ? 'هزینه ارسال ثبت و لینک پرداخت برای مشتری ایمیل شد.'
            : 'هزینه ارسال ثبت شد، اما ارسال ایمیل ناموفق بود. لینک پرداخت را دستی برای مشتری بفرستید.');

        redirect('admin/orders/' . $orderId);
    }

    private function sendShippingLink(int $orderId, string $token, int $cost): bool
    {
        $order = Order::find($orderId);
        $user  = Database::fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [$order['user_id']]);

        if ($user === null) {
            return false;
        }

        $path = url('pay/' . $token);
        $link = str_starts_with($path, 'http')
            ? $path
            : \App\Controllers\PaymentController::siteUrl() . $path;

        return Mailer::sendTemplate(
            $user['email'],
            'پرداخت هزینه ارسال سفارش ' . $order['order_number'],
            'shipping-cost',
            [
                'order' => $order,
                'cost'  => $cost,
                'link'  => $link,
                'name'  => $user['first_name'],
            ],
            trim($user['first_name'] . ' ' . $user['last_name'])
        );
    }
}
