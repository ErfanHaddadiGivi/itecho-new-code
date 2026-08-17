<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Paginator;
use App\Models\Review;

/**
 * تایید و مدیریت نظرات در پنل.
 *
 * طبق PRD، هیچ نظری بدون تایید مدیر در سایت نمایش داده نمی‌شود.
 */
class ReviewController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): void
    {
        Auth::requireAdmin();

        $filters = [
            // پیش‌فرض: نظرهای در انتظار تایید، چون کار اصلی ادمین همین است
            'status' => (string) $this->input('status', 'pending'),
            'q'      => (string) $this->input('q'),
        ];

        $total     = Review::adminCount($filters);
        $paginator = new Paginator($total, self::PER_PAGE, $this->intInput('page', 1));

        $this->view('admin/reviews/index', [
            'title'     => 'نظرات',
            'reviews'   => Review::adminList($filters, $paginator->limit(), $paginator->offset()),
            'paginator' => $paginator,
            'total'     => $total,
            'filters'   => $filters,
            'counts'    => [
                'pending'  => Review::adminCount(['status' => 'pending']),
                'approved' => Review::adminCount(['status' => 'approved']),
                'rejected' => Review::adminCount(['status' => 'rejected']),
            ],
        ], 'admin');
    }

    /**
     * تایید یا رد نظر (به همراه پاسخ اختیاری فروشگاه)
     */
    public function updateStatus(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        try {
            Review::setStatus(
                (int) $id,
                (string) $this->input('status'),
                (string) $this->input('admin_reply')
            );

            Flash::success('وضعیت نظر به‌روزرسانی شد.');
        } catch (\RuntimeException $e) {
            Flash::error($e->getMessage());
        }

        redirect($_SERVER['HTTP_REFERER'] ?? 'admin/reviews');
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();
        Csrf::check();

        Review::deleteAndRecalc((int) $id);

        Flash::success('نظر حذف شد.');
        redirect('admin/reviews');
    }
}
