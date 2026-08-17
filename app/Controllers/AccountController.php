<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Models\Address;
use App\Models\Order;
use App\Models\Review;
use App\Models\Wishlist;

/**
 * حساب کاربری مشتری — پیشخوان، سفارش‌ها و ویرایش پروفایل.
 *
 * دفترچه آدرس، علاقه‌مندی‌ها و نظرات هرکدام کنترلر جدا دارند تا
 * این فایل کوتاه و قابل خواندن بماند.
 */
class AccountController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $userId = (int) Auth::id();
        $orders = Order::forUser($userId);

        $this->view('site/account/index', [
            'title'         => 'حساب کاربری',
            'user'          => Auth::user(),
            'orders'        => array_slice($orders, 0, 5),
            'orderCount'    => count($orders),
            'addressCount'  => Address::countFor($userId),
            'wishlistCount' => Wishlist::countFor($userId),
            'toReview'      => Review::awaitingReview($userId),
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

    // ==================================================================
    //  پروفایل
    // ==================================================================

    public function profile(): void
    {
        $this->requireLogin();

        $this->view('site/account/profile', [
            'title'  => 'اطلاعات حساب',
            'user'   => Auth::user(),
            'errors' => Flash::errors(),
        ], 'site');
    }

    /**
     * ویرایش نام و شماره موبایل.
     * ایمیل قابل تغییر نیست چون شناسه ورود و مقصد کدهای تایید است.
     */
    public function updateProfile(): void
    {
        $this->requireLogin();
        Csrf::check();

        $firstName = (string) $this->input('first_name');
        $lastName  = (string) $this->input('last_name');
        $phone     = en_digits((string) $this->input('phone'));

        $errors = [];

        if ($firstName === '') {
            $errors['first_name'] = 'نام را وارد کنید.';
        }

        if ($lastName === '') {
            $errors['last_name'] = 'نام خانوادگی را وارد کنید.';
        }

        if ($phone !== '' && !preg_match('/^09\d{9}$/', $phone)) {
            $errors['phone'] = 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.';
        }

        if ($errors !== []) {
            $this->backWithErrors($errors, 'account/profile');
        }

        Database::update('users', [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'phone'      => $phone !== '' ? $phone : null,
        ], 'id = ?', [Auth::id()]);

        Flash::success('اطلاعات حساب به‌روزرسانی شد.');
        redirect('account/profile');
    }

    /**
     * تغییر رمز عبور — با تایید رمز فعلی
     */
    public function updatePassword(): void
    {
        $this->requireLogin();
        Csrf::check();

        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['new_password_confirm'] ?? '');

        $user   = Auth::user();
        $errors = [];

        if (!password_verify($current, $user['password_hash'])) {
            $errors['current_password'] = 'رمز عبور فعلی درست نیست.';
        }

        if (mb_strlen($new) < 8) {
            $errors['new_password'] = 'رمز عبور جدید باید حداقل ۸ کاراکتر باشد.';
        } elseif ($new !== $confirm) {
            $errors['new_password_confirm'] = 'تکرار رمز عبور یکسان نیست.';
        }

        if ($errors !== []) {
            $this->backWithErrors($errors, 'account/profile');
        }

        Database::update('users', [
            'password_hash' => password_hash($new, PASSWORD_DEFAULT),
        ], 'id = ?', [$user['id']]);

        Flash::success('رمز عبور تغییر کرد.');
        redirect('account/profile');
    }
}
