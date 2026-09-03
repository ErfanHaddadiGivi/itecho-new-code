<?php
/**
 * بررسی سلامت دیتابیس سایت + ساخت جدول‌های ناقص (ابزار تعمیر).
 *
 * چرا؟ اگر دیتابیس production از روی یک نسخهٔ قدیمی‌ترِ schema ساخته شده باشد،
 * ممکن است بعضی جدول‌های جدید (مثل posts یا banners) وجود نداشته باشند و
 * صفحه‌هایی از سایت با خطای ۵۰۰ بیفتند. این صفحه:
 *   - به دیتابیسِ همین سایت وصل می‌شود (از روی config/config.local.php)،
 *   - همهٔ جدول‌های موردانتظار (از database/schema.sql) را با جدول‌های موجود مقایسه می‌کند،
 *   - و در صورت تأیید، فقط دستورهای CREATE TABLE را (به‌صورت IF NOT EXISTS) اجرا می‌کند.
 *
 * ⚠️ امنیت داده: این ابزار هرگز DROP/DELETE/INSERT اجرا نمی‌کند و هیچ داده‌ای را
 *    پاک یا بازنویسی نمی‌کند؛ فقط جدول‌های «نداشته» را می‌سازد.
 *
 * 🔒 بعد از استفاده این فایل (dbcheck.php) را از روی هاست پاک کنید.
 */

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

