/**
 * ایتکو — جاوااسکریپت صفحه محصول.
 *
 * دو کار انجام می‌دهد:
 *   ۱. تعویض تصویر گالری
 *   ۲. نمایش داینامیک قیمت و موجودی هنگام انتخاب گزینه‌های محصول (Variant)
 */
(function () {
    'use strict';

    // ---------------------------------------------------------------
    // گالری تصاویر
    // ---------------------------------------------------------------
    var mainImage = document.getElementById('gallery-main');

    document.querySelectorAll('.gallery__thumb').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            if (!mainImage) { return; }

            mainImage.src = thumb.getAttribute('data-image');

            document.querySelectorAll('.gallery__thumb').forEach(function (other) {
                other.classList.remove('is-active');
            });
            thumb.classList.add('is-active');
        });
    });

    // ---------------------------------------------------------------
    // انتخاب Variant
    // ---------------------------------------------------------------
    var form = document.getElementById('buy-form');
    if (!form) { return; }

    var variants;
    try {
        variants = JSON.parse(form.getAttribute('data-variants') || '{}');
    } catch (e) {
        variants = {};
    }

    var groups = form.querySelectorAll('.option-group');
    if (groups.length === 0) { return; }

    var variantIdField = document.getElementById('variant-id');
    var priceBox       = document.getElementById('price-box');
    var priceHint      = document.getElementById('price-hint');
    var stockLine      = document.getElementById('stock-line');
    var addButton      = document.getElementById('add-to-cart');
    var qtyInput       = form.querySelector('input[name="quantity"]');

    /** تومان با ارقام فارسی */
    function money(value) {
        var text = String(value).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        var fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        return text.replace(/\d/g, function (d) { return fa[d]; }) + ' تومان';
    }

    /** کلید Variant: شناسه مقادیر انتخاب‌شده، مرتب‌شده و با خط تیره */
    function currentKey() {
        var ids = [];

        for (var i = 0; i < groups.length; i++) {
            var checked = groups[i].querySelector('input[type=radio]:checked');
            if (!checked) { return null; }   // هنوز همه گزینه‌ها انتخاب نشده‌اند
            ids.push(parseInt(checked.value, 10));
        }

        ids.sort(function (a, b) { return a - b; });
        return ids.join('-');
    }

    function update() {
        var key = currentKey();

        if (key === null) {
            variantIdField.value = '';
            addButton.disabled = true;
            addButton.textContent = 'انتخاب گزینه‌ها';
            stockLine.innerHTML = '';
            return;
        }

        var variant = variants[key];

        if (!variant) {
            // این ترکیب اصلاً وجود ندارد
            variantIdField.value = '';
            addButton.disabled = true;
            addButton.textContent = 'ناموجود';
            if (priceHint) { priceHint.hidden = true; }
            stockLine.innerHTML = '<span class="out-stock">این ترکیب موجود نیست</span>';
            return;
        }

        variantIdField.value = variant.id;

        // قیمت
        var html = '';
        if (variant.compare && variant.compare > variant.price) {
            html += '<span class="price-box__old">' + money(variant.compare) + '</span>';
        }
        html += '<span class="price-box__now">' + money(variant.price) + '</span>';
        priceBox.innerHTML = html;

        // موجودی
        if (variant.stock > 0) {
            stockLine.innerHTML = variant.stock <= 3
                ? '<span class="low-stock">تنها ' + money(variant.stock).replace(' تومان', '') + ' عدد باقی مانده</span>'
                : '<span class="in-stock">موجود در انبار</span>';

            addButton.disabled = false;
            addButton.textContent = 'افزودن به سبد خرید';

            if (qtyInput) {
                qtyInput.max = variant.stock;
                if (parseInt(qtyInput.value, 10) > variant.stock) {
                    qtyInput.value = variant.stock;
                }
            }
        } else {
            stockLine.innerHTML = '<span class="out-stock">ناموجود</span>';
            addButton.disabled = true;
            addButton.textContent = 'ناموجود';
        }
    }

    form.querySelectorAll('.option input[type=radio]').forEach(function (radio) {
        radio.addEventListener('change', update);
    });

    update();
})();
