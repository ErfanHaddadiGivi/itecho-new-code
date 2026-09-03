<?php

namespace AppleBot;

/**
 * ثبت رخداد در audit_log برای هر اقدام مالی/مدیریتی.
 * details نباید دادهٔ حساس (کد، اطلاعات شخصی) داشته باشد.
 */
class Audit
{
    public static function log(
        Db $db,
        int $adminTelegramUserId,
        string $action,
        string $entityType,
        string|int $entityId,
        ?array $details = null
    ): void {
        $db->insert('audit_log', [
            'admin_telegram_user_id' => $adminTelegramUserId,
            'action'                 => $action,
            'entity_type'            => $entityType,
            'entity_id'              => (string) $entityId,
            'details_json'           => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }
}
