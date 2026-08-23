<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\SyncLog;
use App\Services\SheetCsv;
use App\Services\SheetSync;

/**
 * Google Sheet / CSV product sync — report viewer and manual CSV upload.
 *
 * The scheduled Google Sheet push lands on the standalone api/sheet-sync.php
 * endpoint. This controller adds a manual path: the owner can upload a CSV with
 * the same columns and it is fed through the very same SheetSync logic.
 */
class SyncLogController extends Controller
{
    /** Max accepted upload size for the CSV (2 MB is plenty for a catalogue). */
    private const MAX_CSV_BYTES = 2 * 1024 * 1024;

    /** Safety cap on the number of rows accepted from one upload. */
    private const MAX_ROWS = 10000;

    public function index(): void
    {
        Auth::requireAdmin();

        $this->view('admin/sync-logs/index', [
            'title' => 'همگام‌سازی محصولات',
            'logs'  => SyncLog::recent(50),
        ], 'admin');
    }

    /**
     * Handle a manual CSV upload and run the sync.
     */
    public function upload(): void
    {
        Auth::requireAdmin();
        Csrf::check();

        $file = $_FILES['csv'] ?? null;

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            Flash::error('لطفاً یک فایل CSV انتخاب کنید.');
            redirect('admin/sync-logs');
        }

        if (($file['error'] ?? 0) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            Flash::error('آپلود فایل ناموفق بود. دوباره تلاش کنید.');
            redirect('admin/sync-logs');
        }

        if (($file['size'] ?? 0) > self::MAX_CSV_BYTES) {
            Flash::error('حجم فایل بیش از حد مجاز است (حداکثر ۲ مگابایت).');
            redirect('admin/sync-logs');
        }

        $content = file_get_contents($file['tmp_name']);
        if ($content === false || trim($content) === '') {
            Flash::error('فایل خالی بود.');
            redirect('admin/sync-logs');
        }

        $rows = SheetCsv::parse($content);
        if ($rows === []) {
            Flash::error('هیچ ردیف معتبری در فایل پیدا نشد. ردیف اول باید سرستون‌ها باشد.');
            redirect('admin/sync-logs');
        }

        if (count($rows) > self::MAX_ROWS) {
            Flash::error('تعداد ردیف‌ها بیش از حد مجاز است.');
            redirect('admin/sync-logs');
        }

        // Deactivation of products missing from the file is opt-in: it is only
        // safe when this CSV is the full catalogue, not a partial price update.
        $deactivateMissing = !empty($_POST['deactivate_missing']);

        try {
            $report = (new SheetSync())->run($rows, 'csv_upload', $deactivateMissing);
        } catch (\Throwable $e) {
            Flash::error('پردازش فایل با خطا مواجه شد. جزئیات در گزارش خطای سرور ثبت شد.');
            error_log('[sheet-sync/csv] ' . $e->getMessage());
            redirect('admin/sync-logs');
        }

        $s = $report['summary'];
        Flash::success(sprintf(
            'فایل پردازش شد — افزوده: %s، به‌روزرسانی: %s، غیرفعال‌شده: %s، ردشده: %s.',
            fa_digits((string) $s['inserted']),
            fa_digits((string) $s['updated']),
            fa_digits((string) $s['deactivated']),
            fa_digits((string) $s['rejected'])
        ));

        redirect('admin/sync-logs');
    }
}
