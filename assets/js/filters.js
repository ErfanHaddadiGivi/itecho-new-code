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

    /* --- اسلایدر دو‌سرِ کشویی قیمت --- */
    var sliderBox = form.querySelector('[data-price-slider]');
    if (sliderBox) { initPriceSlider(sliderBox, form); }

    function initPriceSlider(box, form) {
        var minR   = box.querySelector('.price-slider__range--min');
        var maxR   = box.querySelector('.price-slider__range--max');
        var fill   = box.querySelector('.price-slider__fill');
        var lblMin = form.querySelector('[data-price-label-min]');
        var lblMax = form.querySelector('[data-price-label-max]');
        var inpMin = form.querySelector('[data-price-input-min]');
        var inpMax = form.querySelector('[data-price-input-max]');

        var lo   = parseInt(box.getAttribute('data-min'), 10) || 0;
        var hi   = parseInt(box.getAttribute('data-max'), 10) || 0;
        var span = Math.max(1, hi - lo);

        // گام حرکت: حدود ۲۰۰ پله، گِردشده به عدد رند
        var step = niceStep(span / 200);
        minR.step = maxR.step = step;

        function fmt(n) {
            try { return n.toLocaleString('fa-IR'); } catch (e) { return String(n); }
        }

        /**
         * به‌روزرسانی نما بر اساس مقدار دو دستگیره.
         * writeInputs=true یعنی مقدار را در کادرهای متنی هم بنویس (وقتی منبعِ تغییر خودِ اسلایدر است).
         */
        function render(a, b, writeInputs) {
            var pA = ((a - lo) / span) * 100;
            var pB = ((b - lo) / span) * 100;
            fill.style.left  = pA + '%';
            fill.style.right = (100 - pB) + '%';

            if (lblMin) { lblMin.textContent = fmt(a); }
            if (lblMax) { lblMax.textContent = fmt(b); }

            // وقتی دستگیره‌ی کمینه به بالای بازه نزدیک است، آن را روی دستگیره‌ی بیشینه بیاور
            minR.style.zIndex = (a > lo + span / 2) ? '5' : '3';

            if (writeInputs) {
                inpMin.value = (a > lo) ? a : '';
                inpMax.value = (b < hi) ? b : '';
            }
        }

        /* حرکتِ دستگیره‌ها — جلوگیری از رد شدن از هم */
        minR.addEventListener('input', function () {
            var a = parseInt(minR.value, 10);
            var b = parseInt(maxR.value, 10);
            if (a > b - step) { a = b - step; minR.value = a; }
            render(a, b, true);
        });
        maxR.addEventListener('input', function () {
            var a = parseInt(minR.value, 10);
            var b = parseInt(maxR.value, 10);
            if (b < a + step) { b = a + step; maxR.value = b; }
            render(a, b, true);
        });

        /* رها کردن دستگیره → اعمال فیلتر */
        function submitNow() { form.submit(); }
        minR.addEventListener('change', submitNow);
        maxR.addEventListener('change', submitNow);

        /* تایپ در کادرهای متنی → جابه‌جایی دستگیره‌ها (بدون اعمال خودکار) */
        function fromInputs() {
            var a = parseInt((inpMin.value || '').replace(/\D/g, ''), 10);
            var b = parseInt((inpMax.value || '').replace(/\D/g, ''), 10);
            if (isNaN(a)) { a = lo; }
            if (isNaN(b)) { b = hi; }
            a = Math.min(Math.max(a, lo), hi);
            b = Math.min(Math.max(b, lo), hi);
            if (a > b) { var t = a; a = b; b = t; }
            minR.value = a;
            maxR.value = b;
            render(a, b, false);
        }
        if (inpMin) { inpMin.addEventListener('input', fromInputs); }
        if (inpMax) { inpMax.addEventListener('input', fromInputs); }

        // وضعیت اولیه
        render(parseInt(minR.value, 10), parseInt(maxR.value, 10), false);
    }

    /** نزدیک‌ترین عدد رند (۱، ۲، ۵ × توان ۱۰) به x — برای گامِ خوش‌دست اسلایدر */
    function niceStep(x) {
        if (x <= 1) { return 1; }
        var pow = Math.pow(10, Math.floor(Math.log(x) / Math.LN10));
        var n = x / pow;
        var nice = n < 1.5 ? 1 : (n < 3 ? 2 : (n < 7 ? 5 : 10));
        return nice * pow;
    }
})();
