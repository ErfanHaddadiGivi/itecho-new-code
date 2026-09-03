<?php
/**
 * تنها نقطهٔ ورود ربات (Webhook تلگرام).
 *
 * امنیت:
 *   - فقط POST.
 *   - هدر X-Telegram-Bot-Api-Secret-Token باید با webhook_secret برابر باشد.
 *   - نامطابق = رد (بدون افشای چیزی).
 *
 * تلگرام فقط منتظر پاسخ 200 است؛ کارها هم‌زمان انجام و همیشه 200 برمی‌گردانیم.
 */

declare(strict_types=1);

$services = require __DIR__ . '/bootstrap.php';

/** @var array $config */
$config       = $services['config'];
$ctx          = $services['ctx'];
$stateMachine = $services['stateMachine'];
$adminActions = $services['adminActions'];
$rateLimiter  = $services['rateLimiter'];
$log          = $services['log'];

// --- فقط POST ---
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit;
}

// --- بررسی توکن مخفی وب‌هوک ---
// دو روش (هرکدام که پیام‌رسان پشتیبانی کند): هدر مخفی، یا پارامتر ?s= در آدرس.
$expected   = (string) ($config['webhook_secret'] ?? '');
$fromHeader = (string) ($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '');
$fromQuery  = (string) ($_GET['s'] ?? '');
$ok = $expected !== '' && (
    ($fromHeader !== '' && hash_equals($expected, $fromHeader)) ||
    ($fromQuery  !== '' && hash_equals($expected, $fromQuery))
);
if (!$ok) {
    http_response_code(403);
    exit;
}

// از این‌جا به بعد همیشه 200 (تا تلگرام دوباره نفرستد)
http_response_code(200);

$raw    = file_get_contents('php://input') ?: '';
$update = json_decode($raw, true);
if (!is_array($update)) {
    exit;
}

try {
    // -------------------- پیام‌ها --------------------
    if (isset($update['message']) && isset($update['message']['from'])) {
        $message  = $update['message'];
        $userId   = (int) $message['from']['id'];
        $chatId   = (int) ($message['chat']['id'] ?? $userId);
        $username = $message['from']['username'] ?? null;

        // ربات‌ها را نادیده بگیر
        if (!empty($message['from']['is_bot'])) {
            exit;
        }

        // محدودسازی نرخ
        if (!$rateLimiter->allow($userId)) {
            $ctx->tg->sendMessage($chatId, $ctx->t('rate_limited'));
            exit;
        }

        $text  = isset($message['text']) ? trim((string) $message['text']) : '';
        $conv  = $ctx->conv->get($userId);
        $isAdm = $ctx->isAdmin($userId);

        $adminCommand = $text !== '' && preg_match('#^/(partners|addpartner|ledger|settle|setcard|admin|help)\b#', $text);
        $adminState   = str_starts_with($conv['state'], 'ADMIN_');

        if ($text !== '' && str_starts_with($text, '/start')) {
            // /start همیشه جریان خرید را از نو شروع می‌کند (حتی برای ادمین)
            $stateMachine->handleMessage($userId, $chatId, $username, $message);
        } elseif ($isAdm && ($adminCommand || $adminState)) {
            $adminActions->handleMessage($userId, $chatId, $text, $conv);
        } else {
            $stateMachine->handleMessage($userId, $chatId, $username, $message);
        }
        exit;
    }

    // -------------------- کلیک دکمه‌ها --------------------
    if (isset($update['callback_query'])) {
        $cq         = $update['callback_query'];
        $userId     = (int) $cq['from']['id'];
        $data       = (string) ($cq['data'] ?? '');
        $callbackId = (string) ($cq['id'] ?? '');
        $msg        = $cq['message'] ?? [];
        $chatId     = (int) ($msg['chat']['id'] ?? $userId);
        $messageId  = (int) ($msg['message_id'] ?? 0);
        $username   = $cq['from']['username'] ?? null;

        if (!$rateLimiter->allow($userId)) {
            $ctx->tg->answerCallbackQuery($callbackId, $ctx->t('rate_limited'), true);
            exit;
        }

        $isAdminData = str_starts_with($data, 'ord:') || str_starts_with($data, 'pa:');
        if ($isAdminData && $ctx->isAdmin($userId)) {
            $adminActions->handleCallback($userId, $chatId, $data, $messageId, $callbackId);
        } else {
            $stateMachine->handleCallback($userId, $chatId, $username, $data, $messageId, $callbackId);
        }
        exit;
    }
} catch (\Throwable $e) {
    // هرگز جزئیات به کاربر نشان داده نمی‌شود؛ فقط لاگ سیستمی (بدون دادهٔ حساس)
    $log->error('webhook_exception', [
        'message' => $e->getMessage(),
        'file'    => basename($e->getFile()),
        'line'    => $e->getLine(),
    ]);
    exit;
}
