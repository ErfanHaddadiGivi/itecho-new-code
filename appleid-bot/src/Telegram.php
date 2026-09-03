<?php

namespace AppleBot;

/**
 * کلاینت Telegram Bot API با cURL خام — بدون هیچ کتابخانه‌ای.
 *
 * روی هاست اشتراکی بدون Composer/SSH کار می‌کند. همهٔ فراخوانی‌ها از
 * متد api() عبور می‌کنند که خطاها را در لاگ ثبت می‌کند (بدون دادهٔ حساس).
 */
class Telegram
{
    private string $token;
    private string $apiBase;
    private Logger $log;

    public function __construct(string $token, Logger $log)
    {
        $this->token   = $token;
        $this->apiBase = 'https://api.telegram.org/bot' . $token . '/';
        $this->log     = $log;
    }

    /**
     * فراخوانی خام یک متد Bot API. خروجی: آرایهٔ نتیجه یا null در صورت خطا.
     */
    public function api(string $method, array $params = []): ?array
    {
        $ch = curl_init($this->apiBase . $method);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $params,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $body === '') {
            $this->log->error('telegram_curl_failed', ['method' => $method, 'error' => $err, 'http' => $code]);
            return null;
        }

        $json = json_decode($body, true);
        if (!is_array($json) || empty($json['ok'])) {
            $this->log->error('telegram_api_error', [
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

    public function sendPhoto(int $chatId, string $fileId, string $caption = '', ?array $replyMarkup = null): ?array
    {
        $params = [
            'chat_id'    => $chatId,
            'photo'      => $fileId,
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

    /** حذف دکمه‌های پیام (بعد از اقدام ادمین) */
    public function clearReplyMarkup(int $chatId, int $messageId): ?array
    {
        return $this->api('editMessageReplyMarkup', [
            'chat_id'      => $chatId,
            'message_id'   => $messageId,
            'reply_markup' => json_encode(['inline_keyboard' => []]),
        ]);
    }

    // --- سازنده‌های کیبورد اینلاین ---

    /** یک ردیف دکمه از آرایهٔ [ [متن، callback_data], ... ] */
    public static function inlineRow(array $buttons): array
    {
        $row = [];
        foreach ($buttons as [$text, $data]) {
            $row[] = ['text' => $text, 'callback_data' => $data];
        }
        return $row;
    }

    /** ساخت کیبورد اینلاین از چند ردیف */
    public static function inlineKeyboard(array $rows): array
    {
        return ['inline_keyboard' => $rows];
    }
}
