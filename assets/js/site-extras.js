/* =====================================================================
   قابلیت‌های افزوده‌ی سایت:
   ۱) باز/بسته کردن ویجت تماس چسبان
   ۲) پاپ‌آپ مشاوره — در اولین ورود هر نشست یک‌بار
   جاوااسکریپت خالص، بدون کتابخانه.
   ===================================================================== */
(function () {
    'use strict';

    /* ---------- ویجت تماس چسبان ---------- */
    var fab = document.querySelector('.contact-fab');
    if (fab) {
        var toggle = fab.querySelector('.contact-fab__toggle');
        toggle.addEventListener('click', function () {
            var open = fab.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        // با کلیک بیرون بسته شود
        document.addEventListener('click', function (e) {
            if (fab.classList.contains('is-open') && !fab.contains(e.target)) {
                fab.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* ---------- ویدیوی پس‌زمینه‌ی استیکی که با اسکرول محو می‌شود ---------- */
    var vhero = document.querySelector('[data-video-hero]');
    if (vhero) {
        var vbg = vhero.querySelector('.video-hero__bg');
        var vcontent = vhero.querySelector('.video-hero__content');
        var ticking = false;

        /* روی موبایل، اگر نسخه‌ی جدا آپلود شده باشد، همان را لود می‌کنیم
           (تا فقط یک ویدیو دانلود شود و اندازه‌ها بهم نریزد). */
        var video = vhero.querySelector('.video-hero__video');
        if (video) {
            var mobileSrc = video.getAttribute('data-src-mobile');
            if (mobileSrc && window.matchMedia('(max-width: 600px)').matches) {
                video.setAttribute('src', mobileSrc);
                video.load();
                var playAttempt = video.play();
                if (playAttempt && playAttempt.catch) { playAttempt.catch(function () {}); }
            }
        }

        function fadeOnScroll() {
            var h = window.innerHeight || 1;
            var y = window.scrollY || window.pageYOffset || 0;
            var p = Math.min(1, y / (h * 0.9)); // ۰ در بالا → ۱ بعد از حدود یک صفحه اسکرول
            if (vbg) { vbg.style.opacity = String(1 - p); }
            if (vcontent) {
                vcontent.style.opacity = String(1 - Math.min(1, p * 1.4));
                vcontent.style.transform = 'translateY(' + (y * 0.18) + 'px)';
            }
            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (!ticking) { window.requestAnimationFrame(fadeOnScroll); ticking = true; }
        }, { passive: true });
        fadeOnScroll();
    }

    /* ---------- پاپ‌آپ مشاوره ---------- */
    var popup = document.getElementById('consult-popup');
    if (popup) {
        var KEY = 'itecho_consult_seen';
        var seen = false;
        try { seen = sessionStorage.getItem(KEY) === '1'; } catch (e) { seen = false; }

        function closePopup() {
            popup.setAttribute('hidden', '');
            document.body.classList.remove('no-scroll');
            try { sessionStorage.setItem(KEY, '1'); } catch (e) { /* ignore */ }
        }

        function openPopup() {
            popup.removeAttribute('hidden');
            document.body.classList.add('no-scroll');
        }

        if (!seen) {
            // کمی تأخیر تا صفحه بارگذاری شود و پاپ‌آپ ناگهانی نپرد
            setTimeout(openPopup, 1200);
        }

        popup.querySelectorAll('[data-consult-close]').forEach(function (el) {
            el.addEventListener('click', closePopup);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !popup.hasAttribute('hidden')) { closePopup(); }
        });
    }
})();
