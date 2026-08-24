<?php
/**
 * فهرست مطالب گیمینگ.
 *
 * @var array $posts
 * @var App\Core\Paginator $paginator
 * @var int   $total
 */

use App\Core\View;
?>

<section class="section">
    <div class="container">
        <div class="section__head">
            <h1>مطالب گیمینگ</h1>
        </div>

        <?php if (!$posts): ?>
            <div class="notice">
                <strong>هنوز مطلبی منتشر نشده است.</strong>
                <span>به‌زودی مقاله‌ها و اخبار دنیای گیمینگ اینجا منتشر می‌شوند.</span>
            </div>
        <?php else: ?>
            <div class="blog-grid">
                <?php foreach ($posts as $post): ?>
                    <a class="blog-card" href="<?= e(url('blog/' . $post['slug'])) ?>">
                        <div class="blog-card__media">
                            <?php if (!empty($post['cover_image'])): ?>
                                <img src="<?= e(url('uploads/posts/' . $post['cover_image'])) ?>"
                                     alt="<?= e($post['title']) ?>" loading="lazy">
                            <?php else: ?>
                                <span class="blog-card__ph" aria-hidden="true">🎮</span>
                            <?php endif; ?>
                        </div>
                        <div class="blog-card__body">
                            <h2 class="blog-card__title"><?= e($post['title']) ?></h2>
                            <?php if (!empty($post['excerpt'])): ?>
                                <p class="blog-card__excerpt"><?= e($post['excerpt']) ?></p>
                            <?php endif; ?>
                            <span class="blog-card__meta">
                                <?= e(jdate($post['published_at'] ?? null)) ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php View::partial('site/partials/pagination', ['paginator' => $paginator]); ?>
        <?php endif; ?>
    </div>
</section>
