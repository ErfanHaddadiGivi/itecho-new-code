<?php

namespace AppleBot;

/**
 * وضعیت ماشین حالتِ هر کاربر در جدول conversations.
 * context یک آرایهٔ غیرحساس است (فقط انتخاب‌ها؛ نه دادهٔ شخصی).
 */
class Conversations
{
    private Db $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function get(int $userId): array
    {
        $row = $this->db->fetch('SELECT * FROM conversations WHERE telegram_user_id = ? LIMIT 1', [$userId]);
        if ($row === null) {
            return ['state' => States::START, 'context' => [], 'active_order_id' => null];
        }
        $ctx = [];
        if (!empty($row['context_json'])) {
            $decoded = json_decode($row['context_json'], true);
            if (is_array($decoded)) {
                $ctx = $decoded;
            }
        }
        return [
            'state'           => $row['state'],
            'context'         => $ctx,
            'active_order_id' => $row['active_order_id'] !== null ? (int) $row['active_order_id'] : null,
        ];
    }

    public function save(int $userId, string $state, array $context = [], ?int $activeOrderId = null): void
    {
        $this->db->run(
            'INSERT INTO conversations (telegram_user_id, state, context_json, active_order_id)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE state = VALUES(state),
                                     context_json = VALUES(context_json),
                                     active_order_id = VALUES(active_order_id)',
            [$userId, $state, json_encode($context, JSON_UNESCAPED_UNICODE), $activeOrderId]
        );
    }

    public function reset(int $userId): void
    {
        $this->save($userId, States::START, [], null);
    }
}
