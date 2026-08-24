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
