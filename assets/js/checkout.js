/**
 * ایتکو — جاوااسکریپت صفحه تسویه‌حساب.
 * با تغییر روش تحویل، فیلدهای آدرس و مبلغ نهایی به‌روز می‌شوند.
 */
(function () {
    'use strict';

    var radios     = document.querySelectorAll('input[name="delivery_method"]');
    var postFields = document.getElementById('post-fields');
    var pickupRow  = document.getElementById('row-pickup-fee');
    var postNote   = document.getElementById('post-note');
    var totalEl    = document.getElementById('total-amount');

    if (radios.length === 0 || !postFields) { return; }

    // مبالغ از خود صفحه خوانده می‌شوند تا در جاوااسکریپت عدد ثابت ننویسیم
    var itemsTotal = parseInt(postFields.getAttribute('data-items-total') || '0', 10);
    var pickupFee  = parseInt(postFields.getAttribute('data-pickup-fee') || '0', 10);

    function toFa(text) {
        var fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        return String(text).replace(/\d/g, function (d) { return fa[d]; });
    }

    function money(value) {
        return toFa(String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ',')) + ' تومان';
    }

    function update() {
        var checked = document.querySelector('input[name="delivery_method"]:checked');
        var isPost  = checked && checked.value === 'post';

        postFields.hidden = !isPost;
        if (pickupRow) { pickupRow.hidden = isPost; }
        if (postNote)  { postNote.hidden  = !isPost; }

        // فیلدهای آدرس فقط وقتی اجباری‌اند که ارسال پستی انتخاب شده باشد
        ['province', 'city', 'address_line'].forEach(function (id) {
            var field = document.getElementById(id);
            if (field) { field.required = isPost; }
        });

        if (totalEl) {
            totalEl.textContent = money(isPost ? itemsTotal : itemsTotal + pickupFee);
        }
    }

    radios.forEach(function (radio) {
        radio.addEventListener('change', update);
    });

    update();
})();
