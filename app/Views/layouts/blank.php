<?php
/**
 * چارچوب ساده و بدون منو — برای صفحه ورود.
 *
 * @var string $content
 * @var string $title
 */

use App\Core\Flash;

$messages = Flash::pull();
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'ایتکو') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="centered-page">

<div class="centered-page__inner">
    <?php foreach ($messages as $message): ?>
        <div class="alert alert--<?= e($message['type']) ?>"><?= e($message['text']) ?></div>
    <?php endforeach; ?>

    <?= $content ?>
</div>

</body>
</html>
<?php Flash::clearOld(); ?>