const SITE_BASE = __DIR__;
$schemaFile = SITE_BASE . '/database/schema.sql';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/** اتصال به دیتابیس سایت با استفاده از همان config سایت. */
function connectSiteDb(): array
{
    $configFile = SITE_BASE . '/config/config.php';
    if (!is_file($configFile)) {
        return [null, 'فایل config/config.php پیدا نشد.'];
    }
    // config.php در نبودِ config.local.php خودش یک صفحهٔ ۵۰۳ چاپ کرده و exit می‌کند؛
    // برای همین اول وجود config.local.php را چک می‌کنیم تا این صفحه سالم بماند.
    if (!is_file(SITE_BASE . '/config/config.local.php')) {
        return [null, 'فایل config/config.local.php پیدا نشد — اطلاعات دیتابیس تنظیم نشده است.'];
    }
    require $configFile; // $GLOBALS['app_config'] را پر می‌کند
    $cfg = $GLOBALS['app_config'] ?? null;
    if (!is_array($cfg) || empty($cfg['db'])) {
        return [null, 'اطلاعات دیتابیس در config خالی است.'];
    }
    $d = $cfg['db'];
    try {
        $pdo = new PDO(
            "mysql:host={$d['host']};dbname={$d['name']};charset=" . ($d['charset'] ?? 'utf8mb4'),
            $d['user'], $d['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return [$pdo, (string) $d['name']];
    } catch (PDOException $e) {
        return [null, 'اتصال به دیتابیس ناموفق بود: ' . $e->getMessage()];
    }
}

/** نام همهٔ جدول‌های موردانتظار را از schema.sql بیرون می‌کشد. */
function expectedTables(string $schemaFile): array
{
    if (!is_file($schemaFile)) {
        return [];
    }
    $sql = (string) file_get_contents($schemaFile);
    preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $sql, $m);
    return array_values(array_unique($m[1] ?? []));
}

/** جدول‌های موجود در دیتابیس. */
function existingTables(PDO $pdo): array
{
    return $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * فقط دستورهای CREATE TABLE (و SET ...) را از schema.sql برمی‌گرداند؛
 * DROP / INSERT / DELETE و … حذف می‌شوند تا هیچ داده‌ای از بین نرود.
 * هر CREATE TABLE به CREATE TABLE IF NOT EXISTS تبدیل می‌شود.
 */
function safeCreateStatements(string $schemaFile): array
{
    $sql = (string) file_get_contents($schemaFile);

    // حذف کامنت‌های خطی
    $lines = [];
    foreach (preg_split('/\r\n|\r|\n/', $sql) as $line) {
        $t = ltrim($line);
        if ($t === '' || str_starts_with($t, '--') || str_starts_with($t, '#')) {
            continue;
        }
        $lines[] = $line;
    }
    $joined = implode("\n", $lines);

    $out = [];
    foreach (explode(';', $joined) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') {
            continue;
        }
        $head = strtoupper(ltrim($stmt));
        if (str_starts_with($head, 'CREATE TABLE')) {
            // اطمینان از IF NOT EXISTS
            if (!preg_match('/^CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS/i', $stmt)) {
                $stmt = preg_replace('/^CREATE\s+TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', $stmt, 1);
            }
            $out[] = $stmt;
        } elseif (str_starts_with($head, 'SET ')) {
            // SET NAMES / FOREIGN_KEY_CHECKS / SQL_MODE — بی‌خطر و کمک‌کننده به ترتیب FK
            $out[] = $stmt;
        }
        // بقیه (DROP/INSERT/…) عمداً نادیده گرفته می‌شوند.
    }
    return $out;
}

/* ------------------------- اجرای تعمیر ------------------------- */
$repair = null;
if (($_POST['action'] ?? '') === 'repair') {
    [$pdo, $info] = connectSiteDb();
    if ($pdo === null) {
        $repair = ['ok' => false, 'msg' => $info];
    } elseif (trim((string) ($_POST['confirm_db'] ?? '')) !== $info) {
        $repair = ['ok' => false, 'msg' => 'برای تأیید، نام دقیق دیتابیس («' . $info . '») را در کادر بنویس.'];
    } else {
        $before  = existingTables($pdo);
        $errors  = [];
        foreach (safeCreateStatements($schemaFile) as $stmt) {
            try { $pdo->exec($stmt); }
            catch (PDOException $e) { $errors[] = $e->getMessage(); }
        }
        $after   = existingTables($pdo);
        $created = array_values(array_diff($after, $before));
        $missing = array_values(array_diff(expectedTables($schemaFile), $after));
        $repair = [
            'ok'      => $missing === [],
            'created' => $created,
            'missing' => $missing,
            'errors'  => $errors,
        ];
    }
}

/* ------------------------- گزارش ------------------------- */
[$pdo, $dbInfo] = connectSiteDb();
$connectErr = $pdo === null ? $dbInfo : '';
$expected   = expectedTables($schemaFile);
$have       = $pdo !== null ? existingTables($pdo) : [];
$missing    = $pdo !== null ? array_values(array_diff($expected, $have)) : [];
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>بررسی سلامت دیتابیس ایتکو</title>
<style>
    body { font-family: Tahoma, "Segoe UI", system-ui, sans-serif; background: #0f1720; color: #e6edf3; margin: 0; padding: 24px; line-height: 1.9; }
    .wrap { max-width: 720px; margin: 0 auto; background: #161f2b; border: 1px solid #263241; border-radius: 16px; padding: 26px; }
    h1 { font-size: 22px; margin: 0 0 6px; }
    .sub { color: #9fb0c0; font-size: 13px; margin: 0 0 20px; }
    .check { display: flex; align-items: center; gap: 8px; padding: 6px 0; border-bottom: 1px solid #22303f; font-size: 14px; }
    .ok { color: #4ade80; } .bad { color: #f87171; } .muted { color: #8296a8; font-size: 12px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 4px 14px; margin: 10px 0; }
    .alert { padding: 12px 14px; border-radius: 10px; margin: 14px 0; font-size: 14px; }
    .alert--bad { background: #3a1a1a; border: 1px solid #7f1d1d; color: #fecaca; }
    .alert--ok { background: #15321f; border: 1px solid #166534; color: #bbf7d0; }
    .alert--warn { background: #3a2f13; border: 1px solid #854d0e; color: #fde68a; }
    input { width: 100%; box-sizing: border-box; padding: 10px 12px; border-radius: 9px; border: 1px solid #33465a; background: #0f1720; color: #e6edf3; font-family: inherit; font-size: 14px; direction: ltr; text-align: left; }
    label { display: block; font-size: 13px; margin: 14px 0 5px; color: #c7d3de; }
    .btn { display: inline-block; margin-top: 16px; width: 100%; padding: 12px; border: none; border-radius: 10px; background: linear-gradient(135deg, #0b6e4f, #095b41); color: #fff; font-size: 15px; font-weight: 700; cursor: pointer; }
    code, .code { direction: ltr; display: inline-block; background: #0f1720; border: 1px solid #33465a; border-radius: 8px; padding: 2px 7px; font-family: Consolas, monospace; font-size: 13px; }
    .warn { margin-top: 18px; padding: 12px 14px; border-radius: 10px; background: #3a2f13; border: 1px solid #854d0e; color: #fde68a; font-size: 13px; }
</style>
</head>
<body>
<div class="wrap">
    <h1>🩺 بررسی سلامت دیتابیس سایت</h1>
    <p class="sub">مقایسهٔ جدول‌های موردانتظار با جدول‌های موجود، و ساخت امنِ جدول‌های ناقص</p>

<?php if ($repair !== null): ?>
    <?php if ($repair['ok']): ?>
        <div class="alert alert--ok">✅ همهٔ جدول‌های لازم اکنون موجودند.
            <?php if (!empty($repair['created'])): ?><br>ساخته شد: <code><?= h(implode(', ', $repair['created'])) ?></code><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="alert alert--bad">
            <?php if (!empty($repair['msg'])): ?>
                <?= h($repair['msg']) ?>
            <?php else: ?>
                هنوز این جدول‌ها ساخته نشدند: <code><?= h(implode(', ', $repair['missing'])) ?></code>
                <?php if (!empty($repair['errors'])): ?><br><span class="muted">علت: <?= h($repair['errors'][0]) ?></span><?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($connectErr !== ''): ?>
    <div class="alert alert--bad">❌ <?= h($connectErr) ?></div>
    <p class="muted">تا وقتی اتصال دیتابیس درست نشود، بررسی جدول‌ها ممکن نیست.</p>
<?php elseif (empty($expected)): ?>
    <div class="alert alert--warn">فایل <code>database/schema.sql</code> روی هاست پیدا نشد؛ لیست جدول‌های موردانتظار در دسترس نیست.</div>
<?php else: ?>
    <div class="alert <?= $missing === [] ? 'alert--ok' : 'alert--bad' ?>">
        دیتابیس: <code><?= h($dbInfo) ?></code> —
        <?= count($have) ?> جدول موجود از <?= count($expected) ?> جدول موردانتظار.
        <?= $missing === [] ? 'همه‌چیز کامل است ✅' : (count($missing) . ' جدول کم است ❌') ?>
    </div>

    <div class="grid">
        <?php foreach ($expected as $t): $ok = in_array($t, $have, true); ?>
            <div class="check">
                <span class="<?= $ok ? 'ok' : 'bad' ?>"><?= $ok ? '✓' : '✗' ?></span>
                <span><?= h($t) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($missing !== []): ?>
        <div class="alert alert--warn">جدول‌های ناقص: <code><?= h(implode(', ', $missing)) ?></code></div>
        <form method="post">
            <input type="hidden" name="action" value="repair">
            <label>برای تأیید، نام دقیق دیتابیس را بنویس: <span class="muted">(<?= h($dbInfo) ?>)</span></label>
            <input name="confirm_db" placeholder="<?= h($dbInfo) ?>" autocomplete="off">
            <button class="btn" type="submit">🔧 ساخت جدول‌های ناقص (بدون حذف هیچ داده‌ای)</button>
        </form>
        <p class="muted" style="margin-top:8px">فقط دستورهای <code>CREATE TABLE IF NOT EXISTS</code> اجرا می‌شوند؛ هیچ <code>DROP</code>/<code>DELETE</code> اجرا نمی‌شود.</p>
    <?php endif; ?>
<?php endif; ?>

    <div class="warn">🔒 پس از رفع مشکل، همین فایل <code>dbcheck.php</code> را از روی هاست پاک کن.</div>
</div>
</body>
</html>
