<?php

namespace AppleBot;

/**
 * ظرفِ وابستگی‌های مشترکِ ربات + چند کمک‌کار (لیست ادمین‌ها، اطلاع به ادمین‌ها،
 * ترجمه، قالب‌بندی مبلغ). به StateMachine و AdminActions پاس داده می‌شود.
 */
class BotContext
{
    public Db $db;
    public Messenger $tg;
    public Orders $orders;
    public PartnerService $partners;
    public Settings $settings;
    public Lang $lang;
    public Conversations $conv;
    public Logger $log;

    /** @var int[] شناسه‌های ادمینِ بوت‌استرپ از config */
    private array $configAdminIds;

    public function __construct(
        Db $db,
        Messenger $tg,
        Orders $orders,
        PartnerService $partners,
        Settings $settings,
        Lang $lang,
        Conversations $conv,
        Logger $log,
        array $configAdminIds
    ) {
        $this->db             = $db;
        $this->tg             = $tg;
        $this->orders         = $orders;
        $this->partners       = $partners;
        $this->settings       = $settings;
        $this->lang           = $lang;
        $this->conv           = $conv;
        $this->log            = $log;
        $this->configAdminIds = array_map('intval', $configAdminIds);
    }

    /** فهرست شناسهٔ ادمین‌ها: config + جدول admins (فعال) */
    public function adminTelegramIds(): array
    {
        $ids = $this->configAdminIds;
        foreach ($this->db->fetchAll('SELECT telegram_user_id FROM admins WHERE is_active = 1') as $r) {
            $ids[] = (int) $r['telegram_user_id'];
        }
        return array_values(array_unique($ids));
    }

    public function isAdmin(int $telegramUserId): bool
    {
        return in_array($telegramUserId, $this->adminTelegramIds(), true);
    }

    public function notifyAdmins(string $text, ?array $keyboard = null): void
    {
        foreach ($this->adminTelegramIds() as $adminId) {
            $this->tg->sendMessage($adminId, $text, $keyboard);
        }
    }

    public function t(string $key, array $params = []): string
    {
        return $this->lang->get($key, $params);
    }

    public function money(int $amount): string
    {
        return number_format($amount);
    }
}
