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

    // ---------------------------------------------------------------
    // گرفتن تایید قبل از حذف (فرم‌هایی با ویژگی data-confirm)
    // ---------------------------------------------------------------
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    // ---------------------------------------------------------------
    // باز و بسته کردن نوار فیلتر در موبایل
    // ---------------------------------------------------------------
    var filters       = document.getElementById('filters');
    var filtersToggle = document.querySelector('.filters-toggle');
    var filtersClose  = document.querySelector('.filters__close');

    if (filters && filtersToggle) {
        filtersToggle.addEventListener('click', function () {
            filters.classList.add('is-open');
        });
    }
    if (filters && filtersClose) {
        filtersClose.addEventListener('click', function () {
            filters.classList.remove('is-open');
        });
    }

    // ---------------------------------------------------------------
    // پیشنهاد جستجوی زنده
    // ---------------------------------------------------------------
    var searchForm  = document.querySelector('.search');
    var searchInput = searchForm ? searchForm.querySelector('input[name="q"]') : null;

    if (searchInput) {
        var box = document.createElement('div');
        box.className = 'suggest';
        box.hidden = true;
        searchForm.appendChild(box);

        var timer   = null;
        var lastUrl = searchForm.getAttribute('action').replace(/\/search$/, '/search/suggest');

        function hide() { box.hidden = true; box.innerHTML = ''; }

        searchInput.addEventListener('input', function () {
            var term = searchInput.value.trim();

            window.clearTimeout(timer);

            if (term.length < 2) { hide(); return; }

            // کمی صبر می‌کنیم تا با هر حرف یک درخواست فرستاده نشود
            timer = window.setTimeout(function () {
                fetch(lastUrl + '?q=' + encodeURIComponent(term), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (!data.items || data.items.length === 0) { hide(); return; }

                        box.innerHTML = '';

                        data.items.forEach(function (item) {
                            var link = document.createElement('a');
                            link.className = 'suggest__item';
                            link.href = item.url;

                            if (item.image) {
                                var img = document.createElement('img');
                                img.src = item.image;
                                img.alt = '';
                                link.appendChild(img);
                            }

                            var name = document.createElement('span');
                            name.className = 'suggest__name';
                            name.textContent = item.name;   // متن، نه HTML
                            link.appendChild(name);

                            var price = document.createElement('span');
                            price.className = 'suggest__price';
                            price.textContent = item.price;
                            link.appendChild(price);

                            box.appendChild(link);
                        });

                        box.hidden = false;
                    })
                    .catch(hide);
            }, 250);
        });

        // بستن با کلیک بیرون یا Escape
        document.addEventListener('click', function (event) {
            if (!searchForm.contains(event.target)) { hide(); }
        });
        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') { hide(); }
        });
    }

    // ---------------------------------------------------------------
    // افزودن به سبد بدون بارگذاری دوباره صفحه
    // ---------------------------------------------------------------
    var buyForm = document.getElementById('buy-form');

    if (buyForm) {
        buyForm.addEventListener('submit', function (event) {
            event.preventDefault();

            var button = buyForm.querySelector('button[type=submit]');
            var label  = button.textContent;

            button.disabled = true;
            button.textContent = 'در حال افزودن…';

            fetch(buyForm.action, {
                method: 'POST',
                body: new FormData(buyForm),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    showToast(data.message, data.ok ? 'success' : 'error');

                    if (data.ok) {
                        var badge = document.getElementById('cart-badge');
                        if (badge) {
                            badge.textContent = toFa(String(data.count));
                            badge.hidden = false;
                        }
                    }
                })
                .catch(function () {
                    // اگر جاوااسکریپت به مشکل خورد، فرم به روش معمولی ارسال شود
                    buyForm.submit();
                })
                .finally(function () {
                    button.disabled = false;
                    button.textContent = label;
                });
        });
    }

    // ---------------------------------------------------------------
    // علاقه‌مندی: افزودن/حذف بدون بارگذاری دوباره صفحه
    // ---------------------------------------------------------------
    document.querySelectorAll('.wish-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var button = form.querySelector('button');
            button.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    // کاربر مهمان است → به صفحه ورود می‌رود
                    if (data.needLogin) {
                        window.location.href = data.loginUrl;
                        return;
                    }

                    button.classList.toggle('is-on', data.added);
                    button.setAttribute('aria-pressed', data.added ? 'true' : 'false');
                    button.setAttribute('aria-label',
                        data.added ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها');

                    showToast(data.message, 'success');
                })
                .catch(function () { form.submit(); })
                .finally(function () { button.disabled = false; });
        });
    });

    function toFa(text) {
        var fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        return text.replace(/\d/g, function (d) { return fa[d]; });
    }

    function showToast(message, type) {
        var toast = document.createElement('div');
        toast.className = 'toast toast--' + type;
        toast.setAttribute('role', 'status');
        toast.textContent = message;
        document.body.appendChild(toast);

        window.setTimeout(function () { toast.classList.add('is-visible'); }, 10);
        window.setTimeout(function () {
            toast.classList.remove('is-visible');
            window.setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    }
})();
