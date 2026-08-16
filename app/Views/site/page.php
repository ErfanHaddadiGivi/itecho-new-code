<?php
/**
 * نمایش صفحات ثابت (قوانین، حریم خصوصی، درباره ما، تماس با ما)
 *
 * @var array $page
 */
?>
<section class="section">
    <div class="container">
        <article class="static-page">
            <h1><?= e($page['title']) ?></h1>

            <div class="static-page__content">
                <?php
                /**
                 * محتوای این صفحات فقط توسط مدیر کل از پنل وارد می‌شود،
                 * بنابراین HTML آن بدون فرار دادن نمایش داده می‌شود.
                 */
                echo $page['content'];
                ?>
            </div>
        </article>
    </div>
</section>
