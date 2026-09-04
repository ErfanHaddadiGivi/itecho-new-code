<?php

namespace AppleBot;

/**
 * اقدام‌های ادمین — همه داخل تلگرام.
 *   - تأیید/رد سفارش، ثبت روی حساب همکار، ثبت اطلاعات نهایی
 *   - مدیریت همکار: افزودن، تأیید/رد، خلاصه‌حساب، تسویه
 *
 * هر اقدام مالی/مدیریتی در audit_log ثبت می‌شود. صحتِ ادمین بودن قبلاً
 * در webhook بررسی شده است.
 */
class AdminActions
{
    private BotContext $ctx;

    public function __construct(BotContext $ctx)
    {
        $this->ctx = $ctx;
    }

    // ==================================================================
    //  دکمه‌های اینلاین ادمین
    // ==================================================================
    public function handleCallback(int $adminId, int $chatId, string $data, int $messageId, string $callbackId): void
    {
        $parts = explode(':', $data);

        // سفارش‌ها: ord:<action>:<orderId>
        if ($parts[0] === 'ord' && isset($parts[2])) {
            $action  = $parts[1];
            $orderId = (int) $parts[2];
            $this->ctx->tg->answerCallbackQuery($callbackId);
            $this->ctx->tg->clearReplyMarkup($chatId, $messageId);

            match ($action) {
                'approve'  => $this->startOrder($adminId, $chatId, $orderId, false),
                'oncredit' => $this->startOrder($adminId, $chatId, $orderId, true),
                'reject'   => $this->askRejectReason($adminId, $chatId, $orderId),
                'final'    => $this->askFinalCredentials($adminId, $chatId, $orderId),
                default    => null,
            };
            return;
        }

        // همکار: pa:<action>:<partnerId>
        if ($parts[0] === 'pa' && isset($parts[2])) {
            $action    = $parts[1];
            $partnerId = (int) $parts[2];
            $this->ctx->tg->answerCallbackQuery($callbackId);
            $this->ctx->tg->clearReplyMarkup($chatId, $messageId);

            if ($action === 'approve') {
                $partner = $this->ctx->db->fetch('SELECT * FROM partners WHERE id = ?', [$partnerId]);
                $this->ctx->partners->approve($partnerId, $adminId);
                $this->ctx->tg->sendMessage($chatId, "✅ همکار #{$partnerId} تأیید شد.");
                if ($partner) {
                    $this->ctx->tg->sendMessage((int) $partner['telegram_user_id'], "🎉 حساب همکاری شما تأیید شد. حالا قیمت همکاری برای شما فعال است.");
                }
            } elseif ($action === 'reject') {
                $this->ctx->partners->reject($partnerId, $adminId);
                $this->ctx->tg->sendMessage($chatId, "❌ همکار #{$partnerId} رد شد.");
            }
            return;
        }

        $this->ctx->tg->answerCallbackQuery($callbackId);
    }

    // ==================================================================
    //  پیام‌های متنی ادمین (دستورها + پاسخ به درخواست‌های ورودی)
    // ==================================================================
    public function handleMessage(int $adminId, int $chatId, string $text, array $conv, ?string $photoFileId = null): void
    {
        $state = $conv['state'];

        // پاسخ به «دلیل رد» یا «اطلاعات نهایی»
        if ($state === States::ADMIN_REJECT_REASON) {
            $this->finishReject($adminId, $chatId, $conv, $text);
            return;
        }
        if ($state === States::ADMIN_FINAL_CREDENTIALS) {
            if ($photoFileId !== null && $photoFileId !== '') {
                $this->finishFinalImage($adminId, $chatId, $conv, $photoFileId);
            } elseif (trim($text) !== '') {
                $this->finishFinal($adminId, $chatId, $conv, $text);
            } else {
                $this->ctx->tg->sendMessage($chatId, 'یک «متن» یا یک «عکس» از اطلاعات اپل‌آیدی بفرست.');
            }
            return;
        }

        // دستورها
        $t = trim($text);
        if ($t === '/partners') {
            $this->listPendingPartners($chatId);
            return;
        }
        if (str_starts_with($t, '/addpartner')) {
            $this->addPartner($adminId, $chatId, $t);
            return;
        }
        if (str_starts_with($t, '/ledger')) {
            $this->showLedger($chatId, $t);
            return;
        }
        if (str_starts_with($t, '/settle')) {
            $this->doSettle($adminId, $chatId, $t);
            return;
        }
        if (str_starts_with($t, '/setcard')) {
            $this->setCard($adminId, $chatId, $t);
            return;
        }
        if ($t === '/finishsetup') {
            $this->finishSetup($adminId, $chatId);
            return;
        }
        if ($t === '/admin' || $t === '/help') {
            $this->ctx->tg->sendMessage($chatId, $this->adminHelp());
            return;
        }
    }

