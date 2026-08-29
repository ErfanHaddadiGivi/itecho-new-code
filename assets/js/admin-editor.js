/* =====================================================================
   ویرایشگر ساده‌ی توضیحات محصول در پنل مدیریت.
   یک نوار ابزار بالای هر <textarea data-editor> می‌سازد که با آن می‌توان
   عکس و ویدیو آپلود کرد و تگ آن را درست سرِ جای مکان‌نما در متن درج کرد،
   به‌علاوه چند دکمه‌ی کمکی برای تگ‌های ساده‌ی HTML.

   جاوااسکریپت خالص است؛ اگر JS خاموش باشد، همان textarea معمولی کار می‌کند.
   ===================================================================== */
(function () {
    'use strict';

    var areas = document.querySelectorAll('textarea[data-editor]');
    if (!areas.length) { return; }

    areas.forEach(setupEditor);

    function setupEditor(area) {
        var uploadUrl = area.getAttribute('data-upload-url') || '';
        var form      = area.closest('form');
        var tokenEl   = form ? form.querySelector('[name="_csrf_token"]') : null;
        var token     = tokenEl ? tokenEl.value : '';

        // --- ساخت نوار ابزار ---
        var bar = document.createElement('div');
        bar.className = 'editor-toolbar';

        var buttons = [
            { label: 'درج عکس',  kind: 'image', accept: 'image/jpeg,image/png,image/webp', title: 'آپلود و درج تصویر' },
            { label: 'درج ویدیو', kind: 'video', accept: 'video/mp4,video/webm', title: 'آپلود و درج ویدیو' },
            { sep: true },
            { label: 'پاراگراف', wrap: ['<p>', '</p>'], title: 'پاراگراف' },
            { label: 'تیتر',     wrap: ['<h3>', '</h3>'], title: 'تیتر بخش' },
            { label: 'پررنگ',    wrap: ['<strong>', '</strong>'], title: 'متن پررنگ' },
            { label: 'فهرست',    wrap: ['<ul>\n  <li>', '</li>\n</ul>'], title: 'فهرست نقطه‌ای' },
            { label: 'لینک',     link: true, title: 'افزودن لینک' }
        ];

        buttons.forEach(function (cfg) {
            if (cfg.sep) {
                var s = document.createElement('span');
                s.className = 'editor-toolbar__sep';
                bar.appendChild(s);
                return;
            }
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn--ghost btn--sm editor-btn';
            btn.textContent = cfg.label;
            if (cfg.title) { btn.title = cfg.title; }
            btn.addEventListener('click', function () {
                if (cfg.kind)      { pickAndUpload(cfg.kind, cfg.accept); }
                else if (cfg.link) { insertLink(); }
                else if (cfg.wrap) { wrapSelection(cfg.wrap[0], cfg.wrap[1]); }
            });
            bar.appendChild(btn);
        });

        // وضعیت آپلود
        var status = document.createElement('span');
        status.className = 'editor-status';
        status.setAttribute('aria-live', 'polite');
        bar.appendChild(status);

        // input فایل مخفی (بازاستفاده برای هر دو نوع)
        var fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.style.display = 'none';
        area.parentNode.insertBefore(bar, area);
        area.parentNode.insertBefore(fileInput, area);

        var currentKind = 'image';

        function pickAndUpload(kind, accept) {
            if (!uploadUrl) { return; }
            currentKind = kind;
            fileInput.accept = accept || '';
            fileInput.value = '';   // تا انتخاب دوباره‌ی همان فایل هم event بدهد
            fileInput.click();
        }

        fileInput.addEventListener('change', function () {
            if (!fileInput.files || !fileInput.files.length) { return; }
            uploadFile(fileInput.files[0], currentKind);
        });

        function uploadFile(file, kind) {
            setBusy(true, kind === 'video' ? 'در حال آپلود ویدیو…' : 'در حال آپلود تصویر…');

            var data = new FormData();
            data.append('file', file);
            data.append('kind', kind);
            data.append('_csrf_token', token);

            fetch(uploadUrl, {
                method: 'POST',
                body: data,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
            .then(function (res) { return res.json().catch(function () { return { ok: false, error: 'پاسخ نامعتبر از سرور.' }; }); })
            .then(function (json) {
                if (json && json.ok && json.url) {
                    insertMedia(json.type, json.url);
                    setBusy(false, 'انجام شد ✓');
                } else {
                    setBusy(false, (json && json.error) ? json.error : 'آپلود ناموفق بود.', true);
                }
            })
            .catch(function () {
                setBusy(false, 'ارتباط با سرور برقرار نشد.', true);
            });
        }

        function insertMedia(type, url) {
            var html;
            if (type === 'video') {
                html = '\n<video src="' + url + '" controls preload="metadata" ' +
                       'style="max-width:100%;border-radius:10px;"></video>\n';
            } else {
                html = '\n<img src="' + url + '" alt="" ' +
                       'style="max-width:100%;border-radius:10px;">\n';
            }
            insertAtCursor(html);
        }

        function insertLink() {
            var url = window.prompt('آدرس لینک (با https):', 'https://');
            if (!url) { return; }
            var start = area.selectionStart;
            var end   = area.selectionEnd;
            var text  = area.value.substring(start, end) || 'متن لینک';
            insertAtCursor('<a href="' + url + '" target="_blank" rel="noopener">' + text + '</a>');
        }

        function wrapSelection(before, after) {
            var start = area.selectionStart;
            var end   = area.selectionEnd;
            var sel   = area.value.substring(start, end);
            var html  = before + sel + after;
            insertAtCursor(html, before.length, sel.length);
        }

        /**
         * درج متن سرِ جای مکان‌نما.
         * اگر selInnerStart/selInnerLen داده شود، انتخاب داخل قالب قرار می‌گیرد.
         */
        function insertAtCursor(text, selInnerStart, selInnerLen) {
            var start = area.selectionStart;
            var end   = area.selectionEnd;
            var value = area.value;
            area.value = value.substring(0, start) + text + value.substring(end);

            var caret;
            if (typeof selInnerStart === 'number') {
                area.selectionStart = start + selInnerStart;
                area.selectionEnd   = start + selInnerStart + (selInnerLen || 0);
            } else {
                caret = start + text.length;
                area.selectionStart = area.selectionEnd = caret;
            }
            area.focus();
            // برای اطلاع اسکریپت‌های دیگر از تغییر
            area.dispatchEvent(new Event('input', { bubbles: true }));
        }

        function setBusy(busy, msg, isError) {
            status.textContent = msg || '';
            status.classList.toggle('editor-status--error', !!isError);
            bar.querySelectorAll('.editor-btn').forEach(function (b) { b.disabled = busy; });
        }
    }
})();
