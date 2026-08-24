<?php
/**
 * نمایش یک مطلب.
 *
 * @var array $post
 * @var array $latest
 */
?>

<article class="section">
    <div class="container">
        <nav class="breadcrumb" aria-label="مسیر">
            <a href="<?= e(url('')) ?>">خانه</a> ›
            <a href="<?= e(url('blog')) ?>">مجله آیتکو</a> ›
            <span><?= e($post['title']) ?></span>
        </nav>

        <h1 class="post__title"><?= e($post['title']) ?></h1>
        <div class="post__meta">
            <span><?= e(jdate($post['published_at'] ?? $post['created_at'])) ?></span>
            <span>·</span>
            <span><?= e(fa_digits((string) $post['views'])) ?> بازدید</span>
        </div>

        <?php if (!empty($post['cover_image'])): ?>
            <div class="post__cover">
                <img src="<?= e(url('uploads/posts/' . $post['cover_image'])) ?>" alt="<?= e($post['title']) ?>">
            </div>
        <?php endif; ?>

        <?php if (!empty($post['excerpt'])): ?>
            <p class="post__lead"><?= e($post['excerpt']) ?></p>
        <?php endif; ?>

        <!-- متن مطلب توسط مدیر نوشته می‌شود (HTML ساده) -->
        <div class="post__content static-page__content">
            <?= $post['content'] ?>
        </div>

        <div class="post__foot">
            <a class="btn btn--ghost" href="<?= e(url('blog')) ?>">← بازگشت به مجله</a>
        </div>
    </div>
</article>

<?php if ($latest): ?>
    <section class="section section--alt">
        <div class="container">
            <div class="section__head"><h2>تازه‌ترین مطالب</h2></div>
            <div class="blog-grid">
                <?php foreach ($latest as $item): ?>
                    <?php if ((int) $item['id'] === (int) $post['id']) { continue; } ?>
                    <a class="blog-card" href="<?= e(url('blog/' . $item['slug'])) ?>">
                        <div class="blog-card__media">
                            <?php if (!empty($item['cover_image'])): ?>
                                <img src="<?= e(url('uploads/posts/' . $item['cover_image'])) ?>"
                                     alt="<?= e($item['title']) ?>" loading="lazy">
                            <?php else: ?>
                                <span class="blog-card__ph" aria-hidden="true">🎮</span>
                            <?php endif; ?>
                        </div>
                        <div class="blog-card__body">
                            <h3 class="blog-card__title"><?= e($item['title']) ?></h3>
                            <span class="blog-card__meta"><?= e(jdate($item['published_at'] ?? null)) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
