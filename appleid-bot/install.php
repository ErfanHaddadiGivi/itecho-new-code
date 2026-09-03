<?php
/**
 * نصب‌کنندهٔ ویزاردی ربات اپل‌آیدی (مثل نصاب وردپرس).
 *
 * کارها را خودکار می‌کند:
 *   - بررسی نیازمندی‌های سرور (PHP، افزونه‌ها، دسترسی نوشتن)
 *   - تست اتصال دیتابیس
 *   - ساخت خودکار رمزها/کلیدها (webhook_secret، encryption_key، رمز راه‌اندازی)
 *   - نوشتن config/config.php
 *   - ساخت جدول‌ها (اجرای schema.sql)
 *   - ست‌کردن خودکار وب‌هوک روی پیام‌رسان
 *   - نمایش رمز راه‌اندازی برای ادمین‌شدن با «/claim» در بله
 *
 * پس از نصب: این فایل را پاک کنید.
 *
 * ⚠️ این فایل عمداً وابستگی‌ای به کلاس‌های ربات ندارد (PDO و cURL خام) تا
 *    حتی پیش از وجود config هم کار کند.
 */

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

const BOT_BASE = __DIR__;
$configFile = BOT_BASE . '/config/config.php';
$schemaFile = BOT_BASE . '/sql/schema.sql';
$installed  = is_file($configFile);

/* ------------------------- کمک‌کارها ------------------------- */
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function requirements(): array
{
    $configDir = BOT_BASE . '/config';
    $logsDir   = BOT_BASE . '/logs';
    return [
        ['PHP نسخهٔ ۸.۰ یا بالاتر', PHP_VERSION_ID >= 80000, PHP_VERSION],
        ['افزونهٔ pdo_mysql',        extension_loaded('pdo_mysql'), ''],
        ['افزونهٔ cURL',            extension_loaded('curl'), ''],
        ['افزونهٔ OpenSSL',         extension_loaded('openssl'), ''],
        ['افزونهٔ mbstring',        extension_loaded('mbstring'), ''],
        ['نوشتنی بودن پوشهٔ config', is_writable($configDir), $configDir],
        ['نوشتنی بودن پوشهٔ logs',  is_dir($logsDir) ? is_writable($logsDir) : is_writable(BOT_BASE), $logsDir],
        ['وجود فایل schema.sql',    is_file(BOT_BASE . '/sql/schema.sql'), ''],
    ];
}

function requirementsOk(array $reqs): bool
{
    foreach ($reqs as $r) { if (!$r[1]) { return false; } }
    return true;
}

function detectWebhookUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $scheme . '://' . $host . $dir . '/webhook.php';
}

function curlPost(string $url, array $fields): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $fields,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    $json = is_string($body) ? json_decode($body, true) : null;
    return ['ok' => is_array($json) && !empty($json['ok']), 'error' => $err, 'json' => $json];
}

function writeConfig(string $file, array $data): bool
{
    $php = "<?php\n// ساخته‌شده توسط install.php — " . date('Y-m-d H:i:s') . "\nreturn "
        . var_export($data, true) . ";\n";
    return file_put_contents($file, $php) !== false;
}