    /**
     * دستور عمومی «/claim <رمز راه‌اندازی>» — کاربر با رمزِ نصب، خودش را ادمین می‌کند.
     * این متد پیش از گیتِ ادمین در webhook صدا زده می‌شود (چون کاربر هنوز ادمین نیست).
     */
    public function handleClaim(int $userId, int $chatId, string $text): void
    {
        $parts = preg_split('/\s+/', trim($text), 2);
        $pass  = $parts[1] ?? '';
        $hash  = (string) $this->ctx->settings->get('admin_setup_password_hash', '');

        if ($hash === '') {
            $this->ctx->tg->sendMessage($chatId, 'ثبت ادمین قفل است. (رمز راه‌اندازی تنظیم/فعال نیست)');
            return;
        }
        if ($pass === '' || !password_verify($pass, $hash)) {
            $this->ctx->tg->sendMessage($chatId, 'رمز راه‌اندازی اشتباه است.');
            return;
        }

        $exists = $this->ctx->db->fetch('SELECT id FROM admins WHERE telegram_user_id = ?', [$userId]);
        if ($exists) {
            $this->ctx->tg->sendMessage($chatId, 'شما از قبل ادمین هستید.');
            return;
        }

        $this->ctx->db->insert('admins', ['telegram_user_id' => $userId, 'name' => null, 'is_active' => 1]);
        Audit::log($this->ctx->db, $userId, 'admin_claim', 'admin', $userId);
        $this->ctx->tg->sendMessage(
            $chatId,
            "✅ شما به‌عنوان ادمین ثبت شدید.\n"
            . "برای دیدن دستورها /admin را بزنید.\n"
            . "پس از افزودن همهٔ ادمین‌ها، برای قفل‌کردن ثبت، /finishsetup را بزنید."
        );
    }

    private function finishSetup(int $adminId, int $chatId): void
    {
        $this->ctx->settings->set('admin_setup_password_hash', '');
        Audit::log($this->ctx->db, $adminId, 'finish_setup', 'settings', 'admin_setup');
        $this->ctx->tg->sendMessage($chatId, '🔒 ثبت ادمین قفل شد؛ رمز راه‌اندازی دیگر کار نمی‌کند.');
    }

    // ==================================================================
    //  سفارش‌ها
    // ==================================================================
    private function startOrder(int $adminId, int $chatId, int $orderId, bool $onCredit): void
    {
        $order = $this->ctx->orders->find($orderId);
        if ($order === null || !in_array($order['status'], ['pending_approval', 'pending_payment'], true)) {
            $this->ctx->tg->sendMessage($chatId, "سفارش #{$orderId} در وضعیت قابل‌تأیید نیست.");
            return;
        }

        // ثبت روی حساب همکار (در صورت انتخاب)
        if ($onCredit) {
            $partner = $this->ctx->partners->getByTelegramId((int) $order['telegram_user_id']);
            if ($partner === null || $partner['status'] !== 'approved') {
                $this->ctx->tg->sendMessage($chatId, "این مشتری همکارِ تأییدشده نیست؛ نمی‌توان روی حساب ثبت کرد.");
                return;
            }
            $this->ctx->partners->charge((int) $partner['id'], $orderId, (int) $order['price_amount'], $adminId, 'order #' . $orderId);
            $this->ctx->orders->setStatus($orderId, 'approved_awaiting_code', ['payment_method' => 'partner_account']);
        } else {
            $this->ctx->orders->setStatus($orderId, 'approved_awaiting_code');
        }

        // اطلاع به مشتری — فقط برای سفارش‌های ربات؛ کاربرِ وب وضعیت را در پروفایل می‌بیند
        if (($order['channel'] ?? 'bot') === 'bot') {
            $email = $this->ctx->orders->decryptPersonal($order)['email'];
            $this->ctx->conv->save((int) $order['telegram_user_id'], States::AWAITING_CODE, [], $orderId);
            $this->ctx->tg->sendMessage((int) $order['telegram_user_id'], $this->ctx->t('stay_online_for_code', ['email' => $email]));
        }

        Audit::log($this->ctx->db, $adminId, $onCredit ? 'order_start_oncredit' : 'order_start', 'order', $orderId);
        $where = ($order['channel'] ?? 'bot') === 'web' ? '(سفارش سایت — کاربر از پروفایلش کد را وارد می‌کند)' : 'منتظر کد کاربر بمان.';
        $this->ctx->tg->sendMessage($chatId, "✅ سفارش #{$orderId} شروع شد. {$where}");
    }

