/**
 * ایتکو — جاوااسکریپت فرم محصول در پنل مدیریت.
 *
 *   ۱. افزودن و حذف ردیف مشخصات فنی
 *   ۲. ساخت ترکیب‌های محصول (Variant)
 */
(function () {
    'use strict';

    // ===============================================================
    //  مشخصات فنی
    // ===============================================================
    var specsBox = document.getElementById('specs');
    var specAdd  = document.getElementById('spec-add');

    if (specsBox && specAdd) {
        specAdd.addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'spec-row';
            row.innerHTML =
                '<input type="text" name="spec_key[]" placeholder="عنوان (مثلاً اندازه صفحه)">' +
                '<input type="text" name="spec_value[]" placeholder="مقدار (مثلاً ۶.۷ اینچ)">' +
                '<button type="button" class="btn btn--danger btn--sm spec-remove">حذف</button>';
            specsBox.appendChild(row);
            row.querySelector('input').focus();
        });

        specsBox.addEventListener('click', function (event) {
            if (event.target.classList.contains('spec-remove')) {
                event.target.closest('.spec-row').remove();
            }
        });
    }

    // ===============================================================
    //  تنوع محصول
    // ===============================================================
    var box = document.getElementById('variants');
    if (!box) { return; }

    var attributes;
    try {
        attributes = JSON.parse(box.getAttribute('data-attributes') || '[]');
    } catch (e) {
        attributes = [];
    }

    var selectsBox = document.getElementById('variant-selects');
    var addButton  = document.getElementById('variant-add');
    var rowsBody   = document.getElementById('variant-rows');
    var emptyNote  = document.getElementById('variants-empty');
    var priceNote  = document.getElementById('variant-price-note');

    if (attributes.length === 0) {
        selectsBox.innerHTML = '<span class="field__hint">هنوز ویژگی‌ای تعریف نشده است.</span>';
        addButton.disabled = true;
        return;
    }

    // ساخت یک select برای هر ویژگی
    attributes.forEach(function (attribute) {
        var wrap = document.createElement('label');
        wrap.className = 'variant-builder__field';

        var label = document.createElement('span');
        label.textContent = attribute.name;
        wrap.appendChild(label);

        var select = document.createElement('select');
        select.setAttribute('data-attribute', attribute.id);

        var none = document.createElement('option');
        none.value = '';
        none.textContent = '— ندارد —';
        select.appendChild(none);

        attribute.values.forEach(function (value) {
            var option = document.createElement('option');
            option.value = value.id;
            option.textContent = value.value;
            select.appendChild(option);
        });

        wrap.appendChild(select);
        selectsBox.appendChild(wrap);
    });

    /** ترکیب‌هایی که همین حالا در جدول هستند (برای جلوگیری از تکرار) */
    function existingKeys() {
        var keys = [];

        rowsBody.querySelectorAll('input[name="variant_values[]"]').forEach(function (input) {
            keys.push(input.value.split(',').map(Number).sort(function (a, b) {
                return a - b;
            }).join(','));
        });

        return keys;
    }

    function refreshEmptyNote() {
        var has = rowsBody.querySelectorAll('.variant-row').length > 0;
        emptyNote.hidden = has;
        if (priceNote) { priceNote.hidden = !has; }
    }

    addButton.addEventListener('click', function () {
        var ids = [];
        var labels = [];

        selectsBox.querySelectorAll('select').forEach(function (select) {
            if (select.value === '') { return; }
            ids.push(parseInt(select.value, 10));
            labels.push(select.options[select.selectedIndex].textContent);
        });

        if (ids.length === 0) {
            window.alert('حداقل یک ویژگی را انتخاب کنید.');
            return;
        }

        var key = ids.slice().sort(function (a, b) { return a - b; }).join(',');

        if (existingKeys().indexOf(key) !== -1) {
            window.alert('این ترکیب قبلاً اضافه شده است.');
            return;
        }

        var basePrice = document.getElementById('price');

        var row = document.createElement('tr');
        row.className = 'variant-row';
        row.innerHTML =
            '<td>' +
                '<input type="hidden" name="variant_id[]" value="">' +
                '<input type="hidden" name="variant_values[]" value="' + ids.join(',') + '">' +
                '<span class="variant-row__title"></span>' +
            '</td>' +
            '<td><input type="text" name="variant_price[]" inputmode="numeric" dir="ltr" value="' +
                (basePrice ? basePrice.value : '0') + '"></td>' +
            '<td><input type="text" name="variant_stock[]" inputmode="numeric" dir="ltr" value="0"></td>' +
            '<td><input type="text" name="variant_sku[]" dir="ltr" value=""></td>' +
            '<td><button type="button" class="btn btn--danger btn--sm variant-remove">حذف</button></td>';

        // عنوان با textContent نوشته می‌شود تا نام ویژگی به‌عنوان HTML تفسیر نشود
        row.querySelector('.variant-row__title').textContent = labels.join(' / ');

        rowsBody.appendChild(row);
        refreshEmptyNote();
    });

    rowsBody.addEventListener('click', function (event) {
        if (event.target.classList.contains('variant-remove')) {
            event.target.closest('.variant-row').remove();
            refreshEmptyNote();
        }
    });

    refreshEmptyNote();
})();