/* ------------------------- اجرای نصب ------------------------- */
$result = null;
if (!$installed && ($_POST['action'] ?? '') === 'install') {
    $reqs = requirements();
    if (!requirementsOk($reqs)) {
        $result = ['ok' => false, 'msg' => 'نیازمندی‌های سرور کامل نیست.'];
    } else {
        $db = [
            'host' => trim((string) ($_POST['db_host'] ?? 'localhost')),
            'name' => trim((string) ($_POST['db_name'] ?? '')),
            'user' => trim((string) ($_POST['db_user'] ?? '')),
            'pass' => (string) ($_POST['db_pass'] ?? ''),
            'charset' => 'utf8mb4',
        ];
        $apiBase    = trim((string) ($_POST['api_base_url'] ?? 'https://tapi.bale.ai/bot')) ?: 'https://tapi.bale.ai/bot';
        $botToken   = trim((string) ($_POST['bot_token'] ?? ''));
        $webhookUrl = trim((string) ($_POST['webhook_url'] ?? detectWebhookUrl()));

        $errors = [];
        if ($db['name'] === '' || $db['user'] === '') { $errors[] = 'نام دیتابیس و نام کاربری الزامی است.'; }
        if ($botToken === '') { $errors[] = 'توکن ربات الزامی است.'; }

        // تست اتصال دیتابیس
        $pdo = null;
        if ($errors === []) {
            try {
                $pdo = new PDO(
                    "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4",
                    $db['user'], $db['pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } catch (PDOException $e) {
                $errors[] = 'اتصال به دیتابیس ناموفق بود: ' . $e->getMessage();
            }
        }

        if ($errors !== []) {
            $result = ['ok' => false, 'msg' => implode(' ', $errors)];
        } else {
            // ساخت خودکار رازها
            $webhookSecret = bin2hex(random_bytes(24));
            $encryptionKey = base64_encode(random_bytes(32));
            $setupPassword = bin2hex(random_bytes(5)); // ۱۰ کاراکتر، برای /claim

            // اجرای schema
            try {
                $pdo->exec(file_get_contents($schemaFile));
            } catch (PDOException $e) {
                $result = ['ok' => false, 'msg' => 'ساخت جدول‌ها ناموفق بود: ' . $e->getMessage()];
            }

            if ($result === null) {
                // نوشتن config
                $config = [
                    'db'             => $db,
                    'api_base_url'   => $apiBase,
                    'bot_token'      => $botToken,
                    'webhook_secret' => $webhookSecret,
                    'encryption_key' => $encryptionKey,
                    'admin_ids'      => [],
                    'timezone'       => 'Asia/Tehran',
                    'debug'          => false,
                ];
                if (!writeConfig($configFile, $config)) {
                    $result = ['ok' => false, 'msg' => 'نوشتن config/config.php ناموفق بود (دسترسی نوشتن).'];
                }
            }

            if ($result === null) {
                // ذخیرهٔ رمز راه‌اندازی (هش‌شده) برای /claim
                $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?)
                               ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)')
                    ->execute(['admin_setup_password_hash', password_hash($setupPassword, PASSWORD_DEFAULT)]);

                // ست‌کردن وب‌هوک (secret در آدرس)
                $setUrl  = rtrim($apiBase, '/') . $botToken . '/setWebhook';
                $hook    = curlPost($setUrl, ['url' => $webhookUrl . '?s=' . $webhookSecret]);

                $result = [
                    'ok'            => true,
                    'setupPassword' => $setupPassword,
                    'webhookUrl'    => $webhookUrl . '?s=' . $webhookSecret,
                    'hookOk'        => $hook['ok'],
                    'hookErr'       => $hook['json']['description'] ?? $hook['error'] ?? '',
                    'apiBase'       => $apiBase,
                ];
                $installed = true;
            }
        }
    }
}

