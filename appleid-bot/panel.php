<?php
/**
 * پنل مدیریت محصول/ضمانت ربات اپل‌آیدی.
 *
 * یک صفحهٔ وبِ خودکفا (مثل install.php) برای مدیریت:
 *   - انواع ضمانت (warranty_types)
 *   - محصول‌ها (products) — ریجن، ضمانت، آیکلود، قیمت عادی/همکار، فعال‌بودن
 *   - چند تنظیم پرکاربرد (شماره کارت، نام صاحب کارت، یوزرنیم ربات، روز نگهداری داده)
 *
 * امنیت: با رمزِ پنل محافظت می‌شود (هش در settings.panel_password_hash). بارِ اول
 * که باز می‌کنی، رمز پنل را می‌سازی. همهٔ فرم‌ها CSRF دارند و همهٔ کوئری‌ها Prepared.
 *
 * 🔒 این فایل عمداً وابستگی‌ای به کلاس‌های ربات ندارد (PDO خام).
 */

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

session_start();

const BOT_BASE = __DIR__;
$configFile = BOT_BASE . '/config/config.php';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function money(int $n): string { return number_format($n); }

/* ------------------------- اتصال ------------------------- */
function connectDb(string $configFile): array
{
    if (!is_file($configFile)) {
        return [null, 'فایل config/config.php پیدا نشد. اول ربات را با install.php نصب کن.'];
    }
    $cfg = require $configFile;
    if (!is_array($cfg) || empty($cfg['db'])) {
        return [null, 'اطلاعات دیتابیس در config خالی است.'];
    }
    $d = $cfg['db'];
    try {
        $pdo = new PDO(
            "mysql:host={$d['host']};dbname={$d['name']};charset=" . ($d['charset'] ?? 'utf8mb4'),
            $d['user'], $d['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        return [$pdo, ''];
    } catch (PDOException $e) {
        return [null, 'اتصال به دیتابیس ناموفق بود: ' . $e->getMessage()];
    }
}

function setting(PDO $pdo, string $key, ?string $default = null): ?string
{
    $v = $pdo->prepare('SELECT `value` FROM settings WHERE `key` = ?');
    $v->execute([$key]);
    $r = $v->fetchColumn();
    return $r === false ? $default : (string) $r;
}

function setSetting(PDO $pdo, string $key, string $value): void
{
    $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?)
                   ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)')
        ->execute([$key, $value]);
}

[$pdo, $connErr] = connectDb($configFile);

