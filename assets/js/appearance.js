/* =====================================================================
   شخصی‌سازی ظاهر — همگام‌سازی رنگ‌ها، تم‌های آماده و پیش‌نمایش زنده
   بدون هیچ کتابخانه‌ای، جاوااسکریپت خالص.
   ===================================================================== */
(function () {
    'use strict';

    var form = document.querySelector('.appearance');
    if (!form) { return; }

    var primary = document.getElementById('theme_primary');
    var accent  = document.getElementById('theme_accent');
    var preview = document.getElementById('theme-preview');

    /* اعمال رنگ‌ها روی جعبه پیش‌نمایش */
    function paint() {
        if (!preview) { return; }
        preview.style.setProperty('--pv-primary', primary.value);
        preview.style.setProperty('--pv-accent', accent.value);
    }

    /* هر رنگ‌گزین با فیلد متنی هگز کنارش هماهنگ می‌شود */
    form.querySelectorAll('.js-color').forEach(function (picker) {
        picker.addEventListener('input', function () {
            var hex = form.querySelector('.js-hex[data-for="' + picker.id + '"]');
            if (hex) { hex.value = picker.value; }
            paint();
        });
    });

    form.querySelectorAll('.js-hex').forEach(function (hex) {
        hex.addEventListener('input', function () {
            var val = hex.value.trim();
            if (val.charAt(0) !== '#') { val = '#' + val; }
            /* فقط وقتی رنگ کامل و معتبر است روی رنگ‌گزین اثر بگذار */
            if (/^#[0-9a-fA-F]{6}$/.test(val)) {
                var picker = document.getElementById(hex.getAttribute('data-for'));
                if (picker) { picker.value = val; }
                paint();
            }
        });
    });

    /* تم‌های آماده: با یک کلیک هر دو رنگ را پر می‌کنند */
    form.querySelectorAll('.js-preset').forEach(function (chip) {
        chip.addEventListener('click', function () {
            var p = chip.getAttribute('data-primary');
            var a = chip.getAttribute('data-accent');
            primary.value = p;
            accent.value  = a;
            var hp = form.querySelector('.js-hex[data-for="theme_primary"]');
            var ha = form.querySelector('.js-hex[data-for="theme_accent"]');
            if (hp) { hp.value = p; }
            if (ha) { ha.value = a; }
            paint();
        });
    });

    paint();
})();