    private function askRejectReason(int $adminId, int $chatId, int $orderId): void
    {
        $this->ctx->conv->save($adminId, States::ADMIN_REJECT_REASON, ['order_id' => $orderId], null);
        $this->ctx->tg->sendMessage($chatId, "✍️ دلیل رد سفارش #{$orderId} را بنویس (برای مشتری ارسال می‌شود):");
    }

    private function finishReject(int $adminId, int $chatId, array $conv, string $reason): void
    {
        $orderId = (int) ($conv['context']['order_id'] ?? 0);
        $order   = $orderId ? $this->ctx->orders->find($orderId) : null;
        $this->ctx->conv->reset($adminId);

        if ($order === null) {
            $this->ctx->tg->sendMessage($chatId, "سفارش پیدا نشد.");
            return;
        }

        $this->ctx->orders->reject($orderId, $reason);
        if (($order['channel'] ?? 'bot') === 'bot') {
            $this->ctx->tg->sendMessage((int) $order['telegram_user_id'], $this->ctx->t('order_rejected', ['reason' => htmlspecialchars($reason)]));
            $this->ctx->conv->reset((int) $order['telegram_user_id']);
        }

        Audit::log($this->ctx->db, $adminId, 'order_reject', 'order', $orderId, ['reason' => $reason]);
        $this->ctx->tg->sendMessage($chatId, "❌ سفارش #{$orderId} رد شد و به مشتری اطلاع داده شد.");
    }

    private function askFinalCredentials(int $adminId, int $chatId, int $orderId): void
    {
        $this->ctx->conv->save($adminId, States::ADMIN_FINAL_CREDENTIALS, ['order_id' => $orderId], null);
        $this->ctx->tg->sendMessage($chatId, "📤 اطلاعات نهاییِ اپل‌آیدی سفارش #{$orderId} را بفرست.\nمی‌تونی «متن» بفرستی یا یک «عکس» (اسکرین‌شات) — همون برای مشتری (در بله یا پروفایل سایت) نشان داده می‌شود.");
    }

    /** تحویل به‌صورت عکس: file_id عکس با پیشوند img: ذخیره می‌شود. */
    private function finishFinalImage(int $adminId, int $chatId, array $conv, string $fileId): void
    {
        $orderId = (int) ($conv['context']['order_id'] ?? 0);
        $order   = $orderId ? $this->ctx->orders->find($orderId) : null;
        $this->ctx->conv->reset($adminId);

        if ($order === null) {
            $this->ctx->tg->sendMessage($chatId, "سفارش پیدا نشد.");
            return;
        }

        // ذخیرهٔ مرجع عکس (رمزشده) و تکمیل سفارش
        $this->ctx->orders->complete($orderId, 'img:' . $fileId);

        if (($order['channel'] ?? 'bot') === 'bot') {
            $this->ctx->tg->sendPhoto((int) $order['telegram_user_id'], $fileId, "🎉 اپل‌آیدی شما آماده است. اطلاعات ورود در تصویر بالا.");
            $this->ctx->conv->save((int) $order['telegram_user_id'], States::COMPLETED, [], null);
        }

        Audit::log($this->ctx->db, $adminId, 'order_complete', 'order', $orderId, ['kind' => 'image']);
        $extra = ($order['channel'] ?? 'bot') === 'web' ? '(کاربر از پروفایل سایت می‌بیند)' : 'و عکس برای مشتری ارسال شد.';
        $this->ctx->tg->sendMessage($chatId, "🎉 سفارش #{$orderId} با عکس تکمیل شد {$extra}");
    }