/* ------------------------- CSRF ------------------------- */
if (empty($_SESSION['panel_csrf'])) {
    $_SESSION['panel_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['panel_csrf'];
function checkCsrf(): bool
{
    return isset($_POST['csrf'], $_SESSION['panel_csrf'])
        && hash_equals($_SESSION['panel_csrf'], (string) $_POST['csrf']);
}

$action = $_POST['action'] ?? '';
$msg = null; $err = null;

/* ------------------------- احراز هویت ------------------------- */
$hasPassword = $pdo !== null && (string) setting($pdo, 'panel_password_hash', '') !== '';
$authed = !empty($_SESSION['panel_auth']);

if ($pdo !== null && $action === 'set_password') {
    if (!checkCsrf()) { $err = 'توکن نامعتبر است. صفحه را تازه کن.'; }
    elseif ($hasPassword) { $err = 'رمز پنل قبلاً تنظیم شده.'; }
    else {
        $p1 = (string) ($_POST['pass1'] ?? ''); $p2 = (string) ($_POST['pass2'] ?? '');
        if (strlen($p1) < 6) { $err = 'رمز باید حداقل ۶ کاراکتر باشد.'; }
        elseif ($p1 !== $p2) { $err = 'دو رمز یکسان نیستند.'; }
        else {
            setSetting($pdo, 'panel_password_hash', password_hash($p1, PASSWORD_DEFAULT));
            $_SESSION['panel_auth'] = true;
            $authed = true; $hasPassword = true;
            $msg = 'رمز پنل ساخته شد و وارد شدی.';
        }
    }
}

if ($pdo !== null && $action === 'login') {
    if (!checkCsrf()) { $err = 'توکن نامعتبر است. صفحه را تازه کن.'; }
    else {
        $hash = (string) setting($pdo, 'panel_password_hash', '');
        if ($hash !== '' && password_verify((string) ($_POST['pass'] ?? ''), $hash)) {
            $_SESSION['panel_auth'] = true; $authed = true;
            $msg = 'خوش آمدی.';
        } else {
            $err = 'رمز اشتباه است.';
        }
    }
}

if ($action === 'logout') {
    $_SESSION['panel_auth'] = false; $authed = false;
    $msg = 'خارج شدی.';
}

/* ------------------------- اکشن‌های مدیریت (نیازمند لاگین) ------------------------- */
if ($pdo !== null && $authed && $action !== '' && !in_array($action, ['login', 'logout', 'set_password'], true)) {
    if (!checkCsrf()) {
        $err = 'توکن نامعتبر است. صفحه را تازه کن.';
    } else {
        try {
            switch ($action) {
                case 'wt_save':
                    $id    = (int) ($_POST['id'] ?? 0);
                    $name  = trim((string) ($_POST['name'] ?? ''));
                    $desc  = trim((string) ($_POST['description'] ?? ''));
                    $sort  = (int) ($_POST['sort_order'] ?? 0);
                    $act   = isset($_POST['is_active']) ? 1 : 0;
                    if ($name === '') { $err = 'نام ضمانت الزامی است.'; break; }
                    if ($id > 0) {
                        $pdo->prepare('UPDATE warranty_types SET name=?, description=?, sort_order=?, is_active=? WHERE id=?')
                            ->execute([$name, $desc ?: null, $sort, $act, $id]);
                        $msg = 'ضمانت ویرایش شد.';
                    } else {
                        $pdo->prepare('INSERT INTO warranty_types (name, description, sort_order, is_active) VALUES (?,?,?,?)')
                            ->execute([$name, $desc ?: null, $sort, $act]);
                        $msg = 'ضمانت اضافه شد.';
                    }
                    break;

                case 'wt_delete':
                    $id = (int) ($_POST['id'] ?? 0);
                    try {
                        $pdo->prepare('DELETE FROM warranty_types WHERE id=?')->execute([$id]);
                        $msg = 'ضمانت حذف شد.';
                    } catch (PDOException $e) {
                        $err = 'این ضمانت در محصولی استفاده شده و حذف نمی‌شود. به‌جای حذف، «غیرفعال»‌اش کن.';
                    }
                    break;

                case 'prod_save':
                    $id     = (int) ($_POST['id'] ?? 0);
                    $wt     = (int) ($_POST['warranty_type_id'] ?? 0);
                    $region = trim((string) ($_POST['region'] ?? 'US')) ?: 'US';
                    $icloud = isset($_POST['icloud_enabled']) ? 1 : 0;
                    $preg   = (int) preg_replace('/\D/', '', (string) ($_POST['price_regular'] ?? '0'));
                    $ppar   = (int) preg_replace('/\D/', '', (string) ($_POST['price_partner'] ?? '0'));
                    $sort   = (int) ($_POST['sort_order'] ?? 0);
                    $act    = isset($_POST['is_active']) ? 1 : 0;
                    if ($wt <= 0) { $err = 'نوع ضمانت را انتخاب کن.'; break; }
                    if ($id > 0) {
                        $pdo->prepare('UPDATE products SET region=?, warranty_type_id=?, icloud_enabled=?, price_regular=?, price_partner=?, sort_order=?, is_active=? WHERE id=?')
                            ->execute([$region, $wt, $icloud, $preg, $ppar, $sort, $act, $id]);
                        $msg = 'محصول ویرایش شد.';
                    } else {
                        $pdo->prepare('INSERT INTO products (region, warranty_type_id, icloud_enabled, price_regular, price_partner, sort_order, is_active) VALUES (?,?,?,?,?,?,?)')
                            ->execute([$region, $wt, $icloud, $preg, $ppar, $sort, $act]);
                        $msg = 'محصول اضافه شد.';
                    }
                    break;

                case 'prod_delete':
                    $id = (int) ($_POST['id'] ?? 0);
                    try {
                        $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
                        $msg = 'محصول حذف شد.';
                    } catch (PDOException $e) {
                        $err = 'این محصول در سفارشی ثبت شده و حذف نمی‌شود. به‌جای حذف، «غیرفعال»‌اش کن.';
                    }
                    break;

                case 'settings_save':
                    setSetting($pdo, 'card_number', trim((string) ($_POST['card_number'] ?? '')));
                    setSetting($pdo, 'card_holder_name', trim((string) ($_POST['card_holder_name'] ?? '')));
                    setSetting($pdo, 'bot_username', ltrim(trim((string) ($_POST['bot_username'] ?? '')), '@'));
                    $ret = (int) ($_POST['sensitive_data_retention_days'] ?? 3);
                    setSetting($pdo, 'sensitive_data_retention_days', (string) max(1, $ret));
                    $msg = 'تنظیمات ذخیره شد.';
                    break;
            }
        } catch (PDOException $e) {
            $err = 'خطا: ' . $e->getMessage();
        }
    }
}

/* ------------------------- دادهٔ نمایش ------------------------- */
$warranties = []; $products = []; $S = [];
if ($pdo !== null && $authed) {
    $warranties = $pdo->query('SELECT * FROM warranty_types ORDER BY sort_order, id')->fetchAll();
    $products = $pdo->query(
        'SELECT p.*, w.name AS warranty_name
           FROM products p LEFT JOIN warranty_types w ON w.id = p.warranty_type_id
          ORDER BY p.sort_order, p.id'
    )->fetchAll();
    foreach (['card_number', 'card_holder_name', 'bot_username', 'sensitive_data_retention_days'] as $k) {
        $S[$k] = (string) setting($pdo, $k, '');
    }
    // برای ویرایش (id در کوئری‌استرینگ)
}
$editWt   = null; $editProd = null;
if ($authed) {
    if (($eid = (int) ($_GET['edit_wt'] ?? 0)) > 0) {
        foreach ($warranties as $w) { if ((int) $w['id'] === $eid) { $editWt = $w; break; } }
    }
    if (($eid = (int) ($_GET['edit_prod'] ?? 0)) > 0) {
        foreach ($products as $p) { if ((int) $p['id'] === $eid) { $editProd = $p; break; } }
    }
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>پنل مدیریت ربات اپل‌آیدی</title>
<style>
    body { font-family: Tahoma, "Segoe UI", system-ui, sans-serif; background: #0f1720; color: #e6edf3; margin: 0; padding: 22px; line-height: 1.85; }
    .wrap { max-width: 900px; margin: 0 auto; }
    .card { background: #161f2b; border: 1px solid #263241; border-radius: 14px; padding: 20px 22px; margin-bottom: 18px; }
    h1 { font-size: 21px; margin: 0 0 4px; } h2 { font-size: 16px; margin: 0 0 14px; color: #cfe0ee; }
    .sub { color: #9fb0c0; font-size: 13px; margin: 0 0 16px; }
    label { display: block; font-size: 13px; margin: 10px 0 4px; color: #c7d3de; }
    input[type=text], input[type=password], input[type=number], textarea, select {
        width: 100%; box-sizing: border-box; padding: 9px 11px; border-radius: 8px; border: 1px solid #33465a; background: #0f1720; color: #e6edf3; font-family: inherit; font-size: 14px; }
    input[dir=ltr] { direction: ltr; text-align: left; }
    .row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .row3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
    .btn { display: inline-block; margin-top: 12px; padding: 10px 16px; border: none; border-radius: 9px; background: linear-gradient(135deg, #0b6e4f, #095b41); color: #fff; font-size: 14px; font-weight: 700; cursor: pointer; }
    .btn--sm { padding: 5px 10px; font-size: 12px; margin: 0; }
    .btn--gray { background: #33465a; } .btn--red { background: #7f1d1d; }
    .alert { padding: 11px 13px; border-radius: 9px; margin: 0 0 14px; font-size: 14px; }
    .alert--ok { background: #15321f; border: 1px solid #166534; color: #bbf7d0; }
    .alert--bad { background: #3a1a1a; border: 1px solid #7f1d1d; color: #fecaca; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 6px; }
    th, td { text-align: right; padding: 8px 6px; border-bottom: 1px solid #22303f; }
    th { color: #9fb0c0; font-weight: 600; }
    .pill { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 11px; }
    .pill--on { background: #15321f; color: #6ee7a0; } .pill--off { background: #3a2020; color: #f7a3a3; }
    .muted { color: #8296a8; font-size: 12px; }
    .inline { display: inline; }
    .topbar { display: flex; justify-content: space-between; align-items: center; }
    a { color: #7cc4ff; }
</style>
</head>
<body>
<div class="wrap">

<?php if ($msg): ?><div class="alert alert--ok">✅ <?= h($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert--bad">⚠️ <?= h($err) ?></div><?php endif; ?>

<?php if ($connErr !== ''): ?>
    <div class="card"><h1>پنل مدیریت ربات</h1>
        <div class="alert alert--bad">❌ <?= h($connErr) ?></div>
    </div>

<?php elseif (!$authed): ?>
    <div class="card" style="max-width:420px;margin:8vh auto 0">
        <h1>🔐 پنل مدیریت ربات</h1>
        <?php if (!$hasPassword): ?>
            <p class="sub">اولین ورود: یک رمز برای پنل بساز.</p>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="set_password">
                <label>رمز جدید پنل (حداقل ۶ کاراکتر)</label>
                <input type="password" name="pass1" dir="ltr" required>
                <label>تکرار رمز</label>
                <input type="password" name="pass2" dir="ltr" required>
                <button class="btn" type="submit">ساخت رمز و ورود</button>
            </form>
        <?php else: ?>
            <p class="sub">رمز پنل را وارد کن.</p>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="login">
                <label>رمز پنل</label>
                <input type="password" name="pass" dir="ltr" required autofocus>
                <button class="btn" type="submit">ورود</button>
            </form>
        <?php endif; ?>
    </div>

<?php else: /* ============ داشبورد ============ */ ?>
    <div class="card topbar">
        <div><h1 style="margin:0">🍎 پنل مدیریت ربات اپل‌آیدی</h1>
            <span class="muted">محصول‌ها، ضمانت‌ها و تنظیمات</span></div>
        <form method="post" class="inline"><input type="hidden" name="action" value="logout">
            <button class="btn btn--sm btn--gray" type="submit">خروج</button></form>
    </div>

    <!-- ============ انواع ضمانت ============ -->
    <div class="card">
        <h2>🛡️ انواع ضمانت</h2>
        <table>
            <tr><th>#</th><th>نام</th><th>توضیح</th><th>ترتیب</th><th>وضعیت</th><th></th></tr>
            <?php foreach ($warranties as $w): ?>
                <tr>
                    <td><?= (int) $w['id'] ?></td>
                    <td><?= h($w['name']) ?></td>
                    <td class="muted"><?= h((string) ($w['description'] ?? '')) ?></td>
                    <td><?= (int) $w['sort_order'] ?></td>
                    <td><span class="pill <?= $w['is_active'] ? 'pill--on' : 'pill--off' ?>"><?= $w['is_active'] ? 'فعال' : 'غیرفعال' ?></span></td>
                    <td>
                        <a class="btn btn--sm btn--gray" href="?edit_wt=<?= (int) $w['id'] ?>#wtform">ویرایش</a>
                        <form method="post" class="inline" onsubmit="return confirm('حذف شود؟')">
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                            <input type="hidden" name="action" value="wt_delete">
                            <input type="hidden" name="id" value="<?= (int) $w['id'] ?>">
                            <button class="btn btn--sm btn--red" type="submit">حذف</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$warranties): ?><tr><td colspan="6" class="muted">هنوز ضمانتی نیست.</td></tr><?php endif; ?>
        </table>

        <h2 id="wtform" style="margin-top:18px"><?= $editWt ? 'ویرایش ضمانت #' . (int) $editWt['id'] : 'افزودن ضمانت جدید' ?></h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="wt_save">
            <input type="hidden" name="id" value="<?= $editWt ? (int) $editWt['id'] : 0 ?>">
            <div class="row">
                <div><label>نام ضمانت</label><input type="text" name="name" value="<?= h($editWt['name'] ?? '') ?>" required></div>
                <div><label>ترتیب نمایش</label><input type="number" name="sort_order" value="<?= (int) ($editWt['sort_order'] ?? 0) ?>"></div>
            </div>
            <label>توضیح (اختیاری)</label>
            <input type="text" name="description" value="<?= h((string) ($editWt['description'] ?? '')) ?>">
            <label><input type="checkbox" name="is_active" value="1" <?= (!$editWt || $editWt['is_active']) ? 'checked' : '' ?>> فعال باشد</label>
            <button class="btn" type="submit"><?= $editWt ? 'ذخیرهٔ تغییرات' : 'افزودن' ?></button>
            <?php if ($editWt): ?><a class="btn btn--sm btn--gray" href="panel.php" style="margin-inline-start:8px">انصراف</a><?php endif; ?>
        </form>
    </div>

    <!-- ============ محصول‌ها ============ -->
    <div class="card">
        <h2>📦 محصول‌ها</h2>
        <table>
            <tr><th>#</th><th>ضمانت</th><th>ریجن</th><th>آیکلود</th><th>قیمت عادی</th><th>قیمت همکار</th><th>وضعیت</th><th></th></tr>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td><?= (int) $p['id'] ?></td>
                    <td><?= h((string) ($p['warranty_name'] ?? '—')) ?></td>
                    <td><?= h($p['region']) ?></td>
                    <td><?= $p['icloud_enabled'] ? '✓' : '—' ?></td>
                    <td><?= money((int) $p['price_regular']) ?></td>
                    <td><?= money((int) $p['price_partner']) ?></td>
                    <td><span class="pill <?= $p['is_active'] ? 'pill--on' : 'pill--off' ?>"><?= $p['is_active'] ? 'فعال' : 'غیرفعال' ?></span></td>
                    <td>
                        <a class="btn btn--sm btn--gray" href="?edit_prod=<?= (int) $p['id'] ?>#prodform">ویرایش</a>
                        <form method="post" class="inline" onsubmit="return confirm('حذف شود؟')">
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                            <input type="hidden" name="action" value="prod_delete">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button class="btn btn--sm btn--red" type="submit">حذف</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$products): ?><tr><td colspan="8" class="muted">هنوز محصولی نیست.</td></tr><?php endif; ?>
        </table>

        <h2 id="prodform" style="margin-top:18px"><?= $editProd ? 'ویرایش محصول #' . (int) $editProd['id'] : 'افزودن محصول جدید' ?></h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="prod_save">
            <input type="hidden" name="id" value="<?= $editProd ? (int) $editProd['id'] : 0 ?>">
            <div class="row3">
                <div>
                    <label>نوع ضمانت</label>
                    <select name="warranty_type_id" required>
                        <option value="">— انتخاب —</option>
                        <?php foreach ($warranties as $w): ?>
                            <option value="<?= (int) $w['id'] ?>" <?= ($editProd && (int) $editProd['warranty_type_id'] === (int) $w['id']) ? 'selected' : '' ?>>
                                <?= h($w['name']) ?><?= $w['is_active'] ? '' : ' (غیرفعال)' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label>ریجن</label><input type="text" name="region" value="<?= h($editProd['region'] ?? 'US') ?>" dir="ltr"></div>
                <div><label>ترتیب</label><input type="number" name="sort_order" value="<?= (int) ($editProd['sort_order'] ?? 0) ?>"></div>
            </div>
            <div class="row">
                <div><label>قیمت عادی (تومان)</label><input type="text" name="price_regular" dir="ltr" value="<?= (int) ($editProd['price_regular'] ?? 0) ?>"></div>
                <div><label>قیمت همکار (تومان)</label><input type="text" name="price_partner" dir="ltr" value="<?= (int) ($editProd['price_partner'] ?? 0) ?>"></div>
            </div>
            <label><input type="checkbox" name="icloud_enabled" value="1" <?= ($editProd && $editProd['icloud_enabled']) ? 'checked' : '' ?>> آیکلود فعال</label>
            <label><input type="checkbox" name="is_active" value="1" <?= (!$editProd || $editProd['is_active']) ? 'checked' : '' ?>> فعال باشد</label>
            <button class="btn" type="submit"><?= $editProd ? 'ذخیرهٔ تغییرات' : 'افزودن' ?></button>
            <?php if ($editProd): ?><a class="btn btn--sm btn--gray" href="panel.php" style="margin-inline-start:8px">انصراف</a><?php endif; ?>
        </form>
        <p class="muted" style="margin-top:8px">قیمت‌ها به تومان و فقط عدد. ربات بر اساس ترکیب «نوع ضمانت + آیکلود» محصول را پیدا می‌کند.</p>
    </div>

    <!-- ============ تنظیمات ============ -->
    <div class="card">
        <h2>⚙️ تنظیمات</h2>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="settings_save">
            <div class="row">
                <div><label>شمارهٔ کارت واریز</label><input type="text" name="card_number" dir="ltr" value="<?= h($S['card_number'] ?? '') ?>"></div>
                <div><label>نام صاحب کارت</label><input type="text" name="card_holder_name" value="<?= h($S['card_holder_name'] ?? '') ?>"></div>
            </div>
            <div class="row">
                <div><label>یوزرنیم ربات بله (بدون @)</label><input type="text" name="bot_username" dir="ltr" value="<?= h($S['bot_username'] ?? '') ?>"></div>
                <div><label>روزهای نگهداری دادهٔ حساس</label><input type="number" name="sensitive_data_retention_days" value="<?= (int) ($S['sensitive_data_retention_days'] ?? 3) ?>"></div>
            </div>
            <button class="btn" type="submit">ذخیرهٔ تنظیمات</button>
        </form>
    </div>

    <div class="card">
        <p class="muted" style="margin:0">🔒 وقتی کارت تنظیمات تمام شد، برای امنیت می‌توانی <code>panel.php</code> را هم پاک کنی؛ ولی چون با رمز محافظت می‌شود، نگه‌داشتنش برای مدیریت بعدی هم اشکالی ندارد.</p>
    </div>
<?php endif; ?>

</div>
</body>
</html>
