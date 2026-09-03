<?php

namespace AppleBot;

/**
 * وضعیت همکار (partner) + دفتر حساب (ledger).
 *
 * balance = بدهیِ جاریِ همکار (تومان).
 *   - charge (ثبت روی حساب): balance افزایش می‌یابد.
 *   - settlement (تسویه): balance کاهش می‌یابد.
 *   - adjustment: اصلاح دستی (+ یا -).
 */
class PartnerService
{
    private Db $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function getByTelegramId(int $telegramUserId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM partners WHERE telegram_user_id = ? LIMIT 1',
            [$telegramUserId]
        );
    }

    public function isApprovedPartner(int $telegramUserId): bool
    {
        $p = $this->getByTelegramId($telegramUserId);
        return $p !== null && $p['status'] === 'approved';
    }

    /** نوع قیمتی که به این کاربر نمایش داده می‌شود */
    public function priceTypeFor(int $telegramUserId): string
    {
        return $this->isApprovedPartner($telegramUserId) ? 'partner' : 'regular';
    }

    public function pendingPartners(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM partners WHERE status = 'pending' ORDER BY created_at"
        );
    }

    public function approve(int $partnerId, int $adminTelegramUserId): void
    {
        $this->db->update('partners', [
            'status'      => 'approved',
            'approved_by' => $adminTelegramUserId,
            'approved_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $partnerId]);
        Audit::log($this->db, $adminTelegramUserId, 'partner_approve', 'partner', $partnerId);
    }

    public function reject(int $partnerId, int $adminTelegramUserId): void
    {
        $this->db->update('partners', ['status' => 'rejected'], 'id = :id', ['id' => $partnerId]);
        Audit::log($this->db, $adminTelegramUserId, 'partner_reject', 'partner', $partnerId);
    }

    /**
     * ثبت مبلغ روی حساب همکار (بدهکار شدن) — هنگام «ثبت روی حساب همکار».
     */
    public function charge(int $partnerId, ?int $orderId, int $amount, int $adminTelegramUserId, ?string $note = null): void
    {
        $this->ledgerEntry($partnerId, $orderId, 'charge', $amount, +$amount, $adminTelegramUserId, $note);
    }

    /**
     * تسویه (کاهش بدهی همکار).
     */
    public function settle(int $partnerId, int $amount, int $adminTelegramUserId, ?string $note = null): int
    {
        return $this->ledgerEntry($partnerId, null, 'settlement', $amount, -$amount, $adminTelegramUserId, $note);
    }

    /**
     * درج یک ردیف دفتر و به‌روزرسانی موجودی، به‌صورت تراکنشی و اتمیک.
     * @return int موجودی جدید
     */
    private function ledgerEntry(int $partnerId, ?int $orderId, string $type, int $amount, int $delta, int $adminTelegramUserId, ?string $note): int
    {
        $this->db->beginTransaction();
        try {
            // قفل ردیف همکار تا موجودی هم‌زمان خراب نشود
            $partner = $this->db->fetch('SELECT id, balance FROM partners WHERE id = ? FOR UPDATE', [$partnerId]);
            if ($partner === null) {
                throw new \RuntimeException('partner not found');
            }

            $balanceAfter = (int) $partner['balance'] + $delta;

            $this->db->insert('partner_ledger', [
                'partner_id'             => $partnerId,
                'order_id'               => $orderId,
                'type'                   => $type,
                'amount'                 => $amount,
                'balance_after'          => $balanceAfter,
                'admin_telegram_user_id' => $adminTelegramUserId,
                'note'                   => $note,
            ]);

            $this->db->update('partners', ['balance' => $balanceAfter], 'id = :id', ['id' => $partnerId]);

            Audit::log($this->db, $adminTelegramUserId, 'partner_' . $type, 'partner', $partnerId, [
                'amount'        => $amount,
                'balance_after' => $balanceAfter,
                'order_id'      => $orderId,
            ]);

            $this->db->commit();
            return $balanceAfter;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** خلاصه‌حساب همکار + چند تراکنش اخیر */
    public function summary(int $partnerId, int $limit = 10): array
    {
        $partner = $this->db->fetch('SELECT * FROM partners WHERE id = ? LIMIT 1', [$partnerId]);
        $entries = $this->db->fetchAll(
            'SELECT * FROM partner_ledger WHERE partner_id = ? ORDER BY id DESC LIMIT ' . (int) $limit,
            [$partnerId]
        );
        return ['partner' => $partner, 'entries' => $entries];
    }
}
