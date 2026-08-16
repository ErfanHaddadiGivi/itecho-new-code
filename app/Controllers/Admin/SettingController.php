<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Setting;

/**
 * تنظیمات سایت.
 *
 * فرم به صورت خودکار از روی ردیف‌های جدول settings ساخته می‌شود،
 * پس برای افزودن یک تنظیم جدید فقط کافی است یک ردیف به دیتابیس اضافه شود.
 */
class SettingController extends Controller
{
    /** گروه‌های تنظیمات و عنوان فارسی هر گروه */
    private const GROUPS = [
        'general'  => 'اطلاعات کلی سایت',
        'shipping' => 'ارسال و تحویل',
        'payment'  => 'درگاه پرداخت',
        'mail'     => 'ایمیل و کد تایید',
    ];

    /** فیلدهایی که ورودی متنی ساده نیستند */
    private const FIELD_TYPES = [
        'smtp_password'    => 'password',
        'site_description' => 'textarea',
        'site_address'     => 'textarea',
        'shipping_note'    => 'textarea',
        'pickup_address'   => 'textarea',
        'enamad_code'      => 'textarea',
        'zarinpal_sandbox' => 'toggle',
        'maintenance_mode' => 'toggle',
        'smtp_secure'      => 'select:tls,ssl',
    ];

    public function index(): void
    {
        Auth::requireAdmin();

        $groups = [];
        foreach (self::GROUPS as $key => $label) {
            $groups[$key] = [
                'label'    => $label,
                'settings' => Setting::group($key),
            ];
        }

        $this->view('admin/settings/index', [
            'title'      => 'تنظیمات',
            'groups'     => $groups,
            'fieldTypes' => self::FIELD_TYPES,
        ], 'admin');
    }

    public function update(): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $submitted = $_POST['settings'] ?? [];

        if (!is_array($submitted)) {
            Flash::error('اطلاعات ارسالی معتبر نبود.');
            redirect('admin/settings');
        }

        // فقط کلیدهایی که واقعاً در دیتابیس وجود دارند به‌روزرسانی می‌شوند،
        // تا کسی نتواند با دستکاری فرم، تنظیم دلخواه بسازد.
        $allowed = array_keys(Setting::all());

        foreach ($submitted as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }

            $value = is_scalar($value) ? trim((string) $value) : '';

            // مبالغ و اعداد ممکن است با ارقام فارسی وارد شوند
            if (str_ends_with($key, '_fee') || str_ends_with($key, '_port')
                || str_ends_with($key, '_minutes') || str_ends_with($key, '_per_page')) {
                $value = en_digits($value);
            }

            Setting::set($key, $value);
        }

        // تیک‌های خاموش اصلاً در POST نمی‌آیند، پس جداگانه صفر می‌شوند
        foreach (self::FIELD_TYPES as $key => $type) {
            if ($type === 'toggle' && !isset($submitted[$key]) && in_array($key, $allowed, true)) {
                Setting::set($key, '0');
            }
        }

        Flash::success('تنظیمات ذخیره شد.');
        redirect('admin/settings');
    }
}