/* ------------------------- گزارش اعتبارسنجی (نصب‌شده) ------------------------- */
function validationReport(string $configFile, string $schemaFile): array
{
    $out = [];
    $cfg = is_file($configFile) ? (require $configFile) : null;
    $out[] = ['فایل config', is_array($cfg)];

    $pdo = null;
    if (is_array($cfg) && isset($cfg['db'])) {
        try {
            $d = $cfg['db'];
            $pdo = new PDO("mysql:host={$d['host']};dbname={$d['name']};charset=utf8mb4", $d['user'], $d['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (Throwable $e) { $pdo = null; }
    }
    $out[] = ['اتصال دیتابیس', $pdo !== null];

    $tablesOk = false; $adminCount = 0; $setupOpen = false;
    if ($pdo !== null) {
        try {
            $need = ['settings','admins','warranty_types','products','orders','partners','conversations','web_users'];
            $have = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            $tablesOk = count(array_intersect($need, $have)) === count($need);
            $adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins WHERE is_active=1')->fetchColumn();
            $setupOpen = ((string) ($pdo->query("SELECT `value` FROM settings WHERE `key`='admin_setup_password_hash'")->fetchColumn() ?: '')) !== '';
        } catch (Throwable $e) {}
    }
    $out[] = ['همهٔ جدول‌ها ساخته شده', $tablesOk];
    $out[] = ['کلید رمزنگاری معتبر', is_array($cfg) && strlen((string) base64_decode((string) ($cfg['encryption_key'] ?? ''), true)) === 32];
    $out[] = ['توکن ربات تنظیم شده', is_array($cfg) && trim((string) ($cfg['bot_token'] ?? '')) !== ''];
    $out[] = ['حداقل یک ادمین فعال', $adminCount > 0];

    // وضعیت وب‌هوک (بدون افشای آدرس/راز)
    $hookSet = false;
    if (is_array($cfg) && trim((string) ($cfg['bot_token'] ?? '')) !== '') {
        $info = curlPost(rtrim((string) $cfg['api_base_url'], '/') . $cfg['bot_token'] . '/getWebhookInfo', []);
        $hookSet = $info['ok'] && !empty($info['json']['result']['url']);
    }
    $out[] = ['وب‌هوک ثبت شده', $hookSet];
    $out[] = ['ثبت ادمین (رمز راه‌اندازی) ' . ($setupOpen ? 'باز است' : 'قفل'), true, $setupOpen ? 'برای قفل: /finishsetup در بله' : ''];

    return $out;
}

$page = $result ?? null;
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>نصب ربات اپل‌آیدی</title>
<style>
    body { font-family: Tahoma, "Segoe UI", system-ui, sans-serif; background: #0f1720; color: #e6edf3; margin: 0; padding: 24px; line-height: 1.9; }
    .wrap { max-width: 640px; margin: 0 auto; background: #161f2b; border: 1px solid #263241; border-radius: 16px; padding: 26px; }
    h1 { font-size: 22px; margin: 0 0 6px; }
    .sub { color: #9fb0c0; font-size: 13px; margin: 0 0 20px; }
    .check { display: flex; align-items: center; gap: 8px; padding: 7px 0; border-bottom: 1px solid #22303f; font-size: 14px; }
    .ok { color: #4ade80; } .bad { color: #f87171; } .muted { color: #8296a8; font-size: 12px; }
    label { display: block; font-size: 13px; margin: 14px 0 5px; color: #c7d3de; }
    input { width: 100%; box-sizing: border-box; padding: 10px 12px; border-radius: 9px; border: 1px solid #33465a; background: #0f1720; color: #e6edf3; font-family: inherit; font-size: 14px; }
    input[dir=ltr] { direction: ltr; text-align: left; }
    .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .btn { display: inline-block; margin-top: 20px; width: 100%; padding: 12px; border: none; border-radius: 10px; background: linear-gradient(135deg, #0b6e4f, #095b41); color: #fff; font-size: 15px; font-weight: 700; cursor: pointer; }
    .btn[disabled] { opacity: .5; cursor: not-allowed; }
    .alert { padding: 12px 14px; border-radius: 10px; margin: 14px 0; font-size: 14px; }
    .alert--bad { background: #3a1a1a; border: 1px solid #7f1d1d; color: #fecaca; }
    .alert--ok { background: #15321f; border: 1px solid #166534; color: #bbf7d0; }
    code, .code { direction: ltr; display: inline-block; background: #0f1720; border: 1px solid #33465a; border-radius: 8px; padding: 4px 8px; font-family: Consolas, monospace; font-size: 13px; word-break: break-all; }
    .big { font-size: 20px; font-weight: 800; letter-spacing: 2px; color: #7cffb2; }
    ol { padding-inline-start: 20px; } ol li { margin: 8px 0; font-size: 14px; }
    .warn { margin-top: 18px; padding: 12px 14px; border-radius: 10px; background: #3a2f13; border: 1px solid #854d0e; color: #fde68a; font-size: 13px; }
</style>
</head>
<body>
<div class="wrap">
    <h1>🚀 نصب ربات اپل‌آیدی</h1>
    <p class="sub">نصب سریع و خودکار روی پیام‌رسان بله (سازگار با تلگرام)</p>

<?php if ($page !== null && $page['ok']): /* موفقیت نصب */ ?>
    <div class="alert alert--ok">✅ نصب با موفقیت انجام شد!</div>

    <p><b>۱) ادمین شدن:</b> در بله به ربات این پیام را بفرست (رمز راه‌اندازی):</p>
    <p class="code big">/claim <?= h($page['setupPassword']) ?></p>

    <p><b>۲) وب‌هوک:</b>
        <?php if ($page['hookOk']): ?>
            <span class="ok">به‌صورت خودکار ثبت شد ✅</span>
        <?php else: ?>
            <span class="bad">خودکار ثبت نشد ❌</span> — این آدرس را دستی در بله ثبت کن:<br>
            <span class="code"><?= h(rtrim($page['apiBase'],'/')) ?>&lt;TOKEN&gt;/setWebhook?url=<?= h($page['webhookUrl']) ?></span>
            <?php if ($page['hookErr']): ?><br><span class="muted">پیام: <?= h((string)$page['hookErr']) ?></span><?php endif; ?>
        <?php endif; ?>
    </p>

    <p><b>۳) قدم‌های بعدی:</b></p>
    <ol>
        <li>در بله بعد از /claim، دستور <code>/setcard شماره‌کارت نام</code> را بزن.</li>
        <li>پس از افزودن ادمین‌ها، برای قفل‌کردن ثبت، <code>/finishsetup</code> را بزن.</li>
        <li>Cron هر ۶ ساعت: <span class="code">php <?= h(BOT_BASE) ?>/cron/purge_sensitive.php</span></li>
        <li>اتصال سایت: در config.local.php سایت مقدار <code>appleid_bot_path</code> را برابر <span class="code"><?= h(BOT_BASE) ?></span> بگذار و <code>database/appleid.sql</code> را روی دیتابیس سایت ایمپورت کن.</li>
    </ol>

    <div class="warn">🔒 برای امنیت، همین حالا فایل <code>install.php</code> را از روی هاست پاک کن.</div>

<?php elseif ($installed): /* نصب‌شده: گزارش اعتبارسنجی */ ?>
    <div class="alert alert--ok">این ربات قبلاً نصب شده است. گزارش وضعیت:</div>
    <?php foreach (validationReport($configFile, $schemaFile) as $row): ?>
        <div class="check">
            <span class="<?= $row[1] ? 'ok' : 'bad' ?>"><?= $row[1] ? '✓' : '✗' ?></span>
            <span><?= h($row[0]) ?></span>
            <?php if (!empty($row[2])): ?><span class="muted">— <?= h((string) $row[2]) ?></span><?php endif; ?>
        </div>
    <?php endforeach; ?>
    <div class="warn">🔒 اگر نصب کامل است، فایل <code>install.php</code> را پاک کن. برای نصب دوباره، اول <code>config/config.php</code> را حذف کن.</div>

<?php else: /* فرم نصب */ ?>
    <?php $reqs = requirements(); $reqOk = requirementsOk($reqs); ?>

    <?php if ($page !== null && !$page['ok']): ?>
        <div class="alert alert--bad"><?= h($page['msg']) ?></div>
    <?php endif; ?>

    <h3 style="font-size:15px;margin:6px 0">نیازمندی‌های سرور</h3>
    <?php foreach ($reqs as $r): ?>
        <div class="check">
            <span class="<?= $r[1] ? 'ok' : 'bad' ?>"><?= $r[1] ? '✓' : '✗' ?></span>
            <span><?= h($r[0]) ?></span>
            <?php if (!empty($r[2])): ?><span class="muted">— <?= h((string) $r[2]) ?></span><?php endif; ?>
        </div>
    <?php endforeach; ?>

    <form method="post" style="margin-top:18px">
        <input type="hidden" name="action" value="install">
        <h3 style="font-size:15px;margin:6px 0">دیتابیس</h3>
        <div class="grid">
            <div><label>هاست دیتابیس</label><input name="db_host" value="<?= h($_POST['db_host'] ?? 'localhost') ?>" dir="ltr"></div>
            <div><label>نام دیتابیس</label><input name="db_name" value="<?= h($_POST['db_name'] ?? '') ?>" dir="ltr" required></div>
            <div><label>کاربر دیتابیس</label><input name="db_user" value="<?= h($_POST['db_user'] ?? '') ?>" dir="ltr" required></div>
            <div><label>رمز دیتابیس</label><input name="db_pass" type="password" dir="ltr"></div>
        </div>

        <h3 style="font-size:15px;margin:16px 0 6px">ربات</h3>
        <label>توکن ربات بله</label>
        <input name="bot_token" value="<?= h($_POST['bot_token'] ?? '') ?>" dir="ltr" placeholder="123456:ABC..." required>
        <label>آدرس پایهٔ API</label>
        <input name="api_base_url" value="<?= h($_POST['api_base_url'] ?? 'https://tapi.bale.ai/bot') ?>" dir="ltr">
        <label>آدرس وب‌هوک (خودکار تشخیص داده شد)</label>
        <input name="webhook_url" value="<?= h($_POST['webhook_url'] ?? detectWebhookUrl()) ?>" dir="ltr">

        <p class="muted" style="margin-top:12px">🔑 رمز وب‌هوک، کلید رمزنگاری و رمز راه‌اندازی به‌صورت خودکار ساخته می‌شوند.</p>
        <button class="btn" type="submit" <?= $reqOk ? '' : 'disabled' ?>>نصب و راه‌اندازی خودکار</button>
        <?php if (!$reqOk): ?><p class="muted" style="text-align:center">ابتدا نیازمندی‌های قرمز را رفع کنید.</p><?php endif; ?>
    </form>
<?php endif; ?>
</div>
</body>
</html>
