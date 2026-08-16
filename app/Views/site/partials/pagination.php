<?php
/**
 * نوار صفحه‌بندی
 *
 * @var App\Core\Paginator $paginator
 */

if (!$paginator->hasPages()) {
    return;
}
?>
<nav class="pagination" aria-label="صفحه‌بندی">
    <?php if ($paginator->current > 1): ?>
        <a class="pagination__link" href="<?= e($paginator->urlFor($paginator->current - 1)) ?>"
           rel="prev">قبلی</a>
    <?php endif; ?>

    <?php foreach ($paginator->pages() as $page): ?>
        <?php if ($page === 0): ?>
            <span class="pagination__gap">…</span>
        <?php elseif ($page === $paginator->current): ?>
            <span class="pagination__link is-current" aria-current="page">
                <?= e(fa_digits((string) $page)) ?>
            </span>
        <?php else: ?>
            <a class="pagination__link" href="<?= e($paginator->urlFor($page)) ?>">
                <?= e(fa_digits((string) $page)) ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($paginator->current < $paginator->lastPage): ?>
        <a class="pagination__link" href="<?= e($paginator->urlFor($paginator->current + 1)) ?>"
           rel="next">بعدی</a>
    <?php endif; ?>
</nav>
