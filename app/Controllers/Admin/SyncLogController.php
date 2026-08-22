<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\SyncLog;

/**
 * Read-only viewer for Google Sheet sync reports.
 *
 * Shows the outcome of recent sync runs so the store owner can confirm what
 * was inserted, updated, deactivated or rejected on each run.
 */
class SyncLogController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $this->view('admin/sync-logs/index', [
            'title' => 'همگام‌سازی گوگل‌شیت',
            'logs'  => SyncLog::recent(50),
        ], 'admin');
    }
}
