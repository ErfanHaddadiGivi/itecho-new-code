<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Session;
use App\Models\Order;

/**
 * حساب کاربری مشتری.
 *
 * در این مرحله فقط سفارش‌ها نمایش داده می‌شوند؛ بخش‌های دیگر
 * (دفترچه آدرس، علاقه‌مندی‌ها، ویرایش پروفایل) در مرحله ۵ اضافه می‌شوند.
 */
class AccountController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $orders = Order::forUser((int) Auth::id());

        $this->view('site/account/index', [
            'title'  => 'حساب کاربری',
            'user'   => Auth::user(),
            'orders' => array_slice($orders, 0, 5),
            'total'  => count($orders),
        ], 'site');
    }

    public function orders(): void
    {
        $this->requireLogin();

        $this->view('site/account/orders', [
            'title'  => 'سفارش‌های من',
            'orders' => Order::forUser((int) Auth::id()),
        ], 'site');
    }

    public function order(string $id): void
    {
        $this->requireLogin();

        $order = Order::findForUser((int) $id, (int) Auth::id());

        if ($order === null) {
            $this->notFound('سفارش پیدا نشد');
        }

        $orderId = (int) $order['id'];

        $this->view('site/account/order', [
            'title'    => 'سفارش ' . $order['order_number'],
            'order'    => $order,
            'items'    => Order::items($orderId),
            'history'  => Order::history($orderId),
            'payments' => Order::payments($orderId),
        ], 'site');
    }

    private function requireLogin(): void
    {
        if (Auth::check()) {
            return;
        }

        Session::set('intended_url', $_SERVER['REQUEST_URI'] ?? '');
        Flash::info('برای مشاهده این صفحه وارد حساب کاربری خود شوید.');
        redirect('login');
    }
}