    private function finishFinal(int $adminId, int $chatId, array $conv, string $credentials): void
    {
        $orderId = (int) ($conv['context']['order_id'] ?? 0);
        $order   = $orderId ? $this->ctx->orders->find($orderId) : null;
        $this->ctx->conv->reset($adminId);

        if ($order === null) {
            $this->ctx->tg->sendMessage($chatId, "سفارش پیدا نشد.");
            return;
        }

        // تکمیل: کریدنشال رمز و ذخیره، کد تأیید بلافاصله پاک می‌شود
        $this->ctx->orders->complete($orderId, $credentials);
        if (($order['channel'] ?? 'bot') === 'bot') {
            $this->ctx->tg->sendMessage((int) $order['telegram_user_id'], $this->ctx->t('order_completed', ['credentials' => $credentials]));
            $this->ctx->conv->save((int) $order['telegram_user_id'], States::COMPLETED, [], null);
        }

        Audit::log($this->ctx->db, $adminId, 'order_complete', 'order', $orderId);
        $extra = ($order['channel'] ?? 'bot') === 'web' ? '(کاربر از پروفایل سایت دریافت می‌کند)' : 'و به مشتری تحویل شد.';
        $this->ctx->tg->sendMessage($chatId, "🎉 سفارش #{$orderId} تکمیل شد {$extra}");
    }

    // ==================================================================
    //  همکارها
    // ==================================================================
    private function listPendingPartners(int $chatId): void
    {
        $pending = $this->ctx->partners->pendingPartners();
        if ($pending === []) {
            $this->ctx->tg->sendMessage($chatId, "همکارِ در انتظار تأییدی وجود ندارد.");
            return;
        }
        foreach ($pending as $p) {
            $name = $p['display_name'] ?: '—';
            $kb = Messenger::inlineKeyboard([Messenger::inlineRow([
                ['✅ تأیید', 'pa:approve:' . $p['id']],
                ['❌ رد', 'pa:reject:' . $p['id']],
            ])]);
            $this->ctx->tg->sendMessage($chatId, "👤 همکار #{$p['id']}\nنام: {$name}\nتلگرام: {$p['telegram_user_id']}", $kb);
        }
    }

    private function addPartner(int $adminId, int $chatId, string $text): void
    {
        // /addpartner <telegram_user_id> [name...]
        $parts = preg_split('/\s+/', $text, 3);
        $tgId  = isset($parts[1]) ? (int) Validator::enDigits($parts[1]) : 0;
        $name  = isset($parts[2]) ? Validator::clean($parts[2]) : null;

        if ($tgId <= 0) {
            $this->ctx->tg->sendMessage($chatId, "فرمت درست: <code>/addpartner 123456789 نام</code>");
            return;
        }

        $exists = $this->ctx->partners->getByTelegramId($tgId);
        if ($exists) {
            $this->ctx->tg->sendMessage($chatId, "این کاربر از قبل همکار است (وضعیت: {$exists['status']}).");
            return;
        }

        $id = $this->ctx->db->insert('partners', [
            'telegram_user_id' => $tgId,
            'display_name'     => $name,
            'status'           => 'pending',
        ]);
        Audit::log($this->ctx->db, $adminId, 'partner_add', 'partner', $id, ['telegram_user_id' => $tgId]);
        $this->ctx->tg->sendMessage($chatId, "✅ همکار #{$id} (در انتظار) اضافه شد. با /partners تأییدش کن.");
    }

