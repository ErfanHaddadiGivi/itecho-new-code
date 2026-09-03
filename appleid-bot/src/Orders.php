<?php

namespace AppleBot;

/**
 * ساخت و مدیریت سفارش‌ها. فیلدهای شخصی و کد/کریدنشال با AES رمزنگاری می‌شوند
 * و هرگز به‌صورت خام در دیتابیس یا لاگ نمی‌روند.
 */
class Orders
{
    private Db $db;
    private Crypto $crypto;

    public function __construct(Db $db, Crypto $crypto)
    {
        $this->db     = $db;
        $this->crypto = $crypto;
    }

    /** ساخت سفارش پیش‌نویس از ربات (بعد از انتخاب محصول) */
    public function createDraft(int $telegramUserId, ?string $username, int $productId, string $priceType, int $priceAmount): int
    {
        return $this->db->insert('orders', [
            'channel'           => 'bot',
            'telegram_user_id'  => $telegramUserId,
            'telegram_username' => $username,
            'product_id'        => $productId,
            'price_type'        => $priceType,
            'price_amount'      => $priceAmount,
            'status'            => 'draft',
        ]);
    }

    /** ساخت سفارش پیش‌نویس از سایت (کاربر وب) */
    public function createWebDraft(int $webUserId, int $productId, string $priceType, int $priceAmount): int
    {
        return $this->db->insert('orders', [
            'channel'          => 'web',
            'telegram_user_id' => 0,
            'web_user_id'      => $webUserId,
            'product_id'       => $productId,
            'price_type'       => $priceType,
            'price_amount'     => $priceAmount,
            'status'           => 'draft',
        ]);
    }

    public function find(int $orderId): ?array
    {
        return $this->db->fetch('SELECT * FROM orders WHERE id = ? LIMIT 1', [$orderId]);
    }

    /** سفارش‌های یک کاربر وب (جدیدترین اول) */
    public function listForWebUser(int $webUserId): array
    {
        return $this->db->fetchAll(
            'SELECT o.*, p.icloud_enabled, w.name AS warranty_name
               FROM orders o
               LEFT JOIN products p ON p.id = o.product_id
               LEFT JOIN warranty_types w ON w.id = p.warranty_type_id
              WHERE o.web_user_id = ? AND o.channel = "web"
              ORDER BY o.id DESC',
            [$webUserId]
        );
    }

    /** یک سفارشِ متعلق به همین کاربر وب (بررسی مالکیت) */
    public function findForWebUser(int $orderId, int $webUserId): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM orders WHERE id = ? AND web_user_id = ? AND channel = "web" LIMIT 1',
            [$orderId, $webUserId]
        );
    }

    /** ذخیرهٔ یک فیلد شخصی رمزنگاری‌شده روی سفارش */
    public function setEncryptedField(int $orderId, string $column, string $plain): void
    {
        $allowed = ['first_name_enc', 'last_name_enc', 'phone_enc', 'email_enc', 'birthdate_enc'];
        if (!in_array($column, $allowed, true)) {
            throw new \InvalidArgumentException('bad column');
        }
        $this->db->update('orders', [$column => $this->crypto->encrypt($plain)], 'id = :id', ['id' => $orderId]);
    }

    /** بازگردانی مقادیر شخصیِ بازگشایی‌شده (برای نمایش خلاصه به خودِ مشتری) */
    public function decryptPersonal(array $order): array
    {
        return [
            'first_name' => $this->crypto->decrypt($order['first_name_enc'] ?? null) ?? '',
            'last_name'  => $this->crypto->decrypt($order['last_name_enc'] ?? null) ?? '',
            'phone'      => $this->crypto->decrypt($order['phone_enc'] ?? null) ?? '',
            'email'      => $this->crypto->decrypt($order['email_enc'] ?? null) ?? '',
            'birthdate'  => $this->crypto->decrypt($order['birthdate_enc'] ?? null) ?? '',
        ];
    }

    public function setStatus(int $orderId, string $status, array $extra = []): void
    {
        $data = array_merge(['status' => $status], $extra);
        $this->db->update('orders', $data, 'id = :id', ['id' => $orderId]);
    }

    public function setReceipt(int $orderId, string $fileId): void
    {
        $this->db->update('orders', [
            'receipt_file_id' => $fileId,
            'payment_method'  => 'receipt',
            'status'          => 'pending_approval',
        ], 'id = :id', ['id' => $orderId]);
    }

    public function setVerificationCode(int $orderId, string $plainCode): void
    {
        $this->db->update('orders', [
            'verification_code_enc' => $this->crypto->encrypt($plainCode),
            'status'                => 'code_received',
        ], 'id = :id', ['id' => $orderId]);
    }

    /** ثبت کریدنشال نهایی → تکمیل سفارش و پاک‌سازی فوری کد تأیید (ephemeral) */
    public function complete(int $orderId, string $plainCredentials): void
    {
        $this->db->update('orders', [
            'final_credentials_enc' => $this->crypto->encrypt($plainCredentials),
            'verification_code_enc' => null, // کد بعد از استفاده بلافاصله پاک می‌شود
            'status'                => 'completed',
            'completed_at'          => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $orderId]);
    }

    public function reject(int $orderId, string $reason): void
    {
        $this->db->update('orders', [
            'status'        => 'rejected',
            'reject_reason' => mb_substr($reason, 0, 255),
        ], 'id = :id', ['id' => $orderId]);
    }
}
