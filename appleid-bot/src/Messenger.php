<?php

namespace AppleBot;

/**
 * کلاینت Bot API با cURL خام — بدون هیچ کتابخانه‌ای.
 *
 * برای «پیام‌رسان بله» است که API آن با تلگرام سازگار است؛ فقط آدرس پایه
 * فرق دارد (از config می‌آید):
 *   - بله:    https://tapi.bale.ai/bot
 *   - تلگرام: https://api.telegram.org/bot
 *
 * روی هاست اشتراکی بدون Composer/SSH کار می‌کند.
 */
class Messenger
{
    private string $apiBase;
    private Logger $log;

    private string $fileBase;

    public function __construct(string $token, string $apiBaseUrl, Logger $log)
    {
        $base           = rtrim($apiBaseUrl !== '' ? $apiBaseUrl : 'https://tapi.bale.ai/bot', '/');
        $this->apiBase  = $base . $token . '/';
        // آدرس دانلود فایل: بله/تلگرام هر دو مسیر «/file/bot» دارند.
        $this->fileBase = str_replace('/bot', '/file/bot', $base) . $token . '/';
        $this->log      = $log;
    }

    /**
     * دانلود یک فایل (مثل عکسِ تحویلِ اپل‌آیدی) با file_id.
     * خروجی: ['bytes' => محتوا, 'mime' => نوع] یا null.
     */
    public function downloadFile(string $fileId): ?array
    {
        $info = $this->api('getFile', ['file_id' => $fileId]);
        $path = is_array($info) ? (string) ($info['file_path'] ?? '') : '';
        if ($path === '') {
            return null;
        }
        $ch = curl_init($this->fileBase . ltrim($path, '/'));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 12,
        ]);
        $bytes = curl_exec($ch);
        $mime  = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!is_string($bytes) || $bytes === '' || $code >= 400) {
            return null;
        }
        return ['bytes' => $bytes, 'mime' => $mime !== '' ? $mime : 'image/jpeg'];
    }

    /**
     * فراخوانی خام یک متد. خروجی: آرایهٔ نتیجه یا null در صورت خطا.
     * $params می‌تواند شامل CURLFile برای آپلود فایل باشد.
     */
    public function api(string $method, array $params = []): ?array
    {
        $ch = curl_init($this->apiBase . $method);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $params,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 12,
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $body === '') {
            $this->log->error('messenger_curl_failed', ['method' => $method, 'error' => $err, 'http' => $code]);
            return null;
        }

        $json = json_decode($body, true);
        if (!is_array($json) || empty($json['ok'])) {
            $this->log->error('messenger_api_error', [
                'method'      => $method,
                'http'        => $code,
                'description' => is_array($json) ? ($json['description'] ?? '') : 'invalid json',
            ]);
            return null;
        }

        return $json['result'] ?? [];
    }

    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null, bool $html = true): ?array
    {
        $params = [
            'chat_id'                  => $chatId,
            'text'                     => $text,
            'disable_web_page_preview' => true,
        ];
        if ($html) {
            $params['parse_mode'] = 'HTML';
        }
        if ($replyMarkup !== null) {
            $params['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }
        return $this->api('sendMessage', $params);
    }

    /** ارسال عکس با file_id یا URL */
    public function sendPhoto(int $chatId, string $photo, string $caption = '', ?array $replyMarkup = null): ?array
    {
        $params = ['chat_id' => $chatId, 'photo' => $photo, 'caption' => $caption, 'parse_mode' => 'HTML'];
        if ($replyMarkup !== null) {
            $params['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }
        return $this->api('sendPhoto', $params);
    }

    /** آپلود یک فایل عکسِ روی هاست (مثلاً فیش واریزی سایت) به‌صورت multipart */
    public function sendPhotoFile(int $chatId, string $filePath, string $caption = '', ?array $replyMarkup = null): ?array
    {
        if (!is_file($filePath)) {
            return null;
        }
        $params = [
            'chat_id'    => $chatId,
            'photo'      => new \CURLFile($filePath),
            'caption'    => $caption,
            'parse_mode' => 'HTML',
        ];
        if ($replyMarkup !== null) {
            $params['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }
        return $this->api('sendPhoto', $params);
    }

    public function forwardMessage(int $toChatId, int $fromChatId, int $messageId): ?array
    {
        return $this->api('forwardMessage', [
            'chat_id'      => $toChatId,
            'from_chat_id' => $fromChatId,
            'message_id'   => $messageId,
        ]);
    }

    public function answerCallbackQuery(string $callbackId, string $text = '', bool $alert = false): ?array
    {
        return $this->api('answerCallbackQuery', [
            'callback_query_id' => $callbackId,
            'text'              => $text,
            'show_alert'        => $alert,
        ]);
    }

    public function editMessageText(int $chatId, int $messageId, string $text, ?array $replyMarkup = null): ?array
    {
        $params = [
            'chat_id'                  => $chatId,
            'message_id'               => $messageId,
            'text'                     => $text,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true,
        ];
        if ($replyMarkup !== null) {
            $params['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }
        return $this->api('editMessageText', $params);
    }

    public function clearReplyMarkup(int $chatId, int $messageId): ?array
    {
        return $this->api('editMessageReplyMarkup', [
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'reply_markup' => json_encode(['inline_keyboard' => []]),
        ]);
    }

    // --- سازنده‌های کیبورد اینلاین ---

    public static function inlineRow(array $buttons): array
    {
        $row = [];
        foreach ($buttons as [$text, $data]) {
            $row[] = ['text' => $text, 'callback_data' => $data];
        }
        return $row;
    }

    public static function inlineKeyboard(array $rows): array
    {
        return ['inline_keyboard' => $rows];
    }
}
