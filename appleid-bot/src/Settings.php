<?php

namespace AppleBot;

/**
 * خواندن/نوشتن تنظیمات کلید-مقداری از جدول settings (با کش در حافظه).
 */
class Settings
{
    private Db $db;
    private array $cache = [];
    private bool $loaded = false;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }
        foreach ($this->db->fetchAll('SELECT `key`, `value` FROM settings') as $row) {
            $this->cache[$row['key']] = $row['value'];
        }
        $this->loaded = true;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $this->load();
        return $this->cache[$key] ?? $default;
    }

    public function getInt(string $key, int $default = 0): int
    {
        $val = $this->get($key);
        return ($val === null || $val === '') ? $default : (int) $val;
    }

    public function set(string $key, string $value): void
    {
        $this->db->run(
            'INSERT INTO settings (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$key, $value]
        );
        $this->cache[$key] = $value;
    }
}
