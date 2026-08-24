/* =====================================================================
   تحلیل‌گر سئوی آیتکو — بلادرنگ، بدون کتابخانه.
   الهام‌گرفته از Yoast ولی سبک‌تر و فارسی.
   بر اساس کلمه کلیدی، عنوان، توضیح، نامک و متن، امتیاز و فهرست
   بررسی‌ها را زنده به‌روزرسانی می‌کند و پیش‌نمایش نتیجه‌ی گوگل می‌سازد.
   ===================================================================== */
(function () {
    'use strict';

    var box = document.getElementById('seo-box');
    if (!box) { return; }

    var el = function (id) { return document.getElementById(id); };
    var $title   = el('title');
    var $slug    = el('slug');
    var $content = el('content');
    var $excerpt = el('excerpt');
    var $mTitle  = el('meta_title');
    var $mDesc   = el('meta_description');
    var $kw      = el('focus_keyword');

    var blogBase = box.getAttribute('data-blogbase') || '';
    var hasCoverInitially = box.getAttribute('data-hascover') === '1';
    var coverInput = document.querySelector('input[name="cover_image"]');

    /* --- ابزار متن --- */
    function normalize(s) {
        return (s || '')
            .toString()
            .replace(/ي/g, 'ی')   // ي عربی → ی فارسی
            .replace(/ك/g, 'ک')   // ك عربی → ک فارسی
            .replace(/‌/g, ' ')        // نیم‌فاصله → فاصله
            .toLowerCase()
            .trim();
    }
    function stripTags(html) {
        var d = document.createElement('div');
        d.innerHTML = html || '';
        return d.textContent || d.innerText || '';
    }
    function words(text) {
        var t = normalize(text).replace(/\s+/g, ' ').trim();
        return t === '' ? [] : t.split(' ');
    }
    function faDigits(s) {
        var f = '۰۱۲۳۴۵۶۷۸۹';
        return String(s).replace(/\d/g, function (d) { return f[+d]; });
    }
    function count(haystack, needle) {
        if (!needle) { return 0; }
        var h = normalize(haystack), n = normalize(needle);
        if (n === '') { return 0; }
        var i = 0, c = 0;
        while ((i = h.indexOf(n, i)) !== -1) { c++; i += n.length; }
        return c;
    }

    /* --- محاسبه‌ی بررسی‌ها --- */
    function analyze() {
        var kw       = ($kw.value || '').trim();
        var title    = ($title.value || '').trim();
        var seoTitle = ($mTitle.value || '').trim() || title;
        var mDesc    = ($mDesc.value || '').trim();
        var excerpt  = $excerpt ? ($excerpt.value || '').trim() : '';
        var contentText = stripTags($content ? $content.value : '');
        var contentWords = words(contentText);
        var slug     = ($slug && $slug.value.trim()) || title;
        var hasCover = hasCoverInitially || (coverInput && coverInput.files && coverInput.files.length > 0);

        var checks = [];
        var add = function (status, weight, text) { checks.push({ status: status, weight: weight, text: text }); };
        var kwn = normalize(kw);

        // ۱. کلمه کلیدی
        if (kw === '') {
            add('bad', 3, 'کلمه کلیدی اصلی را وارد کنید.');
        } else {
            add('good', 3, 'کلمه کلیدی اصلی تنظیم شده است.');
        }

        if (kw !== '') {
            // ۲. کلمه کلیدی در عنوان سئو
            add(count(seoTitle, kw) > 0 ? 'good' : 'bad', 3,
                count(seoTitle, kw) > 0 ? 'کلمه کلیدی در عنوان سئو هست.' : 'کلمه کلیدی در عنوان سئو نیست.');

            // ۳. کلمه کلیدی در نامک
            add(normalize(slug).indexOf(kwn.replace(/\s+/g, '-')) > -1 || count(slug, kw) > 0 ? 'good' : 'ok', 2,
                'کلمه کلیدی در نامک (آدرس) مطلب.');

            // ۴. کلمه کلیدی در توضیح متا
            add(count(mDesc, kw) > 0 ? 'good' : 'ok', 2,
                count(mDesc, kw) > 0 ? 'کلمه کلیدی در توضیح متا هست.' : 'بهتر است کلمه کلیدی در توضیح متا باشد.');

            // ۵. کلمه کلیدی در پاراگراف اول
            var firstChunk = contentWords.slice(0, 60).join(' ');
            add(count(firstChunk, kw) > 0 ? 'good' : 'ok', 2,
                'کلمه کلیدی در ابتدای متن.');

            // ۶. تراکم کلمه کلیدی
            if (contentWords.length > 0) {
                var occ = count(contentText, kw);
                var density = (occ * (kwn.split(' ').length)) / contentWords.length * 100;
                if (density === 0) {
                    add('bad', 2, 'کلمه کلیدی در متن به کار نرفته است.');
                } else if (density < 0.5) {
                    add('ok', 2, 'تراکم کلمه کلیدی کم است (' + faDigits(density.toFixed(1)) + '٪).');
                } else if (density <= 2.5) {
                    add('good', 2, 'تراکم کلمه کلیدی مناسب است (' + faDigits(density.toFixed(1)) + '٪).');
                } else {
                    add('ok', 2, 'تراکم کلمه کلیدی زیاد است (' + faDigits(density.toFixed(1)) + '٪).');
                }
            }
        }

        // ۷. طول عنوان سئو
        var tl = seoTitle.length;
        if (tl === 0) { add('bad', 2, 'عنوان سئو خالی است.'); }
        else if (tl < 30) { add('ok', 2, 'عنوان سئو کوتاه است (' + faDigits(tl) + ' کاراکتر).'); }
        else if (tl <= 60) { add('good', 2, 'طول عنوان سئو مناسب است (' + faDigits(tl) + ' کاراکتر).'); }
        else { add('ok', 2, 'عنوان سئو بلند است (' + faDigits(tl) + ' کاراکتر).'); }

        // ۸. طول توضیح متا
        var dl = mDesc.length;
        if (dl === 0) { add('bad', 2, 'توضیح متا خالی است.'); }
        else if (dl < 120) { add('ok', 2, 'توضیح متا کوتاه است (' + faDigits(dl) + ' کاراکتر).'); }
        else if (dl <= 160) { add('good', 2, 'طول توضیح متا مناسب است (' + faDigits(dl) + ' کاراکتر).'); }
        else { add('ok', 2, 'توضیح متا بلند است (' + faDigits(dl) + ' کاراکتر).'); }

        // ۹. طول متن
        if (contentWords.length >= 300) { add('good', 3, 'طول متن خوب است (' + faDigits(contentWords.length) + ' کلمه).'); }
        else if (contentWords.length >= 120) { add('ok', 3, 'متن کمی کوتاه است (' + faDigits(contentWords.length) + ' کلمه).'); }
        else { add('bad', 3, 'متن خیلی کوتاه است (' + faDigits(contentWords.length) + ' کلمه). حداقل ۳۰۰ کلمه توصیه می‌شود.'); }

        // ۱۰. زیرعنوان
        var hasHeading = /<h[2-4][\s>]/i.test($content ? $content.value : '');
        add(hasHeading ? 'good' : 'ok', 1, hasHeading ? 'متن زیرعنوان (h2) دارد.' : 'برای خوانایی بهتر از زیرعنوان استفاده کنید.');

        // ۱۱. تصویر کاور
        add(hasCover ? 'good' : 'ok', 2, hasCover ? 'تصویر کاور دارد.' : 'یک تصویر کاور اضافه کنید.');

        // ۱۲. لینک در متن
        var hasLink = /<a\s/i.test($content ? $content.value : '');
        add(hasLink ? 'good' : 'ok', 1, hasLink ? 'متن حداقل یک لینک دارد.' : 'افزودن لینک مرتبط به سئو کمک می‌کند.');

        render(checks, { seoTitle: seoTitle, mDesc: mDesc, excerpt: excerpt, contentText: contentText, slug: slug });
    }

    /* --- نمایش --- */
    function render(checks, info) {
        // امتیاز
        var got = 0, max = 0;
        checks.forEach(function (c) {
            max += c.weight;
            got += c.weight * (c.status === 'good' ? 1 : (c.status === 'ok' ? 0.5 : 0));
        });
        var score = max > 0 ? Math.round(got / max * 100) : 0;

        var numEl = el('seo-score-num'), lblEl = el('seo-score-label'), scoreEl = el('seo-score');
        numEl.textContent = faDigits(score);
        var band = score >= 80 ? 'good' : (score >= 50 ? 'ok' : 'bad');
        var label = score >= 80 ? 'عالی' : (score >= 50 ? 'خوب' : (score >= 30 ? 'متوسط' : 'ضعیف'));
        lblEl.textContent = label;
        scoreEl.className = 'seo-score seo-score--' + band;

        // فهرست بررسی‌ها (بد و متوسط بالاتر)
        var order = { bad: 0, ok: 1, good: 2 };
        checks.sort(function (a, b) { return order[a.status] - order[b.status]; });
        var list = el('seo-checks');
        list.innerHTML = '';
        checks.forEach(function (c) {
            var li = document.createElement('li');
            li.className = 'seo-check seo-check--' + c.status;
            var dot = document.createElement('span');
            dot.className = 'seo-check__dot';
            dot.textContent = c.status === 'good' ? '✓' : (c.status === 'ok' ? '!' : '×');
            var txt = document.createElement('span');
            txt.textContent = c.text;
            li.appendChild(dot); li.appendChild(txt);
            list.appendChild(li);
        });

        // شمارنده‌ی طول
        var tc = el('meta_title_count'), dc = el('meta_description_count');
        if (tc) { tc.textContent = faDigits(info.seoTitle.length) + ' / ۶۰ کاراکتر'; tc.className = 'seo-count' + (info.seoTitle.length > 60 ? ' seo-count--over' : ''); }
        if (dc) { dc.textContent = faDigits(info.mDesc.length) + ' / ۱۶۰ کاراکتر'; dc.className = 'seo-count' + (info.mDesc.length > 160 ? ' seo-count--over' : ''); }

        // پیش‌نمایش گوگل
        var slugSafe = normalize(info.slug).replace(/\s+/g, '-').replace(/[^\w؀-ۿ-]/g, '');
        el('snippet-url').textContent = (location.origin + blogBase + '/' + slugSafe);
        el('snippet-title').textContent = info.seoTitle || 'عنوان مطلب اینجا نمایش داده می‌شود';
        el('snippet-desc').textContent = info.mDesc || info.excerpt ||
            (info.contentText.slice(0, 160)) || 'توضیح متا اینجا نمایش داده می‌شود…';
    }

    /* --- اتصال رویدادها --- */
    ['input', 'change'].forEach(function (evt) {
        [$title, $slug, $content, $excerpt, $mTitle, $mDesc, $kw, coverInput].forEach(function (node) {
            if (node) { node.addEventListener(evt, analyze); }
        });
    });
    analyze();
})();
