<?php

namespace AppleBot;

/**
 * ماشین حالتِ مسیر مشتری: از /start تا تحویل اپل‌آیدی.
 * فقط منطق سمت کاربر؛ اقدام‌های ادمین در AdminActions است.
 */
class StateMachine
{
    private BotContext $ctx;

    public function __construct(BotContext $ctx)
    {
        $this->ctx = $ctx;
    }

    // ==================================================================
    //  پیام‌های متنی/عکسی کاربر
    // ==================================================================
    public function handleMessage(int $userId, int $chatId, ?string $username, array $message): void
    {
        $text = isset($message['text']) ? trim((string) $message['text']) : '';

        // /start (با یا بدون payload) همیشه جریان را از نو شروع می‌کند
        if ($text !== '' && str_starts_with($text, '/start')) {
            $this->start($userId, $chatId);
            return;
        }

        $conv  = $this->ctx->conv->get($userId);
        $state = $conv['state'];

        switch ($state) {
            case States::ENTERING_FIRST_NAME:
            case States::ENTERING_LAST_NAME:
            case States::ENTERING_PHONE:
            case States::ENTERING_EMAIL:
            case States::ENTERING_BIRTHDATE:
                $this->handleFieldInput($userId, $chatId, $state, $text, $conv);
                return;

            case States::AWAITING_RECEIPT:
                $this->handleReceipt($userId, $chatId, $message, $conv);
                return;

            case States::AWAITING_CODE:
                $this->handleVerificationCode($userId, $chatId, $username, $text, $conv);
                return;

            case States::AWAITING_APPROVAL:
            case States::AWAITING_FINAL:
                $this->ctx->tg->sendMessage($chatId, $this->ctx->t('awaiting_review'));
                return;

            case States::CHOOSING_WARRANTY:
            case States::CHOOSING_ICLOUD:
            case States::CONFIRMING_ORDER:
                // در این حالت‌ها منتظر کلیک دکمه هستیم
                $this->ctx->tg->sendMessage($chatId, $this->ctx->t('unknown_input'));
                return;

            default:
                $this->ctx->tg->sendMessage($chatId, $this->ctx->t('unknown_input'));
        }
    }

    // ==================================================================
    //  کلیک دکمه‌های اینلاینِ کاربر
    // ==================================================================
    public function handleCallback(int $userId, int $chatId, ?string $username, string $data, int $messageId, string $callbackId): void
    {
        $conv = $this->ctx->conv->get($userId);

        // انتخاب ضمانت
        if (str_starts_with($data, 'w:') && $conv['state'] === States::CHOOSING_WARRANTY) {
            $this->ctx->tg->answerCallbackQuery($callbackId);
            $warrantyId = (int) substr($data, 2);
            $wt = $this->ctx->db->fetch('SELECT id FROM warranty_types WHERE id = ? AND is_active = 1', [$warrantyId]);
            if ($wt === null) {
                $this->ctx->tg->sendMessage($chatId, $this->ctx->t('generic_error'));
                return;
            }
            $this->ctx->conv->save($userId, States::CHOOSING_ICLOUD, ['warranty_type_id' => $warrantyId], $conv['active_order_id']);
            $this->ctx->tg->sendMessage($chatId, $this->ctx->t('choose_icloud'), $this->icloudKeyboard());
            return;
        }

        // انتخاب آیکلود → ساخت سفارش پیش‌نویس
        if (str_starts_with($data, 'ic:') && $conv['state'] === States::CHOOSING_ICLOUD) {
            $this->ctx->tg->answerCallbackQuery($callbackId);
            $icloud       = substr($data, 3) === '1' ? 1 : 0;
            $warrantyId   = (int) ($conv['context']['warranty_type_id'] ?? 0);
            $this->startDataCollection($userId, $chatId, $username, $warrantyId, $icloud);
            return;
        }

        // تأیید/اصلاح/لغو در مرحلهٔ خلاصه
        if ($conv['state'] === States::CONFIRMING_ORDER) {
            $this->ctx->tg->answerCallbackQuery($callbackId);
            if ($data === 'ok') {
                $this->confirmOrder($userId, $chatId, $username, $conv);
            } elseif ($data === 'edit') {
                $this->ctx->conv->save($userId, States::ENTERING_FIRST_NAME, [], $conv['active_order_id']);
                $this->ctx->tg->sendMessage($chatId, $this->ctx->t('ask_first_name'));
            } elseif ($data === 'cancel') {
                if ($conv['active_order_id']) {
                    $this->ctx->orders->setStatus($conv['active_order_id'], 'cancelled');
                }
                $this->ctx->conv->reset($userId);
                $this->ctx->tg->sendMessage($chatId, $this->ctx->t('cancelled'));
            }
            return;
        }

        // در غیر این صورت callback نامرتبط
        $this->ctx->tg->answerCallbackQuery($callbackId);
    }

