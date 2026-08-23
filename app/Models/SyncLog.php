<?php

namespace App\Models;

use App\Core\Database;

/**
 * Persisted report of each product-sync run.
 *
 * One row is written per run so the store owner can review the outcome of
 * every sync from the admin panel (see Admin\SyncLogController).
 */
class SyncLog extends Model
{
    protected static string $table = 'sync_logs';

    /**
     * Store the outcome of one sync run.
     *
     * @param string $source   where the batch came from, e.g. 'google_sheet'
     * @param int    $received number of rows received in the payload
     * @param array  $summary  counts: inserted, updated, deactivated, rejected
     * @param array  $rejected list of [sku, reason] for rejected rows
     */
    public static function record(string $source, int $received, array $summary, array $rejected): int
    {
        // Best-effort: if the sync_logs table has not been created yet, the sync
        // itself should still succeed. We log a clear hint instead of failing.
        try {
            return Database::insert('sync_logs', [
                'source'        => $source,
                'received'      => $received,
                'inserted'      => (int) ($summary['inserted'] ?? 0),
                'updated'       => (int) ($summary['updated'] ?? 0),
                'deactivated'   => (int) ($summary['deactivated'] ?? 0),
                'rejected'      => (int) ($summary['rejected'] ?? 0),
                'rejected_rows' => json_encode(
                    array_values($rejected),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
            ]);
        } catch (\PDOException $e) {
            error_log('[sheet-sync] could not write sync_logs (run database/sync-logs.sql?): ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Whether the sync_logs table exists. Used by the admin page to show a
     * clear "import the migration" notice instead of crashing with a 500.
     */
    public static function tableReady(): bool
    {
        try {
            Database::fetchValue('SELECT 1 FROM sync_logs LIMIT 1');
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Most recent runs, newest first.
     */
    public static function recent(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        return Database::fetchAll(
            'SELECT * FROM sync_logs ORDER BY id DESC LIMIT ' . $limit
        );
    }
}
