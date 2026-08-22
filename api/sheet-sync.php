<?php
/**
 * Google Sheet product-sync endpoint.
 *
 * The store owner's Google Apps Script pushes a JSON batch here on a daily
 * schedule (see docs/SHEET-SYNC.md). This endpoint is deliberately NOT behind
 * the admin session: it is authenticated only by a shared secret token carried
 * inside the JSON body, and it accepts POST only.
 *
 * It is a standalone entry point (not routed through index.php), so it keeps
 * working regardless of the app's URL rewriting or install subfolder.
 *
 * All identifiers, comments and error messages are intentionally in English.
 */

declare(strict_types=1);

// Always emit clean JSON — never let a PHP notice leak into the response body.
error_reporting(E_ALL);
ini_set('display_errors', '0');

// ---------------------------------------------------------------------
// Minimal bootstrap: autoloader + config + helpers (no session needed).
// ---------------------------------------------------------------------
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');

spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $file = APP_PATH . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once CONFIG_PATH . '/config.php';
require_once APP_PATH . '/Core/helpers.php';

/**
 * Handle one sync request as pure data → returns [httpStatus, responsePayload].
 *
 * Kept free of any request/response globals so it can be tested directly.
 *
 * @return array{0:int,1:array}
 */
function sheet_sync_handle(string $method, string $rawBody, string $serverToken): array
{
    // 1. POST only.
    if ($method !== 'POST') {
        return [405, ['success' => false, 'error' => 'method not allowed']];
    }

    // 2. Server must have a token configured (fail closed).
    if ($serverToken === '') {
        return [500, ['success' => false, 'error' => 'sync endpoint not configured']];
    }

    // 3. Decode the JSON body.
    $data = json_decode($rawBody, true);
    if (!is_array($data)) {
        return [400, ['success' => false, 'error' => 'invalid JSON body']];
    }

    // 4. Authenticate the token (timing-safe compare).
    $givenToken = is_string($data['token'] ?? null) ? $data['token'] : '';
    if ($givenToken === '' || !hash_equals($serverToken, $givenToken)) {
        return [401, ['success' => false, 'error' => 'unauthorized']];
    }

    // 5. The products array is required. A missing array is a hard error so a
    //    malformed payload can never trigger a catalogue-wide deactivation.
    $products = $data['products'] ?? null;
    if (!is_array($products)) {
        return [400, ['success' => false, 'error' => 'missing products array']];
    }
    if (count($products) > 10000) {
        return [413, ['success' => false, 'error' => 'too many rows']];
    }

    // 6. Run the sync.
    try {
        $report = (new App\Services\SheetSync())->run($products);
    } catch (\Throwable $e) {
        error_log('[sheet-sync] ' . $e->getMessage());
        $error = config('debug') ? ('sync failed: ' . $e->getMessage()) : 'sync failed';
        return [500, ['success' => false, 'error' => $error]];
    }

    return [200, $report];
}

/**
 * Emit a JSON response and stop.
 */
function sheet_sync_respond(int $status, array $payload): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ---------------------------------------------------------------------
// Dispatch the real request only when actually served over HTTP. Under the
// CLI SAPI this file just defines the functions above (used by the tests).
// ---------------------------------------------------------------------
if (PHP_SAPI !== 'cli') {
    [$status, $payload] = sheet_sync_handle(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        file_get_contents('php://input') ?: '',
        (string) config('sheet_sync_token', '')
    );
    sheet_sync_respond($status, $payload);
}
