/* =====================================================================
   فیلتر محصولات (سبک دیجی‌کالا) — جستجوی برند و اعمال خودکار.
   جاوااسکریپت خالص؛ بدون JS هم فرم با دکمه «اعمال فیلتر» کار می‌کند.
   ===================================================================== */
(function () {
    'use strict';

    var form = document.getElementById('filter-form');
    if (!form) { return; }

    /* --- جستجوی داخل لیست برند --- */
    form.querySelectorAll('[data-filter-search]').forEach(function (input) {
        var list = input.parentNode.querySelector('[data-filter-list]');
        if (!list) { return; }
        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase()
                .replace(/ي/g, 'ی').replace(/ك/g, 'ک');
            list.querySelectorAll('.check').forEach(function (row) {
                var name = (row.getAttribute('data-name') || row.textContent || '')
                    .toLowerCase().replace(/ي/g, 'ی').replace(/ك/g, 'ک');
                row.style.display = (q === '' || name.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    });

    /* --- اعمال خودکار با تیک‌زدن (فقط چک‌باکس/رادیو، نه فیلد قیمت) --- */
    if (form.hasAttribute('data-autofilter')) {
        form.addEventListener('change', function (e) {
            var t = e.target;
            if (t && (t.type === 'checkbox' || t.type === 'radio')) {
                form.submit();
            }
        });
    }
})();