    // ==================================================================
    //  مراحل
    // ==================================================================
    private function start(int $userId, int $chatId): void
    {
        $this->ctx->conv->save($userId, States::CHOOSING_WARRANTY, [], null);
        $this->ctx->tg->sendMessage($chatId, $this->ctx->t('welcome'), $this->warrantyKeyboard());
    }

    private function startDataCollection(int $userId, int $chatId, ?string $username, int $warrantyId, int $icloud): void
    {
        $product = $this->productFor($warrantyId, $icloud);
        if ($product === null) {
            // این ترکیب فعال نیست؛ اجازه بده گزینهٔ دیگر آیکلود را امتحان کند
            $this->ctx->tg->sendMessage($chatId, $this->ctx->t('combo_unavailable'), $this->icloudKeyboard());
            return;
        }

        $priceType = $this->ctx->partners->priceTypeFor($userId);
        $amount    = (int) ($priceType === 'partner' ? $product['price_partner'] : $product['price_regular']);

        $orderId = $this->ctx->orders->createDraft($userId, $username, (int) $product['id'], $priceType, $amount);

        $this->ctx->conv->save($userId, States::ENTERING_FIRST_NAME, [], $orderId);
        $this->ctx->tg->sendMessage($chatId, $this->ctx->t('ask_first_name'));
    }

    private function handleFieldInput(int $userId, int $chatId, string $state, string $text, array $conv): void
    {
        $orderId = $conv['active_order_id'];
        if (!$orderId) {
            $this->ctx->tg->sendMessage($chatId, $this->ctx->t('session_expired'));
            $this->ctx->conv->reset($userId);
            return;
        }

        switch ($state) {
            case States::ENTERING_FIRST_NAME:
                $v = Validator::name($text);
                if ($v === null) { $this->ctx->tg->sendMessage($chatId, $this->ctx->t('invalid_name')); return; }
                $this->ctx->orders->setEncryptedField($orderId, 'first_name_enc', $v);
                $this->ctx->conv->save($userId, States::ENTERING_LAST_NAME, [], $orderId);
                $this->ctx->tg->sendMessage($chatId, $this->ctx->t('ask_last_name'));
                break;

            case States::ENTERING_LAST_NAME:
                $v = Validator::name($text);
                if ($v === null) { $this->ctx->tg->sendMessage($chatId, $this->ctx->t('invalid_name')); return; }
                $this->ctx->orders->setEncryptedField($orderId, 'last_name_enc', $v);
                $this->ctx->conv->save($userId, States::ENTERING_PHONE, [], $orderId);
                $this->ctx->tg->sendMessage($chatId, $this->ctx->t('ask_phone'));
                break;

            case States::ENTERING_PHONE:
                $v = Validator::phone($text);
                if ($v === null) { $this->ctx->tg->sendMessage($chatId, $this->ctx->t('invalid_phone')); return; }
                $this->ctx->orders->setEncryptedField($orderId, 'phone_enc', $v);
                $this->ctx->conv->save($userId, States::ENTERING_EMAIL, [], $orderId);
                $this->ctx->tg->sendMessage($chatId, $this->ctx->t('ask_email'));
                break;

            case States::ENTERING_EMAIL:
                $v = Validator::email($text);
                if ($v === null) { $this->ctx->tg->sendMessage($chatId, $this->ctx->t('invalid_email')); return; }
                $this->ctx->orders->setEncryptedField($orderId, 'email_enc', $v);
                $this->ctx->conv->save($userId, States::ENTERING_BIRTHDATE, [], $orderId);
                $this->ctx->tg->sendMessage($chatId, $this->ctx->t('ask_birthdate'));
                break;

            case States::ENTERING_BIRTHDATE:
                $v = Validator::birthdate($text);
                if ($v === null) { $this->ctx->tg->sendMessage($chatId, $this->ctx->t('invalid_date')); return; }
                $this->ctx->orders->setEncryptedField($orderId, 'birthdate_enc', $v);
                $this->ctx->conv->save($userId, States::CONFIRMING_ORDER, [], $orderId);
                $this->sendSummary($chatId, $orderId);
                break;
        }
    }

