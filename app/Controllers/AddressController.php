<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Address;

/**
 * دفترچه آدرس مشتری.
 */
class AddressController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $this->view('site/account/addresses', [
            'title'     => 'دفترچه آدرس',
            'addresses' => Address::forUser((int) Auth::id()),
        ], 'site');
    }

    public function create(): void
    {
        $this->requireLogin();

        $this->view('site/account/address-form', [
            'title'   => 'افزودن آدرس',
            'address' => null,
            'errors'  => Flash::errors(),
        ], 'site');
    }

    public function store(): void
    {
        $this->requireLogin();
        Csrf::check();

        $data   = $this->readForm();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->backWithErrors($errors, 'account/addresses/create');
        }

        Address::add((int) Auth::id(), $data, !empty($_POST['is_default']));

        Flash::success('آدرس جدید ثبت شد.');
        redirect('account/addresses');
    }

    public function edit(string $id): void
    {
        $this->requireLogin();

        $address = Address::findForUser((int) $id, (int) Auth::id());

        if ($address === null) {
            $this->notFound('آدرس پیدا نشد');
        }

        $this->view('site/account/address-form', [
            'title'   => 'ویرایش آدرس',
            'address' => $address,
            'errors'  => Flash::errors(),
        ], 'site');
    }

    public function update(string $id): void
    {
        $this->requireLogin();
        Csrf::check();

        $addressId = (int) $id;

        if (Address::findForUser($addressId, (int) Auth::id()) === null) {
            $this->notFound('آدرس پیدا نشد');
        }

        $data   = $this->readForm();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->backWithErrors($errors, 'account/addresses/' . $addressId . '/edit');
        }

        Address::edit($addressId, (int) Auth::id(), $data, !empty($_POST['is_default']));

        Flash::success('آدرس به‌روزرسانی شد.');
        redirect('account/addresses');
    }

    public function setDefault(string $id): void
    {
        $this->requireLogin();
        Csrf::check();

        Address::setDefault((int) $id, (int) Auth::id());

        Flash::success('آدرس پیش‌فرض تغییر کرد.');
        redirect('account/addresses');
    }

    public function destroy(string $id): void
    {
        $this->requireLogin();
        Csrf::check();

        Address::remove((int) $id, (int) Auth::id());

        Flash::success('آدرس حذف شد.');
        redirect('account/addresses');
    }

    // ------------------------------------------------------------------

    private function readForm(): array
    {
        return [
            'receiver_name' => (string) $this->input('receiver_name'),
            'phone'         => en_digits((string) $this->input('phone')),
            'province'      => (string) $this->input('province'),
            'city'          => (string) $this->input('city'),
            'postal_code'   => en_digits((string) $this->input('postal_code')) ?: null,
            'address_line'  => (string) $this->input('address_line'),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        if ($data['receiver_name'] === '') {
            $errors['receiver_name'] = 'نام تحویل‌گیرنده را وارد کنید.';
        }

        if (!preg_match('/^09\d{9}$/', $data['phone'])) {
            $errors['phone'] = 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.';
        }

        if ($data['province'] === '') {
            $errors['province'] = 'استان را وارد کنید.';
        }

        if ($data['city'] === '') {
            $errors['city'] = 'شهر را وارد کنید.';
        }

        if (mb_strlen($data['address_line']) < 10) {
            $errors['address_line'] = 'نشانی کامل را وارد کنید.';
        }

        if ($data['postal_code'] !== null && !preg_match('/^\d{10}$/', $data['postal_code'])) {
            $errors['postal_code'] = 'کد پستی باید ۱۰ رقم باشد.';
        }

        return $errors;
    }
}
