<?php

namespace AppleBot;

/**
 * لاگ فایلی ساده. پیام‌ها انگلیسی‌اند و هرگز نباید دادهٔ حساس (کد تأیید،
 * اطلاعات شخصی، توکن) در آن‌ها بیاید. فقط برای خطاها و رخدادهای سیستمی.
 */
class Logger
{
    private string $file;
    private bool $debug;

    public function __construct(string $dir, bool $debug = false)
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $this->file  = rtrim($dir, '/') . '/bot.log';
        $this->debug = $debug;
    }

    public function error(string $event, array $context = []): void
    {
        $this->write('ERROR', $event, $context);
    }

    public function info(string $event, array $context = []): void
    {
        if ($this->debug) {
            $this->write('INFO', $event, $context);
        }
    }

    private function write(string $level, string $event, array $context): void
    {
        $line = sprintf(
            "[%s] %s %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $event,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
        );
        @file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);
    }
}
