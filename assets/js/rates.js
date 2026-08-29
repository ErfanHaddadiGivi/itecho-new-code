/* =====================================================================
   باکس نرخ لحظه‌ای ارز (دلار/درهم به تومان).
   مقدار اولیه از سرور (کش) رندر می‌شود؛ این اسکریپت آن را تازه نگه می‌دارد.
   منبع داده: tgju.org (از طریق /api/rates روی خودِ سرور).
   ===================================================================== */
(function () {
    'use strict';

    var box = document.querySelector('[data-rate-box]');
    if (!box) { return; }

    var endpoint = box.getAttribute('data-endpoint');
    if (!endpoint) { return; }

    var REFRESH_MS = 5 * 60 * 1000; // هر ۵ دقیقه

    function faNum(n) {
        try { return Number(n).toLocaleString('fa-IR'); }
        catch (e) { return String(n); }
    }

    function apply(data) {
        if (!data || !data.items) { return; }

        data.items.forEach(function (it) {
            var pill = box.querySelector('[data-rate="' + it.key + '"]');
            if (!pill) { return; }

            var val = pill.querySelector('[data-rate-val]');
            var chg = pill.querySelector('[data-rate-chg]');

            if (val) { val.textContent = faNum(it.toman); }

            if (chg) {
                chg.className = 'rate-pill__chg rate-pill__chg--' + (it.dir || 'none');
                if (it.change && Math.abs(it.change) > 0) {
                    var arrow = it.dir === 'down' ? '▼' : '▲';
                    chg.textContent = arrow + ' ' + faNum(Math.abs(it.change).toFixed(2)) + '٪';
                } else {
                    chg.textContent = '';
                }
            }
        });

        box.classList.toggle('is-stale', !!data.stale);
    }

    function load() {
        fetch(endpoint, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(function (r) { return r.json(); })
        .then(apply)
        .catch(function () { /* بی‌صدا: مقدار قبلی روی صفحه می‌ماند */ });
    }

    load();
    setInterval(load, REFRESH_MS);
})();
