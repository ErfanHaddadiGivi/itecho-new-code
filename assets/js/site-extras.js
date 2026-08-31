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

        // فاصله‌ای که ویدیو رویش کامل محو می‌شود (کسری از ارتفاع صفحه) — قابل تنظیم از پنل
        var fadeFactor = parseFloat(vhero.getAttribute('data-fade')) || 0.9;

        function fadeOnScroll() {
            var h = window.innerHeight || 1;
            var y = window.scrollY || window.pageYOffset || 0;
            var p = Math.min(1, y / (h * fadeFactor)); // ۰ در بالا → ۱ بعد از پیمایش قابل تنظیم
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

/* =====================================================================
   شورتکات بخش‌های صفحه محصول: پرش نرم + برجسته‌سازی بخش فعال.
   همچنین ارتفاع هدرِ چسبان را در متغیر CSS می‌ریزد تا نوار شورتکات درست
   زیر هدر بایستد.
   ===================================================================== */
(function () {
    'use strict';

    var header = document.querySelector('.site-header');

    function setHeaderVar() {
        if (header) {
            document.documentElement.style.setProperty('--header-h', header.offsetHeight + 'px');
        }
    }
    setHeaderVar();
    window.addEventListener('resize', setHeaderVar);
    window.addEventListener('load', setHeaderVar);

    var nav = document.querySelector('.product-nav');
    if (!nav) { return; }

    var links = Array.prototype.slice.call(nav.querySelectorAll('.product-nav__link'));

    function offset() {
        return (header ? header.offsetHeight : 0) + nav.offsetHeight + 16;
    }

    // پرش نرم هنگام کلیک روی شورتکات
    links.forEach(function (link) {
        link.addEventListener('click', function (e) {
            var hash = link.getAttribute('href') || '';
            if (hash.charAt(0) !== '#') { return; }
            var target = document.querySelector(hash);
            if (!target) { return; }
            e.preventDefault();
            var y = target.getBoundingClientRect().top + window.pageYOffset - offset();
            window.scrollTo({ top: y, behavior: 'smooth' });
        });
    });

    // برجسته‌سازی بخشِ در حال دیدن (scroll-spy)
    var sections = links
        .map(function (l) { return document.getElementById((l.getAttribute('href') || '').slice(1)); })
        .filter(Boolean);

    function setActive(id) {
        links.forEach(function (l) {
            l.classList.toggle('is-active', l.getAttribute('href') === '#' + id);
        });
    }

    if ('IntersectionObserver' in window && sections.length) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) { setActive(en.target.id); }
            });
        }, { rootMargin: '-' + (offset() + 20) + 'px 0px -55% 0px', threshold: 0 });
        sections.forEach(function (s) { io.observe(s); });
    }
})();
