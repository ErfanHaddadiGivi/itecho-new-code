/**
 * ایتکو — جاوااسکریپت بخش فروشگاه.
 * بدون هیچ کتابخانه‌ای، فقط جاوااسکریپت خالص.
 */
(function () {
    'use strict';

    // ---------------------------------------------------------------
    // باز و بسته کردن منو در موبایل
    // ---------------------------------------------------------------
    var toggle = document.querySelector('.menu-toggle');
    var nav    = document.getElementById('mega-nav');

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var isOpen = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    // ---------------------------------------------------------------
    // در موبایل، اولین کلیک روی دسته‌ای که زیر‌دسته دارد فقط آن را باز می‌کند.
    // کلیک دوم کاربر را به صفحه دسته می‌برد.
    // ---------------------------------------------------------------
    var isMobile = function () {
        return window.matchMedia('(max-width: 860px)').matches;
    };

    document.querySelectorAll('.mega-nav__item.has-children > a').forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (!isMobile()) {
                return;
            }

            var item = link.parentElement;

            if (!item.classList.contains('is-expanded')) {
                event.preventDefault();
                // بقیه دسته‌ها بسته شوند تا منو شلوغ نشود
                document.querySelectorAll('.mega-nav__item.is-expanded').forEach(function (other) {
                    if (other !== item) {
                        other.classList.remove('is-expanded');
                    }
                });
                item.classList.add('is-expanded');
            }
        });
    });

    // ---------------------------------------------------------------
    // بستن منو با کلید Escape
    // ---------------------------------------------------------------
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && nav && nav.classList.contains('is-open')) {
            nav.classList.remove('is-open');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
                toggle.focus();
            }
        }
    });
})();