    private function showLedger(int $chatId, string $text): void
    {
        $parts     = preg_split('/\s+/', $text);
        $partnerId = isset($parts[1]) ? (int) Validator::enDigits($parts[1]) : 0;
        if ($partnerId <= 0) {
            $this->ctx->tg->sendMessage($chatId, "فرمت درست: <code>/ledger &lt;partner_id&gt;</code>");
            return;
        }

        $data = $this->ctx->partners->summary($partnerId);
        if ($data['partner'] === null) {
            $this->ctx->tg->sendMessage($chatId, "همکار #{$partnerId} پیدا نشد.");
            return;
        }

        $p    = $data['partner'];
        $body = "📒 <b>خلاصه‌حساب همکار #{$partnerId}</b>\n"
            . 'نام: ' . ($p['display_name'] ?: '—') . "\n"
            . 'وضعیت: ' . $p['status'] . "\n"
            . 'مانده (بدهی): <b>' . $this->ctx->money((int) $p['balance']) . " تومان</b>\n———\n";

        if ($data['entries'] === []) {
            $body .= 'تراکنشی ثبت نشده.';
        } else {
            foreach ($data['entries'] as $e) {
                $sign = $e['type'] === 'settlement' ? '-' : '+';
                $body .= $e['created_at'] . ' | ' . $e['type'] . ' | ' . $sign . $this->ctx->money((int) $e['amount'])
                    . ' | مانده: ' . $this->ctx->money((int) $e['balance_after']) . "\n";
            }
        }
        $this->ctx->tg->sendMessage($chatId, $body);
    }

    private function doSettle(int $adminId, int $chatId, string $text): void
    {
        // /settle <partner_id> <amount>
        $parts     = preg_split('/\s+/', $text);
        $partnerId = isset($parts[1]) ? (int) Validator::enDigits($parts[1]) : 0;
        $amount    = isset($parts[2]) ? (int) Validator::enDigits($parts[2]) : 0;

        if ($partnerId <= 0 || $amount <= 0) {
            $this->ctx->tg->sendMessage($chatId, "فرمت درست: <code>/settle &lt;partner_id&gt; &lt;amount&gt;</code>");
            return;
        }
        $partner = $this->ctx->db->fetch('SELECT id FROM partners WHERE id = ?', [$partnerId]);
        if ($partner === null) {
            $this->ctx->tg->sendMessage($chatId, "همکار #{$partnerId} پیدا نشد.");
            return;
        }

        $newBalance = $this->ctx->partners->settle($partnerId, $amount, $adminId, 'settle via bot');
        $this->ctx->tg->sendMessage($chatId, "✅ تسویهٔ " . $this->ctx->money($amount) . " تومان ثبت شد.\nمانده جدید: " . $this->ctx->money($newBalance) . ' تومان');
    }

    private function setCard(int $adminId, int $chatId, string $text): void
    {
        // /setcard <شماره‌کارت> <نام صاحب کارت ...>
        $parts  = preg_split('/\s+/', trim($text), 3);
        $number = isset($parts[1]) ? Validator::clean($parts[1]) : '';
        $holder = isset($parts[2]) ? Validator::clean($parts[2]) : '';

        if ($number === '' || $holder === '') {
            $this->ctx->tg->sendMessage($chatId, "فرمت درست: <code>/setcard 6037xxxxxxxxxxxx نام صاحب کارت</code>");
            return;
        }
        $this->ctx->settings->set('card_number', $number);
        $this->ctx->settings->set('card_holder_name', $holder);
        Audit::log($this->ctx->db, $adminId, 'set_card', 'settings', 'card', ['holder' => $holder]);
        $this->ctx->tg->sendMessage($chatId, "✅ شماره کارت و نام صاحب کارت به‌روزرسانی شد.");
    }

    private function adminHelp(): string
    {
        return "🛠 <b>دستورهای ادمین</b>\n"
            . "/setcard &lt;شماره‌کارت&gt; &lt;نام صاحب کارت&gt; — تنظیم کارت واریز\n"
            . "/partners — لیست همکارهای در انتظار تأیید\n"
            . "/addpartner &lt;tg_id&gt; &lt;نام&gt; — افزودن همکار جدید (در انتظار)\n"
            . "/ledger &lt;partner_id&gt; — خلاصه‌حساب همکار\n"
            . "/settle &lt;partner_id&gt; &lt;amount&gt; — ثبت تسویه\n"
            . "/finishsetup — قفل‌کردن ثبت ادمین (بعد از نصب)\n\n"
            . "تأیید/رد سفارش‌ها و ثبت اطلاعات نهایی، از روی دکمه‌های پیامِ هر سفارش انجام می‌شود.";
    }
}