    private function sendSummary(int $chatId, int $orderId): void
    {
        $order = $this->ctx->orders->find($orderId);
        if ($order === null) { return; }

        $p       = $this->ctx->orders->decryptPersonal($order);
        $product = $this->productWithWarranty((int) $order['product_id']);

        $text = $this->ctx->t('order_summary', [
            'warranty'   => $product['warranty_name'] ?? '-',
            'icloud'     => $this->ctx->t($product['icloud_enabled'] ? 'icloud_yes' : 'icloud_no'),
            'first_name' => $p['first_name'],
            'last_name'  => $p['last_name'],
            'email'      => $p['email'],
            'phone'      => $p['phone'],
            'birthdate'  => $p['birthdate'],
            'amount'     => $this->ctx->money((int) $order['price_amount']),
        ]);

        $kb = Telegram::inlineKeyboard([
            Telegram::inlineRow([[$this->ctx->t('btn_confirm'), 'ok']]),
            Telegram::inlineRow([[$this->ctx->t('btn_edit'), 'edit'], [$this->ctx->t('btn_cancel'), 'cancel']]),
        ]);
        $this->ctx->tg->sendMessage($chatId, $text, $kb);
    }

    private function confirmOrder(int $userId, int $chatId, ?string $username, array $conv): void
    {
        $orderId = $conv['active_order_id'];
        $order   = $orderId ? $this->ctx->orders->find($orderId) : null;
        if ($order === null) {
            $this->ctx->tg->sendMessage($chatId, $this->ctx->t('session_expired'));
            $this->ctx->conv->reset($userId);
            return;
        }

        $isPartner = ($order['price_type'] === 'partner');

        if ($isPartner) {
            // مسیر همکار: بدون فیش مستقیم به بررسی ادمین می‌رود
            $this->ctx->orders->setStatus($orderId, 'pending_approval');
            $this->ctx->conv->save($userId, States::AWAITING_APPROVAL, [], $orderId);
            $this->ctx->tg->sendMessage($chatId, $this->ctx->t('order_sent_for_review'));
            $this->submitOrderToAdmins((int) $orderId, true, false);
        } else {
            // مسیر مشتری عادی: نمایش کارت و انتظار فیش
            $this->ctx->orders->setStatus($orderId, 'pending_payment');
            $this->ctx->conv->save($userId, States::AWAITING_RECEIPT, [], $orderId);
            $this->ctx->tg->sendMessage($chatId, $this->ctx->t('payment_instructions', [
                'amount'      => $this->ctx->money((int) $order['price_amount']),
                'card_number' => $this->ctx->settings->get('card_number', '---'),
                'card_holder' => $this->ctx->settings->get('card_holder_name', '---'),
            ]));
        }
    }

    private function handleReceipt(int $userId, int $chatId, array $message, array $conv): void
    {
        $orderId = $conv['active_order_id'];
        if (!$orderId) {
            $this->ctx->tg->sendMessage($chatId, $this->ctx->t('session_expired'));
            $this->ctx->conv->reset($userId);
            return;
        }

        if (empty($message['photo']) || !is_array($message['photo'])) {
            $this->ctx->tg->sendMessage($chatId, $this->ctx->t('send_receipt_photo'));
            return;
        }

        // بزرگ‌ترین نسخهٔ عکس
        $photos = $message['photo'];
        $fileId = (string) end($photos)['file_id'];

        $this->ctx->orders->setReceipt($orderId, $fileId);
        $this->ctx->conv->save($userId, States::AWAITING_APPROVAL, [], $orderId);
        $this->ctx->tg->sendMessage($chatId, $this->ctx->t('receipt_received'));

        $order      = $this->ctx->orders->find($orderId);
        $isPartner  = $order && $order['price_type'] === 'partner';
        // فوروارد فیش به ادمین‌ها
        $messageId  = (int) ($message['message_id'] ?? 0);
        foreach ($this->ctx->adminTelegramIds() as $adminId) {
            if ($messageId > 0) {
                $this->ctx->tg->forwardMessage($adminId, $chatId, $messageId);
            }
        }
        $this->submitOrderToAdmins((int) $orderId, (bool) $isPartner, true);
    }

