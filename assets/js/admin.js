/**
 * ایتکو — جاوااسکریپت پنل مدیریت.
 */
(function () {
    'use strict';

    // ---------------------------------------------------------------
    // باز و بسته کردن نوار کناری در موبایل
    // ---------------------------------------------------------------
    var toggle  = document.querySelector('.sidebar-toggle');
    var sidebar = document.getElementById('admin-sidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            var isOpen = sidebar.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        // با کلیک بیرون از منو بسته شود
        document.addEventListener('click', function (event) {
            if (!sidebar.classList.contains('is-open')) {
                return;
            }
            if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                sidebar.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ---------------------------------------------------------------
    // گرفتن تایید قبل از عملیات حذف
    // هر فرمی که ویژگی data-confirm داشته باشد، پیام تایید نشان می‌دهد.
    // ---------------------------------------------------------------
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    // ---------------------------------------------------------------
    // بستن نوار کناری با Escape
    // ---------------------------------------------------------------
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && sidebar && sidebar.classList.contains('is-open')) {
            sidebar.classList.remove('is-open');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
        }
    });
})();
