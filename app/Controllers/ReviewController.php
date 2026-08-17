<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Product;
use App\Models\Review;

/**
 * ثبت و مشاهده نظرات توسط مشتری.
 */
class ReviewController extends Controller
{
    /**
     * نظرهای خود کاربر + محصولاتی که خریده و هنوز نظر نداده
     */
    public function mine(): void
    {
        $this->requireLogin();

        $userId = (int) Auth::id();

        $this->view('site/account/reviews', [
            'title'    => 'نظرهای من',
            'reviews'  => Review::forUser($userId),
            'toReview' => Review::awaitingReview($userId),
        ], 'site');
    }

    /**
     * ثبت نظر جدید برای یک محصول
     */
    public function store(): void
    {
        $this->requireLogin('برای ثبت نظر وارد حساب کاربری خود شوید.');
        Csrf::check();

        $productId = $this->intInput('product_id');
        $product   = Product::find($productId);

        if ($product === null) {
            $this->notFound('محصول پیدا نشد');
        }

        try {
            Review::submit(
                (int) Auth::id(),
                $productId,
                $this->intInput('rating'),
                (string) $this->input('title'),
                (string) $this->input('comment')
            );

            Flash::success('نظر شما ثبت شد و پس از تایید مدیر نمایش داده می‌شود.');
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
        }

        redirect('product/' . $product['slug'] . '#reviews');
    }
}