    private function handleVerificationCode(int $userId, int $chatId, ?string $username, string $text, array $conv): void
    {
        $orderId = $conv['active_order_id'];
        if (!$orderId) {
            $this->ctx->tg->sendMessage($chatId, $this->ctx->t('session_expired'));
            $this->ctx->conv->reset($userId);
            return;
        }

        $code = Validator::clean($text);
        if ($code === '' || mb_strlen($code) > 32) {
            $this->ctx->tg->sendMessage($chatId, $this->ctx->t('unknown_input'));
            return;
        }

        $this->ctx->orders->setVerificationCode($orderId, $code);
        $this->ctx->conv->save($userId, States::AWAITING_FINAL, [], $orderId);
        $this->ctx->tg->sendMessage($chatId, $this->ctx->t('code_received_wait'));

        // ارسال کد به ادمین‌ها همراه دکمهٔ ثبت اطلاعات نهایی
        $kb = Telegram::inlineKeyboard([
            Telegram::inlineRow([[$this->ctx->t('btn_final'), 'ord:final:' . $orderId]]),
        ]);
        $this->ctx->notifyAdmins("🔑 کد تأیید سفارش #{$orderId}:\n<code>" . htmlspecialchars($code) . '</code>', $kb);
    }

    // ==================================================================
    //  اطلاع سفارش به ادمین‌ها (شامل اطلاعات لازم برای ساخت اکانت)
    // ==================================================================
    private function submitOrderToAdmins(int $orderId, bool $isPartner, bool $withReceipt): void
    {
        $order = $this->ctx->orders->find($orderId);
        if ($order === null) { return; }

        $p       = $this->ctx->orders->decryptPersonal($order);
        $product = $this->productWithWarranty((int) $order['product_id']);
        $uname   = $order['telegram_username'] ? '@' . $order['telegram_username'] : '—';

        $text = "🆕 <b>سفارش جدید #{$orderId}</b>\n"
            . 'محصول: ' . ($product['warranty_name'] ?? '-') . ' / آیکلود '
            . $this->ctx->t($product['icloud_enabled'] ? 'icloud_yes' : 'icloud_no') . "\n"
            . 'قیمت: ' . ($isPartner ? 'همکار' : 'عادی') . ' — ' . $this->ctx->money((int) $order['price_amount']) . " تومان\n"
            . 'مشتری: ' . $uname . ' (' . $order['telegram_user_id'] . ")\n"
            . "———\n"
            . 'نام: ' . htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) . "\n"
            . 'ایمیل: <code>' . htmlspecialchars($p['email']) . "</code>\n"
            . 'شماره: ' . htmlspecialchars($p['phone']) . "\n"
            . 'تولد: ' . htmlspecialchars($p['birthdate']);

        $rows = [Telegram::inlineRow([
            ['✅ تأیید و شروع', 'ord:approve:' . $orderId],
            ['❌ رد سفارش', 'ord:reject:' . $orderId],
        ])];
        if ($isPartner) {
            $rows[] = Telegram::inlineRow([['🧾 ثبت روی حساب همکار', 'ord:oncredit:' . $orderId]]);
        }

        $this->ctx->notifyAdmins($text, Telegram::inlineKeyboard($rows));
    }

    // ==================================================================
    //  کمک‌کارها
    // ==================================================================
    private function warrantyKeyboard(): array
    {
        $rows = [];
        foreach ($this->ctx->db->fetchAll('SELECT id, name FROM warranty_types WHERE is_active = 1 ORDER BY sort_order, id') as $w) {
            $rows[] = Telegram::inlineRow([[$w['name'], 'w:' . $w['id']]]);
        }
        return Telegram::inlineKeyboard($rows);
    }

    private function icloudKeyboard(): array
    {
        return Telegram::inlineKeyboard([
            Telegram::inlineRow([
                [$this->ctx->t('btn_icloud_on'), 'ic:1'],
                [$this->ctx->t('btn_icloud_off'), 'ic:0'],
            ]),
        ]);
    }

    private function productFor(int $warrantyTypeId, int $icloud): ?array
    {
        return $this->ctx->db->fetch(
            "SELECT * FROM products
              WHERE region = 'US' AND warranty_type_id = ? AND icloud_enabled = ? AND is_active = 1
              ORDER BY sort_order, id LIMIT 1",
            [$warrantyTypeId, $icloud]
        );
    }

    private function productWithWarranty(int $productId): array
    {
        $row = $this->ctx->db->fetch(
            'SELECT p.*, w.name AS warranty_name
               FROM products p
               LEFT JOIN warranty_types w ON w.id = p.warranty_type_id
              WHERE p.id = ? LIMIT 1',
            [$productId]
        );
        return $row ?? [];
    }
}
