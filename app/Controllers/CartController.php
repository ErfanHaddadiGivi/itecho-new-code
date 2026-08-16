<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Cart;
use App\Models\Setting;

/**
 * سبد خرید.
 *
 * مهمان هم می‌تواند سبد داشته باشد؛ ورود فقط هنگام تسویه‌حساب لازم است
 * (مرحله ۴ پیاده‌سازی می‌شود).
 */
class CartController extends Controller
{
    public function index(): void
    {
        $items   = Cart::items();
        $summary = Cart::summary($items);

        $this->view('site/cart', [
            'title'     => 'سبد خرید',
            'items'     => $items,
            'summary'   => $summary,
            'pickupFee' => Setting::getInt('pickup_fee', 0),
        ], 'site');
    }

    public function add(): void
    {
        Csrf::check();

        $productId = $this->intInput('product_id');
        $variantId = $this->intInput('variant_id');
        $quantity  = max(1, $this->intInput('quantity', 1));

        try {
            Cart::add($productId, $variantId > 0 ? $variantId : null, $quantity);
        } catch (\RuntimeException $e) {
            // درخواست AJAX پاسخ JSON می‌گیرد، فرم معمولی پیام فلش
            if ($this->wantsJson()) {
                $this->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }

            Flash::error($e->getMessage());
            $this->backToProduct();
        }

        if ($this->wantsJson()) {
            $this->json([
                'ok'      => true,
                'count'   => Cart::count(),
                'message' => 'محصول به سبد خرید اضافه شد.',
            ]);
        }

        Flash::success('محصول به سبد خرید اضافه شد.');
        redirect('cart');
    }

    public function update(): void
    {
        Csrf::check();

        Cart::updateQuantity($this->intInput('item_id'), $this->intInput('quantity'));

        if ($this->wantsJson()) {
            $items   = Cart::items();
            $summary = Cart::summary($items);

            $this->json([
                'ok'          => true,
                'count'       => $summary['count'],
                'items_total' => money($summary['items_total']),
            ]);
        }

        redirect('cart');
    }

    public function remove(): void
    {
        Csrf::check();

        Cart::remove($this->intInput('item_id'));

        Flash::success('کالا از سبد خرید حذف شد.');
        redirect('cart');
    }

    // ------------------------------------------------------------------

    /**
     * آیا درخواست از جاوااسکریپت آمده است؟
     */
    private function wantsJson(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    private function backToProduct(): never
    {
        $back = $_SERVER['HTTP_REFERER'] ?? '';
        redirect($back !== '' ? $back : 'cart');
    }
}
