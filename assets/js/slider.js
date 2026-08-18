/* =====================================================================
   اسلایدر صفحه اصلی — چرخش خودکار، دکمه‌های قبلی/بعدی، نقطه‌ها و کشیدن انگشت.
   راست‌چین: «بعدی» به سمت چپ حرکت می‌کند.
   بدون کتابخانه؛ اگر جاوااسکریپت نباشد اسلایدها زیر هم دیده می‌شوند.
   ===================================================================== */
(function () {
    'use strict';

    document.querySelectorAll('[data-slider]').forEach(function (root) {
        var track  = root.querySelector('.hero-slider__track');
        var slides = Array.prototype.slice.call(root.querySelectorAll('[data-slide]'));
        var dots   = Array.prototype.slice.call(root.querySelectorAll('[data-slider-dot]'));
        if (!track || slides.length < 2) { return; }

        var index = 0;
        var timer = null;
        var DELAY = 5000;

        /* به CSS اعلام می‌کنیم اسلایدر آماده است تا از حالت پشته‌ای (بدون JS) خارج شود */
        root.setAttribute('data-slider-ready', '');

        function show(i) {
            index = (i + slides.length) % slides.length;
            track.style.transform = 'translateX(' + (index * 100) + '%)'; /* RTL: مثبت یعنی به چپ */
            slides.forEach(function (s, n) {
                s.setAttribute('aria-hidden', n === index ? 'false' : 'true');
            });
            dots.forEach(function (d, n) {
                d.classList.toggle('is-active', n === index);
            });
        }

        function next() { show(index + 1); }
        function prev() { show(index - 1); }

        function start() { stop(); timer = setInterval(next, DELAY); }
        function stop()  { if (timer) { clearInterval(timer); timer = null; } }

        var nextBtn = root.querySelector('[data-slider-next]');
        var prevBtn = root.querySelector('[data-slider-prev]');
        if (nextBtn) { nextBtn.addEventListener('click', function () { next(); start(); }); }
        if (prevBtn) { prevBtn.addEventListener('click', function () { prev(); start(); }); }

        dots.forEach(function (d, n) {
            d.addEventListener('click', function () { show(n); start(); });
        });

        /* مکث هنگام قرار گرفتن ماوس روی اسلایدر */
        root.addEventListener('mouseenter', stop);
        root.addEventListener('mouseleave', start);

        /* کشیدن با انگشت روی موبایل */
        var startX = 0, dragging = false;
        track.addEventListener('touchstart', function (e) {
            dragging = true; startX = e.touches[0].clientX; stop();
        }, { passive: true });
        track.addEventListener('touchend', function (e) {
            if (!dragging) { return; }
            dragging = false;
            var dx = e.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 40) {
                /* در راست‌چین کشیدن به راست یعنی اسلاید قبلی */
                if (dx > 0) { prev(); } else { next(); }
            }
            start();
        }, { passive: true });

        /* اگر تب مخفی شد، چرخش را متوقف کن تا منابع هدر نرود */
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) { stop(); } else { start(); }
        });

        track.style.transform = 'translateX(0)';
        show(0);
        start();
    });
})();
