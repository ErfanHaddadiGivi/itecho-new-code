<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Session;
use App\Models\Wishlist;

/**
 * لیست علاقه‌مندی‌ها.
 *
 * طبق PRD نیازمند حساب کاربری است. اگر مهمان روی قلب کلیک کند،
 * به صفحه ورود می‌رود و پس از ورود به همان محصول برمی‌گردد.
 */
class WishlistController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $this->view('site/account/wishlist', [
            'title'    => 'علاقه‌مندی‌ها',
            'products' => Wishlist::forUser((int) Auth::id()),
        ], 'site');
    }

    /**
     * افزودن یا حذف با یک دکمه.
     * اگر درخواست از جاوااسکریپت آمده باشد، پاسخ JSON برمی‌گردد
     * تا صفحه دوباره بارگذاری نشود.
     */
    public function toggle(): void
    {
        Csrf::check();

        $productId = $this->intInput('product_id');

        if (!Auth::check()) {
            if ($this->wantsJson()) {
                $this->json([
                    'ok'       => false,
                    'needLogin'=> true,
                    'message'  => 'برای افزودن به علاقه‌مندی‌ها وارد شوید.',
                    'loginUrl' => url('login'),
                ], 401);
            }

            Session::set('intended_url', $_SERVER['HTTP_REFERER'] ?? url(''));
            Flash::info('برای افزودن به علاقه‌مندی‌ها وارد حساب کاربری خود شوید.');
            redirect('login');
        }

        $added = Wishlist::toggle((int) Auth::id(), $productId);

        if ($this->wantsJson()) {
            $this->json([
                'ok'      => true,
                'added'   => $added,
                'count'   => Wishlist::countFor((int) Auth::id()),
                'message' => $added ? 'به علاقه‌مندی‌ها اضافه شد.' : 'از علاقه‌مندی‌ها حذف شد.',
            ]);
        }

        Flash::success($added ? 'به علاقه‌مندی‌ها اضافه شد.' : 'از علاقه‌مندی‌ها حذف شد.');
        redirect($_SERVER['HTTP_REFERER'] ?? 'account/wishlist');
    }

    public function remove(): void
    {
        $this->requireLogin();
        Csrf::check();

        Wishlist::remove((int) Auth::id(), $this->intInput('product_id'));

        Flash::success('از علاقه‌مندی‌ها حذف شد.');
        redirect('account/wishlist');
    }

    private function wantsJson(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }
}
