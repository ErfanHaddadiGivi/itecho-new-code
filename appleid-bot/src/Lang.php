<?php

namespace AppleBot;

/**
 * بارگذاری متن‌های فارسی و جای‌گذاری {placeholder}ها.
 */
class Lang
{
    private array $strings;

    public function __construct(string $file)
    {
        $this->strings = require $file;
    }

    public function get(string $key, array $params = []): string
    {
        $text = $this->strings[$key] ?? $key;
        foreach ($params as $k => $v) {
            $text = str_replace('{' . $k . '}', (string) $v, $text);
        }
        return $text;
    }
}
